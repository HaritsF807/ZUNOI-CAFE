<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\Setting;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 0. Clean current data
        Schema::disableForeignKeyConstraints();
        Product::truncate();
        Category::truncate();
        ProductAddon::truncate();
        if (Schema::hasTable('product_addon_assignments')) {
            DB::table('product_addon_assignments')->truncate();
        }
        User::truncate();
        Table::truncate();
        Setting::truncate();
        Schema::enableForeignKeyConstraints();

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

        // 2. Data Meja
        Table::create([
            'table_name' => 'Meja 01',
            'secure_token' => 'x7R9wK',
        ]);

        Table::create([
            'table_name' => 'Meja 02',
            'secure_token' => 'bA2f9i',
        ]);

        Table::create([
            'table_name' => 'Meja 03',
            'secure_token' => 'z3N8oI',
        ]);

        // 3. Kategori Produk
        $catCoffee = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $catNonCoffee = Category::create(['name' => 'Non-Coffee', 'slug' => 'non-coffee']);
        $catSnack = Category::create(['name' => 'Snacks', 'slug' => 'snacks']);

        // 4. Global Addons
        $addons = [
            ['addon_name' => 'Gula Aren', 'extra_price' => 3000, 'category' => 'sugar'],
            ['addon_name' => 'Susu Cair', 'extra_price' => 5000, 'category' => 'milk'],
            ['addon_name' => 'Oat Milk', 'extra_price' => 8000, 'category' => 'milk'],
            ['addon_name' => 'Espresso Shot', 'extra_price' => 7000, 'category' => 'topping'],
            ['addon_name' => 'Whipped Cream', 'extra_price' => 5000, 'category' => 'topping'],
            ['addon_name' => 'Caramel Sauce', 'extra_price' => 4000, 'category' => 'topping'],
            ['addon_name' => 'Ice Cream Scoop', 'extra_price' => 6000, 'category' => 'topping'],
        ];

        $addonModels = [];
        foreach ($addons as $addonData) {
            $addonModels[] = ProductAddon::create($addonData);
        }

        // 5. Produk Utama
        $products = [
            [
                'category_id' => $catCoffee->id,
                'name' => 'Zunoi Signature Latte',
                'description' => 'Kopi susu gula aren racikan khas Zunoi dengan espresso blend premium.',
                'price' => 22000,
                'image' => 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=400&q=80',
                'is_available' => true,
            ],
            [
                'category_id' => $catCoffee->id,
                'name' => 'Americano',
                'description' => 'Double shot espresso dengan air murni.',
                'price' => 15000,
                'image' => 'https://images.unsplash.com/photo-1510591509098-f4fdc6d0ff04?auto=format&fit=crop&w=400&q=80',
                'is_available' => true,
            ],
            [
                'category_id' => $catCoffee->id,
                'name' => 'Cappuccino',
                'description' => 'Espresso dengan foam susu tebal dan taburan cokelat.',
                'price' => 20000,
                'image' => 'https://images.unsplash.com/photo-1534778101976-62847782c213?auto=format&fit=crop&w=400&q=80',
                'is_available' => true,
            ],
            [
                'category_id' => $catNonCoffee->id,
                'name' => 'Red Velvet Latte',
                'description' => 'Minuman cokelat merah velvety dengan susu segar.',
                'price' => 23000,
                'image' => 'https://images.unsplash.com/photo-1611162458324-aae1eb4129a4?auto=format&fit=crop&w=400&q=80',
                'is_available' => true,
            ],
            [
                'category_id' => $catNonCoffee->id,
                'name' => 'Premium Matcha',
                'description' => 'Matcha asli Jepang dipadukan dengan susu segar.',
                'price' => 25000,
                'image' => 'https://images.unsplash.com/photo-1515823662972-da6a2e4d3002?auto=format&fit=crop&w=400&q=80',
                'is_available' => true,
            ],
            [
                'category_id' => $catSnack->id,
                'name' => 'Butter Croissant',
                'description' => 'Pastry renyah dengan rasa mentega premium.',
                'price' => 18000,
                'image' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=400&q=80',
                'is_available' => true,
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::create($productData);

            // Assign some addons to each product using many-to-many
            if (Schema::hasTable('product_addon_assignments')) {
                if (in_array($product->category_id, [$catCoffee->id, $catNonCoffee->id])) {
                    $product->assignedAddons()->sync(collect($addonModels)->pluck('id')->random(rand(3, 5)));
                } else {
                    $snackAddons = collect($addonModels)->where('category', 'topping')->pluck('id');
                    if ($snackAddons->isNotEmpty()) {
                        $product->assignedAddons()->sync($snackAddons->random(rand(1, 2)));
                    }
                }
            }
        }

        // 6. Default Settings
        Setting::create(['key' => 'qris_manual_url', 'value' => 'https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg']);
        Setting::create(['key' => 'fonnte_token', 'value' => 'TokenFonnteAnda123']);
        Setting::create(['key' => 'tokopay_merchant_id', 'value' => 'M-123456']);
        Setting::create(['key' => 'tokopay_secret', 'value' => 'SecretKey...']);
    }
}
