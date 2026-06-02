<?php

namespace Database\Seeders;

use App\Constants\Master\Models\User\UserRoleConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\Area;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = Area::where('level', 2)->inRandomOrder()->take(5)->pluck('id')->toArray();

        // ── Demo Customer (fixed credentials) ──
        $this->createDemoCustomer($areas);

        // ── Random Customers ──
        $this->createRandomCustomers($areas);
    }

    private function createDemoCustomer(array $areaIds): void
    {
        $customer = User::firstOrCreate(
            ['email' => 'customer@viecvat.com'],
            [
                'name' => 'Demo Customer',
                'password' => Hash::make('password'),
                'role' => UserRoleConst::CUSTOMER,
                'status' => UserStatusConst::ACTIVE,
            ]
        );

        if (! $customer->customerProfile) {
            CustomerProfile::create([
                'user_id' => $customer->id,
                'phone' => '0901234567',
                'area_id' => $areaIds[0] ?? null,
                'gender' => 0,
                'birthday' => '1990-05-20',
            ]);
        }

        $this->command->info('✅ Demo Customer created: customer@viecvat.com / password');
    }

    private function createRandomCustomers(array $areaIds): void
    {
        $names = [
            'Nguyễn Thị Mai',
            'Trần Quốc Tuấn',
            'Lê Hoàng Yến',
            'Phạm Minh Châu',
            'Võ Thanh Tùng',
        ];

        foreach ($names as $index => $name) {
            $email = 'customer'.($index + 1).'@viecvat.com';

            $customer = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => UserRoleConst::CUSTOMER,
                    'status' => UserStatusConst::ACTIVE,
                ]
            );

            if (! $customer->customerProfile) {
                CustomerProfile::create([
                    'user_id' => $customer->id,
                    'phone' => '09'.rand(10000000, 99999999),
                    'area_id' => $areaIds[$index % count($areaIds)] ?? null,
                    'gender' => rand(0, 1),
                    'birthday' => now()->subYears(rand(20, 50))->format('Y-m-d'),
                ]);
            }
        }

        $this->command->info('✅ Created '.count($names).' random customers with profiles.');
    }
}
