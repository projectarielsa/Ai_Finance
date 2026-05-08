<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'                     => 'required|string|max:255',
            'email'                    => 'required|email|unique:users,email,' . $user->id,
            'phone'                    => 'nullable|string|max:20',
            'timezone'                 => 'nullable|string',
            'currency'                 => 'nullable|string|max:5',
            'minimum_balance_warning'  => 'nullable|numeric|min:0',
            'whatsapp_notifications'   => 'nullable|boolean',
            'avatar'                   => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::disk('public')->delete($user->avatar);
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $data['whatsapp_notifications'] = $request->boolean('whatsapp_notifications');
        $user->update($data);

        return back()->with('success', 'Profil berhasil diperbarui!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Password saat ini tidak benar.');
        }

        $user->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'Password berhasil diubah!');
    }
}
