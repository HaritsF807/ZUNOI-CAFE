<?php

use App\Http\Controllers\BaristaController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\TokopayWebhookController;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

// Zunoi Caffe - Customer QR Routes
Route::get('/meja/{secure_token}', [TableController::class, 'scanQR'])->name('table.scan');
Route::get('/scan-required', function () {
    return inertia('Customer/ScanRequired');
})->name('scan.required');

Route::middleware(['verify_table_session'])->group(function () {
    Route::get('/order', function () {
        $products = Cache::remember('menu_products', 60 * 5, function () {
            return Product::with(['category', 'assignedAddons'])->get()->map(function ($p) {
                return [
                    'id' => $p->id,
                    'category_id' => $p->category_id,
                    'name' => $p->name,
                    'price' => (int) $p->price,
                    'description' => $p->description,
                    'image' => $p->image,
                    'is_available' => (bool) $p->is_available,
                    'category' => $p->category,
                    'additions' => $p->assignedAddons->map(function ($a) {
                        return [
                            'name' => $a->addon_name,
                            'price' => (int) $a->extra_price,
                        ];
                    })->toArray(),
                ];
            })->toArray();
        });
        $categories = Cache::remember('menu_categories', 60 * 5, function () {
            return Category::orderBy('name', 'asc')->get()->toArray();
        });
        $banners = Cache::remember('menu_banners', 60 * 5, function () {
            return Banner::where('is_active', true)->orderBy('created_at', 'desc')->get()->toArray();
        });

        return inertia('Customer/MenuList', [
            'products' => $products,
            'categories' => $categories,
            'banners' => $banners,
        ]);
    })->name('order.index');

    Route::get('/checkout', function () {
        $qrisUrl = Cache::remember('qris_manual_url', 60 * 60, function () {
            return Setting::getValue('qris_manual_url', 'https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg');
        });
        $promotions = Cache::remember('active_promotions', 60 * 5, function () {
            return Promotion::where('is_active', true)
                ->with(['buyProduct', 'bundlingProduct', 'getProduct'])
                ->get();
        });

        return inertia('Customer/Cart', [
            'qris_manual_url' => $qrisUrl,
            'promotions' => $promotions,
        ]);
    })->name('order.checkout');

    Route::post('/order/store', [OrderController::class, 'store'])->name('order.store');
});

Route::get('/order/success/{secure_key}', [OrderController::class, 'success'])->name('order.success');

// Validasi Voucher (Public untuk Guest & Cashier)
Route::post('/api/vouchers/validate', [PromoController::class, 'validateVoucher']);

