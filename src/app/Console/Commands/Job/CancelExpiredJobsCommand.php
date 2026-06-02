<?php

namespace App\Console\Commands\Job;

use App\Services\User\JobService;
use Illuminate\Console\Command;

class CancelExpiredJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job:cancel-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically cancel jobs that have been pending for more than 30 minutes past their scheduled time';

    /**
     * Create a new command instance.
     */
    public function __construct(
        protected JobService $jobService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting checking for expired jobs...');

        $count = $this->jobService->cancelExpiredJobs();

        $this->info("Successfully cancelled {$count} expired jobs.");
    }
}
