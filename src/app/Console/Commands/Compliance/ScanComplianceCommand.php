<?php

namespace App\Console\Commands\Compliance;

use Illuminate\Console\Command;

use App\Services\Compliance\ComplianceService;
use Illuminate\Support\Facades\Log;

class ScanComplianceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compliance:scan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chạy quét tuân thủ pháp lý thời gian thực cho toàn bộ các doanh nghiệp.';

    /**
     * Execute the console command.
     */
    public function handle(ComplianceService $complianceService)
    {
        $this->info('Bắt đầu tiến trình quét tuân thủ pháp lý...');
        Log::info('Artisan Command compliance:scan started.');

        try {
            $results = $complianceService->runScan();
            
            $this->line("  - Quét Visa: {$results['visa_scanned']} nhân viên -> tạo {$results['visa_issues_created']} cảnh báo.");
            $this->line("  - Quét Hợp đồng: {$results['contract_scanned']} active -> tạo {$results['contract_issues_created']} cảnh báo.");
            $this->line("  - Quét Hóa đơn: {$results['invoice_scanned']} expense -> tạo {$results['invoice_issues_created']} cảnh báo.");
            $this->line("  - Quét Tăng ca: {$results['ot_scanned']} nhân viên -> tạo {$results['ot_issues_created']} cảnh báo.");
            $this->info("  -> Tự động khắc phục (Self-heal): {$results['resolved_count']} cảnh báo.");
        } catch (\Throwable $e) {
            $this->error("Lỗi khi quét tuân thủ: " . $e->getMessage());
            Log::error("Error scanning compliance: " . $e->getMessage());
        }

        $this->info('Đã hoàn thành tiến trình quét tuân thủ thành công!');
        Log::info('Artisan Command compliance:scan completed.');
    }
}
