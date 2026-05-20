<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role !== 'owner') {
            abort(403, 'Unauthorized access.');
        }

        $now = Carbon::now();

        $monthlyRevenue = Order::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->where('payment_status', 'paid')
            ->sum('total_price');

        $dailyRevenue = Order::whereDate('created_at', $now->toDateString())
            ->where('payment_status', 'paid')
            ->sum('total_price');

        return Inertia::render('Statistics/Index', [
            'monthlyRevenue' => (float) $monthlyRevenue,
            'dailyRevenue' => (float) $dailyRevenue,
        ]);
    }
}
