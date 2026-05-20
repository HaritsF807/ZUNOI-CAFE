<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'reservation_date',
        'reservation_time',
        'num_guests',
        'notes',
        'preorder_items',
        'order_id',
        'status',
    ];

    protected $casts = [
        'preorder_items' => 'array',
        'reservation_date' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
