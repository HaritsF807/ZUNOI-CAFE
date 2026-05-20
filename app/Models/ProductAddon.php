<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAddon extends Model
{
    use HasFactory;

    protected $fillable = ['addon_name', 'extra_price', 'category'];

    /**
     * Produk-produk yang menggunakan add-on ini (many-to-many via pivot).
     */
    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'product_addon_assignments',
            'product_addon_id',
            'product_id'
        )->withTimestamps();
    }
}
