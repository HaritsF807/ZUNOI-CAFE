<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Services\FonnteService;

class TokopayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Contoh implementasi sederhana webhook Tokopay
        $signature = $request->header('Signature'); // sesuaikan dengan signature Tokopay
        
        // Logika verifikasi signature diabaikan sementara untuk mockup
        
        $order_id = $request->input('ref_id'); // ID order sistem kita
        $status = $request->input('status'); // Status pembayaran dari tokopay

        Log::info("Tokopay Webhook received for Order ID: $order_id with status: $status");

        if ($status === 'Success') {
            $order = Order::find($order_id);
            if ($order && $order->payment_status !== 'paid') {
                $order->payment_status = 'paid';
                $order->save();

                // Trigger WA via Fonnte
                $fonnte = new FonnteService();
                $message = "Halo {$order->customer_name}, Pembayaran pesanan Anda sebesar Rp " . number_format($order->total_price, 0, ',', '.') . " telah berhasil diterima! Pesanan Anda segera diproses.";
                $fonnte->sendMessage($order->customer_phone, $message);
            }
        }

        return response()->json(['success' => true]);
    }
}
