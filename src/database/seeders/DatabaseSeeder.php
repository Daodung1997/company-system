<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->call(RoleTableSeeder::class); // Re-enable if using m_roles
        // $this->call(UserPermissionTableSeeder::class); // Re-enable if using m_permissions
        $this->call([
            CompanyEmployeeSeeder::class,
            TimesheetDemoSeeder::class,
            ContractSeeder::class,
            TransactionSeeder::class,
            CompanySettingSeeder::class,
        ]);
    }
}
