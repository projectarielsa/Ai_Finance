<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $user  = Auth::user();
        $type  = $request->input('type', 'all');   // all | receivable | payable
        $status = $request->input('status', 'active'); // active | paid | all

        $query = Debt::where('user_id', $user->id)->with(['wallet', 'payments']);

        if ($type !== 'all')   $query->where('type', $type);
        if ($status === 'active') $query->active();
        elseif ($status === 'paid') $query->paid();

        $debts = $query->orderByRaw("FIELD(status,'active','partial','paid','cancelled')")
                       ->orderBy('due_date')
                       ->orderByDesc('debt_date')
                       ->get();

        // Summary stats
        $totalReceivable = Debt::where('user_id', $user->id)->active()->receivable()->sum(DB::raw('amount - paid_amount'));
        $totalPayable    = Debt::where('user_id', $user->id)->active()->payable()->sum(DB::raw('amount - paid_amount'));
        $overdueCount    = Debt::where('user_id', $user->id)->overdue()->count();

        $wallets = $user->wallets()->where('is_active', true)->get();

        return view('debts.index', compact('debts', 'totalReceivable', 'totalPayable', 'overdueCount', 'wallets', 'type', 'status'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'type'          => 'required|in:receivable,payable',
            'contact_name'  => 'required|string|max:200',
            'contact_phone' => 'nullable|string|max:20',
            'amount'        => 'required|numeric|min:1000',
            'description'   => 'nullable|string|max:500',
            'debt_date'     => 'required|date',
            'due_date'      => 'nullable|date|after_or_equal:debt_date',
            'wallet_id'     => 'nullable|exists:wallets,id',
            'notes'         => 'nullable|string|max:1000',
            'notify_on_due' => 'nullable|boolean',
        ]);

        Debt::create([
            ...$data,
            'user_id'       => $user->id,
            'paid_amount'   => 0,
            'status'        => 'active',
            'notify_on_due' => $request->boolean('notify_on_due', true),
        ]);

        return redirect()->route('debts.index')->with('success', ucfirst($data['type'] === 'receivable' ? 'Piutang' : 'Hutang') . ' berhasil ditambahkan!');
    }

    public function show(Debt $debt)
    {
        abort_unless($debt->user_id === Auth::id(), 403);
        $debt->load(['wallet', 'payments.wallet']);
        $wallets = Auth::user()->wallets()->where('is_active', true)->get();
        return view('debts.show', compact('debt', 'wallets'));
    }

    public function edit(Debt $debt)
    {
        abort_unless($debt->user_id === Auth::id(), 403);
        $wallets = Auth::user()->wallets()->where('is_active', true)->get();
        return view('debts.edit', compact('debt', 'wallets'));
    }

    public function update(Request $request, Debt $debt)
    {
        abort_unless($debt->user_id === Auth::id(), 403);

        $data = $request->validate([
            'contact_name'  => 'required|string|max:200',
            'contact_phone' => 'nullable|string|max:20',
            'amount'        => 'required|numeric|min:1000',
            'description'   => 'nullable|string|max:500',
            'debt_date'     => 'required|date',
            'due_date'      => 'nullable|date',
            'wallet_id'     => 'nullable|exists:wallets,id',
            'notes'         => 'nullable|string|max:1000',
            'status'        => 'nullable|in:active,partial,paid,cancelled',
            'notify_on_due' => 'nullable|boolean',
        ]);

        $debt->update([
            ...$data,
            'notify_on_due' => $request->boolean('notify_on_due', true),
        ]);

        return redirect()->route('debts.show', $debt)->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy(Debt $debt)
    {
        abort_unless($debt->user_id === Auth::id(), 403);
        $debt->delete();
        return redirect()->route('debts.index')->with('success', 'Data hutang/piutang dihapus!');
    }

    /** Catat pembayaran (sebagian atau lunas) */
    public function pay(Request $request, Debt $debt)
    {
        abort_unless($debt->user_id === Auth::id(), 403);

        $data = $request->validate([
            'amount'       => 'required|numeric|min:1|max:' . $debt->remaining_amount,
            'wallet_id'    => 'nullable|exists:wallets,id',
            'payment_date' => 'required|date',
            'notes'        => 'nullable|string|max:500',
        ]);

        $user   = Auth::user();
        $amount = (float) $data['amount'];

        try {
            DB::transaction(function () use ($debt, $data, $amount, $user) {
                $transactionId = null;

                // Jika wallet dipilih, buat transaksi dan update saldo
                if (!empty($data['wallet_id'])) {
                    $wallet = Wallet::lockForUpdate()
                        ->where('id', $data['wallet_id'])
                        ->where('user_id', $user->id)
                        ->firstOrFail();

                    // Kalau piutang dibayar → uang masuk ke wallet kita
                    // Kalau hutang dibayar → uang keluar dari wallet kita
                    $txType = $debt->type === 'receivable' ? 'income' : 'expense';

                    if ($txType === 'expense' && !$wallet->hasSufficientBalance($amount)) {
                        throw new \Exception("Saldo {$wallet->name} tidak cukup.");
                    }

                    $tx = Transaction::create([
                        'user_id'          => $user->id,
                        'wallet_id'        => $wallet->id,
                        'type'             => $txType,
                        'amount'           => $amount,
                        'description'      => ($debt->type === 'receivable' ? 'Piutang dibayar: ' : 'Bayar hutang: ') . $debt->contact_name,
                        'transaction_date' => $data['payment_date'],
                        'source'           => 'manual',
                        'status'           => 'completed',
                    ]);

                    $txType === 'income' ? $wallet->credit($amount) : $wallet->debit($amount);
                    $transactionId = $tx->id;
                }

                // Catat pembayaran
                DebtPayment::create([
                    'debt_id'      => $debt->id,
                    'wallet_id'    => $data['wallet_id'] ?? null,
                    'transaction_id' => $transactionId,
                    'amount'       => $amount,
                    'payment_date' => $data['payment_date'],
                    'notes'        => $data['notes'] ?? null,
                    'source'       => 'manual',
                ]);

                // Update saldo hutang
                $debt->addPayment($amount);
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $label = $debt->type === 'receivable' ? 'piutang' : 'hutang';
        $msg   = $debt->fresh()->status === 'paid'
            ? "🎉 {$label} dengan {$debt->contact_name} sudah LUNAS!"
            : "Pembayaran Rp" . number_format($amount, 0, ',', '.') . " berhasil dicatat!";

        return back()->with('success', $msg);
    }

    /** Tandai lunas langsung tanpa catat bayar */
    public function markPaid(Debt $debt)
    {
        abort_unless($debt->user_id === Auth::id(), 403);
        $debt->update(['status' => 'paid', 'paid_amount' => $debt->amount]);
        return back()->with('success', 'Ditandai lunas!');
    }
}
