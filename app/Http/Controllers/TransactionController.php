<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user  = Auth::user();
        $query = $user->transactions()->with(['wallet', 'category', 'targetWallet']);

        if ($request->type)     $query->where('type', $request->type);
        if ($request->wallet)   $query->where('wallet_id', $request->wallet);
        if ($request->category) $query->where('category_id', $request->category);
        if ($request->month)    $query->whereMonth('transaction_date', $request->month);
        if ($request->year)     $query->whereYear('transaction_date', $request->year);
        if ($request->search)   $query->where(function($q) use ($request) {
            $q->where('description', 'like', "%{$request->search}%")
              ->orWhere('merchant', 'like', "%{$request->search}%");
        });

        $transactions = $query->orderBy('transaction_date', 'desc')->paginate(20)->withQueryString();
        $wallets      = $user->wallets()->where('is_active', true)->get();
        $categories   = Category::where(function($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->where('is_active', true)->get();

        return view('transactions.index', compact('transactions', 'wallets', 'categories'));
    }

    public function create()
    {
        $user       = Auth::user();
        $wallets    = $user->wallets()->where('is_active', true)->get();
        $categories = Category::where(function($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->where('is_active', true)->get();
        return view('transactions.create', compact('wallets', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'             => 'required|in:income,expense,transfer',
            'amount'           => 'required|numeric|min:1',
            'wallet_id'        => 'required|exists:wallets,id',
            'target_wallet_id' => 'nullable|exists:wallets,id',
            'category_id'      => 'nullable|exists:categories,id',
            'description'      => 'nullable|string|max:500',
            'notes'            => 'nullable|string|max:1000',
            'merchant'         => 'nullable|string|max:200',
            'transaction_date' => 'required|date',
            'attachment'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user   = Auth::user();
        $wallet = Wallet::where('id', $data['wallet_id'])->where('user_id', $user->id)->firstOrFail();

        // Balance validation
        if (in_array($data['type'], ['expense', 'transfer'])) {
            if (!$wallet->hasSufficientBalance((float)$data['amount'])) {
                return back()->withInput()->with('error', "Saldo {$wallet->name} tidak cukup. Saldo saat ini: " . $wallet->formatted_balance);
            }
        }

        $targetWallet = null;
        if ($data['type'] === 'transfer' && !empty($data['target_wallet_id'])) {
            $targetWallet = Wallet::where('id', $data['target_wallet_id'])->where('user_id', $user->id)->firstOrFail();
        }

        // Handle attachment
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('receipts/' . date('Y/m'), 'public');
        }

        DB::transaction(function () use ($data, $user, $wallet, $targetWallet, $attachmentPath) {
            $transaction = Transaction::create([
                'user_id'          => $user->id,
                'wallet_id'        => $wallet->id,
                'target_wallet_id' => $targetWallet?->id,
                'category_id'      => $data['category_id'] ?? null,
                'type'             => $data['type'],
                'amount'           => $data['amount'],
                'description'      => $data['description'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'merchant'         => $data['merchant'] ?? null,
                'attachment'       => $attachmentPath,
                'transaction_date' => $data['transaction_date'],
                'source'           => 'manual',
                'status'           => 'completed',
            ]);

            // Update balances
            match($data['type']) {
                'income'   => $wallet->credit($data['amount']),
                'expense'  => $wallet->debit($data['amount']),
                'transfer' => (function() use ($wallet, $targetWallet, $data) {
                    $wallet->debit($data['amount']);
                    $targetWallet?->credit($data['amount']);
                })(),
            };
        });

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil disimpan!');
    }

    public function show(Transaction $transaction)
    {
        abort_unless($transaction->user_id === Auth::id(), 403);
        $transaction->load(['wallet', 'targetWallet', 'category', 'receiptScan', 'voiceNoteTranscription']);
        return view('transactions.show', compact('transaction'));
    }

    public function edit(Transaction $transaction)
    {
        abort_unless($transaction->user_id === Auth::id(), 403);
        $user       = Auth::user();
        $wallets    = $user->wallets()->where('is_active', true)->get();
        $categories = Category::where(function($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->where('is_active', true)->get();
        return view('transactions.edit', compact('transaction', 'wallets', 'categories'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === Auth::id(), 403);

        $data = $request->validate([
            'description'      => 'nullable|string|max:500',
            'notes'            => 'nullable|string|max:1000',
            'merchant'         => 'nullable|string|max:200',
            'category_id'      => 'nullable|exists:categories,id',
            'transaction_date' => 'required|date',
        ]);

        $transaction->update($data);
        return redirect()->route('transactions.show', $transaction)->with('success', 'Transaksi diperbarui!');
    }

    public function destroy(Transaction $transaction)
    {
        abort_unless($transaction->user_id === Auth::id(), 403);

        DB::transaction(function () use ($transaction) {
            // Reverse wallet balance
            $wallet = $transaction->wallet;
            match($transaction->type) {
                'income'   => $wallet->debit($transaction->amount),
                'expense'  => $wallet->credit($transaction->amount),
                'transfer' => (function() use ($transaction, $wallet) {
                    $wallet->credit($transaction->amount);
                    $transaction->targetWallet?->debit($transaction->amount);
                })(),
            };
            $transaction->delete();
        });

        return redirect()->route('transactions.index')->with('success', 'Transaksi dihapus!');
    }
}
