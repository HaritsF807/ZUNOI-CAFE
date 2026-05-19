<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTableSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('active_table_id')) {
            return redirect()->route('scan.required');
        }

        // Cek jika pesanan terakhir dibuat lebih dari 5 menit (300 detik) yang lalu
        if ($request->session()->has('order_placed_at')) {
            $placedAt = $request->session()->get('order_placed_at');
            if (time() - $placedAt > 300) {
                $request->session()->forget(['active_table_id', 'active_table_name', 'order_placed_at']);
                return redirect()->route('scan.required');
            }
        }

        return $next($request);
    }
}
