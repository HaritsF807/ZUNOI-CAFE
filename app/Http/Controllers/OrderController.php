<?php

namespace App\Http\Controllers;

use App\Jobs\SendFonnteMessageJob;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Table;
use App\Models\User;
use App\Models\Voucher;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

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
            'payment_proof' => 'nullable|string|max:1000',
            'notes' => 'nullable|string',
        ]);

        if ($validated['payment_method'] === 'qris_manual') {
            $request->validate([
                'payment_proof' => 'required|string|max:1000',
            ]);
        }

        $tableId = session('active_table_id');
        if (! $tableId) {
            return back()->with('error', 'Sesi meja tidak valid.');
        }

        // Rate Limiter: Cegah klik ganda / spam dalam 5 detik per IP
        $rateLimitKey = 'checkout_spam_' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            return back()->with('error', 'Terlalu banyak permintaan. Harap tunggu 5 detik.');
        }
        RateLimiter::hit($rateLimitKey, 5);

        // Hitung total harga
        $totalPrice = 0;
        foreach ($validated['cart_items'] as $item) {
            $totalPrice += ($item['price'] * $item['quantity']);
        }

        // Hitung potongan voucher di server untuk keamanan
        $discountAmount = 0;
        $voucherCode = $request->input('voucher_code');
        if (! empty($voucherCode)) {
            $voucher = Voucher::where('code', $voucherCode)->where('is_active', true)->first();
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

        // Hitung potongan promo otomatis (bundling & buy 1 get 1)
        $promoDiscountAmount = $this->calculatePromoDiscount($validated['cart_items']);

        $finalPrice = $totalPrice - $discountAmount - $promoDiscountAmount;
        if ($finalPrice < 0) {
            $finalPrice = 0;
        }

        // Pengecekan Duplikasi Pesanan (Double Order) dalam 10 detik terakhir
        $recentDuplicate = Order::where('customer_phone', $validated['customer_phone'])
            ->where('total_price', $finalPrice)
            ->where('created_at', '>=', now()->subSeconds(10))
            ->first();

        if ($recentDuplicate) {
            return redirect()->route('order.success', ['secure_key' => $recentDuplicate->secure_key]);
        }

        // Ambil URL bukti pembayaran langsung dari Frontend (Cloudinary)
        $proofUrl = $request->input('payment_proof');

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
            'promo_discount_amount' => $promoDiscountAmount,
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
            ->whereDate('created_at', now('Asia/Jakarta')->toDateString())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'table' => $order->table->table_name ?? 'Takeaway',
                    'type' => $order->order_type === 'dine_in' ? 'Dine In' : 'Takeaway',
                    'name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
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

        // Ambil info kasir/barista/owner yang sedang shift/aktif hari ini (dalam 24 jam terakhir)
        $activeUserIds = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', time() - 24 * 3600)
            ->pluck('user_id')
            ->unique()
            ->toArray();

        if (empty($activeUserIds)) {
            $activeUserIds = [auth()->id()];
        }

        $activeStaff = User::whereIn('id', $activeUserIds)
            ->whereIn('role', ['barista', 'owner'])
            ->get(['id', 'name', 'email', 'role'])
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role === 'owner' ? 'Owner / Manager' : 'Barista / Kasir',
                ];
            });

        return response()->json([
            'orders' => $orders,
            'active_staff' => $activeStaff,
        ]);
    }

    // Memperbarui status pesanan dari dashboard barista
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'order_status' => 'required|in:pending,processing,completed,cancelled',
            'payment_status' => 'sometimes|in:pending,paid',
        ]);

        $order = Order::with('items.product')->findOrFail($id);

        // Jika status order diterima (processing), set juga status pembayaran menjadi paid (LUNAS)
        if ($validated['order_status'] === 'processing') {
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

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diperbarui!',
            'order' => $order,
            'old_status' => $oldStatus,
        ]);
    }

    // Endpoint rahasia untuk dipanggil dari Frontend secara background
    public function sendFonnteNotification(Request $request, $id)
    {
        $order = Order::with('items.product')->findOrFail($id);
        $oldStatus = $request->input('old_status');

        try {
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

                $pricingBreakdown = '';
                $hasDiscount = ((float) $order->discount_amount > 0 || (float) $order->promo_discount_amount > 0);
                if ($hasDiscount) {
                    $subtotal = (float) $order->total_price + (float) $order->discount_amount + (float) $order->promo_discount_amount;
                    $pricingBreakdown .= '💵 *Subtotal:* Rp '.number_format($subtotal, 0, ',', '.')."\n";
                    if ((float) $order->discount_amount > 0) {
                        $pricingBreakdown .= '🎟️ *Voucher ('.($order->voucher_code ?: 'Promo').'):* -Rp '.number_format($order->discount_amount, 0, ',', '.')."\n";
                    }
                    if ((float) $order->promo_discount_amount > 0) {
                        $pricingBreakdown .= '🏷️ *Potongan Promo Otomatis:* -Rp '.number_format($order->promo_discount_amount, 0, ',', '.')."\n";
                    }
                }
                $pricingBreakdown .= '💰 *Total Tagihan:* Rp '.number_format($order->total_price, 0, ',', '.');

                if ($order->payment_method === 'qris_manual') {
                    $message = "☕ *ZUNOI CAFFE - PEMBAYARAN TERVERIFIKASI* ☕\n\n".
                               "Halo *{$order->customer_name}*, terima kasih! Bukti pembayaran QRIS Anda telah berhasil kami verifikasi.\n\n".
                               "*Rincian Transaksi:*\n".
                               "━━━━━━━━━━━━━━━━━━\n".
                               "🆔 *ID Pesanan:* #{$order->id}\n".
                               "📅 *Waktu:* {$timeFormatted} WIB\n".
                               "🛋️ *Tipe:* {$typeName}\n".
                               "💳 *Metode:* QRIS Manual (Toko)\n".
                               $pricingBreakdown."\n".
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
                               $pricingBreakdown."\n".
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
                \App\Jobs\SendFonnteMessageJob::dispatchSync($order->customer_phone, $message);
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

                $pricingBreakdown = '';
                $hasDiscount = ((float) $order->discount_amount > 0 || (float) $order->promo_discount_amount > 0);
                if ($hasDiscount) {
                    $subtotal = (float) $order->total_price + (float) $order->discount_amount + (float) $order->promo_discount_amount;
                    $pricingBreakdown .= '💵 *Subtotal:* Rp '.number_format($subtotal, 0, ',', '.')."\n";
                    if ((float) $order->discount_amount > 0) {
                        $pricingBreakdown .= '🎟️ *Voucher ('.($order->voucher_code ?: 'Promo').'):* -Rp '.number_format($order->discount_amount, 0, ',', '.')."\n";
                    }
                    if ((float) $order->promo_discount_amount > 0) {
                        $pricingBreakdown .= '🏷️ *Potongan Promo Otomatis:* -Rp '.number_format($order->promo_discount_amount, 0, ',', '.')."\n";
                    }
                }
                $pricingBreakdown .= '💰 *Total Belanja:* Rp '.number_format($order->total_price, 0, ',', '.');

                $message = "☕ *ZUNOI CAFFE - PESANAN SELESAI* ☕\n\n".
                           "Halo *{$order->customer_name}*, kabar gembira! Pesanan Anda telah selesai disiapkan dan siap dinikmati!\n\n".
                           "*Rincian Transaksi:*\n".
                           "━━━━━━━━━━━━━━━━━━\n".
                           "🆔 *ID Pesanan:* #{$order->id}\n".
                           "📅 *Waktu:* {$timeFormatted} WIB\n".
                           "🛋️ *Tipe:* {$typeName}\n".
                           $pricingBreakdown."\n".
                           "💵 *Status:* LUNAS (Disajikan)\n\n".
                           "*Daftar Pesanan:*\n".
                           "{$itemList}\n".
                           "━━━━━━━━━━━━━━━━━━\n\n".
                           '🧾 *Struk/Invoice Online:* '.url("/order/success/{$order->secure_key}")."\n\n".
                           "{$deliveryInstruction}\n\n".
                           'Terima kasih banyak telah memesan di Zunoi Caffe. Semoga hari Anda menyenangkan dan penuh energi positif! ☕💛';

                \App\Jobs\SendFonnteMessageJob::dispatchSync($order->customer_phone, $message);
            }
        } catch (\Exception $e) {
            Log::error('Gagal kirim WA background Fonnte: '.$e->getMessage());
        }

        return response()->json(['success' => true]);
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
        $products = Product::with(['category', 'assignedAddons'])->where('is_available', true)->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'image' => $product->image,
                'is_available' => $product->is_available,
                'addons' => $product->assignedAddons->map(function ($addon) {
                    return [
                        'id' => $addon->id,
                        'name' => $addon->addon_name,
                        'price' => (int) $addon->extra_price,
                    ];
                }),
            ];
        });
        $categories = Category::orderBy('name', 'asc')->get();
        $tables = Table::orderBy('table_name', 'asc')->get();
        $promotions = Promotion::where('is_active', true)
            ->with(['buyProduct', 'bundlingProduct', 'getProduct'])
            ->get();

        return inertia('Cashier', [
            'products' => $products,
            'categories' => $categories,
            'tables' => $tables,
            'promotions' => $promotions,
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

        // Rate Limiter untuk Kasir
        $rateLimitKey = 'cashier_checkout_' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            return response()->json([
                'success' => false,
                'message' => 'Harap tunggu 5 detik sebelum membuat pesanan baru (Anti-Spam).'
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 5);

        // Hitung total harga
        $totalPrice = 0;
        foreach ($validated['cart_items'] as $item) {
            $totalPrice += ($item['price'] * $item['quantity']);
        }

        // Hitung potongan voucher di server untuk keamanan
        $discountAmount = 0;
        $voucherCode = $validated['voucher_code'] ?? null;
        if (! empty($voucherCode)) {
            $voucher = Voucher::where('code', $voucherCode)->where('is_active', true)->first();
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

        // Hitung potongan promo otomatis (bundling & buy 1 get 1)
        $promoDiscountAmount = $this->calculatePromoDiscount($validated['cart_items']);

        $finalPrice = $totalPrice - $discountAmount - $promoDiscountAmount;
        if ($finalPrice < 0) {
            $finalPrice = 0;
        }

        // Pengecekan Duplikasi Pesanan untuk Kasir
        $recentDuplicate = Order::where('customer_name', $validated['customer_name'])
            ->where('total_price', $finalPrice)
            ->where('created_at', '>=', now()->subSeconds(10))
            ->first();

        if ($recentDuplicate) {
            return response()->json([
                'success' => true,
                'message' => 'Pesanan ini sudah dibuat beberapa detik yang lalu.',
                'order' => $recentDuplicate->load(['table', 'items.product']),
            ]);
        }

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
            'promo_discount_amount' => $promoDiscountAmount,
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

    /**
     * Hitung diskon dari promo bundling & buy 1 get 1 otomatis secara aman di backend.
     */
    private function calculatePromoDiscount($cartItems)
    {
        $promoDiscount = 0;

        if (! Schema::hasTable('promotions')) {
            return 0;
        }

        $activePromos = Promotion::where('is_active', true)->get();
        if ($activePromos->isEmpty()) {
            return 0;
        }

        // Map cart items by product id for easy lookup
        $cartMap = [];
        foreach ($cartItems as $item) {
            $prodId = (int) $item['id'];
            if (! isset($cartMap[$prodId])) {
                $cartMap[$prodId] = [
                    'quantity' => 0,
                    'price' => (float) $item['price'],
                    'base_price' => (float) ($item['basePrice'] ?? $item['price']),
                ];
            }
            $cartMap[$prodId]['quantity'] += (int) $item['quantity'];
        }

        // Load all required products from database to ensure base prices are correct and secure
        $productIds = array_keys($cartMap);
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        foreach ($cartMap as $id => &$val) {
            if ($products->has($id)) {
                $val['base_price'] = (float) $products->get($id)->price;
            }
        }
        unset($val);

        foreach ($activePromos as $promo) {
            $buyProductId = (int) $promo->buy_product_id;
            $buyQtyRequired = (int) $promo->buy_quantity;
            $bundlingProductId = $promo->bundling_product_id ? (int) $promo->bundling_product_id : null;
            $getProductId = $promo->get_product_id ? (int) $promo->get_product_id : null;
            $getQtyRequired = $promo->get_quantity ? (int) $promo->get_quantity : 1;

            if (! isset($cartMap[$buyProductId])) {
                continue;
            }

            $buyCartQty = $cartMap[$buyProductId]['quantity'];

            if ($promo->type === 'bundling' && $bundlingProductId) {
                if (! isset($cartMap[$bundlingProductId])) {
                    continue;
                }

                $bundCartQty = $cartMap[$bundlingProductId]['quantity'];
                $numBundles = min(floor($buyCartQty / $buyQtyRequired), $bundCartQty);

                if ($numBundles > 0) {
                    if ($promo->discount_type === 'nominal') {
                        $promoDiscount += (float) $promo->discount_value * $numBundles;
                    } elseif ($promo->discount_type === 'percentage') {
                        $buyUnitPrice = $cartMap[$buyProductId]['base_price'];
                        $bundUnitPrice = $cartMap[$bundlingProductId]['base_price'];
                        $singleBundlePrice = ($buyUnitPrice * $buyQtyRequired) + $bundUnitPrice;
                        $promoDiscount += ((float) $promo->discount_value / 100) * $singleBundlePrice * $numBundles;
                    }
                }
            } elseif ($promo->type === 'buy_get' && $getProductId) {
                if (! isset($cartMap[$getProductId])) {
                    continue;
                }

                $getCartQty = $cartMap[$getProductId]['quantity'];

                if ($buyProductId === $getProductId) {
                    // Buy X Get Y of same product
                    $requiredCombo = $buyQtyRequired + $getQtyRequired;
                    $numCombos = floor($buyCartQty / $requiredCombo);

                    if ($numCombos > 0) {
                        $itemUnitPrice = $cartMap[$getProductId]['base_price'];
                        $discountedQty = $numCombos * $getQtyRequired;

                        if ($promo->discount_type === 'free') {
                            $promoDiscount += $itemUnitPrice * $discountedQty;
                        } elseif ($promo->discount_type === 'percentage') {
                            $promoDiscount += ((float) $promo->discount_value / 100) * $itemUnitPrice * $discountedQty;
                        } elseif ($promo->discount_type === 'nominal') {
                            $promoDiscount += (float) $promo->discount_value * $discountedQty;
                        }
                    }
                } else {
                    // Buy X Get Y of different product
                    $numCombos = floor($buyCartQty / $buyQtyRequired);

                    if ($numCombos > 0) {
                        $maxDiscountedQty = $numCombos * $getQtyRequired;
                        $actualDiscountedQty = min($maxDiscountedQty, $getCartQty);

                        if ($actualDiscountedQty > 0) {
                            $itemUnitPrice = $cartMap[$getProductId]['base_price'];

                            if ($promo->discount_type === 'free') {
                                $promoDiscount += $itemUnitPrice * $actualDiscountedQty;
                            } elseif ($promo->discount_type === 'percentage') {
                                $promoDiscount += ((float) $promo->discount_value / 100) * $itemUnitPrice * $actualDiscountedQty;
                            } elseif ($promo->discount_type === 'nominal') {
                                $promoDiscount += (float) $promo->discount_value * $actualDiscountedQty;
                            }
                        }
                    }
                }
            }
        }

        return $promoDiscount;
    }
}
