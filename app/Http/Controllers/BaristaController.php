<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class BaristaController extends Controller
{
    /**
     * Tampilkan halaman manajemen staf barista.
     */
    public function index()
    {
        // Pengecekan keamanan sisi server: Hanya owner yang boleh masuk
        if (auth()->user()->role !== 'owner') {
            abort(403, 'Akses ditolak. Hanya Owner yang dapat mengakses manajemen staf barista.');
        }

        // Ambil daftar pengguna berstatus barista
        $baristas = User::where('role', 'barista')
            ->orderBy('name', 'asc')
            ->get();

        return inertia('StaffManagement', [
            'baristas' => $baristas,
        ]);
    }

    /**
     * Tambahkan staf barista baru ke sistem.
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'owner') {
            abort(403, 'Akses ditolak. Hanya Owner yang dapat menambah staf.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ], [
            'name.required' => 'Nama staf wajib diisi!',
            'email.required' => 'Email wajib diisi!',
            'email.email' => 'Format email tidak valid!',
            'email.unique' => 'Email ini sudah terdaftar oleh pengguna lain!',
            'password.required' => 'Kata sandi wajib diisi!',
            'password.min' => 'Kata sandi minimal berisi 6 karakter!',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'barista',
        ]);

        return redirect()->back()->with('success', 'Akun Barista baru berhasil ditambahkan!');
    }

    /**
     * Perbarui data staf barista yang ada.
     */
    public function update(Request $request, User $user)
    {
        if (auth()->user()->role !== 'owner') {
            abort(403, 'Akses ditolak. Hanya Owner yang dapat mengubah staf.');
        }

        // Pastikan hanya memperbarui user ber-role barista
        if ($user->role !== 'barista') {
            abort(400, 'Pengguna ini bukan merupakan staf barista.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:6',
        ], [
            'name.required' => 'Nama staf wajib diisi!',
            'email.required' => 'Email wajib diisi!',
            'email.email' => 'Format email tidak valid!',
            'email.unique' => 'Email ini sudah terdaftar oleh pengguna lain!',
            'password.min' => 'Kata sandi minimal berisi 6 karakter!',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        // Jika password diisi, ganti kata sandi lama
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->back()->with('success', 'Data Barista berhasil diperbarui!');
    }

    /**
     * Hapus staf barista dari sistem secara permanen.
     */
    public function destroy(User $user)
    {
        if (auth()->user()->role !== 'owner') {
            abort(403, 'Akses ditolak. Hanya Owner yang dapat menghapus staf.');
        }

        if ($user->role !== 'barista') {
            abort(400, 'Pengguna ini bukan merupakan staf barista.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Akun Barista berhasil dihapus secara permanen!');
    }
}
