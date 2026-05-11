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
            'name'                    => 'required|string|max:255',
            'email'                   => 'required|email|unique:users,email,' . $user->id,
            'phone'                   => 'nullable|string|max:20',
            'timezone'                => 'nullable|string',
            'currency'                => 'nullable|string|max:5',
            'minimum_balance_warning' => 'nullable|numeric|min:0',
            'telegram_notifications'  => 'nullable|boolean',
            'avatar'                  => 'nullable|image|max:2048',
            // Reminder settings
            'daily_reminder_enabled'        => 'nullable|boolean',
            'daily_reminder_time'           => 'nullable|regex:/^\d{2}:\d{2}$/',
            'weekly_summary_enabled'        => 'nullable|boolean',
            'big_transaction_alert_enabled' => 'nullable|boolean',
            'big_transaction_threshold'     => 'nullable|numeric|min:10000',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::disk('public')->delete($user->avatar);
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $data['telegram_notifications']          = $request->boolean('telegram_notifications');
        $data['daily_reminder_enabled']          = $request->boolean('daily_reminder_enabled');
        $data['weekly_summary_enabled']          = $request->boolean('weekly_summary_enabled');
        $data['big_transaction_alert_enabled']   = $request->boolean('big_transaction_alert_enabled');
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
