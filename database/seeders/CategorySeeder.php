<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // EXPENSE
            ['name' => 'Makanan & Minuman', 'slug' => 'makanan-minuman', 'type' => 'expense', 'icon' => 'utensils',    'color' => '#f59e0b', 'ai_keywords' => ['makan','minum','kopi','restoran','warteg','nasi','bakso','mie','snack','jajan','food','cafe','warung']],
            ['name' => 'Transport',         'slug' => 'transport',        'type' => 'expense', 'icon' => 'car',         'color' => '#3b82f6', 'ai_keywords' => ['bensin','bbm','parkir','grab','gojek','ojek','bus','kereta','toll','tol','taxi','angkot','ojol','transport']],
            ['name' => 'Belanja Harian',    'slug' => 'belanja-harian',   'type' => 'expense', 'icon' => 'shopping-bag','color' => '#8b5cf6', 'ai_keywords' => ['indomaret','alfamart','minimarket','supermarket','beli','belanja','toserba','hypermart','carrefour']],
            ['name' => 'Tagihan',           'slug' => 'tagihan',          'type' => 'expense', 'icon' => 'receipt',     'color' => '#ef4444', 'ai_keywords' => ['listrik','air','pln','pdam','internet','wifi','pulsa','token','tagihan','bill','bayar']],
            ['name' => 'Kesehatan',         'slug' => 'kesehatan',        'type' => 'expense', 'icon' => 'heart',       'color' => '#ec4899', 'ai_keywords' => ['dokter','apotek','obat','klinik','rs','rumahsakit','kesehatan','vitamin','medical']],
            ['name' => 'Hiburan',           'slug' => 'hiburan',          'type' => 'expense', 'icon' => 'film',        'color' => '#06b6d4', 'ai_keywords' => ['bioskop','netflix','spotify','game','hiburan','nonton','main','karaoke','cinema']],
            ['name' => 'Pendidikan',        'slug' => 'pendidikan',       'type' => 'expense', 'icon' => 'book',        'color' => '#10b981', 'ai_keywords' => ['kursus','sekolah','buku','les','kuliah','spp','ukt','pendidikan','training']],
            ['name' => 'Pakaian',           'slug' => 'pakaian',          'type' => 'expense', 'icon' => 'shirt',       'color' => '#f97316', 'ai_keywords' => ['baju','kaos','celana','sepatu','pakaian','fashion','beli pakaian']],
            ['name' => 'Investasi',         'slug' => 'investasi',        'type' => 'expense', 'icon' => 'trending-up', 'color' => '#14b8a6', 'ai_keywords' => ['investasi','saham','reksa','dana','crypto','aset','nabung']],
            ['name' => 'Lainnya',           'slug' => 'lainnya-expense',  'type' => 'expense', 'icon' => 'more-horizontal','color' => '#6b7280', 'ai_keywords' => []],

            // INCOME
            ['name' => 'Gaji',              'slug' => 'gaji',             'type' => 'income', 'icon' => 'briefcase',   'color' => '#22c55e', 'ai_keywords' => ['gaji','salary','upah','thr','bonus','rapel','insentif']],
            ['name' => 'Freelance',         'slug' => 'freelance',        'type' => 'income', 'icon' => 'laptop',      'color' => '#84cc16', 'ai_keywords' => ['freelance','proyek','project','fee','honor','kontrak']],
            ['name' => 'Investasi Masuk',   'slug' => 'investasi-masuk',  'type' => 'income', 'icon' => 'trending-up', 'color' => '#14b8a6', 'ai_keywords' => ['dividen','bunga','return','profit','imbal']],
            ['name' => 'Hadiah',            'slug' => 'hadiah',           'type' => 'income', 'icon' => 'gift',        'color' => '#a855f7', 'ai_keywords' => ['hadiah','kado','angpao','hibah','dikasih','diberi']],
            ['name' => 'Pendapatan Lain',   'slug' => 'pendapatan-lain',  'type' => 'income', 'icon' => 'plus-circle', 'color' => '#6b7280', 'ai_keywords' => ['masuk','terima','dapat']],

            // TRANSFER
            ['name' => 'Transfer',          'slug' => 'transfer',         'type' => 'transfer', 'icon' => 'arrow-left-right', 'color' => '#3b82f6', 'ai_keywords' => ['transfer','pindah','kirim','tarik tunai','wd']],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => $cat['slug'], 'user_id' => null],
                array_merge($cat, ['is_system' => true, 'is_active' => true])
            );
        }
    }
}
