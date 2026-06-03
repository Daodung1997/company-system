<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('working_hour_configs')->insert([
            [
                'name' => 'Hành chính Mặc định',
                'start_time' => '08:30:00',
                'end_time' => '17:30:00',
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Giai đoạn Kiểm toán Đầu năm',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Giai đoạn Kiểm toán Giữa năm',
                'start_time' => '08:30:00',
                'end_time' => '16:30:00',
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    public function down(): void
    {
        DB::table('working_hour_configs')->truncate();
    }
};
