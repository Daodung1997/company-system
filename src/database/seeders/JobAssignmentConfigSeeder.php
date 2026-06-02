<?php

namespace Database\Seeders;

use App\Models\Configuration;
use App\Services\Admin\ConfigurationService;
use Illuminate\Database\Seeder;

class JobAssignmentConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $defaultConfig = [
            'scan_radius' => 10,
            'max_workers_per_job' => 5,
            'rating_weight' => 0.5,
            'distance_weight' => 0.3,
            'response_rate_weight' => 0.2,
        ];

        Configuration::firstOrCreate(
            ['key' => ConfigurationService::KEY_JOB_ASSIGNMENT_CONFIG],
            ['value' => json_encode($defaultConfig)]
        );
    }
}
