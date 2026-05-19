<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, computed, onMounted } from 'vue';
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';

const triggerToast = (message, type = 'success') => {
    window.dispatchEvent(
        new CustomEvent('zunoi-toast', {
            detail: { message, type },
        }),
    );
};

const user = usePage().props.auth.user;
const tables = ref([]);
const newTableName = ref('');

// State Modal QR Code HD
const showQrModal = ref(false);
const selectedTableForQr = ref(null);

// State Custom Confirm Modal
const showConfirmModal = ref(false);
const confirmTitle = ref('');
const confirmMessage = ref('');
const confirmCallback = ref(null);

const triggerConfirm = (title, message, callback) => {
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

// Get Dynamic Base URL
const appUrl = typeof window !== 'undefined' ? window.location.origin : '';

const fetchTables = async () => {
    try {
        const response = await axios.get('/api/tables');
        tables.value = response.data;
    } catch (error) {
        console.error('Gagal mengambil data meja', error);
    }
};

const addTable = async () => {
    if (!newTableName.value) {
return;
}

    try {
        await axios.post('/api/tables', { table_name: newTableName.value });
        newTableName.value = '';
        fetchTables(); // Refresh list meja
        triggerToast('Meja baru berhasil ditambahkan!', 'success');
    } catch (error) {
        console.error('Gagal tambah meja', error);
    }
};
const deleteTable = (id, tableName) => {
    triggerConfirm(
        'Hapus Meja',
        `Apakah Anda yakin ingin menghapus meja "${tableName}"? QR Code meja ini tidak akan bisa discan lagi oleh pelanggan!`,
        async () => {
            try {
                const response = await axios.delete(`/api/tables/${id}`);

                if (response.data.success) {
                    triggerToast('Meja berhasil dihapus!', 'success');
                    fetchTables(); // Refresh list meja
                }
            } catch (error) {
                console.error('Gagal menghapus meja', error);
                triggerToast('Gagal menghapus meja.', 'error');
            }
        },
    );
}; // Fitur Unduh QR Code sebagai PNG asli
const downloadQr = async (table) => {
    try {
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${appUrl}/meja/${table.secure_token}`;
        const response = await fetch(qrUrl);
        const blob = await response.blob();
        const blobUrl = window.URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = `QR_${table.table_name.replace(/\s+/g, '_')}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(blobUrl);
    } catch (error) {
        triggerToast(
            'Gagal mengunduh QR Code. Silakan klik kanan pada gambar untuk menyimpannya.',
            'error',
        );
    }
};

// Buka modal QR
const openQrModal = (table) => {
    selectedTableForQr.value = table;
    showQrModal.value = true;
};

const closeQrModal = () => {
    showQrModal.value = false;
    selectedTableForQr.value = null;
};

onMounted(() => {
    fetchTables();
});
</script>

<template>
    <Head title="Kelola Meja Kafe" />

    <ZunoiAdminLayout>
        <div class="mx-auto max-w-7xl space-y-8 text-gray-800">
            <!-- HEADER SECTION -->
            <div
                class="relative flex flex-col items-start justify-between overflow-hidden rounded-3xl border border-[#D4A373]/30 bg-[#3B2314] p-6 text-[#FAEDCD] shadow-xl md:flex-row md:items-center"
            >
                <div
                    class="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-[#D4A373]/10 blur-2xl"
                ></div>
                <div class="z-10">
                    <h2
                        class="flex items-center gap-2 text-2xl font-extrabold tracking-wide"
                    >
                        Kelola Meja & QR Code
                        <span
                            class="rounded-full bg-[#D4A373] px-2 py-0.5 align-middle text-[10px] font-black tracking-widest text-[#3B2314] uppercase"
                            >Meja Admin</span
                        >
                    </h2>
                    <p class="mt-1 text-xs text-gray-300">
                        Tambahkan meja baru, lihat dan unduh QR Code pemesanan
                        untuk ditempel di meja fisik.
                    </p>
                </div>
            </div>

            <!-- MEJA & QR CODES LIST SECTION -->
            <div
                class="space-y-6 overflow-hidden rounded-3xl border border-[#D4A373]/20 bg-white p-6 shadow-sm"
            >
                <div
                    class="flex flex-col items-start justify-between gap-4 border-b border-gray-100 pb-5 sm:flex-row sm:items-center"
                >
                    <div>
                        <h3 class="text-md font-bold text-[#3B2314]">
                            Daftar Meja Aktif
                        </h3>
                        <p class="mt-0.5 text-xs text-gray-400">
                            Total saat ini:
                            <span class="font-black text-[#3B2314]"
                                >{{ tables.length }} Meja</span
                            >
                        </p>
                    </div>

                    <!-- Tambah Meja Form -->
                    <div class="flex w-full gap-2 sm:w-auto">
                        <input
                            v-model="newTableName"
                            type="text"
                            placeholder="Nama Meja (Contoh: Meja 05)"
                            class="w-full rounded-xl border-gray-200 px-3 py-2 text-xs shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373] sm:w-48"
                        />
                        <button
                            @click="addTable"
                            class="shrink-0 rounded-xl bg-[#3B2314] px-4 py-2 text-xs font-bold text-white shadow-md transition hover:bg-[#25150c] hover:shadow-lg"
                        >
                            + Tambah Meja
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="tables.length === 0"
                    class="py-16 text-center text-gray-400"
                >
                    <div
                        class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full border bg-gray-50"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            class="h-6 w-6 text-gray-400"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 8.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"
                            />
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-gray-500">
                        Belum Ada Meja Terdaftar
                    </h4>
                    <p class="mt-1 text-xs text-gray-400">
                        Masukkan nama meja di form kanan atas untuk membuat meja
                        dan QR Code secara otomatis.
                    </p>
                </div>

                <!-- Tables Grid -->
                <div
                    class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4"
                    v-else
                >
                    <div
                        v-for="table in tables"
                        :key="table.id"
                        class="group relative flex transform flex-col items-center justify-center rounded-3xl border border-[#D4A373]/20 bg-gradient-to-b from-white to-[#FAEDCD]/10 p-5 text-center transition-all duration-300 hover:-translate-y-1 hover:border-[#D4A373]/60 hover:shadow-lg"
                    >
                        <!-- Delete Button (Top-Right) -->
                        <button
                            @click="deleteTable(table.id, table.table_name)"
                            class="absolute top-3 right-3 rounded-lg p-1.5 text-red-400 transition-colors duration-200 hover:bg-red-50 hover:text-red-600"
                            title="Hapus Meja"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="h-4 w-4"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                />
                            </svg>
                        </button>

                        <h4
                            class="text-md mb-3 flex items-center justify-center gap-1.5 font-extrabold text-[#3B2314]"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="h-4 w-4 text-[#D4A373]"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 8.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"
                                />
                            </svg>
                            {{ table.table_name }}
                        </h4>

                        <!-- QR Code Preview -->
                        <div
                            class="relative mb-3 rounded-2xl border border-gray-100 bg-white p-3 shadow-sm transition-transform duration-300 group-hover:scale-105"
                        >
                            <img
                                :src="`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${appUrl}/meja/${table.secure_token}`"
                                alt="QR Code"
                                class="h-28 w-28 object-contain"
                            />
                        </div>

                        <p
                            class="mb-4 w-full font-mono text-[9px] leading-tight break-all text-gray-400 select-all"
                        >
                            /meja/{{ table.secure_token }}
                        </p>

                        <!-- Actions Buttons -->
                        <div class="flex w-full gap-2">
                            <button
                                @click="openQrModal(table)"
                                class="flex-1 rounded-xl bg-[#3B2314] py-2 text-center text-[10px] font-extrabold text-white transition hover:bg-[#25150c]"
                            >
                                Lihat QR
                            </button>
                            <button
                                @click="downloadQr(table)"
                                class="flex flex-1 items-center justify-center gap-1 rounded-xl border border-[#D4A373]/20 bg-[#D4A373] py-2 text-center text-[10px] font-extrabold text-[#3B2314] transition hover:bg-[#FAEDCD]"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2.5"
                                    stroke="currentColor"
                                    class="h-3.5 w-3.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"
                                    />
                                </svg>
                                Unduh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ZunoiAdminLayout>

    <!-- PREMIUM HD QR CODE POPUP MODAL -->
    <div
        v-if="showQrModal && selectedTableForQr"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#3B2314]/70 p-4 backdrop-blur-sm transition-all duration-300"
    >
        <div
            class="w-full max-w-sm scale-100 transform overflow-hidden rounded-[32px] border border-[#D4A373]/30 bg-white shadow-2xl transition-all duration-300"
        >
            <!-- Modal Header -->
            <div
                class="relative border-b border-[#D4A373]/20 bg-[#3B2314] p-6 text-center text-[#FAEDCD]"
            >
                <button
                    @click="closeQrModal"
                    class="absolute top-4 right-4 text-gray-300 transition hover:text-white"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        stroke="currentColor"
                        class="h-5 w-5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>
                </button>
                <div
                    class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full border border-[#D4A373]/30 bg-[#FAEDCD]/10 text-white"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        class="h-6 w-6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 8.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"
                        />
                    </svg>
                </div>
                <h3 class="text-lg font-extrabold">
                    {{ selectedTableForQr.table_name }}
                </h3>
                <p
                    class="text-[10px] font-bold tracking-widest text-[#D4A373] uppercase"
                >
                    Zunoi Caffe QR Code
                </p>
            </div>

            <!-- Modal Body -->
            <div
                class="flex flex-col items-center justify-center bg-gradient-to-b from-white to-[#FAEDCD]/10 p-8"
            >
                <div
                    class="mb-4 rounded-3xl border border-[#D4A373]/25 bg-white p-4 shadow-md"
                >
                    <img
                        :src="`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${appUrl}/meja/${selectedTableForQr.secure_token}`"
                        alt="QR Code HD"
                        class="h-48 w-48 object-contain"
                    />
                </div>

                <p
                    class="mb-6 w-full rounded-full border bg-gray-50 px-3 py-1.5 text-center font-mono text-[10px] leading-normal break-all text-gray-400 select-all"
                >
                    {{ appUrl }}/meja/{{ selectedTableForQr.secure_token }}
                </p>

                <button
                    @click="downloadQr(selectedTableForQr)"
                    class="flex w-full transform items-center justify-center gap-1.5 rounded-2xl bg-[#3B2314] py-3 text-xs font-bold text-white shadow-md transition hover:bg-[#25150c] hover:shadow-lg active:scale-95"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        stroke="currentColor"
                        class="h-4 w-4"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"
                        />
                    </svg>
                    Unduh Gambar QR (.png)
                </button>
            </div>
        </div>
    </div>

    <!-- PREMIUM CONFIRMATION MODAL -->
    <div
        v-if="showConfirmModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#3B2314]/70 p-4 backdrop-blur-sm"
    >
        <div
            class="w-full max-w-sm scale-100 transform overflow-hidden rounded-[32px] border border-[#D4A373]/30 bg-white shadow-2xl transition-all duration-300"
        >
            <div
                class="relative border-b border-[#D4A373]/20 bg-[#3B2314] p-5 text-center text-[#FAEDCD]"
            >
                <button
                    @click="showConfirmModal = false"
                    class="absolute top-4 right-4 text-gray-300 transition hover:text-white"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        stroke="currentColor"
                        class="h-5 w-5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>
                </button>
                <h3 class="text-md font-extrabold">{{ confirmTitle }}</h3>
                <p
                    class="text-[9px] font-bold tracking-widest text-[#D4A373] uppercase"
                >
                    Konfirmasi Aksi
                </p>
            </div>

            <div class="space-y-3 p-6 text-center">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-red-200 bg-red-50 text-red-500"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        stroke="currentColor"
                        class="h-6 w-6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                        />
                    </svg>
                </div>
                <p class="text-xs leading-relaxed font-bold text-gray-600">
                    {{ confirmMessage }}
                </p>
            </div>

            <div class="flex justify-center gap-3 border-t bg-gray-50 p-6">
                <button
                    @click="showConfirmModal = false"
                    class="rounded-xl border px-5 py-2 text-xs font-bold text-gray-500 transition hover:bg-gray-100"
                >
                    Batal
                </button>
                <button
                    @click="handleConfirmYes"
                    class="rounded-xl bg-red-600 px-6 py-2 text-xs font-bold text-white shadow-md transition hover:bg-red-700 hover:shadow-lg"
                >
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</template>
