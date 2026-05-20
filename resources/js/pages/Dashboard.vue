<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';

const triggerToast = (message, type = 'success') => {
    window.dispatchEvent(
        new CustomEvent('zunoi-toast', {
            detail: { message, type },
        }),
    );
};

const user = usePage().props.auth.user;
const orders = ref([]);
const activeStaff = ref([]);
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

// State Modal Detail Pesanan
const showDetailModal = ref(false);
const selectedOrder = ref(null);

const openDetailModal = (order) => {
    selectedOrder.value = order;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedOrder.value = null;
};

// Sistem Polling API
let pollInterval;
const fetchOrders = async () => {
    try {
        const response = await axios.get('/api/orders/live');
        orders.value = response.data.orders;
        activeStaff.value = response.data.active_staff;
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
            if (showDetailModal.value) closeDetailModal();
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
            if (showDetailModal.value) closeDetailModal();
        }
    } catch (error) {
        console.error('Gagal menyelesaikan pesanan', error);
        triggerToast('Gagal memperbarui status pesanan.', 'error');
    }
};

const showRecapModal = ref(false);
const recapType = ref('today');
const recapStartDate = ref(new Date().toISOString().split('T')[0]);
const recapEndDate = ref(new Date().toISOString().split('T')[0]);
const isSendingRecap = ref(false);

const openRecapModal = () => {
    const todayStr = new Date().toISOString().split('T')[0];
    recapStartDate.value = todayStr;
    recapEndDate.value = todayStr;
    recapType.value = 'today';
    showRecapModal.value = true;
};

const sendReport = async () => {
    isSendingRecap.value = true;
    triggerToast('Mengirim rekapan ke WhatsApp Owner...', 'info');

    let payload = {};
    const todayStr = new Date().toISOString().split('T')[0];

    if (recapType.value === 'today') {
        payload = { start_date: todayStr, end_date: todayStr };
    } else if (recapType.value === 'yesterday') {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        const yesterdayStr = yesterday.toISOString().split('T')[0];
        payload = { start_date: yesterdayStr, end_date: yesterdayStr };
    } else if (recapType.value === 'single') {
        payload = { start_date: recapStartDate.value, end_date: recapStartDate.value };
    } else if (recapType.value === 'range') {
        payload = { start_date: recapStartDate.value, end_date: recapEndDate.value };
    }

    try {
        const response = await axios.post('/api/reports/send-recap', payload);

        if (response.data.success) {
            triggerToast(
                response.data.message || 'Rekapan berhasil dikirim!',
                'success',
            );
            showRecapModal.value = false;
        } else {
            triggerToast(
                response.data.message || 'Gagal mengirim rekapan.',
                'error',
            );
        }
    } catch (error) {
        console.error('Gagal mengirim rekapan', error);
        const errorMsg =
            error.response?.data?.message ||
            'Terjadi kesalahan sistem saat mengirim rekapan.';
        triggerToast(errorMsg, 'error');
    } finally {
        isSendingRecap.value = false;
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
    if (currentTab.value === 'all') {
        return orders.value;
    }

    return orders.value.filter((o) => o.status === currentTab.value);
});

// Pagination State
const currentPage = ref(1);
const itemsPerPage = 5;

watch(currentTab, () => {
    currentPage.value = 1;
});

const totalPages = computed(() => {
    return Math.ceil(filteredOrders.value.length / itemsPerPage);
});

const paginatedOrders = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredOrders.value.slice(start, end);
});

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
};

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};

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
        processingCount: orders.value.filter((o) => o.status === 'processing').length,
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
        console.error('Gagal mengunduh QR Code', error);
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

