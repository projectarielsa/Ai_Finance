<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\GrokAIService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(protected GrokAIService $grokAI) {}

    public function index(Request $request)
    {
        $user  = Auth::user();
        $year  = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $type  = $request->input('type', 'monthly');

        [$startDate, $endDate] = $this->getDateRange($type, $year, $month, $request);

        $transactions = $user->transactions()
            ->completed()
            ->with(['wallet', 'category'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc')
            ->get();

        $totalIncome   = $transactions->where('type', 'income')->sum('amount');
        $totalExpense  = $transactions->where('type', 'expense')->sum('amount');
        $totalTransfer = $transactions->where('type', 'transfer')->sum('amount');

        // By category
        $expenseByCategory = $transactions->where('type', 'expense')
            ->groupBy('category_id')
            ->map(fn($t) => ['total' => $t->sum('amount'), 'name' => $t->first()->category?->name ?? 'Lainnya'])
            ->sortByDesc('total');

        $incomeByCategory = $transactions->where('type', 'income')
            ->groupBy('category_id')
            ->map(fn($t) => ['total' => $t->sum('amount'), 'name' => $t->first()->category?->name ?? 'Lainnya'])
            ->sortByDesc('total');

        // Daily trend for chart
        $dailyData = $transactions->groupBy(fn($t) => $t->transaction_date->format('d M'))
            ->map(fn($t) => [
                'income'  => $t->where('type', 'income')->sum('amount'),
                'expense' => $t->where('type', 'expense')->sum('amount'),
            ]);

        return view('reports.index', compact(
            'transactions', 'totalIncome', 'totalExpense', 'totalTransfer',
            'expenseByCategory', 'incomeByCategory', 'dailyData',
            'year', 'month', 'type', 'startDate', 'endDate'
        ));
    }

    public function exportPdf(Request $request)
    {
        $user  = Auth::user();
        $year  = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $type  = $request->input('type', 'monthly');

        [$startDate, $endDate] = $this->getDateRange($type, $year, $month, $request);

        $transactions = $user->transactions()
            ->completed()
            ->with(['wallet', 'category'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get();

        $totalIncome  = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');

        $pdf = Pdf::loadView('reports.pdf', compact('transactions', 'totalIncome', 'totalExpense', 'user', 'startDate', 'endDate'));
        return $pdf->download("laporan-keuangan-{$startDate}-{$endDate}.pdf");
    }

    public function exportExcel(Request $request)
    {
        $user  = Auth::user();
        $year  = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        return Excel::download(
            new \App\Exports\TransactionsExport($user->id, $year, $month),
            "transactions-{$year}-{$month}.xlsx"
        );
    }

    protected function getDateRange(string $type, int $year, int $month, Request $request): array
    {
        return match($type) {
            'daily'   => [now()->format('Y-m-d'), now()->format('Y-m-d')],
            'weekly'  => [now()->startOfWeek()->format('Y-m-d'), now()->endOfWeek()->format('Y-m-d')],
            'yearly'  => ["{$year}-01-01", "{$year}-12-31"],
            'custom'  => [$request->start_date ?? now()->format('Y-m-01'), $request->end_date ?? now()->format('Y-m-d')],
            default   => ["{$year}-{$month}-01", date('Y-m-t', mktime(0, 0, 0, $month, 1, $year))],
        };
    }
}
