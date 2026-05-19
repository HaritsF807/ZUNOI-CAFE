<?php

use App\Http\Controllers\BaristaController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\TokopayWebhookController;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
        $products = Product::with(['category', 'addons'])->get();
        $categories = Category::orderBy('name', 'asc')->get();
        $banners = \App\Models\Banner::where('is_active', true)->orderBy('created_at', 'desc')->get();
        
        return inertia('Customer/MenuList', [
            'products' => $products,
            'categories' => $categories,
            'banners' => $banners
        ]);
    })->name('order.index');

    Route::get('/checkout', function () {
        $qrisUrl = Setting::getValue('qris_manual_url', 'https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg');

        return inertia('Customer/Cart', [
            'qris_manual_url' => $qrisUrl,
        ]);
    })->name('order.checkout');

    Route::post('/order/store', [OrderController::class, 'store'])->name('order.store');
});

Route::get('/order/success/{secure_key}', [OrderController::class, 'success'])->name('order.success');

// Validasi Voucher (Public untuk Guest & Cashier)
Route::post('/api/vouchers/validate', [\App\Http\Controllers\PromoController::class, 'validateVoucher']);

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
    Route::get('/dashboard/promos', [\App\Http\Controllers\PromoController::class, 'index'])->name('promo.management');

    Route::get('/dashboard/tables', function () {
        return inertia('TableManagement');
    })->name('table.management');

    Route::get('/dashboard/menu-preview', function () {
        $products = Product::with(['category', 'addons'])->get();
        $categories = Category::orderBy('name', 'asc')->get();
        $banners = \App\Models\Banner::where('is_active', true)->orderBy('created_at', 'desc')->get();

        return inertia('MenuPreview', [
            'products' => $products,
            'categories' => $categories,
            'banners' => $banners
        ]);
    })->name('menu.preview');

    Route::get('/dashboard/menu-preview/checkout', function () {
        $qrisUrl = Setting::getValue('qris_manual_url', 'https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg');

        return inertia('MenuPreviewCheckout', [
            'qris_manual_url' => $qrisUrl,
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

    // API Kelola Addon Menu
    Route::post('/api/products/{product_id}/addons', [MenuController::class, 'storeAddon']);
    Route::post('/api/products/{product_id}/addons/default', [MenuController::class, 'useDefaultAddons']);
    Route::put('/api/product-addons/{id}', [MenuController::class, 'updateAddon']);
    Route::delete('/api/product-addons/{id}', [MenuController::class, 'deleteAddon']);

    Route::post('/api/categories', [MenuController::class, 'storeCategory']);
    Route::put('/api/categories/{id}', [MenuController::class, 'updateCategory']);
    Route::delete('/api/categories/{id}', [MenuController::class, 'deleteCategory']);

    // API Kelola Promo
    Route::post('/api/promos', [\App\Http\Controllers\PromoController::class, 'storePromo']);
    Route::post('/api/promos/{id}', [\App\Http\Controllers\PromoController::class, 'updatePromo']);
    Route::delete('/api/promos/{id}', [\App\Http\Controllers\PromoController::class, 'deletePromo']);

    // API Kelola Voucher
    Route::post('/api/vouchers', [\App\Http\Controllers\PromoController::class, 'storeVoucher']);
    Route::post('/api/vouchers/{id}', [\App\Http\Controllers\PromoController::class, 'updateVoucher']);
    Route::delete('/api/vouchers/{id}', [\App\Http\Controllers\PromoController::class, 'deleteVoucher']);

    Route::get('/api/orders/live', [OrderController::class, 'liveOrders']);
    Route::patch('/api/orders/{id}/status', [OrderController::class, 'updateStatus']);

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
});

// Webhook Tokopay
Route::post('/webhook/tokopay', [TokopayWebhookController::class, 'handle'])->name('webhook.tokopay');
Route::get('/simulate-tokopay-payment/{id}', [TokopayWebhookController::class, 'simulateLocalPayment']);

require __DIR__.'/settings.php';
