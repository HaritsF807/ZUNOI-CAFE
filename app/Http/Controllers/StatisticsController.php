<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddon;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role !== 'owner') {
            abort(403, 'Unauthorized access.');
        }

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        $yesterday = $now->copy()->subDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $startOfLastWeek = $now->copy()->subWeek()->startOfWeek();
        $endOfLastWeek = $now->copy()->subWeek()->endOfWeek();

        // --- Performance Mode ---
        
        // Revenues
        $monthlyRevenue = Order::whereBetween('created_at', [$startOfMonth, $now])
            ->where('payment_status', 'paid')
            ->sum('total_price');

        $dailyRevenue = Order::whereDate('created_at', $now->toDateString())
            ->where('payment_status', 'paid')
            ->sum('total_price');

        $yesterdayRevenue = Order::whereDate('created_at', $yesterday->toDateString())
            ->where('payment_status', 'paid')
            ->sum('total_price');

        $thisWeekRevenue = Order::whereBetween('created_at', [$startOfWeek, $now])
            ->where('payment_status', 'paid')
            ->sum('total_price');

        $lastWeekRevenue = Order::whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])
            ->where('payment_status', 'paid')
            ->sum('total_price');

        $lastMonthRevenue = Order::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->where('payment_status', 'paid')
            ->sum('total_price');

        // Orders
        $totalOrders = Order::whereBetween('created_at', [$startOfMonth, $now])
            ->where('payment_status', 'paid')
            ->count();
            
        $lastMonthOrders = Order::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->where('payment_status', 'paid')
            ->count();

        $dailyOrders = Order::whereDate('created_at', $now->toDateString())
            ->where('payment_status', 'paid')
            ->count();

        $yesterdayOrders = Order::whereDate('created_at', $yesterday->toDateString())
            ->where('payment_status', 'paid')
            ->count();

        $thisWeekOrders = Order::whereBetween('created_at', [$startOfWeek, $now])
            ->where('payment_status', 'paid')
            ->count();

        $lastWeekOrders = Order::whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])
            ->where('payment_status', 'paid')
            ->count();

        // Chart Data (Harian)
        $todaysOrders = Order::whereDate('created_at', $now->toDateString())
            ->where('payment_status', 'paid')
            ->get();
            
        $harianLabels = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'];
        $harianData = array_fill(0, count($harianLabels), 0);
        
        foreach ($todaysOrders as $order) {
            $hour = $order->created_at->hour;
            if ($hour >= 8 && $hour <= 22) {
                $binIndex = floor(($hour - 8) / 2);
                if (isset($harianData[$binIndex])) {
                    $harianData[$binIndex] += $order->total_price;
                }
            }
        }

        // Chart Data (Mingguan)
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        
        $thisWeekChartOrders = Order::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->where('payment_status', 'paid')
            ->get();
            
        $mingguanLabels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $mingguanData = array_fill(0, 7, 0);
        
        foreach ($thisWeekChartOrders as $order) {
            $dayOfWeek = $order->created_at->dayOfWeekIso - 1; 
            $mingguanData[$dayOfWeek] += $order->total_price;
        }

        // Chart Data (Bulanan)
        $thisMonthOrders = Order::whereBetween('created_at', [$startOfMonth, $now])
            ->where('payment_status', 'paid')
            ->get();
            
        $bulananLabels = ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4', 'Minggu 5'];
        $bulananData = array_fill(0, 5, 0);
        
        foreach ($thisMonthOrders as $order) {
            $weekOfMonth = ceil($order->created_at->day / 7) - 1;
            if (isset($bulananData[$weekOfMonth])) {
                $bulananData[$weekOfMonth] += $order->total_price;
            }
        }

        // --- Menu Analysis Mode ---
        
        // Top Products
        $topProducts = OrderItem::join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startOfMonth, $now])
            ->where('orders.payment_status', 'paid')
            ->select('products.id', 'products.name', 'categories.name as category', DB::raw('SUM(order_items.quantity) as sales'))
            ->groupBy('products.id', 'products.name', 'categories.name')
            ->orderByDesc('sales')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category,
                    'sales' => (int) $item->sales,
                ];
            });

        // Slow Movers
        $slowMovers = OrderItem::join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startOfMonth, $now])
            ->where('orders.payment_status', 'paid')
            ->select('products.id', 'products.name', 'categories.name as category', DB::raw('SUM(order_items.quantity) as sales'))
            ->groupBy('products.id', 'products.name', 'categories.name')
            ->orderBy('sales')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $suggestion = 'Evaluasi rasa / harga';
                if ($item->category === 'Makanan') {
                    $suggestion = 'Pertimbangkan diskon / paket bundle';
                }
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category,
                    'sales' => (int) $item->sales,
                    'suggestion' => $suggestion,
                ];
            });

        // Add-ons Popularity
        $addonsRaw = OrderItemAddon::join('order_items', 'order_item_addons.order_item_id', '=', 'order_items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startOfMonth, $now])
            ->where('orders.payment_status', 'paid')
            ->select('order_item_addons.addon_name', DB::raw('COUNT(*) as sales'))
            ->groupBy('order_item_addons.addon_name')
            ->orderByDesc('sales')
            ->limit(5)
            ->get();
            
        $addonsLabels = $addonsRaw->pluck('addon_name')->toArray();
        $addonsSeries = $addonsRaw->pluck('sales')->map(fn($s) => (int)$s)->toArray();
        
        $addonsData = [
            'labels' => empty($addonsLabels) ? ['Belum ada data'] : $addonsLabels,
            'series' => empty($addonsSeries) ? [0] : $addonsSeries,
        ];

        // --- Operations Mode ---
        
        $toPercentages = function($series) {
            $total = array_sum($series);
            if ($total == 0) return $series;
            return array_map(fn($v) => round(($v / $total) * 100, 1), $series);
        };

        // Category Sales
        $categorySales = OrderItem::join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startOfMonth, $now])
            ->where('orders.payment_status', 'paid')
            ->select('categories.name', DB::raw('SUM(order_items.quantity * order_items.price_at_sale) as total_revenue'))
            ->groupBy('categories.name')
            ->get();
            
        $categoryLabels = $categorySales->pluck('name')->toArray();
        $categorySeries = $toPercentages($categorySales->pluck('total_revenue')->map(fn($s) => (float)$s)->toArray());

        // Payment Methods
        $paymentMethods = Order::whereBetween('created_at', [$startOfMonth, $now])
            ->where('payment_status', 'paid')
            ->select('payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();
            
        $paymentLabels = $paymentMethods->pluck('payment_method')->map(fn($m) => strtoupper($m))->toArray();
        $paymentSeries = $toPercentages($paymentMethods->pluck('count')->map(fn($c) => (int)$c)->toArray());

        // Order Types
        $orderTypes = Order::whereBetween('created_at', [$startOfMonth, $now])
            ->where('payment_status', 'paid')
            ->select('order_type', DB::raw('COUNT(*) as count'))
            ->groupBy('order_type')
            ->get();
            
        $orderTypeLabels = $orderTypes->pluck('order_type')->map(fn($t) => ucfirst($t))->toArray();
        $orderTypeSeries = $toPercentages($orderTypes->pluck('count')->map(fn($c) => (int)$c)->toArray());

        return Inertia::render('Statistics/Index', [
            'monthlyRevenue' => (float) $monthlyRevenue,
            'dailyRevenue' => (float) $dailyRevenue,
            'lastMonthRevenue' => (float) $lastMonthRevenue,
            'yesterdayRevenue' => (float) $yesterdayRevenue,
            'thisWeekRevenue' => (float) $thisWeekRevenue,
            'lastWeekRevenue' => (float) $lastWeekRevenue,
            'totalOrders' => (int) $totalOrders,
            'lastMonthOrders' => (int) $lastMonthOrders,
            'dailyOrders' => (int) $dailyOrders,
            'yesterdayOrders' => (int) $yesterdayOrders,
            'thisWeekOrders' => (int) $thisWeekOrders,
            'lastWeekOrders' => (int) $lastWeekOrders,
            'harianData' => [
                'labels' => $harianLabels,
                'series' => $harianData,
            ],
            'mingguanData' => [
                'labels' => $mingguanLabels,
                'series' => $mingguanData,
            ],
            'bulananData' => [
                'labels' => $bulananLabels,
                'series' => $bulananData,
            ],
            'topProducts' => $topProducts,
            'slowMovers' => $slowMovers,
            'addonsData' => $addonsData,
            'operationsData' => [
                'category' => [
                    'labels' => empty($categoryLabels) ? ['Belum ada data'] : $categoryLabels,
                    'series' => empty($categorySeries) ? [0] : $categorySeries,
                ],
                'payment' => [
                    'labels' => empty($paymentLabels) ? ['Belum ada data'] : $paymentLabels,
                    'series' => empty($paymentSeries) ? [0] : $paymentSeries,
                ],
                'orderType' => [
                    'labels' => empty($orderTypeLabels) ? ['Belum ada data'] : $orderTypeLabels,
                    'series' => empty($orderTypeSeries) ? [0] : $orderTypeSeries,
                ]
            ]
        ]);
    }
}
