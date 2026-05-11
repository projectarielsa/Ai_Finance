<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::updateOrCreate(['email' => 'admin@financeai.app'], [
            'name'                  => 'Admin FinanceAI',
            'password'              => Hash::make('password'),
            'role'                  => 'admin',
            'is_active'             => true,
            'telegram_notifications'=> false,
        ]);

        // Demo user
        $user = User::updateOrCreate(['email' => 'demo@financeai.app'], [
            'name'                  => 'Demo User',
            'password'              => Hash::make('password'),
            'role'                  => 'user',
            'is_active'             => true,
            'telegram_notifications'=> true,
        ]);

        // Create wallets for demo user
        $walletData = [
            ['name' => 'BCA',      'type' => 'bank',     'provider' => 'BCA',      'color' => '#003087', 'balance' => 5000000],
            ['name' => 'BRI',      'type' => 'bank',     'provider' => 'BRI',      'color' => '#003087', 'balance' => 2500000],
            ['name' => 'Gopay',    'type' => 'e_wallet', 'provider' => 'Gopay',    'color' => '#00aed6', 'balance' => 350000],
            ['name' => 'Dana',     'type' => 'e_wallet', 'provider' => 'Dana',     'color' => '#118eea', 'balance' => 200000],
            ['name' => 'Cash',     'type' => 'cash',     'provider' => 'Cash',     'color' => '#22c55e', 'balance' => 500000],
            ['name' => 'OVO',      'type' => 'e_wallet', 'provider' => 'OVO',      'color' => '#4c1b7c', 'balance' => 150000],
        ];

        $wallets = [];
        foreach ($walletData as $i => $wd) {
            $wallets[] = Wallet::updateOrCreate(
                ['user_id' => $user->id, 'slug' => Str::slug($wd['name'])],
                array_merge($wd, [
                    'user_id'         => $user->id,
                    'slug'            => Str::slug($wd['name']),
                    'initial_balance' => $wd['balance'],
                    'sort_order'      => $i,
                ])
            );
        }

        // Seed demo transactions (last 30 days)
        $categories = Category::whereNull('user_id')->get()->keyBy('slug');

        $demoTransactions = [
            ['type'=>'income',  'amount'=>5000000, 'wallet'=>'BCA',   'cat'=>'gaji',           'desc'=>'Gaji Januari', 'days_ago'=>28],
            ['type'=>'expense', 'amount'=>250000,  'wallet'=>'Gopay', 'cat'=>'makanan-minuman','desc'=>'Makan siang kantin','days_ago'=>25],
            ['type'=>'expense', 'amount'=>80000,   'wallet'=>'Cash',  'cat'=>'transport',      'desc'=>'Isi bensin motor','days_ago'=>24],
            ['type'=>'expense', 'amount'=>350000,  'wallet'=>'BCA',   'cat'=>'tagihan',        'desc'=>'Bayar listrik PLN','days_ago'=>22],
            ['type'=>'transfer','amount'=>500000,  'wallet'=>'BCA',   'target'=>'Gopay',       'desc'=>'Top up Gopay','days_ago'=>20],
            ['type'=>'expense', 'amount'=>75000,   'wallet'=>'Gopay', 'cat'=>'makanan-minuman','desc'=>'Kopi & snack','days_ago'=>19],
            ['type'=>'expense', 'amount'=>150000,  'wallet'=>'BCA',   'cat'=>'belanja-harian', 'desc'=>'Belanja Indomaret','days_ago'=>18],
            ['type'=>'income',  'amount'=>500000,  'wallet'=>'BCA',   'cat'=>'freelance',      'desc'=>'Fee desain logo','days_ago'=>15],
            ['type'=>'expense', 'amount'=>250000,  'wallet'=>'Cash',  'cat'=>'kesehatan',      'desc'=>'Beli obat apotek','days_ago'=>14],
            ['type'=>'expense', 'amount'=>120000,  'wallet'=>'OVO',   'cat'=>'hiburan',        'desc'=>'Nonton bioskop','days_ago'=>12],
            ['type'=>'expense', 'amount'=>45000,   'wallet'=>'Gopay', 'cat'=>'transport',      'desc'=>'Grab ke mall','days_ago'=>10],
            ['type'=>'expense', 'amount'=>200000,  'wallet'=>'BCA',   'cat'=>'belanja-harian', 'desc'=>'Belanja bulanan Alfamart','days_ago'=>8],
            ['type'=>'expense', 'amount'=>35000,   'wallet'=>'Cash',  'cat'=>'makanan-minuman','desc'=>'Mie ayam + es teh','days_ago'=>5],
            ['type'=>'expense', 'amount'=>500000,  'wallet'=>'BRI',   'cat'=>'tagihan',        'desc'=>'Bayar internet Indihome','days_ago'=>3],
            ['type'=>'expense', 'amount'=>60000,   'wallet'=>'Dana',  'cat'=>'makanan-minuman','desc'=>'Lunch delivery GoFood','days_ago'=>1],
        ];

        $walletMap = collect($wallets)->keyBy('name');

        foreach ($demoTransactions as $dt) {
            $wallet = $walletMap->get($dt['wallet']);
            if (!$wallet) continue;

            $targetWalletId = null;
            if (!empty($dt['target'])) {
                $targetWalletId = $walletMap->get($dt['target'])?->id;
            }

            $categoryId = null;
            if (!empty($dt['cat'])) {
                $categoryId = $categories->get($dt['cat'])?->id;
            }

            Transaction::create([
                'user_id'          => $user->id,
                'wallet_id'        => $wallet->id,
                'target_wallet_id' => $targetWalletId,
                'category_id'      => $categoryId,
                'type'             => $dt['type'],
                'amount'           => $dt['amount'],
                'description'      => $dt['desc'],
                'transaction_date' => now()->subDays($dt['days_ago']),
                'source'           => 'manual',
                'status'           => 'completed',
            ]);
        }
    }
}
