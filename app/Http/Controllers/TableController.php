<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function scanQR($secure_token, Request $request)
    {
        $table = Table::where('secure_token', $secure_token)->first();

        if (! $table) {
            abort(404, 'Meja tidak ditemukan atau QR code tidak valid.');
        }

        // Set session
        $request->session()->put('active_table_id', $table->id);
        $request->session()->put('active_table_name', $table->table_name);
        $request->session()->forget('order_placed_at');

        // Redirect to order page with clean URL
        return redirect()->route('order.index');
    }
}
