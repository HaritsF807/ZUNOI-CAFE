<script setup>
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const triggerToast = (message, type = 'success') => {
    window.dispatchEvent(new CustomEvent('zunoi-toast', {
        detail: { message, type }
    }));
};

const user = usePage().props.auth.user;
const orders = ref([]);
const tables = ref([]);
const newTableName = ref('');

// Tab aktif untuk filter pesanan: 'all', 'pending', 'processing', 'completed'
const currentTab = ref('all');

// State Modal QR Code HD
const showQrModal = ref(false);
const selectedTableForQr = ref(null);

// Get Dynamic Base URL
const appUrl = typeof window !== 'undefined' ? window.location.origin : '';

// Audio Notifikasi
const alarmSound =
    typeof window !== 'undefined'
        ? new Audio(
              'https://actions.google.com/sounds/v1/alarms/beep_short.ogg',
          )
        : null;
const pendingCount = computed(
    () => orders.value.filter((o) => o.status === 'pending').length,
);

watch(pendingCount, (newCount, oldCount) => {
    // Bunyikan alarm jika ada pesanan pending baru
    if (newCount > oldCount && alarmSound) {
        alarmSound.play().catch((e) => console.log('Autoplay ditolak.', e));
    }
});

// State Bukti Pembayaran Modal
const showProofModal = ref(false);
const selectedProofUrl = ref('');

const openProofModal = (url) => {
    selectedProofUrl.value = url;
    showProofModal.value = true;
};

// Sistem Polling API
let pollInterval;
const fetchOrders = async () => {
    try {
        const response = await axios.get('/api/orders/live');
        orders.value = response.data;
    } catch (error) {
        console.error('Gagal mengambil data live orders', error);
    }
};

const fetchTables = async () => {
    try {
        const response = await axios.get('/api/tables');
        tables.value = response.data;
    } catch (error) {
        console.error('Gagal mengambil data meja', error);
    }
};

const addTable = async () => {
    if (!newTableName.value) return;
    try {
        await axios.post('/api/tables', { table_name: newTableName.value });
        newTableName.value = '';
        fetchTables(); // Refresh list meja
        triggerToast('Meja baru berhasil ditambahkan!', 'success');
    } catch (error) {
        console.error('Gagal tambah meja', error);
        triggerToast('Gagal menambahkan meja baru.', 'error');
    }
};

// Update status pesanan secara RIIL ke database
const acceptOrder = async (id) => {
    try {
        const response = await axios.patch(`/api/orders/${id}/status`, {
            order_status: 'processing',
        });
        if (response.data.success) {
            fetchOrders();
            triggerToast(`Pesanan #${id} berhasil diterima!`, 'success');
        }
    } catch (error) {
        console.error('Gagal menerima pesanan', error);
        triggerToast('Gagal memperbarui status pesanan.', 'error');
    }
};

const completeOrder = async (id) => {
    try {
        const response = await axios.patch(`/api/orders/${id}/status`, {
            order_status: 'completed',
        });
        if (response.data.success) {
            fetchOrders();
            triggerToast(`Pesanan #${id} ditandai sebagai selesai!`, 'success');
        }
    } catch (error) {
        console.error('Gagal menyelesaikan pesanan', error);
        triggerToast('Gagal memperbarui status pesanan.', 'error');
    }
};

const sendReport = async () => {
    triggerToast('Mengirim rekapan harian ke WhatsApp Owner...', 'info');
    try {
        const response = await axios.post('/api/reports/send-recap');
        if (response.data.success) {
            triggerToast(response.data.message || 'Rekapan harian berhasil dikirim!', 'success');
        } else {
            triggerToast(response.data.message || 'Gagal mengirim rekapan.', 'error');
        }
    } catch (error) {
        console.error('Gagal mengirim rekapan', error);
        const errorMsg = error.response?.data?.message || 'Terjadi kesalahan sistem saat mengirim rekapan.';
        triggerToast(errorMsg, 'error');
    }
};

