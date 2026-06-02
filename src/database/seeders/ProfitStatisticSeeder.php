<?php

namespace Database\Seeders;

use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Payment\PaymentColumn;
use App\Constants\Master\Models\Payment\PaymentMethodConst;
use App\Constants\Master\Models\Payment\PaymentStatusConst;
use App\Constants\Master\Models\ServiceCategory\ServiceCategoryLevelConst;
use App\Constants\Master\Models\User\UserRoleConst;
use App\Models\Job;
use App\Models\Payment;
use App\Models\ServiceCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfitStatisticSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = User::where('role', UserRoleConst::CUSTOMER)->pluck('id')->toArray();
        $workers = User::where('role', UserRoleConst::WORKER)->pluck('id')->toArray();
        $subCategories = ServiceCategory::where('level', ServiceCategoryLevelConst::SUB)->pluck('id')->toArray();
        $areas = \App\Models\Area::pluck('id')->toArray();

        if (empty($customers) || empty($workers) || empty($subCategories) || empty($areas)) {
            $this->command->warn('⚠️ Missing users, areas or service categories. Run AreaSeeder, UserTableSeeder, CustomerSeeder, WorkerSeeder and ServiceCategorySeeder first.');

            return;
        }

        $this->command->info('Seeding Profit Statistics data...');

        // Clear existing payments and related jobs to avoid duplication issues during testing
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Payment::query()->forceDelete();
        // Optionally delete jobs created by this seeder or all completed/refunded jobs
        // For demo purposes, we'll keep it simple and just create new ones.
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $totalJobs = 1500;
        $count = 0;

        for ($i = 0; $i < $totalJobs; $i++) {
            // Generate a random date within the last 90 days
            $paidAt = Carbon::now()->subDays(rand(0, 90))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

            $customerId = $customers[array_rand($customers)];
            $workerId = $workers[array_rand($workers)];
            $serviceId = $subCategories[array_rand($subCategories)];
            $areaId = $areas[array_rand($areas)];

            // Random price between 200k and 5M
            $amount = rand(20, 500) * 10000;
            $platformFeeRate = 0.1; // 10% platform fee
            $platformFee = (int) ($amount * $platformFeeRate);
            $workerEarning = $amount - $platformFee;

            // Create Job
            $job = Job::create([
                'code' => 'JOB'.$paidAt->format('ymdHi').rand(1000, 9999),
                'customer_id' => $customerId,
                'worker_id' => $workerId,
                'service_id' => $serviceId,
                'area_id' => $areaId,
                'description' => 'Mô tả công việc mẫu cho thống kê lợi nhuận #'.($i + 1),
                'address' => 'Địa chỉ mẫu '.rand(1, 100),
                'latitude' => 10.762622,
                'longitude' => 106.660172,
                'status' => JobStatusConst::COMPLETED,
                'scheduled_date' => $paidAt->format('Y-m-d'),
                'time_slot' => '08:00-10:00',
                'total_amount' => $amount,
                'platform_fee' => $platformFee,
                'quotation_price' => $workerEarning,
                'completed_at' => $paidAt,
                'confirmed_at' => $paidAt,
                'created_at' => $paidAt->copy()->subDays(2),
                'updated_at' => $paidAt,
            ]);

            // Create Payment
            $status = rand(1, 10) > 1 ? PaymentStatusConst::PAID : PaymentStatusConst::REFUNDED;

            $paymentData = [
                PaymentColumn::CODE => 'PAY'.$paidAt->format('YmdHis').rand(100, 999),
                PaymentColumn::JOB_ID => $job->id,
                PaymentColumn::AMOUNT => $amount,
                PaymentColumn::PLATFORM_FEE => $platformFee,
                PaymentColumn::WORKER_EARNING => $workerEarning,
                PaymentColumn::PAYMENT_METHOD => PaymentMethodConst::BANK_TRANSFER,
                PaymentColumn::STATUS => $status,
                PaymentColumn::PAID_AT => $paidAt,
                'created_at' => $paidAt,
                'updated_at' => $paidAt,
            ];

            if ($status === PaymentStatusConst::REFUNDED) {
                $refundedAt = $paidAt->copy()->addDays(rand(1, 3));
                $paymentData[PaymentColumn::REFUNDED_AT] = $refundedAt;
                $paymentData[PaymentColumn::REFUNDED_AMOUNT] = $platformFee; // Refund the platform fee part or more

                // Update job status to REFUNDED
                $job->update(['status' => JobStatusConst::REFUNDED, 'updated_at' => $refundedAt]);
            }

            Payment::create($paymentData);
            $count++;
        }

        $this->command->info("✅ Successfully seeded {$count} payments and jobs for statistics.");
    }
}
