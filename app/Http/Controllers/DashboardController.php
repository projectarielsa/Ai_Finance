<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\GrokAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // AI insight
        $prevMonthExpense = $user->transactions()->completed()->byMonth($year, $month - 1)->where('type', 'expense')->sum('amount');
        $comparisonPct    = $prevMonthExpense > 0 ? round((($monthlyExpense - $prevMonthExpense) / $prevMonthExpense) * 100, 1) : 0;
        $aiInsight        = $this->getAiInsight($user, $monthlyIncome, $monthlyExpense, $topCategories, $comparisonPct);

        return view('dashboard.index', compact(
            'wallets', 'totalBalance', 'monthlyIncome', 'monthlyExpense',
            'monthlyTransfer', 'netCashflow', 'recentTransactions',
            'topCategories', 'chartData', 'aiInsight'
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
        $labels  = [];
        $income  = [];
        $expense = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');
            $income[]  = (float)$user->transactions()->completed()->byMonth($date->year, $date->month)->where('type', 'income')->sum('amount');
            $expense[] = (float)$user->transactions()->completed()->byMonth($date->year, $date->month)->where('type', 'expense')->sum('amount');
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