// Form Integrasi (Khusus Owner)
const integrationForm = ref({
    fonnte_token: 'TokenFonnteAnda123',
    tokopay_merchant_id: 'M-123456',
    tokopay_secret: 'SecretKey...',
});

const saveIntegration = () => {
    triggerToast('Pengaturan Integrasi berhasil disimpan!', 'success');
};

// Filtered Orders berdasarkan tab yang dipilih
const filteredOrders = computed(() => {
    if (currentTab.value === 'all') return orders.value;
    return orders.value.filter((o) => o.status === currentTab.value);
});

// Analytics Summary
const stats = computed(() => {
    const activeOrders = orders.value.filter((o) => o.status !== 'cancelled');
    const totalRev = activeOrders.reduce((sum, o) => sum + o.total, 0);
    const dineInCount = activeOrders.filter((o) => o.type === 'Dine In').length;
    const takeawayCount = activeOrders.filter(
        (o) => o.type === 'Takeaway',
    ).length;

    return {
        totalRevenue: totalRev,
        pendingCount: orders.value.filter((o) => o.status === 'pending').length,
        activeTables: tables.value.length,
        dineInPercent: activeOrders.length
            ? Math.round((dineInCount / activeOrders.length) * 100)
            : 0,
        takeawayPercent: activeOrders.length
            ? Math.round((takeawayCount / activeOrders.length) * 100)
            : 0,
    };
});

// Fitur Unduh QR Code sebagai PNG asli
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
        console.error("Gagal mengunduh QR Code", error);
        triggerToast("Gagal mengunduh QR Code. Silakan klik kanan pada gambar untuk menyimpannya.", "error");
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

const deleteTable = async (id, tableName) => {
    if (
        !confirm(
            `Apakah Anda yakin ingin menghapus ${tableName}? Seluruh data barcode meja ini akan dinonaktifkan.`,
        )
    )
        return;
    try {
        const response = await axios.delete(`/api/tables/${id}`);
        if (response.data.success) {
            fetchTables();
            triggerToast("Meja berhasil dihapus!", "success");
        }
    } catch (error) {
        console.error("Gagal menghapus meja", error);
        triggerToast("Gagal menghapus meja.", "error");
    }
};

onMounted(() => {
    fetchOrders(); // Initial fetch
    fetchTables(); // Ambil data meja
    pollInterval = setInterval(fetchOrders, 3000); // Polling setiap 3 detik
});

onUnmounted(() => {
    clearInterval(pollInterval);
});
</script>

