<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_id', 'secure_key', 'customer_name', 'customer_phone', 'total_price',
        'order_type', 'payment_method', 'payment_status', 'order_status', 'payment_proof', 'notes',
        'voucher_code', 'discount_amount', 'promo_discount_amount',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->secure_key = Str::random(16); // Generate 16 char unique secure key
        });
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
