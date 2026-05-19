<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class PromoController extends Controller
{
    // Render the Promo Management dashboard page
    public function index()
    {
        $banners = Banner::orderBy('created_at', 'desc')->get();
        $vouchers = \App\Models\Voucher::orderBy('created_at', 'desc')->get();

        return Inertia::render('PromoManagement', [
            'banners' => $banners,
            'vouchers' => $vouchers
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

        $voucher = \App\Models\Voucher::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Voucher baru berhasil ditambahkan!',
            'voucher' => $voucher
        ]);
    }

    // Update an existing promo voucher
    public function updateVoucher(Request $request, $id)
    {
        $voucher = \App\Models\Voucher::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code,' . $id,
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
            'voucher' => $voucher
        ]);
    }

    // Delete a promo voucher
    public function deleteVoucher($id)
    {
        $voucher = \App\Models\Voucher::findOrFail($id);
        $voucher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil dihapus!'
        ]);
    }

    // Validate a promo voucher code
    public function validateVoucher(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric'
        ]);

        $voucher = \App\Models\Voucher::where('code', $request->code)
            ->where('is_active', true)
            ->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher tidak valid atau sudah tidak aktif!'
            ], 422);
        }

        if ($request->subtotal < $voucher->min_purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Minimal pembelian untuk menggunakan voucher ini adalah Rp ' . number_format($voucher->min_purchase, 0, ',', '.')
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
            'discount_amount' => (float)$discount
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
            'image_file' => 'nullable|image|max:5120' // Raw fallback file upload
        ]);

        $imageUrl = null;

        // Process Base64 cropped image first
        if (!empty($validated['image_data'])) {
            $imageData = $validated['image_data'];
            
            // Extract file extension and base64 string
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageDecoded = substr($imageData, strpos($imageData, ',') + 1);
                $imageDecoded = base64_decode($imageDecoded);
                
                $ext = strtolower($type[1]); // png, jpeg, webp, etc.
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $ext = 'jpg';
                }
                
                $filename = time() . '_' . Str::random(10) . '.' . $ext;
                
                if (!file_exists(public_path('uploads/promos'))) {
                    mkdir(public_path('uploads/promos'), 0755, true);
                }
                
                file_put_contents(public_path('uploads/promos/' . $filename), $imageDecoded);
                $imageUrl = '/uploads/promos/' . $filename;
            }
        } 
        // Fallback to raw file upload if no base64 was sent
        elseif ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            
            if (!file_exists(public_path('uploads/promos'))) {
                mkdir(public_path('uploads/promos'), 0755, true);
            }
            
            $file->move(public_path('uploads/promos'), $filename);
            $imageUrl = '/uploads/promos/' . $filename;
        }

        if (!$imageUrl) {
            return response()->json([
                'success' => false,
                'message' => 'Gambar banner promo wajib diunggah!'
            ], 422);
        }

        $banner = Banner::create([
            'image_url' => $imageUrl,
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Promo baru berhasil ditambahkan!',
            'banner' => $banner
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
            'image_file' => 'nullable|image|max:5120'
        ]);

        $imageUrl = $banner->image_url;

        // Process Base64 cropped image first
        if (!empty($validated['image_data'])) {
            $imageData = $validated['image_data'];
            
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageDecoded = substr($imageData, strpos($imageData, ',') + 1);
                $imageDecoded = base64_decode($imageDecoded);
                
                $ext = strtolower($type[1]);
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $ext = 'jpg';
                }
                
                $filename = time() . '_' . Str::random(10) . '.' . $ext;
                
                if (!file_exists(public_path('uploads/promos'))) {
                    mkdir(public_path('uploads/promos'), 0755, true);
                }
                
                file_put_contents(public_path('uploads/promos/' . $filename), $imageDecoded);
                
                // Delete old image file
                $oldPath = public_path($banner->image_url);
                if (file_exists($oldPath) && is_file($oldPath)) {
                    @unlink($oldPath);
                }
                
                $imageUrl = '/uploads/promos/' . $filename;
            }
        } 
        // Fallback to raw file upload
        elseif ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            
            if (!file_exists(public_path('uploads/promos'))) {
                mkdir(public_path('uploads/promos'), 0755, true);
            }
            
            $file->move(public_path('uploads/promos'), $filename);
            
            // Delete old image file
            $oldPath = public_path($banner->image_url);
            if (file_exists($oldPath) && is_file($oldPath)) {
                @unlink($oldPath);
            }
            
            $imageUrl = '/uploads/promos/' . $filename;
        }

        $banner->update([
            'image_url' => $imageUrl,
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil diperbarui!',
            'banner' => $banner
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
            'message' => 'Promo berhasil dihapus!'
        ]);
    }
}
