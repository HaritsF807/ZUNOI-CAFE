<script setup>
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

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
const alarmSound = typeof window !== 'undefined' ? new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg') : null;
const pendingCount = computed(() => orders.value.filter(o => o.status === 'pending').length);

watch(pendingCount, (newCount, oldCount) => {
    // Bunyikan alarm jika ada pesanan pending baru
    if (newCount > oldCount && alarmSound) {
        alarmSound.play().catch(e => console.log('Autoplay ditolak.', e));
    }
});

// Sistem Polling API
let pollInterval;
const fetchOrders = async () => {
    try {
        const response = await axios.get('/api/orders/live');
        orders.value = response.data;
    } catch (error) {
        console.error("Gagal mengambil data live orders", error);
    }
};

const fetchTables = async () => {
    try {
        const response = await axios.get('/api/tables');
        tables.value = response.data;
    } catch (error) {
        console.error("Gagal mengambil data meja", error);
    }
};

const addTable = async () => {
    if(!newTableName.value) return;
    try {
        await axios.post('/api/tables', { table_name: newTableName.value });
        newTableName.value = '';
        fetchTables(); // Refresh list meja
        alert("Meja baru berhasil ditambahkan!");
    } catch (error) {
        console.error("Gagal tambah meja", error);
    }
};

// Update status pesanan secara RIIL ke database
const acceptOrder = async (id) => {
    try {
        const response = await axios.patch(`/api/orders/${id}/status`, { order_status: 'processing' });
        if (response.data.success) {
            fetchOrders();
            alert(`Pesanan #${id} berhasil diterima!`);
        }
    } catch (error) {
        console.error("Gagal menerima pesanan", error);
        alert("Gagal memperbarui status pesanan.");
    }
};

const completeOrder = async (id) => {
    try {
        const response = await axios.patch(`/api/orders/${id}/status`, { order_status: 'completed' });
        if (response.data.success) {
            fetchOrders();
            alert(`Pesanan #${id} ditandai sebagai selesai!`);
        }
    } catch (error) {
        console.error("Gagal menyelesaikan pesanan", error);
        alert("Gagal memperbarui status pesanan.");
    }
};

const sendReport = () => {
    alert("Rekapan harian sedang dikirim ke WhatsApp Owner...");
};

// Form Integrasi (Khusus Owner)
const integrationForm = ref({
    fonnte_token: 'TokenFonnteAnda123',
    tokopay_merchant_id: 'M-123456',
    tokopay_secret: 'SecretKey...'
});

const saveIntegration = () => {
    alert("Pengaturan Integrasi berhasil disimpan! (Mockup)");
};

// Filtered Orders berdasarkan tab yang dipilih
const filteredOrders = computed(() => {
    if (currentTab.value === 'all') return orders.value;
    return orders.value.filter(o => o.status === currentTab.value);
});

