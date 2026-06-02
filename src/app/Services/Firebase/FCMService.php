<?php

namespace App\Services\Firebase;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FCMService
{
    protected $messaging;

    public function __construct()
    {
        $credentialsPath = config('services.firebase.credentials');

        if ($credentialsPath && file_exists($credentialsPath)) {
            try {
                $factory = (new Factory)->withServiceAccount($credentialsPath);
                $this->messaging = $factory->createMessaging();
            } catch (\Throwable $e) {
                Log::error('Firebase Init Error: '.$e->getMessage());
                $this->messaging = null;
            }
        } else {
            Log::warning('Firebase credentials not found at: '.$credentialsPath);
            $this->messaging = null;
        }
    }

    /**
     * Send Multicast Message to multiple tokens
     */
    public function sendMulticast(array $tokens, string $title, string $body, array $data = [])
    {
        if (! $this->messaging || empty($tokens)) {
            return;
        }

        $successCount = 0;
        $failureCount = 0;

        $notification = Notification::create($title, $body);

        foreach ($tokens as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification)
                    ->withData($data);

                $this->messaging->send($message);
                $successCount++;
            } catch (\Throwable $e) {
                Log::error('FCM Send Multicast Token Error: '.$e->getMessage(), [
                    'token' => $token,
                    'title' => $title,
                ]);
                $failureCount++;
            }
        }

        Log::info("FCM Multicast Sent: Success={$successCount} Failures={$failureCount}");
    }

    /**
     * Send Message to single token
     */
    public function sendToToken(string $token, string $title, string $body, array $data = [])
    {
        if (! $this->messaging || empty($token)) {
            return;
        }

        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);

        } catch (\Throwable $e) {
            Log::error('FCM Send Single Error: '.$e->getMessage());
        }
    }
}
