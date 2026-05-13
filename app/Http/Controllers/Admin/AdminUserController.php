<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withTrashed();
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }
        $users = $query->latest()->paginate(20)->withQueryString();
        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['wallets', 'transactions' => fn($q) => $q->latest()->limit(10)]);
        return view('admin.users.show', compact('user'));
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', 'Status user diperbarui!');
    }

    public function toggleRole(User $user)
    {
        $user->update(['role' => $user->role === 'admin' ? 'user' : 'admin']);
        return back()->with('success', 'Role user diperbarui!');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => 'required|min:8|confirmed']);
        $user->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'Password direset!');
    }

    public function destroy(User $user)
    {
        // Jangan izinkan hapus diri sendiri
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun Anda sendiri!');
        }

        // Soft delete user
        $user->delete();
        return back()->with('success', "User {$user->name} berhasil dihapus!");
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        return back()->with('success', "User {$user->name} berhasil dipulihkan!");
    }

    public function forceDelete($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun Anda sendiri!');
        }

        // Hapus permanen beserta relasi
        $user->wallets()->delete();
        $user->transactions()->delete();
        $user->categories()->delete();
        $user->budgets()->delete();
        $user->goals()->delete();
        $user->recurringTransactions()->delete();
        $user->debts()->delete();
        $user->forceDelete();

        return back()->with('success', "User {$user->name} dihapus permanen!");
    }
}