const deleteTable = async (id, tableName) => {
    if (
        !confirm(
            `Apakah Anda yakin ingin menghapus ${tableName}? Seluruh data barcode meja ini akan dinonaktifkan.`,
        )
    ) {
        return;
    }

    try {
        const response = await axios.delete(`/api/tables/${id}`);

        if (response.data.success) {
            fetchTables();
            triggerToast('Meja berhasil dihapus!', 'success');
        }
    } catch (error) {
        console.error('Gagal menghapus meja', error);
        triggerToast('Gagal menghapus meja.', 'error');
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
                class="relative flex flex-col items-start justify-between overflow-hidden rounded-xl border border-[#D4A373]/30 bg-[#3B2314] p-6 text-[#FAEDCD] shadow-xl md:flex-row md:items-center"
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
                    <button
                        v-if="user.role === 'owner'"
                        @click="openRecapModal"
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
                    class="group rounded-xl border border-[#D4A373]/20 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md"
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
                    class="rounded-xl border border-[#D4A373]/20 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md"
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
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 font-bold text-red-600"
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
                    class="rounded-xl border border-[#D4A373]/20 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <span
                            class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                            >Jumlah Meja</span
                        >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-100 font-bold text-orange-600"
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
                    class="rounded-xl border border-[#D4A373]/20 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <span
                            class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                            >Rasio Dine-in</span
                        >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-100 font-bold text-teal-600"
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

            <!-- ACTIVE STAFF ON SHIFT (Owner Only) -->
            <div v-if="user.role === 'owner'" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#3B2314] text-[#FAEDCD]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A11.386 11.386 0 0 1 10.089 21c-2.243 0-4.352-.648-6.124-1.772a4.125 4.125 0 0 1 7.533-2.493M15 9.75a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM18.75 8.25a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-[#3B2314]">Staf & Shift Aktif Hari Ini</h3>
                            <p class="text-[10px] text-gray-400">Daftar barista dan kasir yang login ke sistem dalam 24 jam terakhir.</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-[10px] font-bold text-green-800 border border-green-200 flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        {{ activeStaff.length }} Online
                    </span>
                </div>
                
                <div class="flex flex-wrap gap-4">
                    <div v-for="staff in activeStaff" :key="staff.id" class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50/50 px-4 py-2.5 shadow-sm min-w-[200px] flex-1 md:flex-none">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#FAEDCD] font-bold text-[#3B2314] uppercase text-xs">
                            {{ staff.name.charAt(0) }}
                        </div>
                        <div>
                            <p class="text-xs font-black text-gray-800">{{ staff.name }}</p>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                <p class="text-[9px] font-black tracking-wider uppercase text-gray-400">{{ staff.role }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LIVE ORDERS BLOCK (Filter Tabs, Cards, & Real Action Buttons) -->
            <div
                class="overflow-hidden rounded-xl border border-[#D4A373]/20 bg-white shadow-sm"
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
                        class="flex w-full rounded-lg border bg-gray-100 p-1.5 text-xs font-bold md:w-auto"
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
                            class="flex flex-1 items-center justify-center gap-1.5 rounded-xl px-4 py-2 transition-all duration-200 md:flex-none"
                            :class="
                                currentTab === 'processing'
                                    ? 'bg-yellow-500 text-white shadow-sm'
                                    : 'text-gray-500 hover:text-yellow-600'
                            "
                        >
                            Diproses
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-red-400"
                                v-if="stats.processingCount > 0"
                            ></span>
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
                            v-for="order in paginatedOrders"
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
                                <div class="mb-3">
                                    <!-- Baris Atas: ID Pesanan, No Meja -->
                                    <div class="mb-2 flex items-center gap-2">
                                        <span class="text-lg font-black text-[#3B2314]">#{{ order.id }}</span>
                                        <span class="rounded-md bg-[#3B2314] px-2 py-0.5 text-[10px] font-bold text-white shadow-sm border-none">
                                            {{ order.table }}
                                        </span>
                                    </div>
                                    
                                    <!-- Baris Bawah: Takeaway/DineIn, Payment Method, Status Bayar -->
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-md px-2 py-0.5 text-[10px] font-black tracking-wider uppercase"
                                            :class="order.type === 'Dine In' ? 'border border-[#D4A373]/20 bg-[#FAEDCD] text-[#3B2314]' : 'border bg-gray-100 text-gray-600'">
                                            {{ order.type }}
                                        </span>

                                        <span class="flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold"
                                            :class="{
                                                'border border-blue-200 bg-blue-50 text-blue-700': order.payment_method === 'qris_tokopay',
                                                'border border-amber-200 bg-amber-50 text-amber-700': order.payment_method === 'qris_manual',
                                                'border border-teal-200 bg-teal-50 text-teal-700': order.payment_method === 'cashier',
                                            }">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3.5 w-3.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-19.5 5.25h19.5m-19.5 0h19.5M2.25 18h19.5A2.25 2.25 0 0 0 24 15.75V8.25A2.25 2.25 0 0 0 21.75 6H2.25A2.25 2.25 0 0 0 0 8.25v7.5A2.25 2.25 0 0 0 2.25 18Z" />
                                            </svg>
                                            {{ order.payment_method === 'qris_tokopay' ? 'Tokopay QRIS' : order.payment_method === 'qris_manual' ? 'QRIS Manual' : 'Bayar Kasir' }}
                                        </span>

                                        <span class="rounded-md px-2 py-0.5 text-[10px] font-black tracking-wider uppercase"
                                            :class="order.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500'">
                                            {{ order.payment_status === 'paid' ? 'LUNAS' : 'BELUM BAYAR' }}
                                        </span>
                                    </div>
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
                                                class="text-[9px] text-[#3B2314]/80 italic"
                                                >| ({{ item.notes }})</span>
                                        </div>
                                    </div>

                                    <!-- Voucher / Discount Details (Admin Only View) -->
                                    <div
                                        v-if="order.discount_amount > 0"
                                        class="flex flex-wrap gap-2 mt-2 items-center"
                                    >
                                        <span
                                            class="inline-flex items-center gap-1 rounded-xl bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-xs font-bold text-emerald-700"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5">
                                                <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V14.34a2.247 2.247 0 0 1-1.125.41 2.25 2.25 0 0 1-3.75-2.25 2.25 2.25 0 0 1 4.875-1.077V3.5A1.5 1.5 0 0 0 15.5 2h-11Zm10 6A1.5 1.5 0 1 0 16 5a1.5 1.5 0 0 0-1.5 3Zm-7-2a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Zm0 4a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Z" clip-rule="evenodd" />
                                            </svg>
                                            Voucher: {{ order.voucher_code }} (-Rp {{ order.discount_amount.toLocaleString('id-ID') }})
                                        </span>
                                        <span
                                            class="text-[10px] font-bold text-gray-400"
                                        >
                                            Subtotal: Rp {{ (order.total + order.discount_amount).toLocaleString('id-ID') }}
                                        </span>
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
                            <div class="mt-4 flex w-full shrink-0 justify-end gap-2 lg:mt-0 lg:w-auto">
                                <button
                                    @click="openDetailModal(order)"
                                    class="flex w-full transform items-center justify-center gap-1.5 rounded-xl bg-[#3B2314] px-5 py-2.5 text-xs font-bold text-white shadow-md transition hover:bg-[#25150c] hover:shadow-lg active:scale-95 lg:w-auto"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    Lihat Detail
                                </button>
                            </div>
                        </div>

                        <!-- Pagination Controls -->
                        <div v-if="totalPages > 1" class="flex flex-col items-center justify-center gap-3 border-t border-gray-100 pt-4 mt-4">
                            <span class="text-xs text-gray-500">
                                Menampilkan {{ (currentPage - 1) * itemsPerPage + 1 }} sampai {{ Math.min(currentPage * itemsPerPage, filteredOrders.length) }} dari {{ filteredOrders.length }} pesanan
                            </span>
                            <div class="flex items-center gap-2">
                                <button 
                                    @click="prevPage" 
                                    :disabled="currentPage === 1"
                                    class="rounded-lg border px-3 py-1.5 text-xs font-bold transition-all"
                                    :class="currentPage === 1 ? 'text-gray-300 border-gray-100 cursor-not-allowed' : 'bg-[#D4A373] text-[#3B2314] border-[#D4A373] hover:bg-[#FAEDCD] shadow-sm'"
                                >
                                    Sebelumnya
                                </button>
                                <span class="text-xs font-bold text-[#3B2314]">
                                    Halaman {{ currentPage }} dari {{ totalPages }}
                                </span>
                                <button 
                                    @click="nextPage" 
                                    :disabled="currentPage === totalPages"
                                    class="rounded-lg border px-3 py-1.5 text-xs font-bold transition-all"
                                    :class="currentPage === totalPages ? 'text-gray-300 border-gray-100 cursor-not-allowed' : 'bg-[#D4A373] text-[#3B2314] border-[#D4A373] hover:bg-[#FAEDCD] shadow-sm'"
                                >
                                    Selanjutnya
                                </button>
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
                class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                @click.self="showProofModal = false"
            >
                <div
                    class="animate-scale-in relative w-full max-w-lg overflow-hidden rounded-xl bg-white p-6 shadow-2xl"
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
                        class="flex max-h-[60vh] items-center justify-center overflow-y-auto rounded-lg border bg-gray-50 p-2"
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
        <!-- Modal Kirim Rekapan WA (Owner Only) -->
        <Transition
            enter-active-class="ease-out duration-300 transition"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="ease-in duration-200 transition"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showRecapModal"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                @click.self="showRecapModal = false"
            >
                <div
                    class="relative w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl transition-all"
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b p-5">
                        <h3 class="flex items-center gap-2 text-lg font-black text-[#3B2314]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5 text-[#D4A373]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                            </svg>
                            Kirim Rekapan WA
                        </h3>
                        <button
                            @click="showRecapModal = false"
                            class="text-gray-400 transition hover:text-gray-600 rounded-full hover:bg-gray-100 p-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-5">
                        <div class="space-y-3">
                            <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Pilih Rentang Waktu</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    @click="recapType = 'today'"
                                    type="button"
                                    class="flex flex-col items-center justify-center p-3 rounded-xl border text-center transition-all duration-200 active:scale-95"
                                    :class="recapType === 'today' ? 'border-[#3B2314] bg-[#FAEDCD]/20 text-[#3B2314] font-bold shadow-sm' : 'border-gray-200 hover:border-gray-300 text-gray-600'"
                                >
                                    <span class="text-xs">Hari Ini</span>
                                </button>
                                <button
                                    @click="recapType = 'yesterday'"
                                    type="button"
                                    class="flex flex-col items-center justify-center p-3 rounded-xl border text-center transition-all duration-200 active:scale-95"
                                    :class="recapType === 'yesterday' ? 'border-[#3B2314] bg-[#FAEDCD]/20 text-[#3B2314] font-bold shadow-sm' : 'border-gray-200 hover:border-gray-300 text-gray-600'"
                                >
                                    <span class="text-xs">Kemarin</span>
                                </button>
                                <button
                                    @click="recapType = 'single'"
                                    type="button"
                                    class="flex flex-col items-center justify-center p-3 rounded-xl border text-center transition-all duration-200 active:scale-95"
                                    :class="recapType === 'single' ? 'border-[#3B2314] bg-[#FAEDCD]/20 text-[#3B2314] font-bold shadow-sm' : 'border-gray-200 hover:border-gray-300 text-gray-600'"
                                >
                                    <span class="text-xs">Pilih Tanggal</span>
                                </button>
                                <button
                                    @click="recapType = 'range'"
                                    type="button"
                                    class="flex flex-col items-center justify-center p-3 rounded-xl border text-center transition-all duration-200 active:scale-95"
                                    :class="recapType === 'range' ? 'border-[#3B2314] bg-[#FAEDCD]/20 text-[#3B2314] font-bold shadow-sm' : 'border-gray-200 hover:border-gray-300 text-gray-600'"
                                >
                                    <span class="text-xs">Rentang Tanggal</span>
                                </button>
                            </div>
                        </div>

                        <!-- Date Pickers -->
                        <div class="relative overflow-hidden min-h-[80px] flex items-center">
                            <div v-if="recapType === 'single'" class="space-y-2 w-full">
                                <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal Laporan</label>
                                <input
                                    v-model="recapStartDate"
                                    type="date"
                                    class="w-full rounded-xl border border-gray-200 bg-white p-3 text-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373] focus:outline-none"
                                />
                            </div>

                            <div v-else-if="recapType === 'range'" class="grid grid-cols-2 gap-4 w-full">
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal Mulai</label>
                                    <input
                                        v-model="recapStartDate"
                                        type="date"
                                        class="w-full rounded-xl border border-gray-200 bg-white p-3 text-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373] focus:outline-none"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal Selesai</label>
                                    <input
                                        v-model="recapEndDate"
                                        type="date"
                                        class="w-full rounded-xl border border-gray-200 bg-white p-3 text-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373] focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div v-else class="text-center w-full text-xs text-gray-400 italic">
                                Rekapan akan mencakup transaksi untuk periode: <span class="font-bold text-[#3B2314]">{{ recapType === 'today' ? 'Hari Ini' : 'Kemarin' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="border-t bg-gray-50 p-5 flex justify-end gap-3">
                        <button
                            @click="showRecapModal = false"
                            class="rounded-xl bg-gray-200 px-5 py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-300 transition shadow-sm"
                            type="button"
                        >
                            Batal
                        </button>
                        <button
                            @click="sendReport"
                            :disabled="isSendingRecap"
                            class="flex items-center gap-2 rounded-xl bg-[#3B2314] px-6 py-2.5 text-xs font-bold text-white shadow-md transition hover:bg-[#25150c] hover:shadow-lg disabled:opacity-50 active:scale-95"
                            type="button"
                        >
                            <svg v-if="isSendingRecap" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isSendingRecap ? 'Mengirim...' : 'Kirim Rekap WA' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
        <!-- Modal Detail Pesanan -->
        <Transition
            enter-active-class="ease-out duration-300 transition"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="ease-in duration-200 transition"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showDetailModal"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                @click.self="closeDetailModal"
            >
                <div class="animate-scale-in relative w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-2xl flex flex-col max-h-[90vh]">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b p-5">
                        <div>
                            <h3 class="flex items-center gap-1.5 text-lg font-black text-[#3B2314]">
                                Detail Pesanan #{{ selectedOrder?.id }}
                            </h3>
                            <div class="text-xs font-medium text-gray-500 mt-1 flex flex-col gap-0.5">
                                <p>Waktu Masuk: {{ selectedOrder?.time }} WIB</p>
                                <p>Nomor Meja: <span class="font-bold text-gray-700">{{ selectedOrder?.table }}</span> <span v-if="selectedOrder?.type" class="text-gray-400">({{ selectedOrder?.type }})</span></p>
                            </div>
                        </div>
                        <button
                            @click="closeDetailModal"
                            class="text-gray-400 transition hover:text-gray-600 rounded-full hover:bg-gray-100 p-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="overflow-y-auto p-6 space-y-6 flex-1">
                        
                        <!-- Info Pelanggan & Pembayaran -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                                <h4 class="mb-3 text-xs font-black tracking-widest text-gray-400 uppercase">Data Pelanggan</h4>
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-500">NAMA</p>
                                        <p class="text-sm font-bold text-gray-800">{{ selectedOrder?.name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-500">NO WHATSAPP</p>
                                        <p class="text-sm font-bold text-gray-800">{{ selectedOrder?.customer_phone || '-' }}</p>
                                    </div>
                                    <div v-if="selectedOrder?.notes">
                                        <p class="text-[10px] font-bold text-gray-500">CATATAN BARISTA</p>
                                        <p class="text-sm font-bold text-amber-600">"{{ selectedOrder.notes }}"</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                                <h4 class="mb-3 text-xs font-black tracking-widest text-gray-400 uppercase">Info Pembayaran</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center">
                                        <p class="text-[10px] font-bold text-gray-500">METODE</p>
                                        <span class="rounded-md bg-white px-2 py-1 text-[10px] font-bold border shadow-sm">
                                            {{ selectedOrder?.payment_method === 'qris_tokopay' ? 'Tokopay QRIS' : selectedOrder?.payment_method === 'qris_manual' ? 'QRIS Manual' : 'Bayar Kasir' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <p class="text-[10px] font-bold text-gray-500">STATUS</p>
                                        <span class="rounded-md px-2 py-1 text-[10px] font-bold shadow-sm"
                                            :class="selectedOrder?.payment_status === 'paid' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-gray-100 text-gray-600 border border-gray-200'">
                                            {{ selectedOrder?.payment_status === 'paid' ? 'LUNAS' : 'BELUM BAYAR' }}
                                        </span>
                                    </div>
                                    <div v-if="selectedOrder?.payment_proof" class="pt-2 mt-2 border-t border-gray-200">
                                        <p class="text-[10px] font-bold text-gray-500 mb-2">BUKTI TRANSFER</p>
                                        <button @click="openProofModal(selectedOrder.payment_proof)" class="w-full rounded-xl border border-blue-200 bg-blue-50 py-2 text-xs font-bold text-blue-700 hover:bg-blue-100 transition flex items-center justify-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                            Lihat Bukti Transfer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Daftar Pesanan -->
                        <div>
                            <h4 class="mb-3 text-xs font-black tracking-widest text-gray-400 uppercase border-b pb-2">Item Pesanan</h4>
                            <div class="space-y-3">
                                <div v-for="(item, idx) in selectedOrder?.items" :key="idx" class="flex justify-between items-start border-b border-gray-50 pb-3 last:border-0 last:pb-0">
                                    <div class="flex gap-3">
                                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#3B2314] text-xs font-black text-white">
                                            {{ item.quantity }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-800">{{ item.name }}</p>
                                            <p v-if="item.notes" class="text-[13px] font-semibold text-gray-800 italic mt-0.5">Catatan: {{ item.notes }}</p>
                                        </div>
                                    </div>
                                    <div class="text-sm font-bold text-gray-700 whitespace-nowrap">
                                        Rp {{ (item.price * item.quantity).toLocaleString('id-ID') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        
                        <!-- Rincian Harga -->
                        <div class="rounded-2xl border border-[#D4A373]/30 bg-[#FAEDCD]/30 p-4">
                            <div v-if="selectedOrder?.discount_amount > 0" class="flex justify-between text-sm mb-2 text-gray-600">
                                <span>Subtotal</span>
                                <span>Rp {{ (selectedOrder.total + selectedOrder.discount_amount).toLocaleString('id-ID') }}</span>
                            </div>
                            <div v-if="selectedOrder?.discount_amount > 0" class="flex justify-between text-sm mb-2 text-emerald-600">
                                <span>Voucher ({{ selectedOrder.voucher_code }})</span>
                                <span>- Rp {{ selectedOrder.discount_amount.toLocaleString('id-ID') }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-[#D4A373]/20">
                                <span class="text-sm font-black text-gray-800">Total Akhir</span>
                                <span class="text-xl font-black text-[#3B2314]">Rp {{ selectedOrder?.total?.toLocaleString('id-ID') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer / Actions -->
                    <div class="border-t bg-gray-50 p-5 flex justify-end gap-3 rounded-b-3xl">
                        <button
                            @click="closeDetailModal"
                            class="rounded-xl bg-gray-200 px-5 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-300 transition shadow-sm"
                        >
                            Tutup
                        </button>
                        
                        <button
                            v-if="selectedOrder?.status === 'pending'"
                            @click="acceptOrder(selectedOrder.id)"
                            class="flex items-center gap-2 rounded-xl bg-[#3B2314] px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-[#25150c] hover:shadow-lg active:scale-95"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Terima Pesanan
                        </button>

                        <button
                            v-if="selectedOrder?.status === 'processing'"
                            @click="completeOrder(selectedOrder.id)"
                            class="flex items-center gap-2 rounded-xl bg-green-600 px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-green-700 hover:shadow-lg active:scale-95"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                            </svg>
                            Tandai Selesai
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </ZunoiAdminLayout>
</template>