// Endpoint untuk Dashboard
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard/cashier', [OrderController::class, 'cashierIndex'])->name('cashier.index');
    Route::post('/api/orders/cashier', [OrderController::class, 'storeCashierOrder'])->name('orders.cashier.store');

    // Barista Staff Management CRUD (Owner Only)
    Route::get('/dashboard/staff', [BaristaController::class, 'index'])->name('staff.management');
    Route::post('/api/staff', [BaristaController::class, 'store'])->name('staff.store');
    Route::put('/api/staff/{user}', [BaristaController::class, 'update'])->name('staff.update');
    Route::delete('/api/staff/{user}', [BaristaController::class, 'destroy'])->name('staff.destroy');

    Route::get('/dashboard/menu', [MenuController::class, 'index'])->name('menu.management');
    Route::get('/dashboard/promos', [PromoController::class, 'index'])->name('promo.management');
    Route::get('/dashboard/statistics', [StatisticsController::class, 'index'])->name('statistics.index');
    Route::get('/dashboard/history', [\App\Http\Controllers\HistoryController::class, 'index'])->name('history.index');

    Route::get('/dashboard/tables', function () {
        return inertia('TableManagement');
    })->name('table.management');

    Route::get('/dashboard/menu-preview', function () {
        $products = Cache::remember('menu_products', 60 * 5, function () {
            return Product::with(['category', 'assignedAddons'])->get()->map(function ($p) {
                return [
                    'id' => $p->id,
                    'category_id' => $p->category_id,
                    'name' => $p->name,
                    'price' => (int) $p->price,
                    'description' => $p->description,
                    'image' => $p->image,
                    'is_available' => (bool) $p->is_available,
                    'category' => $p->category,
                    'additions' => $p->assignedAddons->map(function ($a) {
                        return [
                            'name' => $a->addon_name,
                            'price' => (int) $a->extra_price,
                        ];
                    }),
                ];
            });
        });
        $categories = Cache::remember('menu_categories', 60 * 5, function () {
            return Category::orderBy('name', 'asc')->get();
        });
        $banners = Cache::remember('menu_banners', 60 * 5, function () {
            return Banner::where('is_active', true)->orderBy('created_at', 'desc')->get();
        });

        return inertia('MenuPreview', [
            'products' => $products,
            'categories' => $categories,
            'banners' => $banners,
        ]);
    })->name('menu.preview');

    Route::get('/dashboard/menu-preview/checkout', function () {
        $qrisUrl = Cache::remember('qris_manual_url', 60 * 60, function () {
            return Setting::getValue('qris_manual_url', 'https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg');
        });
        $promotions = Cache::remember('active_promotions', 60 * 5, function () {
            return Promotion::where('is_active', true)
                ->with(['buyProduct', 'bundlingProduct', 'getProduct'])
                ->get();
        });

        return inertia('MenuPreviewCheckout', [
            'qris_manual_url' => $qrisUrl,
            'promotions' => $promotions,
        ]);
    })->name('menu.preview.checkout');

    Route::get('/dashboard/menu-preview/success', function (Request $request) {
        return inertia('MenuPreviewSuccess', [
            'customer_name' => $request->query('customer_name'),
            'order_type' => $request->query('order_type'),
            'payment_method' => $request->query('payment_method'),
            'total_price' => $request->query('total_price'),
            'items' => $request->query('items'),
            'voucher_code' => $request->query('voucher_code'),
            'discount_amount' => $request->query('discount_amount'),
            'promo_discount_amount' => $request->query('promo_discount_amount'),
        ]);
    })->name('menu.preview.success');

    Route::get('/dashboard/integration', [SettingController::class, 'integrationIndex'])->name('integration.setup');
    Route::post('/api/settings', [SettingController::class, 'updateSettings'])->name('settings.update');
    Route::post('/api/reports/send-recap', [SettingController::class, 'sendRecap'])->name('reports.send-recap');

    // API Kelola Menu
    Route::post('/api/products', [MenuController::class, 'storeProduct']);
    Route::put('/api/products/{id}', [MenuController::class, 'updateProduct']);
    Route::delete('/api/products/{id}', [MenuController::class, 'deleteProduct']);
    Route::patch('/api/products/{id}/toggle-availability', [MenuController::class, 'toggleProductAvailability']);
    Route::post('/api/products/{id}/sync-addons', [MenuController::class, 'syncProductAddons']);

    // API Kelola Addon Global
    Route::post('/api/addons', [MenuController::class, 'storeAddon']);
    Route::put('/api/addons/{id}', [MenuController::class, 'updateAddon']);
    Route::delete('/api/addons/{id}', [MenuController::class, 'deleteAddon']);

    Route::post('/api/categories', [MenuController::class, 'storeCategory']);
    Route::put('/api/categories/{id}', [MenuController::class, 'updateCategory']);
    Route::delete('/api/categories/{id}', [MenuController::class, 'deleteCategory']);

    // API Kelola Promo
    Route::post('/api/promos', [PromoController::class, 'storePromo']);
    Route::post('/api/promos/{id}', [PromoController::class, 'updatePromo']);
    Route::delete('/api/promos/{id}', [PromoController::class, 'deletePromo']);

    // API Kelola Voucher
    Route::post('/api/vouchers', [PromoController::class, 'storeVoucher']);
    Route::post('/api/vouchers/{id}', [PromoController::class, 'updateVoucher']);
    Route::delete('/api/vouchers/{id}', [PromoController::class, 'deleteVoucher']);

    // API Kelola Potongan & Buy 1 Get 1
    Route::post('/api/promotions', [PromoController::class, 'storePromotion']);
    Route::post('/api/promotions/{id}', [PromoController::class, 'updatePromotion']);
    Route::delete('/api/promotions/{id}', [PromoController::class, 'deletePromotion']);

    Route::get('/api/orders/live', [OrderController::class, 'liveOrders']);
    Route::patch('/api/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::post('/api/orders/{id}/send-notification', [OrderController::class, 'sendFonnteNotification']);

    // Manajemen Meja
    Route::get('/api/tables', function () {
        return response()->json(Table::all());
    });
    Route::post('/api/tables', function (Request $request) {
        $validated = $request->validate(['table_name' => 'required|string|max:50']);
        $token = Str::random(6); // Generate random 6 char token

        $table = Table::create([
            'table_name' => $validated['table_name'],
            'secure_token' => $token,
        ]);

        return response()->json($table);
    });
    Route::delete('/api/tables/{id}', function ($id) {
        $table = Table::findOrFail($id);
        $table->delete();

        return response()->json(['success' => true]);
    });

    // Reservasi Admin
    Route::get('/dashboard/reservasi', [ReservationController::class, 'index'])->name('reservation.index');
    Route::post('/api/reservations', [ReservationController::class, 'store'])->name('reservation.store');
    Route::patch('/api/reservations/{id}/status', [ReservationController::class, 'updateStatus']);
    Route::delete('/api/reservations/{id}', [ReservationController::class, 'destroy']);
});

// Webhook Tokopay
Route::post('/webhook/tokopay', [TokopayWebhookController::class, 'handle'])->name('webhook.tokopay');
Route::get('/simulate-tokopay-payment/{id}', [TokopayWebhookController::class, 'simulateLocalPayment']);

require __DIR__.'/settings.php';
