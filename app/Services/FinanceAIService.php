<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Debt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceAIService
{
    public function __construct(
        protected GrokAIService $grokAI
    ) {}

    /**
     * Main dashboard AI data.
     */
    public function dashboard(User $user): array
    {
        $healthScore = $this->calculateHealthScore($user);
        $prediction  = $this->predictEndMonthBalance($user);

        $stats = [ 'income' => $this->monthlyIncome($user), 'expense' => $this->monthlyExpense($user), 'top_category' => $this->topExpenseCategory($user), 'comparison' => $this->monthlyComparison($user), 'saving_rate' => $this->savingRate($user), 'health_score' => $healthScore['score'], 'prediction' => $prediction['predicted_balance'], ];

        return [
            'healthScore' => $healthScore,
            'prediction'  => $prediction,
            'insight'     => $this->generateInsight($user, $stats),
        ];
    }

    /**
     * Financial Health Score
     */
    public function calculateHealthScore(User $user): array
    {
        $income       = $this->monthlyIncome($user);
        $expense      = $this->monthlyExpense($user);
        $savingRate   = $this->savingRate($user);
        $debtRatio    = $this->debtRatio($user);

        $score = 0;

        // Cashflow positive
        if ($income > $expense) {
            $score += 25;
        }

        // Saving rate
        if ($savingRate >= 30) {
            $score += 25;
        } elseif ($savingRate >= 15) {
            $score += 18;
        } elseif ($savingRate >= 5) {
            $score += 10;
        }

        // Debt ratio
        if ($debtRatio <= 20) {
            $score += 20;
        } elseif ($debtRatio <= 40) {
            $score += 10;
        }

        // Expense control
        $expenseRatio = $income > 0
            ? ($expense / $income) * 100
            : 100;

        if ($expenseRatio <= 70) {
            $score += 15;
        } elseif ($expenseRatio <= 90) {
            $score += 8;
        }

        // Consistency
        $transactionCount = Transaction::where('user_id', $user->id)
            ->whereMonth('transaction_date', now()->month)
            ->count();

        if ($transactionCount >= 20) {
            $score += 15;
        } elseif ($transactionCount >= 10) {
            $score += 8;
        }

        $status = match (true) {
            $score >= 85 => 'Excellent',
            $score >= 70 => 'Good',
            $score >= 50 => 'Fair',
            default      => 'Poor',
        };

        $emoji = match ($status) {
            'Excellent' => '🔥',
            'Good'      => '✅',
            'Fair'      => '⚠️',
            default     => '🚨',
        };

        return [
            'score'  => min(100, $score),
            'status' => $status,
            'emoji'  => $emoji,
        ];
    }

    /**
     * Predict end month balance.
     */
    public function predictEndMonthBalance(User $user): array
    {
        $balance = $user->wallets()->sum('balance');

        $expense = $this->monthlyExpense($user);

        $daysPassed = max(now()->day, 1);

        $dailyAverageExpense = $expense / $daysPassed;

        $remainingDays = now()->daysInMonth - $daysPassed;

        $predictedBalance =
            $balance - ($dailyAverageExpense * $remainingDays);

        $message = $predictedBalance < 0
            ? '⚠️ Dengan pola pengeluaran saat ini, saldo berpotensi habis sebelum akhir bulan.'
            : '📈 Kondisi keuangan masih aman hingga akhir bulan.';

        return [
            'predicted_balance' => round($predictedBalance),
            'daily_average_expense' => round($dailyAverageExpense),
            'message' => $message,
        ];
    }

    /**
     * Generate smart AI insight.
     */
    public function generateInsight(User $user, array $stats): string
    {
        $insights = [];

        // Expense vs income
        if ($stats['expense'] > $stats['income']) {
            $insights[] =
                'Pengeluaran bulan ini lebih besar daripada pemasukan.';
        }

        // Saving rate
        if ($stats['saving_rate'] >= 20) {
            $insights[] =
                'Tingkat menabung Anda cukup baik bulan ini.';
        }

        // Prediction
        if ($stats['prediction'] < 0) {
            $insights[] =
                'Saldo diprediksi negatif sebelum akhir bulan.';
        }

        // Top category
        if (!empty($stats['top_category'])) {
            $insights[] =
                'Kategori pengeluaran terbesar saat ini adalah ' .
                $stats['top_category'] . '.';
        }

        $summary = implode(' ', $insights);

        return $this->grokAI->generateFinancialInsight($user, [
            'income'       => $stats['income'],
            'expense'      => $stats['expense'],
            'top_category' => $stats['top_category'],
            'comparison'   => $stats['comparison'],
            'summary'      => $summary,
        ]);
    }

    /**
     * Monthly income.
     */
    protected function monthlyIncome(User $user): int
    {
        return (int) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereMonth('transaction_date', now()->month)
            ->sum('amount');
    }

    /**
     * Monthly expense.
     */
    protected function monthlyExpense(User $user): int
    {
        return (int) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereMonth('transaction_date', now()->month)
            ->sum('amount');
    }

    /**
     * Saving rate.
     */
    protected function savingRate(User $user): int
    {
        $income  = $this->monthlyIncome($user);
        $expense = $this->monthlyExpense($user);

        if ($income <= 0) {
            return 0;
        }

        return (int) round(
            (($income - $expense) / $income) * 100
        );
    }

    /**
 * Debt ratio.
 */
protected function debtRatio(User $user): int
{
    $income = $this->monthlyIncome($user);

    if ($income <= 0) {
        return 100;
    }

    $debts = Debt::where('user_id', $user->id)
        ->where('type', 'payable')
        ->where('status', 'pending')
        ->sum(\Illuminate\Support\Facades\DB::raw('amount - paid_amount'));

    return (int) round(($debts / $income) * 100);
}

    /**
     * Top expense category.
     */
    protected function topExpenseCategory(User $user): ?string
    {
        $top = Transaction::with('category')
            ->where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereMonth('transaction_date', now()->month)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->first();

        return $top?->category?->name;
    }

    /**
     * Monthly comparison.
     */
    protected function monthlyComparison(User $user): string
    {
        $currentExpense = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereMonth('transaction_date', now()->month)
            ->sum('amount');

        $lastMonthExpense = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereMonth(
                'transaction_date',
                now()->subMonth()->month
            )
            ->sum('amount');

        if ($lastMonthExpense <= 0) {
            return 'Tidak ada data bulan lalu';
        }

        $change =
            (($currentExpense - $lastMonthExpense)
            / $lastMonthExpense) * 100;

        return round($change) . '%';
    }
}
