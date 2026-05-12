<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GoalController extends Controller
{
    public function index()
    {
        $user  = Auth::user();
        $goals = Goal::where('user_id', $user->id)
            ->withTrashed(false)
            ->with('wallet')
            ->orderByRaw("FIELD(status,'active','paused','completed','cancelled')")
            ->orderBy('target_date')
            ->get();

        $wallets = $user->wallets()->where('is_active', true)->get();

        return view('goals.index', compact('goals', 'wallets'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'title'                 => 'required|string|max:200',
            'description'           => 'nullable|string|max:1000',
            'target_amount'         => 'required|numeric|min:1000',
            'current_amount'        => 'nullable|numeric|min:0',
            'target_date'           => 'nullable|date|after:today',
            'wallet_id'             => 'nullable|exists:wallets,id',
            'icon'                  => 'nullable|string|max:10',
            'color'                 => 'nullable|string|max:7',
            'notify_on_milestone'   => 'nullable|boolean',
        ]);

        Goal::create([
            ...$data,
            'user_id'              => $user->id,
            'current_amount'       => $data['current_amount'] ?? 0,
            'status'               => 'active',
            'notify_on_milestone'  => $request->boolean('notify_on_milestone', true),
        ]);

        return redirect()->route('goals.index')->with('success', 'Tujuan keuangan berhasil ditambahkan!');
    }

    public function edit(Goal $goal)
    {
        abort_unless($goal->user_id === Auth::id(), 403);
        $user    = Auth::user();
        $wallets = $user->wallets()->where('is_active', true)->get();
        return view('goals.edit', compact('goal', 'wallets'));
    }

    public function update(Request $request, Goal $goal)
    {
        abort_unless($goal->user_id === Auth::id(), 403);

        $data = $request->validate([
            'title'                 => 'required|string|max:200',
            'description'           => 'nullable|string|max:1000',
            'target_amount'         => 'required|numeric|min:1000',
            'target_date'           => 'nullable|date',
            'wallet_id'             => 'nullable|exists:wallets,id',
            'icon'                  => 'nullable|string|max:10',
            'color'                 => 'nullable|string|max:7',
            'status'                => 'nullable|in:active,paused,cancelled',
            'notify_on_milestone'   => 'nullable|boolean',
        ]);

        $goal->update([
            ...$data,
            'notify_on_milestone' => $request->boolean('notify_on_milestone', true),
        ]);

        return redirect()->route('goals.index')->with('success', 'Tujuan keuangan diperbarui!');
    }

    /** Tambah dana ke goal */
    public function addFunds(Request $request, Goal $goal)
    {
        abort_unless($goal->user_id === Auth::id(), 403);

        $data = $request->validate([
            'amount'    => 'required|numeric|min:1000',
            'wallet_id' => 'nullable|exists:wallets,id',
        ]);

        $user   = Auth::user();
        $amount = (float) $data['amount'];

        try {
            DB::transaction(function () use ($goal, $amount, $data, $user) {
                // Debit dari wallet jika dipilih
                if (!empty($data['wallet_id'])) {
                    $wallet = Wallet::lockForUpdate()->findOrFail($data['wallet_id']);
                    if (!$wallet->hasSufficientBalance($amount)) {
                        throw new \Exception("Saldo {$wallet->name} tidak cukup.");
                    }
                    $wallet->debit($amount);

                    Transaction::create([
                        'user_id'          => $user->id,
                        'wallet_id'        => $data['wallet_id'],
                        'type'             => 'expense',
                        'amount'           => $amount,
                        'description'      => "Tabungan tujuan: {$goal->title}",
                        'transaction_date' => now(),
                        'source'           => 'manual',
                        'status'           => 'completed',
                    ]);
                }

                $goal->addFunds($amount);
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Dana berhasil ditambahkan ke tujuan!');
    }

    public function destroy(Goal $goal)
    {
        abort_unless($goal->user_id === Auth::id(), 403);
        $goal->delete();
        return back()->with('success', 'Tujuan keuangan dihapus!');
    }
}
