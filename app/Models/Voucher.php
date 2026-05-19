<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'discount_type',
        'discount_value',
        'min_purchase',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'min_purchase' => 'float',
        'is_active' => 'boolean',
    ];
}
