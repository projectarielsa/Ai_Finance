<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: modify ENUM to include 'cancelled'
        DB::statement("ALTER TABLE receipt_scans MODIFY COLUMN status ENUM('pending','processed','failed','confirmed','cancelled') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE receipt_scans MODIFY COLUMN status ENUM('pending','processed','failed','confirmed') DEFAULT 'pending'");
    }
};