<template>
    <Head title="Dashboard" />

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
                        Dashboard
                        {{ user.role === 'owner' ? 'Owner' : 'Barista' }}
                        <span
                            class="rounded-full bg-[#D4A373] px-2 py-0.5 align-middle text-[10px] font-black tracking-widest text-[#3B2314] uppercase"
                            >Zunoi PRO</span
                        >
                    </h2>
                    <p class="mt-1 text-xs text-gray-300">
                        Kelola pesanan QR, cetak barcode meja, dan integrasikan
                        gateway sistem Anda secara instan.
                    </p>
                </div>

                <div class="z-10 mt-4 flex items-center gap-3 md:mt-0">
                    <!-- Live Polling Indicator -->
                    <div
                        class="flex items-center gap-2 rounded-full border border-[#D4A373]/20 bg-[#FAEDCD]/10 px-3 py-1.5"
                    >
                        <span class="relative flex h-2.5 w-2.5">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"
                            ></span>
                            <span
                                class="relative inline-flex h-2.5 w-2.5 rounded-full bg-green-500"
                            ></span>
                        </span>
                        <span
                            class="text-[10px] font-bold tracking-widest text-green-400 uppercase"
                            >Live Polling</span
                        >
                    </div>

                    <button
                        v-if="user.role === 'owner'"
                        @click="sendReport"
                        class="flex transform items-center gap-2 rounded-xl bg-[#D4A373] px-5 py-2 text-sm font-bold text-[#3B2314] shadow-md transition-all duration-300 hover:bg-[#FAEDCD] hover:shadow-lg active:scale-95"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"
                            />
                        </svg>
                        Kirim Rekapan WA
                    </button>
                </div>
            </div>

            <!-- ANALYTICS CARDS (Warm & Clean UI) -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                <!-- Card 1: Revenue -->
                <div
                    class="group rounded-3xl border border-[#D4A373]/20 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <span
                            class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                            >Pendapatan</span
                        >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#FAEDCD] font-bold text-[#3B2314]"
                        >
                            Rp
                        </div>
                    </div>
                    <h3
                        class="text-2xl font-black tracking-tight text-[#3B2314]"
                    >
                        Rp {{ stats.totalRevenue.toLocaleString('id-ID') }}
                    </h3>
                    <p class="mt-1 text-xs text-gray-500">
                        Akumulasi transaksi hari ini
                    </p>
                </div>

                <!-- Card 2: Pending Orders -->
                <div
                    class="rounded-3xl border border-[#D4A373]/20 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md"
                    :class="{
                        'border-red-300 bg-red-50/10 ring-2 ring-red-100':
                            stats.pendingCount > 0,
                    }"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <span
                            class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                            >Pesanan Pending</span
                        >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-red-100 font-bold text-red-600"
                        >
                            <span
                                class="relative flex h-3 w-3"
                                v-if="stats.pendingCount > 0"
                            >
                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"
                                ></span>
                                <span
                                    class="relative inline-flex h-3 w-3 rounded-full bg-red-500"
                                ></span>
                            </span>
                            <svg
                                v-else
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
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                />
                            </svg>
                        </div>
                    </div>
                    <h3
                        class="text-2xl font-black tracking-tight"
                        :class="
                            stats.pendingCount > 0
                                ? 'text-red-600'
                                : 'text-[#3B2314]'
                        "
                    >
                        {{ stats.pendingCount }}
                    </h3>
                    <p class="mt-1 text-xs text-gray-500">
                        Pesanan baru yang butuh konfirmasi
                    </p>
                </div>

                <!-- Card 3: Active Tables -->
                <div
                    class="rounded-3xl border border-[#D4A373]/20 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <span
                            class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                            >Jumlah Meja</span
                        >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-orange-100 font-bold text-orange-600"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="h-5 w-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 8.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"
                                />
                            </svg>
                        </div>
                    </div>
                    <h3
                        class="text-2xl font-black tracking-tight text-[#3B2314]"
                    >
                        {{ stats.activeTables }} Meja
                    </h3>
                    <p class="mt-1 text-xs text-gray-500">
                        Meja ber-QR terdaftar saat ini
                    </p>
                </div>

                <!-- Card 4: Ratio -->
                <div
                    class="rounded-3xl border border-[#D4A373]/20 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <span
                            class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                            >Rasio Dine-in</span
                        >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-teal-100 font-bold text-teal-600"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="h-5 w-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z"
                                />
                            </svg>
                        </div>
                    </div>
                    <h3
                        class="text-2xl font-black tracking-tight text-[#3B2314]"
                    >
                        {{ stats.dineInPercent }}% :
                        {{ stats.takeawayPercent }}%
                    </h3>
                    <p class="mt-1 text-xs text-gray-500">
                        Persentase Dine-In vs Takeaway
                    </p>
                </div>
            </div>

            <!-- LIVE ORDERS BLOCK (Filter Tabs, Cards, & Real Action Buttons) -->
            <div
                class="overflow-hidden rounded-3xl border border-[#D4A373]/20 bg-white shadow-sm"
            >
                <div
                    class="flex flex-col items-start justify-between gap-4 border-b border-gray-100 p-6 md:flex-row md:items-center"
                >
                    <div>
                        <h3
                            class="flex items-center gap-2 text-lg font-bold text-[#3B2314]"
                        >
                            <span
                                class="h-2.5 w-2.5 animate-pulse rounded-full bg-red-500"
                            ></span>
                            Live Order Management
                        </h3>
                        <p class="mt-1 text-xs text-gray-400">
                            Pantau pesanan yang masuk dan perbarui status
                            pengerjaan secara waktu nyata.
                        </p>
                    </div>

                    <!-- Tabs Filter Modern -->
                    <div
                        class="flex w-full rounded-2xl border bg-gray-100 p-1.5 text-xs font-bold md:w-auto"
                    >
                        <button
                            @click="currentTab = 'all'"
                            class="flex-1 rounded-xl px-4 py-2 transition-all duration-200 md:flex-none"
                            :class="
                                currentTab === 'all'
                                    ? 'bg-[#3B2314] text-white shadow-sm'
                                    : 'text-gray-500 hover:text-[#3B2314]'
                            "
                        >
                            Semua
                        </button>
                        <button
                            @click="currentTab = 'pending'"
                            class="flex flex-1 items-center justify-center gap-1.5 rounded-xl px-4 py-2 transition-all duration-200 md:flex-none"
                            :class="
                                currentTab === 'pending'
                                    ? 'bg-red-500 text-white shadow-sm'
                                    : 'text-gray-500 hover:text-red-500'
                            "
                        >
                            Pending
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-red-400"
                                v-if="stats.pendingCount > 0"
                            ></span>
                        </button>
                        <button
                            @click="currentTab = 'processing'"
                            class="flex-1 rounded-xl px-4 py-2 transition-all duration-200 md:flex-none"
                            :class="
                                currentTab === 'processing'
                                    ? 'bg-yellow-500 text-white shadow-sm'
                                    : 'text-gray-500 hover:text-yellow-600'
                            "
                        >
                            Diproses
                        </button>
                        <button
                            @click="currentTab = 'completed'"
                            class="flex-1 rounded-xl px-4 py-2 transition-all duration-200 md:flex-none"
                            :class="
                                currentTab === 'completed'
                                    ? 'bg-green-600 text-white shadow-sm'
                                    : 'text-gray-500 hover:text-green-600'
                            "
                        >
                            Selesai
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div
                        v-if="filteredOrders.length === 0"
                        class="flex flex-col items-center justify-center py-12 text-center text-gray-400"
                    >
                        <div
                            class="mb-3 flex h-16 w-16 items-center justify-center rounded-full border bg-gray-50"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="h-8 w-8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15.182 16.318A4.486 4.486 0 0 0 12.016 15a4.486 4.486 0 0 0-3.198 1.318M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z"
                                />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-gray-500">
                            Tidak ada pesanan
                        </h4>
                        <p class="mt-1 text-xs text-gray-400">
                            Belum ada pesanan dengan status '{{ currentTab }}'
                            saat ini.
                        </p>
                    </div>

                    <div class="space-y-4" v-else>
                        <div
                            v-for="order in filteredOrders"
                            :key="order.id"
                            class="flex flex-col items-start justify-between rounded-2xl border p-5 transition-all duration-300 hover:border-[#D4A373]/50 hover:shadow-sm lg:flex-row lg:items-center"
                            :class="{
                                'border-red-200 bg-red-50/10':
                                    order.status === 'pending',
                                'border-yellow-200 bg-yellow-50/10':
                                    order.status === 'processing',
                                'border-green-200 bg-green-50/10':
                                    order.status === 'completed',
                            }"
                        >
                            <div class="w-full flex-1">
                                <div
                                    class="mb-2 flex flex-wrap items-center gap-2"
                                >
                                    <span
                                        class="text-lg font-black text-[#3B2314]"
                                        >#{{ order.id }}</span
                                    >

                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px] font-black tracking-wider uppercase"
                                        :class="
                                            order.type === 'Dine In'
                                                ? 'border border-[#D4A373]/20 bg-[#FAEDCD] text-[#3B2314]'
                                                : 'border bg-gray-100 text-gray-600'
                                        "
                                    >
                                        {{ order.type }}
                                    </span>

                                    <span
                                        class="flex items-center gap-1 rounded-full border border-[#D4A373]/20 bg-white px-2 py-0.5 text-[10px] font-bold text-gray-700"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="2"
                                            stroke="currentColor"
                                            class="h-3.5 w-3.5 text-gray-400"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25a7.5 7.5 0 1 1 15 0Z"
                                            />
                                        </svg>
                                        {{ order.table }}
                                    </span>

                                    <!-- Payment Method & Status Badges -->
                                    <span
                                        class="flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                        :class="{
                                            'border border-blue-200 bg-blue-50 text-blue-700':
                                                order.payment_method ===
                                                'qris_tokopay',
                                            'border border-amber-200 bg-amber-50 text-amber-700':
                                                order.payment_method ===
                                                'qris_manual',
                                            'border border-teal-200 bg-teal-50 text-teal-700':
                                                order.payment_method ===
                                                'cashier',
                                        }"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="2"
                                            stroke="currentColor"
                                            class="h-3.5 w-3.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M2.25 8.25h19.5M2.25 9h19.5m-19.5 5.25h19.5m-19.5 0h19.5M2.25 18h19.5A2.25 2.25 0 0 0 24 15.75V8.25A2.25 2.25 0 0 0 21.75 6H2.25A2.25 2.25 0 0 0 0 8.25v7.5A2.25 2.25 0 0 0 2.25 18Z"
                                            />
                                        </svg>
                                        {{
                                            order.payment_method ===
                                            'qris_tokopay'
                                                ? 'Tokopay QRIS'
                                                : order.payment_method ===
                                                    'qris_manual'
                                                  ? 'QRIS Manual'
                                                  : 'Bayar Kasir'
                                        }}
                                    </span>

                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px] font-black tracking-wider uppercase"
                                        :class="
                                            order.payment_status === 'paid'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-gray-100 text-gray-500'
                                        "
                                    >
                                        {{
                                            order.payment_status === 'paid'
                                                ? 'LUNAS'
                                                : 'BELUM BAYAR'
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between gap-6 lg:justify-start"
                                >
                                    <div>
                                        <p
                                            class="text-xs font-bold tracking-wider text-gray-400 uppercase"
                                        >
                                            Pelanggan
                                        </p>
                                        <p
                                            class="text-sm font-bold text-gray-700"
                                        >
                                            {{ order.name }}
                                        </p>
                                    </div>
                                    <div class="h-6 w-px bg-gray-200"></div>
                                    <div>
                                        <p
                                            class="text-xs font-bold tracking-wider text-gray-400 uppercase"
                                        >
                                            Total Belanja
                                        </p>
                                        <p
                                            class="text-sm font-extrabold text-[#3B2314]"
                                        >
                                            Rp
                                            {{
                                                order.total.toLocaleString(
                                                    'id-ID',
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div class="h-6 w-px bg-gray-200"></div>
                                    <div>
                                        <p
                                            class="text-xs font-bold tracking-wider text-gray-400 uppercase"
                                        >
                                            Waktu
                                        </p>
                                        <p
                                            class="text-sm font-semibold text-gray-600"
                                        >
                                            {{ order.time }} WIB
                                        </p>
                                    </div>
                                    <div
                                        v-if="order.payment_proof"
                                        class="h-6 w-px bg-gray-200"
                                    ></div>
                                    <div v-if="order.payment_proof">
                                        <p
                                            class="text-xs font-bold tracking-wider text-gray-400 uppercase"
                                        >
                                            Bukti Bayar
                                        </p>
                                        <button
                                            @click="
                                                openProofModal(
                                                    order.payment_proof,
                                                )
                                            "
                                            class="mt-0.5 flex cursor-pointer items-center gap-0.5 text-xs font-black text-blue-600 hover:text-blue-800 focus:outline-none"
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
                                                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                                />
                                            </svg>
                                            Lihat Bukti
                                        </button>
                                    </div>
                                </div>

                                <!-- Detail Pesanan & Catatan -->
                                <div
                                    class="mt-4 space-y-2 border-t border-dashed border-gray-100 pt-3"
                                >
                                    <!-- Items List -->
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            v-for="(item, idx) in order.items"
                                            :key="idx"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-[#3B2314]/10 bg-[#3B2314]/5 px-2.5 py-1 text-xs font-bold text-[#3B2314]"
                                        >
                                            <span
                                                class="flex h-4 w-4 items-center justify-center rounded-full bg-[#3B2314] text-[9px] font-black text-white"
                                                >{{ item.quantity }}</span
                                            >
                                            <span>{{ item.name }}</span>
                                            <span
                                                v-if="item.notes"
                                                class="text-[9px] text-[#D4A373] italic"
                                                >({{ item.notes }})</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Notes -->
                                    <div
                                        v-if="order.notes"
                                        class="flex items-start gap-1.5 rounded-xl border border-yellow-100 bg-yellow-50/50 p-2.5 text-xs font-medium text-amber-900"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="2.5"
                                            stroke="currentColor"
                                            class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-700"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                            />
                                        </svg>
                                        <div class="text-left leading-relaxed">
                                            <span
                                                class="block text-[9px] font-extrabold tracking-wider text-amber-800 uppercase"
                                                >Catatan Pelanggan:</span
                                            >
                                            <span>"{{ order.notes }}"</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions Buttons -->
                            <div
                                class="mt-4 flex w-full shrink-0 justify-end gap-2 lg:mt-0 lg:w-auto"
                            >
                                <button
                                    v-if="order.status === 'pending'"
                                    @click="acceptOrder(order.id)"
                                    class="flex w-full transform items-center justify-center gap-1 rounded-xl bg-[#3B2314] px-5 py-2.5 text-xs font-bold text-white shadow-md transition hover:bg-[#25150c] hover:shadow-lg active:scale-95 lg:w-auto"
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
                                            d="m4.5 12.75 6 6 9-13.5"
                                        />
                                    </svg>
                                    Terima Pesanan
                                </button>

                                <button
                                    v-if="order.status === 'processing'"
                                    @click="completeOrder(order.id)"
                                    class="flex w-full transform items-center justify-center gap-1 rounded-xl bg-green-600 px-5 py-2.5 text-xs font-bold text-white shadow-md transition hover:bg-green-700 hover:shadow-lg active:scale-95 lg:w-auto"
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
                                            d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"
                                        />
                                    </svg>
                                    Tandai Selesai
                                </button>

                                <span
                                    v-if="order.status === 'completed'"
                                    class="flex items-center gap-1.5 rounded-xl bg-green-100 px-4 py-2 text-xs font-black tracking-wider text-green-800 uppercase select-none"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2.5"
                                        stroke="currentColor"
                                        class="h-4 w-4 text-green-700"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m4.5 12.75 6 6 9-13.5"
                                        />
                                    </svg>
                                    Selesai Dikerjakan
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modal Zoom Bukti Pembayaran -->
        <Transition
            enter-active-class="ease-out duration-300 transition"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="ease-in duration-200 transition"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showProofModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                @click.self="showProofModal = false"
            >
                <div
                    class="animate-scale-in relative w-full max-w-lg overflow-hidden rounded-3xl bg-white p-6 shadow-2xl"
                >
                    <!-- Header -->
                    <div
                        class="mb-4 flex items-center justify-between border-b pb-3"
                    >
                        <h3
                            class="flex items-center gap-1.5 text-sm font-black text-gray-800"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="h-4 w-4 text-[#D4A373]"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"
                                />
                            </svg>
                            Bukti Pembayaran QRIS
                        </h3>
                        <button
                            @click="showProofModal = false"
                            class="text-gray-400 transition hover:text-gray-600"
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
                                    d="M6 18 18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Image -->
                    <div
                        class="flex max-h-[60vh] items-center justify-center overflow-y-auto rounded-2xl border bg-gray-50 p-2"
                    >
                        <img
                            :src="selectedProofUrl"
                            alt="Bukti Pembayaran"
                            class="max-h-[50vh] max-w-full rounded-xl object-contain shadow-sm"
                        />
                    </div>

                    <!-- Footer -->
                    <div class="mt-4 flex justify-end">
                        <button
                            @click="showProofModal = false"
                            class="rounded-xl bg-[#3B2314] px-5 py-2 text-xs font-bold text-white shadow-md transition hover:bg-[#25150c] active:scale-95"
                        >
                            Tutup Detail
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </ZunoiAdminLayout>
</template>
