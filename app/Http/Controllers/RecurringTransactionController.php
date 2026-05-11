<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecurringTransactionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $recurring = RecurringTransaction::where('user_id', $user->id)
            ->with(['wallet', 'category', 'targetWallet'])
            ->orderBy('next_run_date')
            ->get();

        return view('recurring.index', compact('recurring'));
    }

    public function create()
    {
        $user       = Auth::user();
        $wallets    = $user->wallets()->where('is_active', true)->get();
        $categories = Category::forUser($user->id)->where('is_active', true)->orderBy('name')->get();
        return view('recurring.create', compact('wallets', 'categories'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'title'             => 'required|string|max:200',
            'type'              => 'required|in:income,expense,transfer',
            'amount'            => 'required|numeric|min:1',
            'wallet_id'         => 'required|exists:wallets,id',
            'target_wallet_id'  => 'nullable|exists:wallets,id',
            'category_id'       => 'nullable|exists:categories,id',
            'frequency'         => 'required|in:daily,weekly,monthly,yearly',
            'start_date'        => 'required|date',
            'end_date'          => 'nullable|date|after:start_date',
            'description'       => 'nullable|string|max:500',
            'merchant'          => 'nullable|string|max:200',
            'auto_execute'      => 'nullable|boolean',
        ]);

        // Pastikan wallet milik user
        $wallet = Wallet::where('id', $data['wallet_id'])->where('user_id', $user->id)->firstOrFail();

        RecurringTransaction::create([
            ...$data,
            'user_id'          => $user->id,
            'next_run_date'    => $data['start_date'],
            'is_active'        => true,
            'auto_execute'     => $request->boolean('auto_execute', true),
        ]);

        return redirect()->route('recurring.index')->with('success', 'Transaksi berulang berhasil ditambahkan!');
    }

    public function edit(RecurringTransaction $recurring)
    {
        abort_unless($recurring->user_id === Auth::id(), 403);
        $user       = Auth::user();
        $wallets    = $user->wallets()->where('is_active', true)->get();
        $categories = Category::forUser($user->id)->where('is_active', true)->orderBy('name')->get();
        return view('recurring.edit', compact('recurring', 'wallets', 'categories'));
    }

    public function update(Request $request, RecurringTransaction $recurring)
    {
        abort_unless($recurring->user_id === Auth::id(), 403);

        $data = $request->validate([
            'title'             => 'required|string|max:200',
            'amount'            => 'required|numeric|min:1',
            'wallet_id'         => 'required|exists:wallets,id',
            'target_wallet_id'  => 'nullable|exists:wallets,id',
            'category_id'       => 'nullable|exists:categories,id',
            'frequency'         => 'required|in:daily,weekly,monthly,yearly',
            'end_date'          => 'nullable|date',
            'description'       => 'nullable|string|max:500',
            'merchant'          => 'nullable|string|max:200',
            'is_active'         => 'nullable|boolean',
            'auto_execute'      => 'nullable|boolean',
        ]);

        $recurring->update([
            ...$data,
            'is_active'    => $request->boolean('is_active', true),
            'auto_execute' => $request->boolean('auto_execute', true),
        ]);

        return redirect()->route('recurring.index')->with('success', 'Transaksi berulang diperbarui!');
    }

    public function destroy(RecurringTransaction $recurring)
    {
        abort_unless($recurring->user_id === Auth::id(), 403);
        $recurring->delete();
        return back()->with('success', 'Transaksi berulang dihapus!');
    }

    /** Manual execute sekarang */
    public function executeNow(RecurringTransaction $recurring)
    {
        abort_unless($recurring->user_id === Auth::id(), 403);
        $user = Auth::user();

        try {
            DB::transaction(function () use ($recurring, $user) {
                $wallet = Wallet::lockForUpdate()->findOrFail($recurring->wallet_id);

                if (in_array($recurring->type, ['expense', 'transfer'])) {
                    if (!$wallet->hasSufficientBalance($recurring->amount)) {
                        throw new \Exception("Saldo {$wallet->name} tidak cukup.");
                    }
                }

                Transaction::create([
                    'user_id'          => $user->id,
                    'wallet_id'        => $recurring->wallet_id,
                    'target_wallet_id' => $recurring->target_wallet_id,
                    'category_id'      => $recurring->category_id,
                    'type'             => $recurring->type,
                    'amount'           => $recurring->amount,
                    'description'      => $recurring->title . ' (auto)',
                    'merchant'         => $recurring->merchant,
                    'transaction_date' => now(),
                    'source'           => 'manual',
                    'status'           => 'completed',
                ]);

                match ($recurring->type) {
                    'income'   => $wallet->credit($recurring->amount),
                    'expense'  => $wallet->debit($recurring->amount),
                    'transfer' => (function () use ($recurring, $wallet): void {
                        $wallet->debit($recurring->amount);
                        Wallet::find($recurring->target_wallet_id)?->credit($recurring->amount);
                    })(),
                };

                $recurring->update([
                    'last_run_date' => now()->toDateString(),
                    'next_run_date' => $recurring->calculateNextRunDate()->toDateString(),
                ]);
            });

            return back()->with('success', "Transaksi \"{$recurring->title}\" berhasil dijalankan!");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
