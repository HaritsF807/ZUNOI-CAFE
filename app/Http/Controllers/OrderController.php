<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    // Menerima pesanan dari Customer (Cart.vue)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'order_type' => 'required|in:dine_in,takeaway',
            'payment_method' => 'required|in:cashier,qris_tokopay,qris_manual',
            'cart_items' => 'required|array',
            'payment_proof' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'notes' => 'nullable|string',
        ]);

        if ($validated['payment_method'] === 'qris_manual') {
            $request->validate([
                'payment_proof' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);
        }

        $tableId = session('active_table_id');
        if (! $tableId) {
            return back()->with('error', 'Sesi meja tidak valid.');
        }

        // Hitung total harga
        $totalPrice = 0;
        foreach ($validated['cart_items'] as $item) {
            $totalPrice += ($item['price'] * $item['quantity']);
        }

        // Hitung potongan voucher di server untuk keamanan
        $discountAmount = 0;
        $voucherCode = $request->input('voucher_code');
        if (!empty($voucherCode)) {
            $voucher = \App\Models\Voucher::where('code', $voucherCode)->where('is_active', true)->first();
            if ($voucher && $totalPrice >= $voucher->min_purchase) {
                if ($voucher->discount_type === 'percentage') {
                    $discountAmount = ($voucher->discount_value / 100) * $totalPrice;
                } else {
                    $discountAmount = $voucher->discount_value;
                }

                if ($discountAmount > $totalPrice) {
                    $discountAmount = $totalPrice;
                }
            }
        }

        $finalPrice = $totalPrice - $discountAmount;

        // Simpan bukti pembayaran ke database sebagai Base64 jika ada
        $proofUrl = null;
        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $imageData = file_get_contents($file->getRealPath());
            $base64 = base64_encode($imageData);
            $proofUrl = 'data:'.$file->getMimeType().';base64,'.$base64;
        }

        // Buat Order Induk
        $order = Order::create([
            'table_id' => $tableId,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'total_price' => $finalPrice,
            'order_type' => $validated['order_type'],
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'payment_proof' => $proofUrl,
            'notes' => $validated['notes'] ?? null,
            'voucher_code' => $voucherCode,
            'discount_amount' => $discountAmount,
        ]);

        // Simpan Item Pesanan
        foreach ($validated['cart_items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['id'],
                'quantity' => $item['quantity'],
                'price_at_sale' => $item['price'],
                'notes' => $item['notes'] ?? null,
            ]);
        }

        // Set timestamp pesanan agar akses /order & /checkout kadaluarsa dalam 5 menit
        session(['order_placed_at' => time()]);

        return redirect()->route('order.success', ['secure_key' => $order->secure_key]);
    }

    // Endpoint API untuk Polling Dashboard Barista
    public function liveOrders()
    {
        $orders = Order::with(['table', 'items.product'])
            ->whereIn('order_status', ['pending', 'processing', 'completed'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'table' => $order->table->table_name ?? 'Takeaway',
                    'type' => $order->order_type === 'dine_in' ? 'Dine In' : 'Takeaway',
                    'name' => $order->customer_name,
                    'total' => (float) $order->total_price,
                    'status' => $order->order_status,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'payment_proof' => $order->payment_proof,
                    'notes' => $order->notes,
                    'time' => $order->created_at->format('H:i'),
                    'voucher_code' => $order->voucher_code,
                    'discount_amount' => (float) $order->discount_amount,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'name' => $item->product->name ?? 'Produk Terhapus',
                            'quantity' => $item->quantity,
                            'price' => (float) $item->price_at_sale,
                            'notes' => $item->notes,
                        ];
                    }),
                ];
            });

        return response()->json($orders);
    }

    // Memperbarui status pesanan dari dashboard barista
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'order_status' => 'required|in:pending,processing,completed,cancelled',
            'payment_status' => 'sometimes|in:pending,paid',
        ]);

        $order = Order::with('items.product')->findOrFail($id);

        // Jika status order diterima (processing) dan pembayaran via QRIS Manual, set juga status pembayaran menjadi paid (LUNAS)
        if ($validated['order_status'] === 'processing' && $order->payment_method === 'qris_manual') {
            $order->payment_status = 'paid';
        }

        // Jika status order selesai (completed), set juga status pembayaran menjadi lunas (paid) jika belum lunas
        if ($validated['order_status'] === 'completed') {
            $order->payment_status = 'paid';
        }

        $oldStatus = $order->order_status;
        $order->order_status = $validated['order_status'];
        if (isset($validated['payment_status'])) {
            $order->payment_status = $validated['payment_status'];
        }

        $order->save();

        // Kirim WhatsApp Notifikasi Perubahan Status via Fonnte
        try {
            $fonnte = new FonnteService;
            if ($order->order_status === 'processing' && $oldStatus !== 'processing') {
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

                if ($order->payment_method === 'qris_manual') {
                    $message = "☕ *ZUNOI CAFFE - PEMBAYARAN TERVERIFIKASI* ☕\n\n".
                               "Halo *{$order->customer_name}*, terima kasih! Bukti pembayaran QRIS Anda telah berhasil kami verifikasi.\n\n".
                               "*Rincian Transaksi:*\n".
                               "━━━━━━━━━━━━━━━━━━\n".
                               "🆔 *ID Pesanan:* #{$order->id}\n".
                               "📅 *Waktu:* {$timeFormatted} WIB\n".
                               "🛋️ *Tipe:* {$typeName}\n".
                               "💳 *Metode:* QRIS Manual (Toko)\n".
                               '💰 *Total Tagihan:* Rp '.number_format($order->total_price, 0, ',', '.')."\n".
                               "💵 *Status:* LUNAS (Terverifikasi)\n\n".
                               "*Daftar Pesanan:*\n".
                               "{$itemList}\n";

                    if (! empty($order->notes)) {
                        $message .= "\n📝 *Catatan Khusus Barista:*\n\"{$order->notes}\"\n";
                    }

                    $message .= "━━━━━━━━━━━━━━━━━━\n\n".
                                '🧾 *Struk/Invoice Online:* '.url("/order/success/{$order->secure_key}")."\n\n".
                                '*Pesanan Anda saat ini sedang DIPROSES oleh Barista Zunoi!* Silakan bersantai sejenak, kami akan mengabari Anda setelah pesanan siap disajikan. ☕💛';
                } else {
                    $payStatusText = $order->payment_status === 'paid' ? 'LUNAS' : 'BELUM BAYAR';
                    $methodText = $order->payment_method === 'cashier' ? 'Bayar di Kasir' : 'QRIS Otomatis (Tokopay)';
                    $message = "☕ *ZUNOI CAFFE - PESANAN DIPROSES* ☕\n\n".
                               "Halo *{$order->customer_name}*, pesanan Anda saat ini telah masuk antrean pengerjaan!\n\n".
                               "*Rincian Transaksi:*\n".
                               "━━━━━━━━━━━━━━━━━━\n".
                               "🆔 *ID Pesanan:* #{$order->id}\n".
                               "📅 *Waktu:* {$timeFormatted} WIB\n".
                               "🛋️ *Tipe:* {$typeName}\n".
                               "💳 *Metode:* {$methodText}\n".
                               '💰 *Total Tagihan:* Rp '.number_format($order->total_price, 0, ',', '.')."\n".
                               "💵 *Status Pembayaran:* {$payStatusText}\n\n".
                               "*Daftar Pesanan:*\n".
                               "{$itemList}\n";

                    if (! empty($order->notes)) {
                        $message .= "\n📝 *Catatan Khusus Barista:*\n\"{$order->notes}\"\n";
                    }

                    $message .= "━━━━━━━━━━━━━━━━━━\n\n".
                                '🧾 *Struk/Invoice Online:* '.url("/order/success/{$order->secure_key}")."\n\n".
                                '*Barista Zunoi sedang memproses pesanan Anda dengan penuh cinta!* Mohon tunggu sejenak, kami akan memberikan notifikasi setelah pesanan Anda selesai disiapkan. ☕💛';
                }
                $fonnte->sendMessage($order->customer_phone, $message);
            } elseif ($order->order_status === 'completed' && $oldStatus !== 'completed') {
                $itemList = '';
                foreach ($order->items as $item) {
                    $itemList .= '• '.($item->product->name ?? 'Menu Kopi').' x'.$item->quantity."\n";
                }
                $itemList = trim($itemList);
                $typeName = $order->order_type === 'dine_in' ? 'Dine In (Makan di Tempat)' : 'Takeaway (Bawa Pulang)';
                $timeFormatted = $order->created_at->timezone('Asia/Jakarta')->format('H:i');

                $deliveryInstruction = $order->order_type === 'dine_in'
                    ? '*Barista kami akan segera mengantarkan pesanan Anda langsung ke meja Anda. Silakan duduk manis dan bersiap menikmati!*'
                    : '*Silakan ambil pesanan Anda di meja Barista/Kasir Zunoi Caffe.*';

                $message = "☕ *ZUNOI CAFFE - PESANAN SELESAI* ☕\n\n".
                           "Halo *{$order->customer_name}*, kabar gembira! Pesanan Anda telah selesai disiapkan dan siap dinikmati!\n\n".
                           "*Rincian Transaksi:*\n".
                           "━━━━━━━━━━━━━━━━━━\n".
                           "🆔 *ID Pesanan:* #{$order->id}\n".
                           "📅 *Waktu:* {$timeFormatted} WIB\n".
                           "🛋️ *Tipe:* {$typeName}\n".
                           '💰 *Total Belanja:* Rp '.number_format($order->total_price, 0, ',', '.')."\n".
                           "💵 *Status:* LUNAS (Disajikan)\n\n".
                           "*Daftar Pesanan:*\n".
                           "{$itemList}\n".
                           "━━━━━━━━━━━━━━━━━━\n\n".
                           '🧾 *Struk/Invoice Online:* '.url("/order/success/{$order->secure_key}")."\n\n".
                           "{$deliveryInstruction}\n\n".
                           'Terima kasih banyak telah memesan di Zunoi Caffe. Semoga hari Anda menyenangkan dan penuh energi positif! ☕💛';

                $fonnte->sendMessage($order->customer_phone, $message);
            }
        } catch (\Exception $e) {
            Log::error('Gagal kirim WA update status: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diperbarui!',
            'order' => $order,
        ]);
    }

    // Halaman sukses sederhana
    public function success($secure_key)
    {
        $order = Order::with(['items.product', 'table'])->where('secure_key', $secure_key)->firstOrFail();

        return inertia('Customer/Success', ['order' => $order]);
    }

    // Halaman Kasir POS
    public function cashierIndex()
    {
        $products = \App\Models\Product::with(['category', 'addons'])->where('is_available', true)->get();
        $categories = \App\Models\Category::orderBy('name', 'asc')->get();
        $tables = \App\Models\Table::orderBy('table_name', 'asc')->get();

        return inertia('Cashier', [
            'products' => $products,
            'categories' => $categories,
            'tables' => $tables,
        ]);
    }

    // Proses Simpan Pesanan Kasir POS
    public function storeCashierOrder(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'order_type' => 'required|in:dine_in,takeaway',
            'table_id' => 'required_if:order_type,dine_in|nullable|exists:tables,id',
            'payment_method' => 'required|in:cash,qris_manual,qris_tokopay',
            'cart_items' => 'required|array|min:1',
            'notes' => 'nullable|string',
            'voucher_code' => 'nullable|string',
        ]);

        // Hitung total harga
        $totalPrice = 0;
        foreach ($validated['cart_items'] as $item) {
            $totalPrice += ($item['price'] * $item['quantity']);
        }

        // Hitung potongan voucher di server untuk keamanan
        $discountAmount = 0;
        $voucherCode = $validated['voucher_code'] ?? null;
        if (!empty($voucherCode)) {
            $voucher = \App\Models\Voucher::where('code', $voucherCode)->where('is_active', true)->first();
            if ($voucher && $totalPrice >= $voucher->min_purchase) {
                if ($voucher->discount_type === 'percentage') {
                    $discountAmount = ($voucher->discount_value / 100) * $totalPrice;
                } else {
                    $discountAmount = $voucher->discount_value;
                }

                if ($discountAmount > $totalPrice) {
                    $discountAmount = $totalPrice;
                }
            }
        }

        $finalPrice = $totalPrice - $discountAmount;

        // Tentukan status awal
        // Jika bayar tunai (cash), otomatis Lunas (paid) dan langsung Diproses (processing)
        $paymentStatus = 'pending';
        $orderStatus = 'pending';

        if ($validated['payment_method'] === 'cash') {
            $paymentStatus = 'paid';
            $orderStatus = 'processing';
        }

        // Buat Order
        $order = Order::create([
            'table_id' => $validated['order_type'] === 'dine_in' ? $validated['table_id'] : null,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'] ?? '-',
            'total_price' => $finalPrice,
            'order_type' => $validated['order_type'],
            'payment_method' => $validated['payment_method'] === 'cash' ? 'cashier' : $validated['payment_method'],
            'payment_status' => $paymentStatus,
            'order_status' => $orderStatus,
            'notes' => $validated['notes'] ?? null,
            'voucher_code' => $voucherCode,
            'discount_amount' => $discountAmount,
        ]);

        // Simpan Items
        foreach ($validated['cart_items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['id'],
                'quantity' => $item['quantity'],
                'price_at_sale' => $item['price'],
                'notes' => $item['notes'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesanan kasir berhasil dibuat!',
            'order' => $order->load(['table', 'items.product']),
        ]);
    }
}
