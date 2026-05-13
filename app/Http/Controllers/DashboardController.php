<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Budget;
use App\Models\Debt;
use App\Models\Goal;
use App\Services\GrokAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(protected GrokAIService $grokAI) {}

    public function index()
    {
        $user  = Auth::user();
        $now   = now();
        $year  = $now->year;
        $month = $now->month;

        // Wallet summary
        $wallets      = $user->wallets()->where('is_active', true)->orderBy('sort_order')->get();
        $totalBalance = $wallets->where('include_in_total', true)->sum('balance');

        // Monthly stats
        $monthlyIncome   = $user->transactions()->completed()->byMonth($year, $month)->where('type', 'income')->sum('amount');
        $monthlyExpense  = $user->transactions()->completed()->byMonth($year, $month)->where('type', 'expense')->sum('amount');
        $monthlyTransfer = $user->transactions()->completed()->byMonth($year, $month)->where('type', 'transfer')->sum('amount');
        $netCashflow     = $monthlyIncome - $monthlyExpense;

        // Recent transactions
        $recentTransactions = $user->transactions()
            ->with(['wallet', 'category', 'targetWallet'])
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get();

        // Top expense categories
        $topCategories = $user->transactions()
            ->completed()
            ->where('type', 'expense')
            ->byMonth($year, $month)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->with('category')
            ->limit(5)
            ->get();

        // Chart data (last 6 months)
        $chartData = $this->getChartData($user, 6);

        // AI insight — pakai Carbon untuk hitung bulan lalu agar Januari tidak jadi bulan 0
        $prevMonth        = now()->subMonth();
        $prevMonthExpense = $user->transactions()->completed()->byMonth($prevMonth->year, $prevMonth->month)->where('type', 'expense')->sum('amount');
        $comparisonPct    = $prevMonthExpense > 0 ? round((($monthlyExpense - $prevMonthExpense) / $prevMonthExpense) * 100, 1) : 0;
        $aiInsight        = $this->getAiInsight($user, $monthlyIncome, $monthlyExpense, $topCategories, $comparisonPct);

        // Budgets this month (with spent)
        $budgets = Budget::where('user_id', $user->id)
            ->forMonth($year, $month)
            ->with('category')
            ->get()
            ->map(fn($b) => $b->append(['spent', 'percentage', 'remaining']))
            ->sortByDesc('percentage')
            ->take(4);

        // Active goals
        $goals = Goal::where('user_id', $user->id)
            ->active()
            ->orderBy('target_date')
            ->take(3)
            ->get();

        // Debt summary for widget
        $debtSummary = [
            'total_receivable'  => Debt::where('user_id', $user->id)->active()->receivable()->sum(\Illuminate\Support\Facades\DB::raw('amount - paid_amount')),
            'total_payable'     => Debt::where('user_id', $user->id)->active()->payable()->sum(\Illuminate\Support\Facades\DB::raw('amount - paid_amount')),
            'receivable_count'  => Debt::where('user_id', $user->id)->active()->receivable()->count(),
            'payable_count'     => Debt::where('user_id', $user->id)->active()->payable()->count(),
            'overdue'           => Debt::where('user_id', $user->id)->overdue()->count(),
        ];

        return view('dashboard.index', compact(
            'wallets', 'totalBalance', 'monthlyIncome', 'monthlyExpense',
            'monthlyTransfer', 'netCashflow', 'recentTransactions',
            'topCategories', 'chartData', 'aiInsight', 'budgets', 'goals', 'debtSummary'
        ));
    }

    public function getChartDataApi(Request $request)
    {
        $user   = Auth::user();
        $months = (int)$request->input('months', 6);
        return response()->json($this->getChartData($user, $months));
    }

    protected function getChartData($user, int $months): array
    {
        $startDate = now()->subMonths($months - 1)->startOfMonth();

        // Single query grouped by year+month — no more N+1 loop!
        $rows = $user->transactions()
            ->completed()
            ->whereIn('type', ['income', 'expense'])
            ->where('transaction_date', '>=', $startDate)
            ->selectRaw('YEAR(transaction_date) as y, MONTH(transaction_date) as m, type, SUM(amount) as total')
            ->groupBy('y', 'm', 'type')
            ->get()
            ->groupBy(fn($r) => "{$r->y}-{$r->m}");

        $labels  = [];
        $income  = [];
        $expense = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $key   = $date->year . '-' . $date->month;
            $group = $rows->get($key, collect());

            $labels[]  = $date->format('M Y');
            $income[]  = (float) ($group->firstWhere('type', 'income')?->total ?? 0);
            $expense[] = (float) ($group->firstWhere('type', 'expense')?->total ?? 0);
        }

        return compact('labels', 'income', 'expense');
    }

    protected function getAiInsight($user, float $income, float $expense, $topCategories, float $comparisonPct): string
    {
        try {
            $topCat = $topCategories->first()?->category?->name ?? 'Lainnya';
            $comparison = $comparisonPct >= 0
                ? "naik {$comparisonPct}% dari bulan lalu"
                : "turun " . abs($comparisonPct) . "% dari bulan lalu";

            return $this->grokAI->generateFinancialInsight($user, [
                'income'      => number_format($income, 0, ',', '.'),
                'expense'     => number_format($expense, 0, ',', '.'),
                'top_category'=> $topCat,
                'comparison'  => $comparison,
            ]);
        } catch (\Throwable $e) {
            $diff = $income - $expense;
            if ($diff > 0) return "Keuangan bulan ini positif! Anda berhasil menabung Rp" . number_format($diff, 0, ',', '.') . ". Pertahankan pengeluaran yang efisien.";
            return "Pengeluaran melebihi pemasukan bulan ini. Tinjau kategori pengeluaran terbesar Anda.";
        }
    }
}
