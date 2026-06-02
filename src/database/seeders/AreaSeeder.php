<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreaSeeder extends Seeder
{
    /**
     * Vietnamese administrative units seeder.
     *
     * Source: https://github.com/thanglequoc/vietnamese-provinces-database
     * Data file: database/data/vn_units.json (downloaded from GitHub)
     * Data updated: 02 Sep 2025 (includes Huế as municipality)
     *
     * Mapped to m_areas:
     * - Level 1: Provinces/Cities (parent_id = null)
     * - Level 2: Wards/Communes (parent_id = province)
     */
    public function run(): void
    {
        $filePath = database_path('data/vn_units.json');

        if (! file_exists($filePath)) {
            $this->command->error("Data file not found: {$filePath}");
            $this->command->error('Please download from: https://github.com/thanglequoc/vietnamese-provinces-database');

            return;
        }

        $this->command->info('🇻🇳 Seeding Vietnamese administrative units from local JSON...');

        $provinces = json_decode(file_get_contents($filePath), true);

        if (empty($provinces)) {
            $this->command->error('Failed to parse JSON file. Aborting.');

            return;
        }

        // Disable FK checks for truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('m_areas')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $now = now();
        $provinceSortOrder = 1;
        $totalWards = 0;

        foreach ($provinces as $province) {
            // Level 1: Province/City
            $areaProvince = Area::create([
                'code' => $province['Code'],
                'name' => $province['FullName'] ?? $province['Name'],
                'parent_id' => null,
                'level' => 1,
                'status' => 'active',
                'sort_order' => $provinceSortOrder++,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Level 2: Wards/Communes
            if (! empty($province['Wards'])) {
                $wardSortOrder = 1;
                $wardBatch = [];

                foreach ($province['Wards'] as $ward) {
                    $wardBatch[] = [
                        'code' => $ward['Code'],
                        'name' => $ward['FullName'] ?? $ward['Name'],
                        'parent_id' => $areaProvince->id,
                        'level' => 2,
                        'status' => 'active',
                        'sort_order' => $wardSortOrder++,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // Batch insert every 500 records
                    if (count($wardBatch) >= 500) {
                        DB::table('m_areas')->insert($wardBatch);
                        $totalWards += count($wardBatch);
                        $wardBatch = [];
                    }
                }

                if (! empty($wardBatch)) {
                    DB::table('m_areas')->insert($wardBatch);
                    $totalWards += count($wardBatch);
                }
            }
        }

        $totalProvinces = $provinceSortOrder - 1;
        $this->command->info("✅ Seeded {$totalProvinces} provinces + {$totalWards} wards/communes.");
    }
}
