<?php

namespace App\Jobs;

use App\Services\FonnteService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendFonnteMessageJob implements ShouldQueue
{
    use Queueable;

    protected $phone;
    protected $message;

    /**
     * Create a new job instance.
     */
    public function __construct($phone, $message)
    {
        $this->phone = $phone;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $fonnte = new FonnteService();
            $fonnte->sendMessage($this->phone, $this->message);
        } catch (\Exception $e) {
            Log::error('Job Fonnte Gagal: ' . $e->getMessage());
        }
    }
}
