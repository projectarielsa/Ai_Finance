<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ApiCredentialController;
use App\Http\Controllers\Admin\AppSettingController;
use App\Http\Controllers\Admin\TelegramSettingController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

// ─── Auth Routes ─────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('login',    [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login',   [LoginController::class, 'login'])->middleware('throttle:10,1');
    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register',[RegisterController::class, 'register']);
    Route::get('forgot-password',         [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password',        [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('reset-password/{token}',  [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password',         [ForgotPasswordController::class, 'reset'])->name('password.update');
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Telegram Webhook (public, no auth) ──────────────────────────────────────
Route::post('webhook/telegram',             [TelegramController::class, 'webhook'])->name('webhook.telegram');
Route::get('telegram/setup-webhook',        [TelegramController::class, 'setupWebhook'])->name('telegram.setup-webhook')->middleware('auth');
Route::get('telegram/delete-webhook',       [TelegramController::class, 'deleteWebhook'])->name('telegram.delete-webhook')->middleware('auth');
Route::get('telegram/webhook-info',         [TelegramController::class, 'webhookInfo'])->name('telegram.webhook-info')->middleware('auth');
Route::get('telegram/test',                 [TelegramController::class, 'testConnection'])->name('telegram.test')->middleware('auth');

// ─── Authenticated Routes ─────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/chart-data', [DashboardController::class, 'getChartDataApi'])->name('dashboard.chart-data');

    // Profile
    Route::get('profile',                   [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile',                   [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password',          [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Wallets
    Route::resource('wallets', WalletController::class);
    Route::post('wallets/{wallet}/adjust-balance', [WalletController::class, 'adjustBalance'])->name('wallets.adjust-balance');

    // Transactions
    Route::resource('transactions', TransactionController::class);

    // Reports
    Route::get('reports',          [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/pdf',      [ReportController::class, 'exportPdf'])->name('reports.pdf');
    Route::get('reports/excel',    [ReportController::class, 'exportExcel'])->name('reports.excel');

    // Categories
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Budgets
    Route::get('budgets',               [BudgetController::class, 'index'])->name('budgets.index');
    Route::post('budgets',              [BudgetController::class, 'store'])->name('budgets.store');
    Route::put('budgets/{budget}',      [BudgetController::class, 'update'])->name('budgets.update');
    Route::delete('budgets/{budget}',   [BudgetController::class, 'destroy'])->name('budgets.destroy');

    // Recurring Transactions
    Route::resource('recurring', RecurringTransactionController::class)->except(['show']);
    Route::post('recurring/{recurring}/execute', [RecurringTransactionController::class, 'executeNow'])->name('recurring.execute');

    // Debts (Hutang & Piutang)
    Route::get('debts',                             [DebtController::class, 'index'])->name('debts.index');
    Route::post('debts',                            [DebtController::class, 'store'])->name('debts.store');
    Route::get('debts/{debt}',                      [DebtController::class, 'show'])->name('debts.show');
    Route::get('debts/{debt}/edit',                 [DebtController::class, 'edit'])->name('debts.edit');
    Route::put('debts/{debt}',                      [DebtController::class, 'update'])->name('debts.update');
    Route::delete('debts/{debt}',                   [DebtController::class, 'destroy'])->name('debts.destroy');
    Route::post('debts/{debt}/pay',                 [DebtController::class, 'pay'])->name('debts.pay');
    Route::post('debts/{debt}/mark-paid',           [DebtController::class, 'markPaid'])->name('debts.markPaid');

    // Goals
    Route::get('goals',                         [GoalController::class, 'index'])->name('goals.index');
    Route::post('goals',                         [GoalController::class, 'store'])->name('goals.store');
    Route::get('goals/{goal}/edit',              [GoalController::class, 'edit'])->name('goals.edit');
    Route::put('goals/{goal}',                   [GoalController::class, 'update'])->name('goals.update');
    Route::post('goals/{goal}/add-funds',        [GoalController::class, 'addFunds'])->name('goals.add-funds');
    Route::delete('goals/{goal}',                [GoalController::class, 'destroy'])->name('goals.destroy');
});

// ─── Admin Routes ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::get('users',                          [AdminUserController::class, 'index'])->name('users.index');
    Route::get('users/{user}',                   [AdminUserController::class, 'show'])->name('users.show');
    Route::post('users/{user}/toggle-active',    [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::post('users/{user}/toggle-role',      [AdminUserController::class, 'toggleRole'])->name('users.toggle-role');
    Route::post('users/{user}/reset-password',   [AdminUserController::class, 'resetPassword'])->name('users.reset-password');

    // API Credentials
    Route::resource('api-credentials', ApiCredentialController::class)->except(['show']);
    Route::post('api-credentials/{apiCredential}/test', [ApiCredentialController::class, 'testConnection'])->name('api-credentials.test');

    // App Settings
    Route::get('settings',           [AppSettingController::class, 'index'])->name('settings.index');
    Route::post('settings',          [AppSettingController::class, 'store'])->name('settings.store');
    Route::put('settings',           [AppSettingController::class, 'update'])->name('settings.update');
    Route::delete('settings/{appSetting}', [AppSettingController::class, 'destroy'])->name('settings.destroy');

    // Telegram Settings
    Route::get('telegram',                    [TelegramSettingController::class, 'index'])->name('telegram.index');
    Route::post('telegram/test',              [TelegramSettingController::class, 'testConnection'])->name('telegram.test-connection');
    Route::post('telegram/set-webhook',       [TelegramSettingController::class, 'setWebhook'])->name('telegram.set-webhook');
    Route::post('telegram/delete-webhook',    [TelegramSettingController::class, 'deleteWebhook'])->name('telegram.delete-webhook');
    Route::post('telegram/send-test',         [TelegramSettingController::class, 'sendTest'])->name('telegram.send-test');
    // Logs
    Route::get('ai-logs',  fn() => view('admin.logs.ai',  ['logs' => \App\Models\AiLog::with('user')->latest()->paginate(50)]))->name('ai-logs');
    Route::get('tg-logs',  fn() => view('admin.logs.telegram', ['messages' => \App\Models\TelegramMessage::with('user')->latest()->paginate(50)]))->name('tg-logs');
});
