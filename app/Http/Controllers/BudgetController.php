<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $user  = Auth::user();
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year',  now()->year);

        $budgets = Budget::where('user_id', $user->id)
            ->forMonth($year, $month)
            ->with('category')
            ->orderBy('category_id')
            ->get();

        // Hitung spent & percentage untuk tiap budget
        $budgets->each(fn($b) => $b->append(['spent', 'percentage', 'remaining']));

        $categories = Category::forUser($user->id)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('budgets.index', compact('budgets', 'categories', 'month', 'year'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'category_id'  => 'required|exists:categories,id',
            'limit_amount' => 'required|numeric|min:1000',
            'month'        => 'required|integer|between:1,12',
            'year'         => 'required|integer|min:2020|max:' . (now()->year + 10),
            'alert_at_80'  => 'nullable|boolean',
            'alert_at_100' => 'nullable|boolean',
            'notes'        => 'nullable|string|max:255',
            'is_recurring' => 'nullable|boolean',
        ]);

        $isRecurring = $request->boolean('is_recurring');

        Budget::updateOrCreate(
            [
                'user_id'     => $user->id,
                'category_id' => $data['category_id'],
                'month'       => $data['month'],
                'year'        => $data['year'],
            ],
            [
                'limit_amount'   => $data['limit_amount'],
                'alert_at_80'    => $request->boolean('alert_at_80', true),
                'alert_at_100'   => $request->boolean('alert_at_100', true),
                'alert_sent_80'  => false,
                'alert_sent_100' => false,
                'notes'          => $data['notes'] ?? null,
                'is_recurring'   => $isRecurring,
            ]
        );

        return back()->with('success', 'Budget berhasil disimpan!');
    }

    public function update(Request $request, Budget $budget)
    {
        abort_unless($budget->user_id === Auth::id(), 403);

        $data = $request->validate([
            'limit_amount' => 'required|numeric|min:1000',
            'alert_at_80'  => 'nullable|boolean',
            'alert_at_100' => 'nullable|boolean',
            'notes'        => 'nullable|string|max:255',
        ]);

        $budget->update([
            'limit_amount'   => $data['limit_amount'],
            'alert_at_80'    => $request->boolean('alert_at_80', true),
            'alert_at_100'   => $request->boolean('alert_at_100', true),
            'alert_sent_80'  => false, // reset alert
            'alert_sent_100' => false,
            'notes'          => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Budget diperbarui!');
    }

    public function destroy(Budget $budget)
    {
        abort_unless($budget->user_id === Auth::id(), 403);
        $budget->delete();
        return back()->with('success', 'Budget dihapus!');
    }
}
