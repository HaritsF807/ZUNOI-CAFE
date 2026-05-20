<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAddon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MenuController extends Controller
{
    // Mengembalikan halaman Vue Manajemen Menu dengan data produk dan kategori
    public function index()
    {
        $categories = Category::orderBy('name', 'asc')->get();
        $products = Product::with(['category', 'assignedAddons'])->orderBy('created_at', 'desc')->get()->map(function($p) {
            return [
                'id' => $p->id,
                'category_id' => $p->category_id,
                'name' => $p->name,
                'price' => (int) $p->price,
                'description' => $p->description,
                'image' => $p->image,
                'is_available' => (bool) $p->is_available,
                'category' => $p->category,
                'assigned_addons' => $p->assignedAddons->map(function($a) {
                    return [
                        'id' => $a->id,
                        'name' => $a->addon_name,
                        'price' => (int) $a->extra_price,
                    ];
                })
            ];
        });
        $addons = ProductAddon::orderBy('addon_name', 'asc')->get()->map(function($a) {
            return [
                'id' => $a->id,
                'name' => $a->addon_name,
                'price' => (int) $a->extra_price,
                'category' => $a->category
            ];
        });

        return Inertia::render('MenuManagement', [
            'categories' => $categories,
            'products' => $products,
            'globalAddons' => $addons,
        ]);
    }

    /**
     * Menghubungkan addon-addon global ke produk spesifik.
     */
    public function syncProductAddons(Request $request, $id)
    {
        $validated = $request->validate([
            'addon_ids' => 'required|array',
            'addon_ids.*' => 'exists:product_addons,id'
        ]);

        $product = Product::findOrFail($id);
        $product->assignedAddons()->sync($validated['addon_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Add-ons berhasil dipasangkan ke menu!',
            'assigned_addons' => $product->assignedAddons->map(function($a) {
                return [
                    'id' => $a->id,
                    'name' => $a->addon_name,
                    'price' => (int) $a->extra_price,
                ];
            })
        ]);
    }

    // --- MANAJEMEN KATEGORI ---

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil ditambahkan!',
            'category' => $category,
        ]);
    }

    public function updateCategory(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,'.$id,
        ]);

        $category = Category::findOrFail($id);
        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui!',
            'category' => $category,
        ]);
    }

    public function deleteCategory($id)
    {
        $category = Category::findOrFail($id);
        $category->delete(); // cascades and deletes products in the category if constrained

        return response()->json([
            'success' => true,
            'message' => 'Kategori dan seluruh menunya berhasil dihapus!',
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
            'is_available' => 'sometimes|boolean',
        ]);

        $imageUrl = $validated['image'] ?? 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=300&auto=format&fit=crop';

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $filename = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();

            // Buat direktori jika belum ada
            if (! file_exists(public_path('uploads/products'))) {
                mkdir(public_path('uploads/products'), 0777, true);
            }

            $file->move(public_path('uploads/products'), $filename);
            $imageUrl = '/uploads/products/'.$filename;
        }

        $product = Product::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'image' => $imageUrl,
            'is_available' => $validated['is_available'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan!',
            'product' => $product,
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
            'is_available' => 'sometimes|boolean',
        ]);

        $product = Product::findOrFail($id);

        $imageUrl = $validated['image'] ?? $product->image;

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $filename = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();

            // Buat direktori jika belum ada
            if (! file_exists(public_path('uploads/products'))) {
                mkdir(public_path('uploads/products'), 0777, true);
            }

            $file->move(public_path('uploads/products'), $filename);
            $imageUrl = '/uploads/products/'.$filename;
        }

        $product->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'image' => $imageUrl,
            'is_available' => $validated['is_available'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui!',
            'product' => $product,
        ]);
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus!',
        ]);
    }

    public function toggleProductAvailability($id)
    {
        $product = Product::findOrFail($id);
        $product->is_available = ! $product->is_available;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Status ketersediaan menu berhasil diperbarui!',
            'is_available' => $product->is_available,
        ]);
    }

    // --- MANAJEMEN ADDON PRODUK ---

    public function storeAddon(Request $request)
    {
        $validated = $request->validate([
            'addon_name' => 'required|string|max:100',
            'extra_price' => 'required|numeric|min:0',
            'category' => 'required|string|max:50'
        ]);

        $addon = ProductAddon::create([
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

    // useDefaultAddons removed since it's global now
}
