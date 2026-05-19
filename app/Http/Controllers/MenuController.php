<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAddon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    // Mengembalikan halaman Vue Manajemen Menu dengan data produk dan kategori
    public function index()
    {
        $categories = Category::orderBy('name', 'asc')->get();
        $products = Product::with(['category', 'addons'])->orderBy('created_at', 'desc')->get();

        return Inertia::render('MenuManagement', [
            'categories' => $categories,
            'products' => $products
        ]);
    }

    // --- MANAJEMEN KATEGORI ---
    
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name'
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil ditambahkan!',
            'category' => $category
        ]);
    }

    public function updateCategory(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $id
        ]);

        $category = Category::findOrFail($id);
        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui!',
            'category' => $category
        ]);
    }

    public function deleteCategory($id)
    {
        $category = Category::findOrFail($id);
        $category->delete(); // cascades and deletes products in the category if constrained

        return response()->json([
            'success' => true,
            'message' => 'Kategori dan seluruh menunya berhasil dihapus!'
        ]);
    }

    // --- MANAJEMEN PRODUK ---

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:1000',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_available' => 'sometimes|boolean'
        ]);

        $imageUrl = $validated['image'] ?? 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=300&auto=format&fit=crop';

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            
            // Buat direktori jika belum ada
            if (!file_exists(public_path('uploads/products'))) {
                mkdir(public_path('uploads/products'), 0777, true);
            }
            
            $file->move(public_path('uploads/products'), $filename);
            $imageUrl = '/uploads/products/' . $filename;
        }

        $product = Product::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'image' => $imageUrl,
            'is_available' => $validated['is_available'] ?? true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan!',
            'product' => $product
        ]);
    }

    public function updateProduct(Request $request, $id)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:1000',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_available' => 'sometimes|boolean'
        ]);

        $product = Product::findOrFail($id);
        
        $imageUrl = $validated['image'] ?? $product->image;

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            
            // Buat direktori jika belum ada
            if (!file_exists(public_path('uploads/products'))) {
                mkdir(public_path('uploads/products'), 0777, true);
            }
            
            $file->move(public_path('uploads/products'), $filename);
            $imageUrl = '/uploads/products/' . $filename;
        }

        $product->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'image' => $imageUrl,
            'is_available' => $validated['is_available'] ?? true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui!',
            'product' => $product
        ]);
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus!'
        ]);
    }

    public function toggleProductAvailability($id)
    {
        $product = Product::findOrFail($id);
        $product->is_available = !$product->is_available;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Status ketersediaan menu berhasil diperbarui!',
            'is_available' => $product->is_available
        ]);
    }

    // --- MANAJEMEN ADDON PRODUK ---

    public function storeAddon(Request $request, $productId)
    {
        $validated = $request->validate([
            'addon_name' => 'required|string|max:100',
            'extra_price' => 'required|numeric|min:0',
            'category' => 'required|string|max:50'
        ]);

        $product = Product::findOrFail($productId);
        
        $addon = $product->addons()->create([
            'addon_name' => $validated['addon_name'],
            'extra_price' => $validated['extra_price'],
            'category' => $validated['category']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Add-on berhasil ditambahkan!',
            'addon' => [
                'id' => $addon->id,
                'name' => $addon->addon_name,
                'price' => (int) $addon->extra_price,
                'category' => $addon->category
            ]
        ]);
    }

    public function updateAddon(Request $request, $id)
    {
        $validated = $request->validate([
            'addon_name' => 'required|string|max:100',
            'extra_price' => 'required|numeric|min:0',
            'category' => 'required|string|max:50'
        ]);

        $addon = ProductAddon::findOrFail($id);
        $addon->update([
            'addon_name' => $validated['addon_name'],
            'extra_price' => $validated['extra_price'],
            'category' => $validated['category']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Add-on berhasil diperbarui!',
            'addon' => [
                'id' => $addon->id,
                'name' => $addon->addon_name,
                'price' => (int) $addon->extra_price,
                'category' => $addon->category
            ]
        ]);
    }

    public function deleteAddon($id)
    {
        $addon = ProductAddon::findOrFail($id);
        $addon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Add-on berhasil dihapus!'
        ]);
    }

    public function useDefaultAddons($productId)
    {
        $product = Product::findOrFail($productId);
        
        // Hapus addon yang sudah ada untuk menghindari duplikasi
        $product->addons()->delete();
        
        $defaultAddons = [
            ['addon_name' => 'Gula', 'extra_price' => 0, 'category' => 'sugar'],
            ['addon_name' => 'Es Batu', 'extra_price' => 0, 'category' => 'ice'],
            ['addon_name' => 'Whipped Cream', 'extra_price' => 5000, 'category' => 'topping'],
            ['addon_name' => 'Espresso Shot', 'extra_price' => 7000, 'category' => 'topping'],
        ];
        
        foreach ($defaultAddons as $addon) {
            $product->addons()->create($addon);
        }

        // Kembalikan daftar addon terformat
        $addons = $product->addons()->get()->map(function ($a) {
            return [
                'id' => $a->id,
                'name' => $a->addon_name,
                'price' => (int) $a->extra_price,
                'category' => $a->category
            ];
        });
        
        return response()->json([
            'success' => true,
            'message' => 'Template addon standar berhasil diterapkan!',
            'addons' => $addons
        ]);
    }
}
