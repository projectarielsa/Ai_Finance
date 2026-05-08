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
}
