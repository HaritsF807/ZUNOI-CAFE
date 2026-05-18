<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    protected $token;

    public function __construct()
    {
        $this->token = env('FONNTE_TOKEN', 'mock-token');
    }

    public function sendMessage($target, $message)
    {
        // Jika token belum di-set, kita log saja (Mockup)
        if ($this->token === 'mock-token' || env('APP_ENV') === 'local') {
            Log::info("Fonnte WA Mock: Send to $target => $message");
            return true;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Fonnte WA Error: " . $e->getMessage());
            return false;
        }
    }
}
