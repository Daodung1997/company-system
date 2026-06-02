<?php

namespace App\Console\Commands;

use App\Services\Firebase\FCMService;
use App\Repositories\User\UserDeviceRepository;
use Illuminate\Console\Command;

class TestFcmCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:test {--token= : The FCM token to send to} {--userId= : Send to all registered devices of this user ID} {--title=Test Push Notification} {--body=This is a test notification from Antigravity Backend}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test Firebase Push Notification to verify credentials and functionality';

    /**
     * Create a new command instance.
     */
    public function __construct(
        protected FCMService $fcmService,
        protected UserDeviceRepository $userDeviceRepository
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = $this->option('token');
        $userId = $this->option('userId');
        $title = $this->option('title');
        $body = $this->option('body');

        $this->info("Checking FCM Configuration...");
        
        $reflector = new \ReflectionClass($this->fcmService);
        $property = $reflector->getProperty('messaging');
        $property->setAccessible(true);
        $messaging = $property->getValue($this->fcmService);

        if (!$messaging) {
            $this->error("FCM Service failed to initialize! Check credentials path in config/services.php or .env (FIREBASE_CREDENTIALS).");
            return 1;
        }

        $this->info("FCM Service initialized successfully.");

        $tokens = [];

        if ($token) {
            $tokens[] = $token;
            $this->info("Target: Sending to specified token.");
        } elseif ($userId) {
            $tokens = $this->userDeviceRepository->getTokensByUserId((int) $userId);
            $this->info("Target: User ID {$userId} has " . count($tokens) . " registered tokens.");
        } else {
            // Find any user device with a token to test
            $latestDevice = \App\Models\UserDevice::whereNotNull('fcm_token')->orderBy('id', 'desc')->first();
            if ($latestDevice) {
                $tokens[] = $latestDevice->fcm_token;
                $this->info("Target: No token/userId specified. Auto-selected latest registered device in DB (User ID: {$latestDevice->user_id}, Device: {$latestDevice->device_name}).");
            } else {
                $this->error("No registered devices found in database! Please specify a '--token' option to test.");
                return 1;
            }
        }

        if (empty($tokens)) {
            $this->error("No FCM tokens available to send notification.");
            return 1;
        }

        $this->info("Sending push notification...");
        $this->info("Title: {$title}");
        $this->info("Body:  {$body}");

        $dataPayload = [
            'test_mode' => 'true',
            'sent_at' => now()->toIso8601String(),
            'type' => 'test'
        ];

        if (count($tokens) === 1) {
            $this->info("Sending single push to token: " . substr($tokens[0], 0, 15) . "...");
            try {
                $this->fcmService->sendToToken($tokens[0], $title, $body, $dataPayload);
                $this->info("Single push call executed. Check your device!");
            } catch (\Throwable $e) {
                $this->error("FCM Single Push Exception: " . $e->getMessage());
            }
        } else {
            $this->info("Sending multicast push to " . count($tokens) . " tokens...");
            try {
                $this->fcmService->sendMulticast($tokens, $title, $body, $dataPayload);
                $this->info("Multicast push calls executed. Check your devices!");
            } catch (\Throwable $e) {
                $this->error("FCM Multicast Push Exception: " . $e->getMessage());
            }
        }

        $this->info("FCM Test Command Completed.");
        return 0;
    }
}
