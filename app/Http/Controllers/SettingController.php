<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Services\FonnteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SettingController extends Controller
{
    /**
     * Display the integration setup page with current settings.
     */
    public function integrationIndex()
    {
        $settings = [
            'qris_manual_url' => Setting::getValue('qris_manual_url', 'https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg'),
            'fonnte_token' => Setting::getValue('fonnte_token', 'TokenFonnteAnda123'),
            'tokopay_merchant_id' => Setting::getValue('tokopay_merchant_id', 'M-123456'),
            'tokopay_secret' => Setting::getValue('tokopay_secret', 'SecretKey...'),
            'owner_whatsapp' => Setting::getValue('owner_whatsapp', ''),
        ];

        return Inertia::render('IntegrationSetup', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the integration settings.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'qris_manual_url' => 'required|url|max:1000',
            'fonnte_token' => 'required|string|max:255',
            'tokopay_merchant_id' => 'required|string|max:255',
            'tokopay_secret' => 'required|string|max:255',
            'owner_whatsapp' => 'nullable|string|max:20',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
        }

        return redirect()->back()->with('success', 'Pengaturan integrasi berhasil diperbarui!');
    }

    /**
     * Kirim rekapan harian ke WhatsApp Owner.
     */
    public function sendRecap(Request $request)
    {
        $ownerWhatsapp = Setting::getValue('owner_whatsapp');
        $fonnteToken = Setting::getValue('fonnte_token');

        if (empty($ownerWhatsapp)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp Owner belum dikonfigurasi di Pengaturan Integrasi!',
            ], 422);
        }

        if (empty($fonnteToken) || $fonnteToken === 'TokenFonnteAnda123') {
            return response()->json([
                'success' => false,
                'message' => 'Token API Fonnte belum dikonfigurasi atau masih menggunakan nilai default!',
            ], 422);
        }

        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            if ($startDate && $endDate) {
                $startDateStr = Carbon::parse($startDate)->toDateString();
                $endDateStr = Carbon::parse($endDate)->toDateString();
            } else {
                $startDateStr = Carbon::now('Asia/Jakarta')->toDateString();
                $endDateStr = $startDateStr;
            }

            $isSingleDay = ($startDateStr === $endDateStr);
            $dateFormatted = $isSingleDay
                ? Carbon::parse($startDateStr)->translatedFormat('d F Y')
                : Carbon::parse($startDateStr)->translatedFormat('d F Y').' s/d '.Carbon::parse($endDateStr)->translatedFormat('d F Y');

            $totalOrders = Order::whereDate('created_at', '>=', $startDateStr)
                ->whereDate('created_at', '<=', $endDateStr)
                ->count();

            $paidOrders = Order::whereDate('created_at', '>=', $startDateStr)
                ->whereDate('created_at', '<=', $endDateStr)
                ->where('payment_status', 'paid')
                ->count();

            $totalSales = Order::whereDate('created_at', '>=', $startDateStr)
                ->whereDate('created_at', '<=', $endDateStr)
                ->where('payment_status', 'paid')
                ->sum('total_price');

            $titleRecap = $isSingleDay ? 'REKAP PENJUALAN HARIAN' : 'REKAP PENJUALAN PERIODE';
            $messageIntro = 'Berikut adalah rincian rekapan transaksi penjualan:';

            $message = "📊 *LAPORAN {$titleRecap}* 📊\n".
                       "☕ *ZUNOI CAFFE* ☕\n\n".
                       "{$messageIntro}\n\n".
                       '📅 *'.($isSingleDay ? 'Tanggal' : 'Periode').':* '.$dateFormatted."\n".
                       "━━━━━━━━━━━━━━━━━━\n".
                       "📈 *RINGKASAN PERFORMA:*\n".
                       "• Total Transaksi: {$totalOrders} pesanan\n".
                       "• Transaksi Sukses/Lunas: {$paidOrders} pesanan\n".
                       '• *Total Omset/Pendapatan:* Rp '.number_format($totalSales, 0, ',', '.')."\n".
                       "━━━━━━━━━━━━━━━━━━\n".
                       "💳 *METODE PEMBAYARAN (Lunas):*\n";

            $payments = Order::whereDate('created_at', '>=', $startDateStr)
                ->whereDate('created_at', '<=', $endDateStr)
                ->where('payment_status', 'paid')
                ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(total_price) as total'))
                ->groupBy('payment_method')
                ->get();

            if ($payments->isEmpty()) {
                $message .= "Belum ada transaksi lunas pada periode ini.\n";
            } else {
                foreach ($payments as $pay) {
                    $methodName = $pay->payment_method === 'cashier' ? 'Kasir/Tunai' : ($pay->payment_method === 'qris_manual' ? 'QRIS Manual' : 'QRIS Otomatis');
                    $message .= "• {$methodName}: {$pay->count}x (Rp ".number_format($pay->total, 0, ',', '.').")\n";
                }
            }

            $message .= "━━━━━━━━━━━━━━━━━━\n".
                        "🛋️ *TIPE LAYANAN (Lunas):*\n";

            $types = Order::whereDate('created_at', '>=', $startDateStr)
                ->whereDate('created_at', '<=', $endDateStr)
                ->where('payment_status', 'paid')
                ->select('order_type', DB::raw('count(*) as count'))
                ->groupBy('order_type')
                ->get();

            if ($types->isEmpty()) {
                $message .= "Belum ada transaksi lunas pada periode ini.\n";
            } else {
                foreach ($types as $t) {
                    $typeName = $t->order_type === 'dine_in' ? 'Dine In (Makan di tempat)' : 'Takeaway';
                    $message .= "• {$typeName}: {$t->count}x\n";
                }
            }

            $message .= "━━━━━━━━━━━━━━━━━━\n".
                        '🏆 *5 MENU TERLARIS'.($isSingleDay ? ' HARI INI' : ' PERIODE INI').":*\n";

            $topItems = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_qty'))
                ->whereHas('order', function ($query) use ($startDateStr, $endDateStr) {
                    $query->whereDate('created_at', '>=', $startDateStr)
                        ->whereDate('created_at', '<=', $endDateStr)
                        ->where('payment_status', 'paid');
                })
                ->with('product')
                ->groupBy('product_id')
                ->orderBy('total_qty', 'desc')
                ->limit(5)
                ->get();

            if ($topItems->isEmpty()) {
                $message .= "Belum ada menu yang terjual pada periode ini.\n";
            } else {
                $rank = 1;
                foreach ($topItems as $item) {
                    $prodName = $item->product->name ?? 'Menu Kopi';
                    $message .= "{$rank}. {$prodName} (Terjual {$item->total_qty}x)\n";
                    $rank++;
                }
            }

            $message .= "━━━━━━━━━━━━━━━━━━\n\n".
                        'Laporan ini dibuat otomatis oleh Sistem ZUNOI-CAFFE. Tetap semangat, semoga hari esok mendatangkan lebih banyak berkah dan pelanggan! ☕💛🌱';

            $fonnte = new FonnteService;
            $fonnte->sendMessage($ownerWhatsapp, $message);

            return response()->json([
                'success' => true,
                'message' => 'Rekapan berhasil dikirim ke WhatsApp Owner!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim rekapan: '.$e->getMessage(),
            ], 500);
        }
    }
}
