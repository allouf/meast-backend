<?php

namespace App\Library;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class Twilio
{
    protected Client $client;
    protected string $from;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        $this->from = config('services.twilio.from');
    }

    /**
     * Send SMS message
     */
    public function send(string $to, string $message): bool
    {
        try {
            $this->client->messages->create($to, [
                'from' => $this->from,
                'body' => $message,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send SMS to {$to}: {$e->getMessage()}");
            return false;
        }
    }
}
