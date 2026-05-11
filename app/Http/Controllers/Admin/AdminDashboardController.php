<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiLog;
use App\Models\TelegramMessage;
use App\Models\Transaction;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalUsers        = User::count();
        $totalTransactions = Transaction::count();
        $totalAiRequests   = AiLog::count();
        $totalTgMessages   = TelegramMessage::count();
        $recentUsers       = User::latest()->limit(5)->get();
        $recentAiLogs      = AiLog::with('user')->latest()->limit(10)->get();
        $recentTgMessages  = TelegramMessage::with('user')->latest()->limit(10)->get();
        $aiSuccessRate     = AiLog::count() > 0
            ? round(AiLog::where('success', true)->count() / AiLog::count() * 100, 1)
            : 0;

        return view('admin.dashboard', compact(
            'totalUsers', 'totalTransactions', 'totalAiRequests',
            'totalTgMessages', 'recentUsers', 'recentAiLogs',
            'recentTgMessages', 'aiSuccessRate'
        ));
    }
}