// Analytics Summary
const stats = computed(() => {
    const activeOrders = orders.value.filter(o => o.status !== 'cancelled');
    const totalRev = activeOrders.reduce((sum, o) => sum + o.total, 0);
    const dineInCount = activeOrders.filter(o => o.type === 'Dine In').length;
    const takeawayCount = activeOrders.filter(o => o.type === 'Takeaway').length;
    
    return {
        totalRevenue: totalRev,
        pendingCount: orders.value.filter(o => o.status === 'pending').length,
        activeTables: tables.value.length,
        dineInPercent: activeOrders.length ? Math.round((dineInCount / activeOrders.length) * 100) : 0,
        takeawayPercent: activeOrders.length ? Math.round((takeawayCount / activeOrders.length) * 100) : 0
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
        alert("Gagal mengunduh QR Code. Silakan klik kanan pada gambar untuk menyimpannya.");
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
    if (!confirm(`Apakah Anda yakin ingin menghapus ${tableName}? Seluruh data barcode meja ini akan dinonaktifkan.`)) return;
    try {
        const response = await axios.delete(`/api/tables/${id}`);
        if (response.data.success) {
            fetchTables();
            alert("Meja berhasil dihapus!");
        }
    } catch (error) {
        console.error("Gagal menghapus meja", error);
        alert("Gagal menghapus meja.");
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
        <div class="max-w-7xl mx-auto space-y-8 text-gray-800">
            
            <!-- HEADER SECTION -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-[#3B2314] text-[#FAEDCD] p-6 rounded-3xl shadow-xl border border-[#D4A373]/30 relative overflow-hidden">
                <div class="absolute -right-16 -top-16 w-48 h-48 bg-[#D4A373]/10 rounded-full blur-2xl"></div>
                <div class="z-10">
                    <h2 class="font-extrabold text-2xl tracking-wide flex items-center gap-2">
                        Dashboard {{ user.role === 'owner' ? 'Owner' : 'Barista' }}
                        <span class="text-[10px] bg-[#D4A373] text-[#3B2314] font-black uppercase px-2 py-0.5 rounded-full tracking-widest align-middle">Zunoi PRO</span>
                    </h2>
                    <p class="text-xs text-gray-300 mt-1">Kelola pesanan QR, cetak barcode meja, dan integrasikan gateway sistem Anda secara instan.</p>
                </div>
                
                <div class="mt-4 md:mt-0 flex items-center gap-3 z-10">
                    <!-- Live Polling Indicator -->
                    <div class="flex items-center gap-2 bg-[#FAEDCD]/10 px-3 py-1.5 rounded-full border border-[#D4A373]/20">
                        <span class="relative flex h-2.5 w-2.5">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                        </span>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-green-400">Live Polling</span>
                    </div>

                    <button v-if="user.role === 'owner'" @click="sendReport" class="bg-[#D4A373] hover:bg-[#FAEDCD] text-[#3B2314] px-5 py-2 rounded-xl text-sm font-bold shadow-md transition-all duration-300 hover:shadow-lg transform active:scale-95 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                        </svg>
                        Kirim Rekapan WA
                    </button>
                </div>
            </div>

            <!-- ANALYTICS CARDS (Warm & Clean UI) -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Card 1: Revenue -->
                <div class="bg-white p-6 rounded-3xl border border-[#D4A373]/20 shadow-sm hover:shadow-md transition-all duration-300 group">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Pendapatan</span>
                        <div class="w-10 h-10 rounded-2xl bg-[#FAEDCD] text-[#3B2314] flex items-center justify-center font-bold">Rp</div>
                    </div>
                    <h3 class="text-2xl font-black text-[#3B2314] tracking-tight">Rp {{ stats.totalRevenue.toLocaleString('id-ID') }}</h3>
                    <p class="text-xs text-gray-500 mt-1">Akumulasi transaksi hari ini</p>
                </div>

                <!-- Card 2: Pending Orders -->
                <div class="bg-white p-6 rounded-3xl border border-[#D4A373]/20 shadow-sm hover:shadow-md transition-all duration-300"
                     :class="{'border-red-300 ring-2 ring-red-100 bg-red-50/10': stats.pendingCount > 0}">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Pesanan Pending</span>
                        <div class="w-10 h-10 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center font-bold">
                            <span class="relative flex h-3 w-3" v-if="stats.pendingCount > 0">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                            </span>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-black tracking-tight" :class="stats.pendingCount > 0 ? 'text-red-600' : 'text-[#3B2314]'">{{ stats.pendingCount }}</h3>
                    <p class="text-xs text-gray-500 mt-1">Pesanan baru yang butuh konfirmasi</p>
                </div>

                <!-- Card 3: Active Tables -->
                <div class="bg-white p-6 rounded-3xl border border-[#D4A373]/20 shadow-sm hover:shadow-md transition-all duration-300">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Jumlah Meja</span>
                        <div class="w-10 h-10 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 8.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-black text-[#3B2314] tracking-tight">{{ stats.activeTables }} Meja</h3>
                    <p class="text-xs text-gray-500 mt-1">Meja ber-QR terdaftar saat ini</p>
                </div>

                <!-- Card 4: Ratio -->
                <div class="bg-white p-6 rounded-3xl border border-[#D4A373]/20 shadow-sm hover:shadow-md transition-all duration-300">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Rasio Dine-in</span>
                        <div class="w-10 h-10 rounded-2xl bg-teal-100 text-teal-600 flex items-center justify-center font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
                              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-black text-[#3B2314] tracking-tight">{{ stats.dineInPercent }}% : {{ stats.takeawayPercent }}%</h3>
                    <p class="text-xs text-gray-500 mt-1">Persentase Dine-In vs Takeaway</p>
                </div>
            </div>

            <!-- LIVE ORDERS BLOCK (Filter Tabs, Cards, & Real Action Buttons) -->
            <div class="bg-white rounded-3xl border border-[#D4A373]/20 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-[#3B2314] flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
                            Live Order Management
                        </h3>
                        <p class="text-xs text-gray-400 mt-1">Pantau pesanan yang masuk dan perbarui status pengerjaan secara waktu nyata.</p>
                    </div>

                    <!-- Tabs Filter Modern -->
                    <div class="flex bg-gray-100 p-1.5 rounded-2xl w-full md:w-auto text-xs font-bold border">
                        <button @click="currentTab = 'all'" 
                                class="flex-1 md:flex-none px-4 py-2 rounded-xl transition-all duration-200"
                                :class="currentTab === 'all' ? 'bg-[#3B2314] text-white shadow-sm' : 'text-gray-500 hover:text-[#3B2314]'">
                            Semua
                        </button>
                        <button @click="currentTab = 'pending'" 
                                class="flex-1 md:flex-none px-4 py-2 rounded-xl transition-all duration-200 flex items-center gap-1.5 justify-center"
                                :class="currentTab === 'pending' ? 'bg-red-500 text-white shadow-sm' : 'text-gray-500 hover:text-red-500'">
                            Pending
                            <span class="w-1.5 h-1.5 rounded-full bg-red-400" v-if="stats.pendingCount > 0"></span>
                        </button>
                        <button @click="currentTab = 'processing'" 
                                class="flex-1 md:flex-none px-4 py-2 rounded-xl transition-all duration-200"
                                :class="currentTab === 'processing' ? 'bg-yellow-500 text-white shadow-sm' : 'text-gray-500 hover:text-yellow-600'">
                            Diproses
                        </button>
                        <button @click="currentTab = 'completed'" 
                                class="flex-1 md:flex-none px-4 py-2 rounded-xl transition-all duration-200"
                                :class="currentTab === 'completed' ? 'bg-green-600 text-white shadow-sm' : 'text-gray-500 hover:text-green-600'">
                            Selesai
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div v-if="filteredOrders.length === 0" class="flex flex-col items-center justify-center py-12 text-center text-gray-400">
                        <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center border mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 16.318A4.486 4.486 0 0 0 12.016 15a4.486 4.486 0 0 0-3.198 1.318M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                            </svg>
                        </div>
                        <h4 class="font-bold text-gray-500 text-sm">Tidak ada pesanan</h4>
                        <p class="text-xs text-gray-400 mt-1">Belum ada pesanan dengan status '{{ currentTab }}' saat ini.</p>
                    </div>

                    <div class="space-y-4" v-else>
                        <div v-for="order in filteredOrders" :key="order.id" 
                             class="flex flex-col lg:flex-row justify-between items-start lg:items-center p-5 border rounded-2xl transition-all duration-300 hover:border-[#D4A373]/50 hover:shadow-sm"
                             :class="{
                                 'border-red-200 bg-red-50/10': order.status === 'pending', 
                                 'border-yellow-200 bg-yellow-50/10': order.status === 'processing',
                                 'border-green-200 bg-green-50/10': order.status === 'completed'
                             }">
                            
                            <div class="flex-1 w-full">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="font-black text-lg text-[#3B2314]">#{{ order.id }}</span>
                                    
                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-black uppercase tracking-wider" 
                                          :class="order.type === 'Dine In' ? 'bg-[#FAEDCD] text-[#3B2314] border border-[#D4A373]/20' : 'bg-gray-100 text-gray-600 border'">
                                        {{ order.type }}
                                    </span>

                                    <span class="text-[10px] bg-white border border-[#D4A373]/20 px-2 py-0.5 rounded-full font-bold text-gray-700 flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25a7.5 7.5 0 1 1 15 0Z" />
                                        </svg>
                                        {{ order.table }}
                                    </span>

                                    <!-- Payment Method & Status Badges -->
                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold flex items-center gap-1"
                                          :class="{
                                              'bg-blue-50 text-blue-700 border border-blue-200': order.payment_method === 'qris_tokopay',
                                              'bg-amber-50 text-amber-700 border border-amber-200': order.payment_method === 'qris_manual',
                                              'bg-teal-50 text-teal-700 border border-teal-200': order.payment_method === 'cashier'
                                          }">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-19.5 5.25h19.5m-19.5 0h19.5M2.25 18h19.5A2.25 2.25 0 0 0 24 15.75V8.25A2.25 2.25 0 0 0 21.75 6H2.25A2.25 2.25 0 0 0 0 8.25v7.5A2.25 2.25 0 0 0 2.25 18Z" />
                                        </svg>
                                        {{ order.payment_method === 'qris_tokopay' ? 'Tokopay QRIS' : (order.payment_method === 'qris_manual' ? 'QRIS Manual' : 'Bayar Kasir') }}
                                    </span>

                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-black uppercase tracking-wider"
                                          :class="order.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500'">
                                        {{ order.payment_status === 'paid' ? 'LUNAS' : 'BELUM BAYAR' }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between lg:justify-start gap-6">
                                    <div>
                                        <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Pelanggan</p>
                                        <p class="text-sm font-bold text-gray-700">{{ order.name }}</p>
                                    </div>
                                    <div class="h-6 w-px bg-gray-200"></div>
                                    <div>
                                        <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Total Belanja</p>
                                        <p class="text-sm font-extrabold text-[#3B2314]">Rp {{ order.total.toLocaleString('id-ID') }}</p>
                                    </div>
                                    <div class="h-6 w-px bg-gray-200"></div>
                                    <div>
                                        <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Waktu</p>
                                        <p class="text-sm font-semibold text-gray-600">{{ order.time }} WIB</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions Buttons -->
                            <div class="mt-4 lg:mt-0 flex gap-2 w-full lg:w-auto shrink-0 justify-end">
                                <button v-if="order.status === 'pending'" @click="acceptOrder(order.id)" 
                                        class="w-full lg:w-auto bg-[#3B2314] hover:bg-[#25150c] text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition transform active:scale-95 flex items-center justify-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    Terima Pesanan
                                </button>
                                
                                <button v-if="order.status === 'processing'" @click="completeOrder(order.id)" 
                                        class="w-full lg:w-auto bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition transform active:scale-95 flex items-center justify-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                    </svg>
                                    Tandai Selesai
                                </button>

                                <span v-if="order.status === 'completed'" 
                                      class="px-4 py-2 bg-green-100 text-green-800 text-xs font-black uppercase rounded-xl tracking-wider flex items-center gap-1.5 select-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-green-700">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    Selesai Dikerjakan
                                </span>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </ZunoiAdminLayout>
</template>
