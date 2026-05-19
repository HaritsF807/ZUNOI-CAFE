<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            'payment_proof' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        if ($validated['payment_method'] === 'qris_manual') {
            $request->validate([
                'payment_proof' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
            ]);
        }

        $tableId = session('active_table_id');
        if (!$tableId) {
            return back()->with('error', 'Sesi meja tidak valid.');
        }

        // Hitung total harga
        $totalPrice = 0;
        foreach ($validated['cart_items'] as $item) {
            $totalPrice += ($item['price'] * $item['quantity']);
        }

        // Upload bukti pembayaran jika ada
        $proofUrl = null;
        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $filename = time() . '_proof_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            if (!file_exists(public_path('uploads/proofs'))) {
                mkdir(public_path('uploads/proofs'), 0777, true);
            }
            
            $file->move(public_path('uploads/proofs'), $filename);
            $proofUrl = '/uploads/proofs/' . $filename;
        }

        // Buat Order Induk
        $order = Order::create([
            'table_id' => $tableId,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'total_price' => $totalPrice,
            'order_type' => $validated['order_type'],
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'payment_proof' => $proofUrl,
        ]);

        // Simpan Item Pesanan
        foreach ($validated['cart_items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['id'],
                'quantity' => $item['quantity'],
                'price_at_sale' => $item['price'],
                'notes' => $item['notes'] ?? null
            ]);
        }

        return redirect()->route('order.success', ['id' => $order->id]);
    }

    // Endpoint API untuk Polling Dashboard Barista
    public function liveOrders()
    {
        $orders = Order::with('table')
            ->whereIn('order_status', ['pending', 'processing'])
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
                    'time' => $order->created_at->format('H:i')
                ];
            });

        return response()->json($orders);
    }
    
    // Memperbarui status pesanan dari dashboard barista
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'order_status' => 'required|in:pending,processing,completed,cancelled',
            'payment_status' => 'sometimes|in:pending,paid'
        ]);

        $order = Order::findOrFail($id);
        
        // Jika status order selesai, set juga status pembayaran menjadi lunas (paid) jika belum lunas
        if ($validated['order_status'] === 'completed') {
            $order->payment_status = 'paid';
        }
        
        $order->order_status = $validated['order_status'];
        if (isset($validated['payment_status'])) {
            $order->payment_status = $validated['payment_status'];
        }
        
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diperbarui!',
            'order' => $order
        ]);
    }

    // Halaman sukses sederhana
    public function success($id)
    {
        $order = Order::with(['items.product', 'table'])->findOrFail($id);
        return inertia('Customer/Success', ['order' => $order]);
    }
}
