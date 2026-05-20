<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';

interface Barista {
    id: number;
    name: string;
    email: string;
    created_at: string;
}

const props = defineProps<{
    baristas: Barista[];
}>();

// Helper to trigger global toast
const triggerToast = (message: string, type: 'success' | 'error' = 'success') => {
    window.dispatchEvent(
        new CustomEvent('zunoi-toast', {
            detail: { message, type },
        }),
    );
};

// Form states
const showAddModal = ref(false);
const showEditModal = ref(false);
const showConfirmModal = ref(false);

const nameInput = ref('');
const emailInput = ref('');
const passwordInput = ref('');

const selectedBarista = ref<Barista | null>(null);

// Confirmation Modal states
const confirmTitle = ref('');
const confirmMessage = ref('');
const confirmCallback = ref<(() => void) | null>(null);

const triggerConfirm = (title: string, message: string, callback: () => void) => {
    confirmTitle.value = title;
    confirmMessage.value = message;
    confirmCallback.value = callback;
    showConfirmModal.value = true;
};

const handleConfirmYes = () => {
    if (confirmCallback.value) {
        confirmCallback.value();
    }
    showConfirmModal.value = false;
};

// Actions
const openAddModal = () => {
    nameInput.value = '';
    emailInput.value = '';
    passwordInput.value = '';
    showAddModal.value = true;
};

const closeAddModal = () => {
    showAddModal.value = false;
};

const openEditModal = (barista: Barista) => {
    selectedBarista.value = barista;
    nameInput.value = barista.name;
    emailInput.value = barista.email;
    passwordInput.value = '';
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    selectedBarista.value = null;
};

const submitAddBarista = () => {
    if (!nameInput.value.trim() || !emailInput.value.trim() || !passwordInput.value.trim()) {
        triggerToast('Harap isi semua kolom formulir!', 'error');
        return;
    }

    router.post('/api/staff', {
        name: nameInput.value,
        email: emailInput.value,
        password: passwordInput.value,
    }, {
        onSuccess: () => {
            closeAddModal();
            triggerToast('Akun Barista baru berhasil ditambahkan!');
        },
        onError: (errors: any) => {
            const firstError = Object.values(errors)[0] as string;
            triggerToast(firstError || 'Gagal menambahkan barista.', 'error');
        }
    });
};

const submitEditBarista = () => {
    if (!selectedBarista.value) return;

    if (!nameInput.value.trim() || !emailInput.value.trim()) {
        triggerToast('Harap isi kolom nama dan email!', 'error');
        return;
    }

    router.put(`/api/staff/${selectedBarista.value.id}`, {
        name: nameInput.value,
        email: emailInput.value,
        password: passwordInput.value || null,
    }, {
        onSuccess: () => {
            closeEditModal();
            triggerToast('Data Barista berhasil diperbarui!');
        },
        onError: (errors: any) => {
            const firstError = Object.values(errors)[0] as string;
            triggerToast(firstError || 'Gagal memperbarui barista.', 'error');
        }
    });
};

const handleDeleteBarista = (barista: Barista) => {
    triggerConfirm(
        'Hapus Akun Barista',
        `Apakah Anda yakin ingin menghapus akun barista "${barista.name}" secara permanen? Staf ini tidak akan bisa login lagi ke kasir POS.`,
        () => {
            router.delete(`/api/staff/${barista.id}`, {
                onSuccess: () => {
                    triggerToast('Akun Barista berhasil dihapus secara permanen!', 'success');
                },
                onError: () => {
                    triggerToast('Gagal menghapus barista.', 'error');
                }
            });
        }
    );
};

const formatLocalDate = (dateStr: string) => {
    try {
        return new Date(dateStr).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    } catch (e) {
        return dateStr;
    }
};
</script>

