<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    protected $token;

    public function __construct()
    {
        $this->token = Setting::getValue('fonnte_token', env('FONNTE_TOKEN', 'mock-token'));
    }

    public function sendMessage($target, $message)
    {
        // Jika token adalah default mockup atau kosong, kita log saja (Mockup)
        if ($this->token === 'mock-token' || $this->token === 'TokenFonnteAnda123' || empty($this->token)) {
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
            Log::error('Fonnte WA Error: '.$e->getMessage());

            return false;
        }
    }
}
