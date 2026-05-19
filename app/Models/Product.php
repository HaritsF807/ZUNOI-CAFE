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

    protected $appends = ['additions'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function addons()
    {
        return $this->hasMany(ProductAddon::class);
    }

    public function getAdditionsAttribute()
    {
        return $this->addons->map(function ($addon) {
            return [
                'id' => $addon->id,
                'name' => $addon->addon_name,
                'price' => (int) $addon->extra_price,
                'category' => $addon->category,
            ];
        });
    }
}