<template>
    <Head title="Kelola Staf Barista" />

    <ZunoiAdminLayout>
        <div class="mx-auto max-w-7xl space-y-8 text-gray-800">
            <!-- HEADER SECTION -->
            <div
                class="relative flex flex-col items-start justify-between overflow-hidden rounded-xl border border-[#D4A373]/30 bg-[#3B2314] p-6 text-[#FAEDCD] shadow-xl md:flex-row md:items-center"
            >
                <div
                    class="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-[#D4A373]/10 blur-2xl"
                ></div>
                <div class="z-10">
                    <h2
                        class="flex items-center gap-2 text-2xl font-extrabold tracking-wide"
                    >
                        Kelola Staf Barista
                        <span
                            class="rounded-full bg-[#D4A373] px-2 py-0.5 align-middle text-[10px] font-black tracking-widest text-[#3B2314] uppercase"
                            >Owner</span
                        >
                    </h2>
                    <p class="mt-1 text-xs text-gray-300">
                        Tambahkan akun staf baru, edit informasi login barista, atau hapus akses login ke kasir POS kafe Anda.
                    </p>
                </div>
                <button
                    @click="openAddModal"
                    class="z-10 mt-4 shrink-0 rounded-xl bg-[#D4A373] px-4 py-2.5 text-xs font-black text-[#3B2314] shadow-md transition hover:bg-[#FAEDCD] hover:scale-[1.02] active:scale-95 md:mt-0"
                >
                    + Tambah Barista Baru
                </button>
            </div>

            <!-- STATS SECTION -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3">
                <div class="rounded-xl border border-[#D4A373]/20 bg-white p-6 shadow-sm flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-50 text-[#D4A373]">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A11.386 11.386 0 0 1 10.089 20.8M15 19.128a11.386 11.386 0 0 1-4.911 1.672M10.089 20.8A11.302 11.302 0 0 1 4.5 18.75m5.589 2.05A11.21 11.21 0 0 1 4.5 18.75m0 0V16.5A4.125 4.125 0 0 1 8.56 12.35m-4.06 6.4A11.37 11.37 0 0 1 3 15.75m0 0v-2.25A4.125 4.125 0 0 1 7.125 9.375M2.625 10.5h1.125c.375 0 .625-.125.75-.375l.5-.875m10.125.375h1.125c.375 0 .625-.125.75-.375l.5-.875M12 2.25a3.375 3.375 0 1 0 0 6.75 3.375 3.375 0 0 0 0-6.75ZM19.5 7.5a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5ZM4.5 7.5a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400">Total Staf Aktif</p>
                        <h4 class="text-xl font-extrabold text-[#3B2314]">{{ baristas.length }} Barista</h4>
                    </div>
                </div>
            </div>

            <!-- BARISTA LIST TABLE SECTION -->
            <div class="overflow-hidden rounded-xl border border-[#D4A373]/20 bg-white shadow-sm">
                <div class="border-b border-gray-100 p-6">
                    <h3 class="text-md font-bold text-[#3B2314]">Daftar Staf Barista Aktif</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Berikut adalah akun staf yang diizinkan untuk mengelola transaksi POS dan kasir.</p>
                </div>

                <!-- Empty State -->
                <div v-if="baristas.length === 0" class="py-16 text-center text-gray-400">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full border bg-gray-50 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <p class="text-xs font-bold">Belum ada akun Barista terdaftar.</p>
                    <p class="text-[10px] text-gray-400 mt-1">Silakan klik "+ Tambah Barista Baru" di atas untuk mendaftarkan staf baru.</p>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/70 border-b border-gray-100 text-xs font-extrabold text-[#3B2314] uppercase tracking-wider">
                                <th class="p-6">Nama Barista</th>
                                <th class="p-6">Email Staf</th>
                                <th class="p-6">Tanggal Terdaftar</th>
                                <th class="p-6 text-center">Aksi Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                            <tr v-for="barista in baristas" :key="barista.id" class="hover:bg-amber-50/10 transition">
                                <td class="p-6 font-extrabold text-[#3B2314]">{{ barista.name }}</td>
                                <td class="p-6 text-gray-500 font-mono text-xs">{{ barista.email }}</td>
                                <td class="p-6 text-gray-400 text-xs">{{ formatLocalDate(barista.created_at) }}</td>
                                <td class="p-6">
                                    <div class="flex items-center justify-center gap-3">
                                        <button
                                            @click="openEditModal(barista)"
                                            class="inline-flex items-center gap-1 rounded-xl bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-600 transition hover:bg-amber-100 hover:text-[#3B2314]"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.83 17.93a4.5 4.5 0 0 1-2.22 1.14l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-2.22L16.862 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                            Ubah
                                        </button>
                                        <button
                                            @click="handleDeleteBarista(barista)"
                                            class="inline-flex items-center gap-1 rounded-xl bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600 transition hover:bg-red-100 hover:text-red-700"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL: TAMBAH BARISTA -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden border border-[#D4A373]/20 transition-all duration-300">
                <div class="p-6 bg-[#3B2314] text-[#FAEDCD] flex items-center justify-between">
                    <div>
                        <h3 class="text-md font-extrabold tracking-wide">Pendaftaran Barista Baru</h3>
                        <p class="text-[10px] text-gray-300 mt-0.5">Daftarkan akun staf baru untuk mengoperasikan kasir POS.</p>
                    </div>
                    <button @click="closeAddModal" class="text-[#FAEDCD]/60 hover:text-white transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider block">Nama Lengkap *</label>
                        <input
                            v-model="nameInput"
                            type="text"
                            placeholder="Contoh: Barista Andalan"
                            class="w-full bg-white text-gray-700 text-sm px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#D4A373]"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider block">Alamat Email *</label>
                        <input
                            v-model="emailInput"
                            type="email"
                            placeholder="Contoh: barista@zunoi.id"
                            class="w-full bg-white text-gray-700 text-sm px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#D4A373]"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider block">Kata Sandi (Min 6 Karakter) *</label>
                        <input
                            v-model="passwordInput"
                            type="password"
                            placeholder="Masukkan kata sandi staf..."
                            class="w-full bg-white text-gray-700 text-sm px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#D4A373]"
                        />
                    </div>
                </div>

                <div class="p-6 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                    <button
                        @click="closeAddModal"
                        class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl text-xs font-bold transition"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitAddBarista"
                        class="px-5 py-2.5 bg-[#3B2314] hover:bg-[#D4A373] text-[#FAEDCD] hover:text-[#3B2314] rounded-xl text-xs font-extrabold transition shadow-md"
                    >
                        Tambah Barista
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: UBAH BARISTA -->
        <div v-if="showEditModal && selectedBarista" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden border border-[#D4A373]/20 transition-all duration-300">
                <div class="p-6 bg-[#3B2314] text-[#FAEDCD] flex items-center justify-between">
                    <div>
                        <h3 class="text-md font-extrabold tracking-wide">Ubah Informasi Barista</h3>
                        <p class="text-[10px] text-gray-300 mt-0.5">Ubah rincian profil staf barista kafe Anda.</p>
                    </div>
                    <button @click="closeEditModal" class="text-[#FAEDCD]/60 hover:text-white transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider block">Nama Lengkap *</label>
                        <input
                            v-model="nameInput"
                            type="text"
                            placeholder="Nama Lengkap..."
                            class="w-full bg-white text-gray-700 text-sm px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#D4A373]"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider block">Alamat Email *</label>
                        <input
                            v-model="emailInput"
                            type="email"
                            placeholder="Alamat Email..."
                            class="w-full bg-white text-gray-700 text-sm px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#D4A373]"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center">
                            <label class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider block">Kata Sandi Baru (Opsional)</label>
                            <span class="text-[9px] text-gray-400 font-bold">* Kosongkan jika tidak diubah</span>
                        </div>
                        <input
                            v-model="passwordInput"
                            type="password"
                            placeholder="Masukkan kata sandi baru jika ingin diubah..."
                            class="w-full bg-white text-gray-700 text-sm px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#D4A373]"
                        />
                    </div>
                </div>

                <div class="p-6 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                    <button
                        @click="closeEditModal"
                        class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl text-xs font-bold transition"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitEditBarista"
                        class="px-5 py-2.5 bg-[#3B2314] hover:bg-[#D4A373] text-[#FAEDCD] hover:text-[#3B2314] rounded-xl text-xs font-extrabold transition shadow-md"
                    >
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: CUSTOM CONFIRMATION DIALOG -->
        <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full overflow-hidden border border-red-100 transition-all duration-300">
                <div class="p-6 bg-red-600 text-white flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/20">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-extrabold tracking-wide uppercase">{{ confirmTitle }}</h4>
                    </div>
                </div>

                <div class="p-6 text-xs text-gray-600 leading-relaxed">
                    {{ confirmMessage }}
                </div>

                <div class="p-6 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                    <button
                        @click="showConfirmModal = false"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl text-xs font-bold transition"
                    >
                        Batal
                    </button>
                    <button
                        @click="handleConfirmYes"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition shadow-md"
                    >
                        Hapus Akun
                    </button>
                </div>
            </div>
        </div>
    </ZunoiAdminLayout>
</template>
