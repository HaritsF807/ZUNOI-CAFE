<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

// Zunoi Caffe - Customer QR Routes
Route::get('/meja/{secure_token}', [\App\Http\Controllers\TableController::class, 'scanQR'])->name('table.scan');
Route::get('/scan-required', function () {
    return inertia('Customer/ScanRequired');
})->name('scan.required');

Route::middleware(['verify_table_session'])->group(function () {
    Route::get('/order', function () {
        $products = \App\Models\Product::with('category')->get();
        $categories = \App\Models\Category::orderBy('name', 'asc')->get();
        return inertia('Customer/MenuList', [
            'products' => $products,
            'categories' => $categories
        ]);
    })->name('order.index');
    
    Route::get('/checkout', function () {
        $qrisUrl = \App\Models\Setting::getValue('qris_manual_url', 'https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg');
        return inertia('Customer/Cart', [
            'qris_manual_url' => $qrisUrl
        ]);
    })->name('order.checkout');

    Route::post('/order/store', [\App\Http\Controllers\OrderController::class, 'store'])->name('order.store');
    Route::get('/order/success/{id}', [\App\Http\Controllers\OrderController::class, 'success'])->name('order.success');
});

// Endpoint untuk Dashboard
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard/menu', [\App\Http\Controllers\MenuController::class, 'index'])->name('menu.management');
    
    Route::get('/dashboard/tables', function() {
        return inertia('TableManagement');
    })->name('table.management');

    Route::get('/dashboard/menu-preview', function () {
        $products = \App\Models\Product::with('category')->get();
        $categories = \App\Models\Category::orderBy('name', 'asc')->get();
        return inertia('MenuPreview', [
            'products' => $products,
            'categories' => $categories
        ]);
    })->name('menu.preview');

    Route::get('/dashboard/menu-preview/checkout', function () {
        $qrisUrl = \App\Models\Setting::getValue('qris_manual_url', 'https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg');
        return inertia('MenuPreviewCheckout', [
            'qris_manual_url' => $qrisUrl
        ]);
    })->name('menu.preview.checkout');

    Route::get('/dashboard/menu-preview/success', function (\Illuminate\Http\Request $request) {
        return inertia('MenuPreviewSuccess', [
            'customer_name' => $request->query('customer_name'),
            'order_type' => $request->query('order_type'),
            'payment_method' => $request->query('payment_method'),
            'total_price' => $request->query('total_price'),
            'items' => $request->query('items')
        ]);
    })->name('menu.preview.success');

    Route::get('/dashboard/integration', [\App\Http\Controllers\SettingController::class, 'integrationIndex'])->name('integration.setup');
    Route::post('/api/settings', [\App\Http\Controllers\SettingController::class, 'updateSettings'])->name('settings.update');
    
    // API Kelola Menu
    Route::post('/api/products', [\App\Http\Controllers\MenuController::class, 'storeProduct']);
    Route::put('/api/products/{id}', [\App\Http\Controllers\MenuController::class, 'updateProduct']);
    Route::delete('/api/products/{id}', [\App\Http\Controllers\MenuController::class, 'deleteProduct']);
    Route::patch('/api/products/{id}/toggle-availability', [\App\Http\Controllers\MenuController::class, 'toggleProductAvailability']);
    
    Route::post('/api/categories', [\App\Http\Controllers\MenuController::class, 'storeCategory']);
    Route::put('/api/categories/{id}', [\App\Http\Controllers\MenuController::class, 'updateCategory']);
    Route::delete('/api/categories/{id}', [\App\Http\Controllers\MenuController::class, 'deleteCategory']);

    Route::get('/api/orders/live', [\App\Http\Controllers\OrderController::class, 'liveOrders']);
    Route::patch('/api/orders/{id}/status', [\App\Http\Controllers\OrderController::class, 'updateStatus']);
    
    // Manajemen Meja
    Route::get('/api/tables', function() {
        return response()->json(\App\Models\Table::all());
    });
    Route::post('/api/tables', function(\Illuminate\Http\Request $request) {
        $validated = $request->validate(['table_name' => 'required|string|max:50']);
        $token = \Illuminate\Support\Str::random(6); // Generate random 6 char token
        
        $table = \App\Models\Table::create([
            'table_name' => $validated['table_name'],
            'secure_token' => $token
        ]);
        return response()->json($table);
    });
    Route::delete('/api/tables/{id}', function($id) {
        $table = \App\Models\Table::findOrFail($id);
        $table->delete();
        return response()->json(['success' => true]);
    });
});

// Webhook Tokopay
Route::post('/webhook/tokopay', [\App\Http\Controllers\TokopayWebhookController::class, 'handle'])->name('webhook.tokopay');
Route::get('/simulate-tokopay-payment/{id}', [\App\Http\Controllers\TokopayWebhookController::class, 'simulateLocalPayment']);

require __DIR__.'/settings.php';
