<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Table;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAddon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Staff & Owner
        User::create([
            'name' => 'Owner Zunoi',
            'email' => 'owner@zunoi.id',
            'password' => Hash::make('password'),
            'role' => 'owner',
        ]);

        User::create([
            'name' => 'Barista Andalan',
            'email' => 'barista@zunoi.id',
            'password' => Hash::make('password'),
            'role' => 'barista',
        ]);

        // 2. Data Meja (dengan Secure Token)
        Table::create([
            'table_name' => 'Meja 01',
            'secure_token' => 'x7R9wK' // URL: /meja/x7R9wK
        ]);
        
        Table::create([
            'table_name' => 'Meja 02',
            'secure_token' => 'bA2f9i' // URL: /meja/bA2f9i
        ]);

        // 3. Kategori Produk
        $catCoffee = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $catNonCoffee = Category::create(['name' => 'Non-Coffee', 'slug' => 'non-coffee']);
        $catSnack = Category::create(['name' => 'Snacks', 'slug' => 'snacks']);

        // 4. Produk Utama
        $latte = Product::create([
            'category_id' => $catCoffee->id,
            'name' => 'Zunoi Signature Latte',
            'description' => 'Kopi susu gula aren racikan khas Zunoi dengan espresso blend premium.',
            'price' => 22000,
            'image' => 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=400&q=80',
            'is_available' => true,
        ]);

        Product::create([
            'category_id' => $catCoffee->id,
            'name' => 'Americano',
            'description' => 'Double shot espresso dengan air murni.',
            'price' => 15000,
            'image' => 'https://images.unsplash.com/photo-1510591509098-f4fdc6d0ff04?auto=format&fit=crop&w=400&q=80',
            'is_available' => true,
        ]);

        Product::create([
            'category_id' => $catCoffee->id,
            'name' => 'Cappuccino',
            'description' => 'Espresso dengan foam susu tebal dan taburan cokelat.',
            'price' => 20000,
            'image' => 'https://images.unsplash.com/photo-1534778101976-62847782c213?auto=format&fit=crop&w=400&q=80',
            'is_available' => true,
        ]);

        Product::create([
            'category_id' => $catCoffee->id,
            'name' => 'Caramel Macchiato',
            'description' => 'Susu vanilla dengan sirup karamel dan layer espresso.',
            'price' => 24000,
            'image' => 'https://images.unsplash.com/photo-1485808191679-5f86510681a2?auto=format&fit=crop&w=400&q=80',
            'is_available' => true,
        ]);

        Product::create([
            'category_id' => $catNonCoffee->id,
            'name' => 'Red Velvet Latte',
            'description' => 'Minuman cokelat merah velvety dengan susu segar.',
            'price' => 23000,
            'image' => 'https://images.unsplash.com/photo-1611162458324-aae1eb4129a4?auto=format&fit=crop&w=400&q=80',
            'is_available' => true,
        ]);

        Product::create([
            'category_id' => $catNonCoffee->id,
            'name' => 'Premium Matcha',
            'description' => 'Matcha asli Jepang dipadukan dengan susu segar.',
            'price' => 25000,
            'image' => 'https://images.unsplash.com/photo-1515823662972-da6a2e4d3002?auto=format&fit=crop&w=400&q=80',
            'is_available' => false, // Sengaja dibuat habis untuk tes UI
        ]);

        Product::create([
            'category_id' => $catSnack->id,
            'name' => 'Butter Croissant',
            'description' => 'Pastry renyah dengan rasa mentega premium.',
            'price' => 18000,
            'image' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=400&q=80',
            'is_available' => true,
        ]);

        Product::create([
            'category_id' => $catSnack->id,
            'name' => 'Fudgy Brownie',
            'description' => 'Brownies padat dengan potongan cokelat asli.',
            'price' => 15000,
            'image' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=400&q=80',
            'is_available' => true,
        ]);

        // 5. Product Add-ons (Ekstra/Topping)
        ProductAddon::create([
            'product_id' => $latte->id,
            'addon_name' => 'Extra Shot Espresso',
            'extra_price' => 5000,
            'category' => 'coffee_extra'
        ]);

        ProductAddon::create([
            'product_id' => $latte->id,
            'addon_name' => 'Oat Milk',
            'extra_price' => 8000,
            'category' => 'milk_type'
        ]);
    }
}
