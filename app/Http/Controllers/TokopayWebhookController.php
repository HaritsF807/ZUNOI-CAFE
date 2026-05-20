<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            $order = Order::with('items.product')->find($order_id);
            if ($order && $order->payment_status !== 'paid') {
                $order->payment_status = 'paid';
                $order->save();

                // Trigger WA via Fonnte
                try {
                    $itemList = '';
                    foreach ($order->items as $item) {
                        $itemList .= '• '.($item->product->name ?? 'Menu Kopi').' x'.$item->quantity."\n";
                        if (! empty($item->notes)) {
                            $itemList .= '   └ *Catatan:* "'.$item->notes."\"\n";
                        }
                    }
                    $itemList = trim($itemList);
                    $typeName = $order->order_type === 'dine_in' ? 'Dine In (Makan di Tempat)' : 'Takeaway (Bawa Pulang)';
                    $timeFormatted = $order->created_at->timezone('Asia/Jakarta')->format('H:i');

                    $pricingBreakdown = '';
                    $hasDiscount = ((float)$order->discount_amount > 0 || (float)$order->promo_discount_amount > 0);
                    if ($hasDiscount) {
                        $subtotal = (float)$order->total_price + (float)$order->discount_amount + (float)$order->promo_discount_amount;
                        $pricingBreakdown .= "💵 *Subtotal:* Rp " . number_format($subtotal, 0, ',', '.') . "\n";
                        if ((float)$order->discount_amount > 0) {
                            $pricingBreakdown .= "🎟️ *Voucher (" . ($order->voucher_code ?: 'Promo') . "):* -Rp " . number_format($order->discount_amount, 0, ',', '.') . "\n";
                        }
                        if ((float)$order->promo_discount_amount > 0) {
                            $pricingBreakdown .= "🏷️ *Potongan Promo Otomatis:* -Rp " . number_format($order->promo_discount_amount, 0, ',', '.') . "\n";
                        }
                    }
                    $pricingBreakdown .= '💰 *Total Tagihan:* Rp ' . number_format($order->total_price, 0, ',', '.');

                    $message = "☕ *ZUNOI CAFFE - PEMBAYARAN SUKSES* ☕\n\n".
                               "Halo *{$order->customer_name}*, terima kasih! Pembayaran Anda telah kami terima secara otomatis.\n\n".
                               "*Rincian Transaksi:*\n".
                               "━━━━━━━━━━━━━━━━━━\n".
                               "🆔 *ID Pesanan:* #{$order->id}\n".
                               "📅 *Waktu:* {$timeFormatted} WIB\n".
                               "🛋️ *Tipe:* {$typeName}\n".
                               "💳 *Metode:* QRIS Otomatis (Tokopay)\n".
                               $pricingBreakdown."\n".
                               "💵 *Status:* LUNAS\n\n".
                               "*Daftar Menu:*\n".
                               "{$itemList}\n";

                    if (! empty($order->notes)) {
                        $message .= "\n📝 *Catatan Khusus Barista:*\n\"{$order->notes}\"\n";
                    }

                    $message .= "━━━━━━━━━━━━━━━━━━\n\n".
                                '🧾 *Struk/Invoice Online:* '.url("/order/success/{$order->secure_key}")."\n\n".
                                'Pesanan Anda telah diteruskan ke Barista kami dan sedang dalam antrean pengerjaan. Kami akan mengirimkan notifikasi lagi begitu pesanan Anda mulai diproses. Selamat menunggu! 💛';

                    $fonnte = new FonnteService;
                    $fonnte->sendMessage($order->customer_phone, $message);
                } catch (\Exception $e) {
                    Log::error('Fonnte Webhook WA Error: '.$e->getMessage());
                }
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Simulasi lokal pembayaran Tokopay QRIS sukses.
     */
    public function simulateLocalPayment($id)
    {
        if (config('app.env') !== 'local' && config('app.env') !== 'testing') {
            abort(403, 'Hanya dapat dijalankan di lingkungan lokal/testing.');
        }

        $order = Order::with('items.product')->find($id);
        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
        }

        if ($order->payment_status !== 'paid') {
            $order->payment_status = 'paid';
            $order->save();

            // Trigger WA via Fonnte
            try {
                $itemList = '';
                foreach ($order->items as $item) {
                    $itemList .= '• '.($item->product->name ?? 'Menu Kopi').' x'.$item->quantity."\n";
                    if (! empty($item->notes)) {
                        $itemList .= '   └ *Catatan:* "'.$item->notes."\"\n";
                    }
                }
                $itemList = trim($itemList);
                $typeName = $order->order_type === 'dine_in' ? 'Dine In (Makan di Tempat)' : 'Takeaway (Bawa Pulang)';
                $timeFormatted = $order->created_at->timezone('Asia/Jakarta')->format('H:i');

                $pricingBreakdown = '';
                $hasDiscount = ((float)$order->discount_amount > 0 || (float)$order->promo_discount_amount > 0);
                if ($hasDiscount) {
                    $subtotal = (float)$order->total_price + (float)$order->discount_amount + (float)$order->promo_discount_amount;
                    $pricingBreakdown .= "💵 *Subtotal:* Rp " . number_format($subtotal, 0, ',', '.') . "\n";
                    if ((float)$order->discount_amount > 0) {
                        $pricingBreakdown .= "🎟️ *Voucher (" . ($order->voucher_code ?: 'Promo') . "):* -Rp " . number_format($order->discount_amount, 0, ',', '.') . "\n";
                    }
                    if ((float)$order->promo_discount_amount > 0) {
                        $pricingBreakdown .= "🏷️ *Potongan Promo Otomatis:* -Rp " . number_format($order->promo_discount_amount, 0, ',', '.') . "\n";
                    }
                }
                $pricingBreakdown .= '💰 *Total Tagihan:* Rp ' . number_format($order->total_price, 0, ',', '.');

                $message = "☕ *ZUNOI CAFFE - PEMBAYARAN SUKSES [SIMULASI]* ☕\n\n".
                           "Halo *{$order->customer_name}*, terima kasih! [SIMULASI] Pembayaran Anda telah kami terima secara otomatis.\n\n".
                           "*Rincian Transaksi:*\n".
                           "━━━━━━━━━━━━━━━━━━\n".
                           "🆔 *ID Pesanan:* #{$order->id}\n".
                           "📅 *Waktu:* {$timeFormatted} WIB\n".
                           "🛋️ *Tipe:* {$typeName}\n".
                           "💳 *Metode:* QRIS Otomatis (Tokopay)\n".
                           $pricingBreakdown."\n".
                           "💵 *Status:* LUNAS\n\n".
                           "*Daftar Menu:*\n".
                           "{$itemList}\n";

                if (! empty($order->notes)) {
                    $message .= "\n📝 *Catatan Khusus Barista:*\n\"{$order->notes}\"\n";
                }

                $message .= "━━━━━━━━━━━━━━━━━━\n\n".
                            '🧾 *Struk/Invoice Online:* '.url("/order/success/{$order->secure_key}")."\n\n".
                            'Pesanan Anda telah diteruskan ke Barista kami dan sedang dalam antrean pengerjaan. Kami akan mengirimkan notifikasi lagi begitu pesanan Anda mulai diproses. Selamat menunggu! 💛';

                $fonnte = new FonnteService;
                $fonnte->sendMessage($order->customer_phone, $message);
            } catch (\Exception $e) {
                // Abaikan error WA saat pengujian lokal
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Simulasi Pembayaran Tokopay QRIS untuk Pesanan #{$id} Berhasil! Status diubah menjadi LUNAS.",
            'order' => $order,
        ]);
    }
}
