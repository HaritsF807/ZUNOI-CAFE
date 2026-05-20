<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'name', 'description', 'price', 'image', 'is_available'];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Add-ons yang di-assign ke produk ini (many-to-many via pivot).
     */
    public function assignedAddons()
    {
        return $this->belongsToMany(
            ProductAddon::class,
            'product_addon_assignments',
            'product_id',
            'product_addon_id'
        )->withTimestamps();
    }
}
