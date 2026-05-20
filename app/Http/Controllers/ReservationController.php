<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\Table;
use App\Services\FonnteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ReservationController extends Controller
{
    /**
     * Halaman admin - daftar semua reservasi + data untuk buat reservasi baru.
     */
    public function index()
    {
        $reservations = Reservation::with('order.items.product')
            ->orderBy('reservation_date', 'asc')
            ->orderBy('reservation_time', 'asc')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'customer_name' => $r->customer_name,
                'customer_phone' => $r->customer_phone,
                'reservation_date' => $r->reservation_date->format('Y-m-d'),
                'reservation_time' => $r->reservation_time,
                'num_guests' => $r->num_guests,
                'notes' => $r->notes,
                'preorder_items' => $r->preorder_items,
                'order_id' => $r->order_id,
                'order' => $r->order ? [
                    'id' => $r->order->id,
                    'total_price' => (int) $r->order->total_price,
                    'items' => $r->order->items->map(fn ($item) => [
                        'id' => $item->id,
                        'name' => $item->product->name ?? 'Produk Terhapus',
                        'quantity' => $item->quantity,
                        'price' => (int) $item->price_at_sale,
                        'notes' => $item->notes,
                    ]),
                ] : null,
                'status' => $r->status,
                'created_at' => $r->created_at->format('Y-m-d H:i:s'),
            ]);

        // Data untuk form buat reservasi baru (sama seperti Cashier)
        $products = Product::with(['category', 'assignedAddons'])
            ->where('is_available', true)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'category_id' => $p->category_id,
                'name' => $p->name,
                'price' => (int) $p->price,
                'description' => $p->description,
                'image' => $p->image,
                'is_available' => (bool) $p->is_available,
                'addons' => $p->assignedAddons->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->addon_name,
                    'price' => (int) $a->extra_price,
                ]),
            ]);

        $categories = Category::orderBy('name', 'asc')->get();
        $tables = Table::orderBy('table_name', 'asc')->get();

        return Inertia::render('ReservationManagement', [
            'reservations' => $reservations,
            'products' => $products,
            'categories' => $categories,
            'tables' => $tables,
        ]);
    }

    /**
     * Simpan reservasi baru (dari admin), buat order dari pre-order, kirim WA.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_time' => 'required|string',
            'num_guests' => 'required|integer|min:1|max:50',
            'table_id' => 'nullable|exists:tables,id',
            'notes' => 'nullable|string|max:1000',
            'preorder_items' => 'nullable|array',
            'preorder_items.*.id' => 'required_with:preorder_items|integer|exists:products,id',
            'preorder_items.*.quantity' => 'required_with:preorder_items|integer|min:1',
            'preorder_items.*.price' => 'required_with:preorder_items|numeric',
            'preorder_items.*.notes' => 'nullable|string|max:500',
        ]);

        // Buat Order resmi dari pre-order jika ada
        $orderId = null;
        $preorderData = null;
        if (! empty($validated['preorder_items'])) {
            $cartItems = $validated['preorder_items'];
            $productIds = array_column($cartItems, 'id');
            $dbProducts = Product::whereIn('id', $productIds)->get()->keyBy('id');

            $totalPrice = 0;
            $preorderData = [];
            foreach ($cartItems as $item) {
                $dbProduct = $dbProducts[$item['id']] ?? null;
                $priceToUse = isset($item['price']) ? (int) $item['price'] : ($dbProduct ? (int) $dbProduct->price : 0);
                $totalPrice += $priceToUse * $item['quantity'];
                $preorderData[] = [
                    'id' => $item['id'],
                    'name' => $dbProduct->name ?? 'Menu',
                    'price' => $priceToUse,
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ];
            }

            $order = Order::create([
                'table_id' => $validated['table_id'] ?? null,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'total_price' => $totalPrice,
                'order_type' => 'dine_in',
                'payment_method' => 'cashier',
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'notes' => '[PRE-ORDER RESERVASI] '.($validated['notes'] ?? ''),
                'voucher_code' => null,
                'discount_amount' => 0,
                'promo_discount_amount' => 0,
            ]);

            foreach ($cartItems as $item) {
                $dbProduct = $dbProducts[$item['id']] ?? null;
                $priceToUse = isset($item['price']) ? (int) $item['price'] : ($dbProduct ? (int) $dbProduct->price : 0);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'price_at_sale' => $priceToUse,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $orderId = $order->id;
        }

        // Simpan reservasi
        $reservation = Reservation::create([
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'reservation_date' => $validated['reservation_date'],
            'reservation_time' => $validated['reservation_time'],
            'num_guests' => $validated['num_guests'],
            'notes' => $validated['notes'] ?? null,
            'preorder_items' => $preorderData,
            'order_id' => $orderId,
            'status' => 'pending',
        ]);

        // Kirim WA notifikasi
        $this->sendNotifications($reservation);

        return response()->json([
            'success' => true,
            'message' => 'Reservasi berhasil dibuat!',
            'reservation' => $reservation->load('order.items.product'),
        ]);
    }

    /**
     * Update status reservasi (confirm/cancel).
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);

        $reservation = Reservation::findOrFail($id);
        $oldStatus = $reservation->status;
        $reservation->status = $validated['status'];
        $reservation->save();

        // Kirim WA notifikasi ke pelanggan saat status berubah
        try {
            $fonnte = new FonnteService;
            $dateFormatted = Carbon::parse($reservation->reservation_date)->translatedFormat('d F Y');

            if ($validated['status'] === 'confirmed' && $oldStatus !== 'confirmed') {
                $message = "✅ *ZUNOI CAFFE - RESERVASI DIKONFIRMASI*\n\n"
                    ."Halo *{$reservation->customer_name}*! 🎉\n"
                    ."Reservasi meja Anda telah kami *konfirmasi*.\n\n"
                    ."📅 *Tanggal:* {$dateFormatted}\n"
                    ."🕐 *Waktu:* {$reservation->reservation_time}\n"
                    ."👥 *Jumlah Tamu:* {$reservation->num_guests} orang\n\n"
                    .($reservation->order_id ? "🍽️ *Pre-order menu Anda sudah tercatat dan siap disiapkan.*\n\n" : '')
                    ."Kami menantikan kedatangan Anda!\n"
                    .'_Zunoi Caffe_';

                $fonnte->sendMessage($reservation->customer_phone, $message);
            }

            if ($validated['status'] === 'cancelled' && $oldStatus !== 'cancelled') {
                $message = "❌ *ZUNOI CAFFE - RESERVASI DIBATALKAN*\n\n"
                    ."Halo *{$reservation->customer_name}*,\n"
                    ."Mohon maaf, reservasi Anda pada *{$dateFormatted}* pukul *{$reservation->reservation_time}* telah dibatalkan.\n\n"
                    ."Jika ada pertanyaan, silakan hubungi kami langsung.\n"
                    .'_Zunoi Caffe_';

                $fonnte->sendMessage($reservation->customer_phone, $message);
            }
        } catch (\Exception $e) {
            Log::error('Reservation WA Error: '.$e->getMessage());
        }

        return response()->json(['success' => true, 'reservation' => $reservation]);
    }

    /**
     * Hapus reservasi.
     */
    public function destroy($id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Kirim notifikasi WA ke pelanggan dan owner saat reservasi baru dibuat.
     */
    private function sendNotifications(Reservation $reservation): void
    {
        try {
            $fonnte = new FonnteService;
            $ownerWa = Setting::getValue('owner_whatsapp', '');
            $dateFormatted = Carbon::parse($reservation->reservation_date)->translatedFormat('d F Y');

            // WA ke Pelanggan
            $customerMsg = "☕ *ZUNOI CAFFE - RESERVASI DITERIMA*\n\n"
                ."Halo *{$reservation->customer_name}*! Terima kasih telah melakukan reservasi.\n\n"
                ."📋 *Detail Reservasi Anda:*\n"
                ."━━━━━━━━━━━━━━━━━━\n"
                ."📅 *Tanggal:* {$dateFormatted}\n"
                ."🕐 *Waktu:* {$reservation->reservation_time}\n"
                ."👥 *Jumlah Tamu:* {$reservation->num_guests} orang\n";

            if (! empty($reservation->notes)) {
                $customerMsg .= "📝 *Catatan:* {$reservation->notes}\n";
            }

            if ($reservation->order_id) {
                $customerMsg .= "\n🍽️ *Pre-order menu Anda sudah tercatat!*\n";
            }

            $customerMsg .= "\n⏳ Reservasi Anda sedang *menunggu konfirmasi* dari kami.\n"
                ."Kami akan segera menghubungi Anda.\n\n"
                .'_Zunoi Caffe_';

            $fonnte->sendMessage($reservation->customer_phone, $customerMsg);

            // WA ke Owner
            if (! empty($ownerWa)) {
                $preorderNote = $reservation->order_id
                    ? "\n🍽️ *Dengan Pre-Order!* (Order ID: #{$reservation->order_id})\n"
                    : '';

                $ownerMsg = "📋 *RESERVASI BARU - ZUNOI CAFFE*\n\n"
                    ."━━━━━━━━━━━━━━━━━━\n"
                    ."👤 *Nama:* {$reservation->customer_name}\n"
                    ."📞 *WA:* {$reservation->customer_phone}\n"
                    ."📅 *Tanggal:* {$dateFormatted}\n"
                    ."🕐 *Waktu:* {$reservation->reservation_time}\n"
                    ."👥 *Tamu:* {$reservation->num_guests} orang\n"
                    .$preorderNote;

                if (! empty($reservation->notes)) {
                    $ownerMsg .= "📝 *Catatan:* {$reservation->notes}\n";
                }

                $ownerMsg .= "\n➡️ Konfirmasi di dashboard reservasi.";

                $fonnte->sendMessage($ownerWa, $ownerMsg);
            }
        } catch (\Exception $e) {
            Log::error('Reservation WA Notification Error: '.$e->getMessage());
        }
    }
}
