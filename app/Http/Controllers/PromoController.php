<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PromoController extends Controller
{
    // Render the Promo Management dashboard page
    public function index()
    {
        // Auto-run migrations if promotions table or orders columns don't exist yet
        if (! Schema::hasTable('promotions') || ! Schema::hasColumn('orders', 'promo_discount_amount')) {
            try {
                Artisan::call('migrate --force');
            } catch (\Exception $e) {
                Log::error('Auto-migration failed: '.$e->getMessage());
            }
        }

        $banners = Banner::orderBy('created_at', 'desc')->get();
        $vouchers = Voucher::orderBy('created_at', 'desc')->get();

        $promotions = [];
        if (Schema::hasTable('promotions')) {
            $promotions = Promotion::with(['buyProduct', 'bundlingProduct', 'getProduct'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $products = Product::orderBy('name', 'asc')->get();

        return Inertia::render('PromoManagement', [
            'banners' => $banners,
            'vouchers' => $vouchers,
            'promotions' => $promotions,
            'products' => $products,
        ]);
    }

    // Save a new promo voucher
    public function storeVoucher(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code',
            'name' => 'required|string|max:100',
            'discount_type' => 'required|in:percentage,nominal',
            'discount_value' => 'required|numeric|min:0',
            'min_purchase' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
        ], [
            'code.required' => 'Kode voucher wajib diisi!',
            'code.unique' => 'Kode voucher ini sudah digunakan!',
            'name.required' => 'Nama voucher wajib diisi!',
            'discount_type.required' => 'Tipe potongan wajib dipilih!',
            'discount_value.required' => 'Nilai potongan wajib diisi!',
            'min_purchase.required' => 'Minimal pembelian wajib diisi!',
        ]);

        $voucher = Voucher::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Voucher baru berhasil ditambahkan!',
            'voucher' => $voucher,
        ]);
    }

    // Update an existing promo voucher
    public function updateVoucher(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code,'.$id,
            'name' => 'required|string|max:100',
            'discount_type' => 'required|in:percentage,nominal',
            'discount_value' => 'required|numeric|min:0',
            'min_purchase' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
        ], [
            'code.required' => 'Kode voucher wajib diisi!',
            'code.unique' => 'Kode voucher ini sudah digunakan!',
            'name.required' => 'Nama voucher wajib diisi!',
            'discount_type.required' => 'Tipe potongan wajib dipilih!',
            'discount_value.required' => 'Nilai potongan wajib diisi!',
            'min_purchase.required' => 'Minimal pembelian wajib diisi!',
        ]);

        $voucher->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil diperbarui!',
            'voucher' => $voucher,
        ]);
    }

    // Delete a promo voucher
    public function deleteVoucher($id)
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil dihapus!',
        ]);
    }

    // Validate a promo voucher code
    public function validateVoucher(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric',
        ]);

        $voucher = Voucher::where('code', $request->code)
            ->where('is_active', true)
            ->first();

        if (! $voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher tidak valid atau sudah tidak aktif!',
            ], 422);
        }

        if ($request->subtotal < $voucher->min_purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Minimal pembelian untuk menggunakan voucher ini adalah Rp '.number_format($voucher->min_purchase, 0, ',', '.'),
            ], 422);
        }

        // Calculate discount
        $discount = 0;
        if ($voucher->discount_type === 'percentage') {
            $discount = ($voucher->discount_value / 100) * $request->subtotal;
        } else {
            $discount = $voucher->discount_value;
        }

        // Discount cannot exceed subtotal
        if ($discount > $request->subtotal) {
            $discount = $request->subtotal;
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil diterapkan!',
            'voucher' => $voucher,
            'discount_amount' => (float) $discount,
        ]);
    }

    // Save a new promo banner
    public function storePromo(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'image_data' => 'nullable|string', // Base64 cropped image
            'image_file' => 'nullable|image|max:5120', // Raw fallback file upload
        ]);

        $imageUrl = null;

        // Process Base64 cropped image first
        if (! empty($validated['image_data'])) {
            $imageData = $validated['image_data'];

            // Extract file extension and base64 string
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageDecoded = substr($imageData, strpos($imageData, ',') + 1);
                $imageDecoded = base64_decode($imageDecoded);

                $ext = strtolower($type[1]); // png, jpeg, webp, etc.
                if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $ext = 'jpg';
                }

                $filename = time().'_'.Str::random(10).'.'.$ext;

                if (! file_exists(public_path('uploads/promos'))) {
                    mkdir(public_path('uploads/promos'), 0755, true);
                }

                file_put_contents(public_path('uploads/promos/'.$filename), $imageDecoded);
                $imageUrl = '/uploads/promos/'.$filename;
            }
        }
        // Fallback to raw file upload if no base64 was sent
        elseif ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $filename = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();

            if (! file_exists(public_path('uploads/promos'))) {
                mkdir(public_path('uploads/promos'), 0755, true);
            }

            $file->move(public_path('uploads/promos'), $filename);
            $imageUrl = '/uploads/promos/'.$filename;
        }

        if (! $imageUrl) {
            return response()->json([
                'success' => false,
                'message' => 'Gambar banner promo wajib diunggah!',
            ], 422);
        }

        $banner = Banner::create([
            'image_url' => $imageUrl,
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Promo baru berhasil ditambahkan!',
            'banner' => $banner,
        ]);
    }

    // Update an existing promo banner
    public function updatePromo(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $validated = $request->validate([
            'title' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'image_data' => 'nullable|string', // Base64 cropped image
            'image_file' => 'nullable|image|max:5120',
        ]);

        $imageUrl = $banner->image_url;

        // Process Base64 cropped image first
        if (! empty($validated['image_data'])) {
            $imageData = $validated['image_data'];

            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageDecoded = substr($imageData, strpos($imageData, ',') + 1);
                $imageDecoded = base64_decode($imageDecoded);

                $ext = strtolower($type[1]);
                if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $ext = 'jpg';
                }

                $filename = time().'_'.Str::random(10).'.'.$ext;

                if (! file_exists(public_path('uploads/promos'))) {
                    mkdir(public_path('uploads/promos'), 0755, true);
                }

                file_put_contents(public_path('uploads/promos/'.$filename), $imageDecoded);

                // Delete old image file
                $oldPath = public_path($banner->image_url);
                if (file_exists($oldPath) && is_file($oldPath)) {
                    @unlink($oldPath);
                }

                $imageUrl = '/uploads/promos/'.$filename;
            }
        }
        // Fallback to raw file upload
        elseif ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $filename = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();

            if (! file_exists(public_path('uploads/promos'))) {
                mkdir(public_path('uploads/promos'), 0755, true);
            }

            $file->move(public_path('uploads/promos'), $filename);

            // Delete old image file
            $oldPath = public_path($banner->image_url);
            if (file_exists($oldPath) && is_file($oldPath)) {
                @unlink($oldPath);
            }

            $imageUrl = '/uploads/promos/'.$filename;
        }

        $banner->update([
            'image_url' => $imageUrl,
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil diperbarui!',
            'banner' => $banner,
        ]);
    }

    // Delete a promo banner
    public function deletePromo($id)
    {
        $banner = Banner::findOrFail($id);

        // Delete image file
        $imagePath = public_path($banner->image_url);
        if (file_exists($imagePath) && is_file($imagePath)) {
            @unlink($imagePath);
        }

        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil dihapus!',
        ]);
    }

    // Save a new promo deal (bundling or buy get)
    public function storePromotion(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:bundling,buy_get',
            'buy_product_id' => 'required|exists:products,id',
            'buy_quantity' => 'required|integer|min:1',
            'bundling_product_id' => 'required_if:type,bundling|nullable|exists:products,id',
            'get_product_id' => 'required_if:type,buy_get|nullable|exists:products,id',
            'get_quantity' => 'required_if:type,buy_get|nullable|integer|min:1',
            'discount_type' => 'required|in:percentage,nominal,free',
            'discount_value' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
        ], [
            'name.required' => 'Nama promo wajib diisi!',
            'buy_product_id.required' => 'Produk utama wajib dipilih!',
            'bundling_product_id.required_if' => 'Produk bundling wajib dipilih!',
            'get_product_id.required_if' => 'Produk bonus wajib dipilih!',
            'discount_type.required' => 'Tipe potongan wajib dipilih!',
            'discount_value.required' => 'Nilai potongan wajib diisi!',
        ]);

        $promotion = Promotion::create($validated);

        // Load relationships
        $promotion->load(['buyProduct', 'bundlingProduct', 'getProduct']);

        return response()->json([
            'success' => true,
            'message' => 'Promo baru berhasil ditambahkan!',
            'promotion' => $promotion,
        ]);
    }

    // Update an existing promo deal
    public function updatePromotion(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:bundling,buy_get',
            'buy_product_id' => 'required|exists:products,id',
            'buy_quantity' => 'required|integer|min:1',
            'bundling_product_id' => 'required_if:type,bundling|nullable|exists:products,id',
            'get_product_id' => 'required_if:type,buy_get|nullable|exists:products,id',
            'get_quantity' => 'required_if:type,buy_get|nullable|integer|min:1',
            'discount_type' => 'required|in:percentage,nominal,free',
            'discount_value' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
        ], [
            'name.required' => 'Nama promo wajib diisi!',
            'buy_product_id.required' => 'Produk utama wajib dipilih!',
            'bundling_product_id.required_if' => 'Produk bundling wajib dipilih!',
            'get_product_id.required_if' => 'Produk bonus wajib dipilih!',
            'discount_type.required' => 'Tipe potongan wajib dipilih!',
            'discount_value.required' => 'Nilai potongan wajib diisi!',
        ]);

        $promotion->update($validated);

        // Load relationships
        $promotion->load(['buyProduct', 'bundlingProduct', 'getProduct']);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil diperbarui!',
            'promotion' => $promotion,
        ]);
    }

    // Delete a promo deal
    public function deletePromotion($id)
    {
        $promotion = Promotion::findOrFail($id);
        $promotion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil dihapus!',
        ]);
    }
}
