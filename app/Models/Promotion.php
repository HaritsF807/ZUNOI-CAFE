<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'buy_product_id',
        'buy_quantity',
        'bundling_product_id',
        'get_product_id',
        'get_quantity',
        'discount_type',
        'discount_value',
        'is_active',
    ];

    protected $casts = [
        'buy_quantity' => 'integer',
        'get_quantity' => 'integer',
        'discount_value' => 'float',
        'is_active' => 'boolean',
    ];

    // Relations
    public function buyProduct()
    {
        return $this->belongsTo(Product::class, 'buy_product_id');
    }

    public function bundlingProduct()
    {
        return $this->belongsTo(Product::class, 'bundling_product_id');
    }

    public function getProduct()
    {
        return $this->belongsTo(Product::class, 'get_product_id');
    }
}
