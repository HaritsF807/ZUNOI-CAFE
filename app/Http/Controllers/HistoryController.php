<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items.product', 'items.addons', 'table'])->orderBy('created_at', 'desc');

        // Apply Date Filters
        if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Pagination
        $orders = $query->paginate(15)->withQueryString();

        // Transform collection to format items summary
        $orders->getCollection()->transform(function ($order) {
            $itemsSummary = $order->items->map(function ($item) {
                return $item->quantity . 'x ' . ($item->product ? $item->product->name : 'Unknown');
            })->implode(', ');

            return [
                'id' => $order->id,
                'secure_key' => $order->secure_key,
                'customer_name' => $order->customer_name ?: ($order->table ? 'Meja ' . $order->table->table_number : 'Takeaway/Online'),
                'order_type' => $order->order_type,
                'items_summary' => $itemsSummary,
                'items' => $order->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->product ? $item->product->name : 'Unknown',
                        'quantity' => $item->quantity,
                        'price_at_sale' => (float) $item->price_at_sale,
                        'notes' => $item->notes,
                        'addons' => $item->addons->map(function($addon) {
                            return [
                                'name' => $addon->addon_name,
                                'price' => (float) $addon->addon_price
                            ];
                        })->toArray()
                    ];
                })->toArray(),
                'discount_amount' => (float) $order->discount_amount,
                'promo_discount_amount' => (float) $order->promo_discount_amount,
                'total_price' => (float) $order->total_price,
                'payment_method' => $order->payment_method ? strtoupper($order->payment_method) : '-',
                'payment_status' => $order->payment_status,
                'payment_proof' => $order->payment_proof,
                'order_status' => $order->order_status,
                'notes' => $order->notes,
                'created_at' => $order->created_at->format('Y-m-d H:i'),
                'table' => $order->table,
            ];
        });

        return Inertia::render('History/Index', [
            'orders' => $orders,
            'filters' => $request->only(['start_date', 'end_date'])
        ]);
    }
}
