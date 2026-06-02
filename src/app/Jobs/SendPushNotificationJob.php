<?php

namespace App\Jobs;

use App\Services\Firebase\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected array $tokens,
        protected string $title,
        protected string $body,
        protected array $data = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FCMService $fcmService): void
    {
        if (empty($this->tokens)) {
            return;
        }

        Log::info('Processing SendPushNotificationJob for '.count($this->tokens).' tokens.', [
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ]);

        $fcmService->sendMulticast($this->tokens, $this->title, $this->body, $this->data);
    }
}
