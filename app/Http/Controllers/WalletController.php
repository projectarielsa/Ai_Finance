<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function index()
    {
        $user    = Auth::user();
        $wallets = $user->wallets()->withCount('transactions')->orderBy('sort_order')->get();
        return view('wallets.index', compact('wallets'));
    }

    public function create()
    {
        return view('wallets.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'type'             => 'required|in:bank,e_wallet,cash,investment,credit_card,other',
            'provider'         => 'nullable|string|max:100',
            'color'            => 'nullable|string|max:7',
            'initial_balance'  => 'nullable|numeric|min:0',
            'account_number'   => 'nullable|string|max:50',
            'description'      => 'nullable|string|max:500',
            'include_in_total' => 'nullable|boolean',
        ]);

        $user = Auth::user();

        // Check unique slug for user
        $slug = Str::slug($data['name']);
        $slugExists = Wallet::where('user_id', $user->id)->where('slug', $slug)->exists();
        if ($slugExists) $slug = $slug . '-' . Str::random(4);

        $balance = (float)($data['initial_balance'] ?? 0);

        Wallet::create([
            'user_id'          => $user->id,
            'name'             => $data['name'],
            'slug'             => $slug,
            'type'             => $data['type'],
            'provider'         => $data['provider'] ?? null,
            'color'            => $data['color'] ?? '#3b82f6',
            'balance'          => $balance,
            'initial_balance'  => $balance,
            'account_number'   => $data['account_number'] ?? null,
            'description'      => $data['description'] ?? null,
            'include_in_total' => $request->boolean('include_in_total', true),
        ]);

        return redirect()->route('wallets.index')->with('success', 'Wallet berhasil ditambahkan!');
    }

    public function show(Wallet $wallet)
    {
        $this->authorizeWallet($wallet);

        $transactions = $wallet->transactions()
            ->with(['category', 'targetWallet'])
            ->orderBy('transaction_date', 'desc')
            ->paginate(20);

        // Monthly chart (income vs expense for this wallet) — single query
        $months    = 6;
        $startDate = now()->subMonths($months - 1)->startOfMonth();

        $chartRows = $wallet->transactions()
            ->completed()
            ->whereIn('type', ['income', 'expense'])
            ->where('transaction_date', '>=', $startDate)
            ->selectRaw('YEAR(transaction_date) as y, MONTH(transaction_date) as m, type, SUM(amount) as total')
            ->groupBy('y', 'm', 'type')
            ->get()
            ->groupBy(fn($r) => "{$r->y}-{$r->m}");

        $chartLabels  = [];
        $chartIncome  = [];
        $chartExpense = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $key   = $date->year . '-' . $date->month;
            $group = $chartRows->get($key, collect());

            $chartLabels[]  = $date->format('M Y');
            $chartIncome[]  = (float) ($group->firstWhere('type', 'income')?->total ?? 0);
            $chartExpense[] = (float) ($group->firstWhere('type', 'expense')?->total ?? 0);
        }

        $chartData = ['labels' => $chartLabels, 'income' => $chartIncome, 'expense' => $chartExpense];

        return view('wallets.show', compact('wallet', 'transactions', 'chartData'));
    }

    public function edit(Wallet $wallet)
    {
        $this->authorizeWallet($wallet);
        return view('wallets.edit', compact('wallet'));
    }

    public function update(Request $request, Wallet $wallet)
    {
        $this->authorizeWallet($wallet);

        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'type'             => 'required|in:bank,e_wallet,cash,investment,credit_card,other',
            'provider'         => 'nullable|string|max:100',
            'color'            => 'nullable|string|max:7',
            'account_number'   => 'nullable|string|max:50',
            'description'      => 'nullable|string|max:500',
            'include_in_total' => 'nullable|boolean',
            'is_active'        => 'nullable|boolean',
        ]);

        $wallet->update([
            'name'             => $data['name'],
            'type'             => $data['type'],
            'provider'         => $data['provider'] ?? null,
            'color'            => $data['color'] ?? $wallet->color,
            'account_number'   => $data['account_number'] ?? null,
            'description'      => $data['description'] ?? null,
            'include_in_total' => $request->boolean('include_in_total', true),
            'is_active'        => $request->boolean('is_active', true),
        ]);

        return redirect()->route('wallets.index')->with('success', 'Wallet berhasil diperbarui!');
    }

    public function destroy(Wallet $wallet)
    {
        $this->authorizeWallet($wallet);

        if ($wallet->transactions()->exists()) {
            return back()->with('error', 'Wallet tidak bisa dihapus karena masih memiliki transaksi.');
        }

        $wallet->delete();
        return redirect()->route('wallets.index')->with('success', 'Wallet berhasil dihapus!');
    }

    public function adjustBalance(Request $request, Wallet $wallet)
    {
        $this->authorizeWallet($wallet);
        $request->validate(['balance' => 'required|numeric|min:0']);

        $newBalance = (float) $request->balance;

        \Illuminate\Support\Facades\DB::transaction(function () use ($wallet, $newBalance) {
            // Lock row agar tidak ada race condition dari request bersamaan
            $wallet = Wallet::lockForUpdate()->findOrFail($wallet->id);

            $oldBalance = (float) $wallet->balance;
            $diff       = $newBalance - $oldBalance;

            if ($diff == 0) return;

            $wallet->update(['balance' => $newBalance]);

            // Audit trail
            \App\Models\Transaction::create([
                'user_id'          => $wallet->user_id,
                'wallet_id'        => $wallet->id,
                'type'             => $diff > 0 ? 'income' : 'expense',
                'amount'           => abs($diff),
                'description'      => 'Penyesuaian saldo manual',
                'transaction_date' => now(),
                'source'           => 'manual',
                'status'           => 'completed',
            ]);
        });

        return response()->json(['success' => true, 'balance' => $wallet->fresh()->balance]);
    }

    protected function authorizeWallet(Wallet $wallet): void
    {
        abort_unless($wallet->user_id === Auth::id(), 403);
    }
}
