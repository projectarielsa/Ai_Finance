<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Budget;
use App\Models\Debt;
use App\Models\Goal;
use App\Services\GrokAIService;
use App\Services\FinanceAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        protected GrokAIService $grokAI,
        protected FinanceAIService $financeAI
    ) {}

    public function index()
    {
        $user  = Auth::user();
        $now   = now();
        $year  = $now->year;
        $month = $now->month;

        // Wallet summary
        $wallets = $user->wallets()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $totalBalance = $wallets
            ->where('include_in_total', true)
            ->sum('balance');

        // Monthly stats
        $monthlyIncome = $user->transactions()
            ->completed()
            ->byMonth($year, $month)
            ->where('type', 'income')
            ->sum('amount');

        $monthlyExpense = $user->transactions()
            ->completed()
            ->byMonth($year, $month)
            ->where('type', 'expense')
            ->sum('amount');

        $monthlyTransfer = $user->transactions()
            ->completed()
            ->byMonth($year, $month)
            ->where('type', 'transfer')
            ->sum('amount');

        $netCashflow = $monthlyIncome - $monthlyExpense;

        // Recent transactions
        $recentTransactions = $user->transactions()
            ->with(['wallet', 'category', 'targetWallet'])
            ->byMonth($year, $month)
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

        // Chart data
        $chartData = $this->getMonthlyChartData($user, $year, $month);

        // AI Insight lama
        $prevMonth = now()->subMonth();

        $prevMonthExpense = $user->transactions()
            ->completed()
            ->byMonth($prevMonth->year, $prevMonth->month)
            ->where('type', 'expense')
            ->sum('amount');

        $comparisonPct = $prevMonthExpense > 0
            ? round((($monthlyExpense - $prevMonthExpense) / $prevMonthExpense) * 100, 1)
            : 0;

        $aiInsight = $this->getAiInsight(
            $user,
            $monthlyIncome,
            $monthlyExpense,
            $topCategories,
            $comparisonPct
        );

        // SMART FINANCE AI
        $financeAI = $this->financeAI->dashboard($user);

        $healthScore = $financeAI['healthScore'];
        $prediction  = $financeAI['prediction'];
        $smartInsight = $financeAI['insight'];

        // Budgets
        $budgets = Budget::where('user_id', $user->id)
            ->forMonth($year, $month)
            ->with('category')
            ->get()
            ->map(fn($b) => $b->append([
                'spent',
                'percentage',
                'remaining'
            ]))
            ->sortByDesc('percentage')
            ->take(4);

        // Goals
        $goals = Goal::where('user_id', $user->id)
            ->active()
            ->orderBy('target_date')
            ->take(3)
            ->get();

        // Debt summary
        $debtSummary = [
            'total_receivable' => Debt::where('user_id', $user->id)
                ->active()
                ->receivable()
                ->sum(DB::raw('amount - paid_amount')),

            'total_payable' => Debt::where('user_id', $user->id)
                ->active()
                ->payable()
                ->sum(DB::raw('amount - paid_amount')),

            'receivable_count' => Debt::where('user_id', $user->id)
                ->active()
                ->receivable()
                ->count(),

            'payable_count' => Debt::where('user_id', $user->id)
                ->active()
                ->payable()
                ->count(),

            'overdue' => Debt::where('user_id', $user->id)
                ->overdue()
                ->count(),
        ];

        return view('dashboard.index', compact(
            'wallets',
            'totalBalance',
            'monthlyIncome',
            'monthlyExpense',
            'monthlyTransfer',
            'netCashflow',
            'recentTransactions',
            'topCategories',
            'chartData',
            'aiInsight',
            'smartInsight',
            'healthScore',
            'prediction',
            'budgets',
            'goals',
            'debtSummary'
        ));
    }

    public function getChartDataApi(Request $request)
    {
        $user   = Auth::user();
        $months = (int) $request->input('months', 6);

        return response()->json(
            $this->getChartData($user, $months)
        );
    }

    protected function getMonthlyChartData($user, int $year, int $month): array
    {
        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfDay();

        $endDate = $startDate
            ->copy()
            ->endOfMonth();

        $daysInMonth = $startDate->daysInMonth;

        $rows = $user->transactions()
            ->completed()
            ->whereIn('type', ['income', 'expense'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('DAY(transaction_date) as d, type, SUM(amount) as total')
            ->groupBy('d', 'type')
            ->get()
            ->groupBy('d');

        $labels  = [];
        $income  = [];
        $expense = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $labels[] = (string) $day;

            $group = $rows->get((string) $day, collect());

            $income[] = (float) (
                $group->firstWhere('type', 'income')?->total ?? 0
            );

            $expense[] = (float) (
                $group->firstWhere('type', 'expense')?->total ?? 0
            );
        }

        return compact('labels', 'income', 'expense');
    }

    protected function getChartData($user, int $months): array
    {
        $startDate = now()
            ->subMonths($months - 1)
            ->startOfMonth();

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

            $labels[] = $date->format('M Y');

            $income[] = (float) (
                $group->firstWhere('type', 'income')?->total ?? 0
            );

            $expense[] = (float) (
                $group->firstWhere('type', 'expense')?->total ?? 0
            );
        }

        return compact('labels', 'income', 'expense');
    }

    protected function getAiInsight(
    $user,
    float $income,
    float $expense,
    $topCategories,
    float $comparisonPct
): string {
    try {

        $topCat = $topCategories->first()?->category?->name ?? 'Lainnya';

        $surplus = $income - $expense;

        $comparisonText = $comparisonPct >= 0
            ? "naik {$comparisonPct}% dibanding bulan lalu"
            : "turun " . abs($comparisonPct) . "% dibanding bulan lalu";

        $prompt = "
        Kamu adalah AI financial advisor modern untuk aplikasi finance premium.

        Buat insight singkat maksimal 3 kalimat.
        Gunakan bahasa Indonesia yang natural, modern, elegan, dan terasa seperti AI asli.
        Jangan terlalu formal.
        Jangan gunakan poin-poin.
        Fokus ke analisa dan saran actionable.

        Data user:
        - Total pemasukan: Rp " . number_format($income,0,',','.') . "
        - Total pengeluaran: Rp " . number_format($expense,0,',','.') . "
        - Selisih cashflow: Rp " . number_format($surplus,0,',','.') . "
        - Pengeluaran terbesar: {$topCat}
        - Perubahan pengeluaran: {$comparisonText}

        Gaya jawaban:
        - Human friendly
        - Smart financial AI
        - Premium fintech style
        - Maksimal 60 kata
        ";

        $response = $this->grokAI->chat($prompt);

        if (!empty($response)) {
            return trim($response);
        }

        throw new \Exception('Empty AI response');

    } catch (\Throwable $e) {

        $diff = $income - $expense;

        if ($diff > 0) {

            return "Cashflow bulan ini masih sangat sehat dengan surplus Rp" .
                number_format($diff,0,',','.') .
                ". Pengeluaran terbesar ada di kategori {$topCat}, jadi masih ada ruang untuk optimasi agar tabungan bisa tumbuh lebih cepat.";

        }

        return "Pengeluaran bulan ini sudah melampaui pemasukan. Fokus utama saat ini adalah mengurangi pengeluaran di kategori {$topCat} agar cashflow kembali stabil.";
    }
}
}