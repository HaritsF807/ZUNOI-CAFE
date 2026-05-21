<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps<{
    orders: any;
    filters: {
        start_date?: string;
        end_date?: string;
        search?: string;
        preset?: string;
    };
}>();

// Tab State
const activeTab = ref<'pembelian' | 'pembayaran'>('pembelian');

// Search and Filter State
const searchQuery = ref(props.filters.search || '');
const activePreset = ref<'today' | 'last7' | 'thisMonth' | null>((props.filters.preset as any) || null);
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const dateRange = ref<[Date, Date] | null>(null);
const isMounted = ref(false);

watch(dateRange, (newVal, oldVal) => {
    // Only apply if it's a complete range (2 dates) or cleared (null)
    if ((newVal && newVal.length === 2) || newVal === null) {
        if (newVal === null) activePreset.value = null;
        applyFilter();
    }
});

// Modal state
const showProofModal = ref(false);
const currentProofImage = ref('');
const showDetailsModal = ref(false);
const selectedOrder = ref<any>(null);

const openProofModal = (proofStr: string) => {
    currentProofImage.value = proofStr;
    showProofModal.value = true;
};

const openDetailsModal = (order: any) => {
    selectedOrder.value = order;
    showDetailsModal.value = true;
};

onMounted(() => {
    isMounted.value = true;
    if (props.filters.start_date && props.filters.end_date) {
        dateRange.value = [new Date(props.filters.start_date), new Date(props.filters.end_date)];
    }
});

// Format IDR Currency
const formatIDR = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const applyFilter = () => {
    if (dateRange.value && dateRange.value.length === 2) {
        // Adjust for local timezone offsets to get correct YYYY-MM-DD
        const start = new Date(dateRange.value[0]);
        start.setMinutes(start.getMinutes() - start.getTimezoneOffset());
        startDate.value = start.toISOString().split('T')[0];

        const end = new Date(dateRange.value[1]);
        end.setMinutes(end.getMinutes() - end.getTimezoneOffset());
        endDate.value = end.toISOString().split('T')[0];
    } else {
        startDate.value = '';
        endDate.value = '';
    }

    router.get('/dashboard/history', {
        start_date: startDate.value,
        end_date: endDate.value,
        search: searchQuery.value,
        preset: activePreset.value
    }, { preserveState: true, replace: true });
};

const formatDateLocal = (date: Date) => {
    const d = new Date(date);
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().split('T')[0];
};

const setPreset = (preset: 'today' | 'last7' | 'thisMonth') => {
    activePreset.value = preset;
    const today = new Date();
    
    if (preset === 'today') {
        dateRange.value = [today, today];
    } else if (preset === 'last7') {
        const last7 = new Date(today);
        last7.setDate(today.getDate() - 6);
        dateRange.value = [last7, today];
    } else if (preset === 'thisMonth') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        dateRange.value = [firstDay, today];
    }
    
    applyFilter();
};

const clearFilter = () => {
    activePreset.value = null;
    searchQuery.value = '';
    dateRange.value = null;
    startDate.value = '';
    endDate.value = '';
    applyFilter();
};

const displayFormatDate = (dateString: string) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(date);
};

const getStatusColor = (status: string) => {
    switch(status) {
        case 'completed': return 'bg-green-100 text-green-700 border-green-200';
        case 'cancelled': return 'bg-red-100 text-red-700 border-red-200';
        case 'pending': return 'bg-yellow-100 text-yellow-700 border-yellow-200';
        case 'preparing': return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'ready': return 'bg-purple-100 text-purple-700 border-purple-200';
        case 'paid': return 'bg-green-100 text-green-700 border-green-200';
        case 'unpaid': return 'bg-gray-100 text-gray-700 border-gray-200';
        case 'refunded': return 'bg-red-100 text-red-700 border-red-200';
        default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
};

const getStatusLabel = (status: string) => {
    switch(status) {
        case 'completed': return 'Selesai';
        case 'cancelled': return 'Dibatalkan';
        case 'pending': return 'Menunggu';
        case 'preparing': return 'Disiapkan';
        case 'ready': return 'Siap Diambil';
        case 'paid': return 'Lunas';
        case 'unpaid': return 'Belum Bayar';
        case 'refunded': return 'Dikembalikan (Refund)';
        default: return status;
    }
};
</script>

<template>
    <Head title="Riwayat Transaksi" />

    <ZunoiAdminLayout>
        <div class="mb-6 flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
            <div>
                <h1 class="text-2xl font-bold text-[#3B2314]">Riwayat Transaksi</h1>
                <p class="text-sm font-medium text-[#D4A373]">
                    Lacak semua pesanan dan pembayaran yang pernah terjadi.
                </p>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl shadow-sm border border-[#D4A373]/20 p-5 mb-6 flex flex-col gap-4">
            <div class="flex flex-col md:flex-row justify-between md:items-end gap-6">
                <!-- Preset Filters -->
                <div class="w-full md:w-auto flex-1">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Pilih Cepat</label>
                    <div class="flex flex-wrap gap-2">
                        <button @click="setPreset('today')" :class="['px-4 py-2 text-xs font-bold rounded-xl transition', activePreset === 'today' ? 'bg-[#D4A373] text-white' : 'bg-[#3B2314] text-white hover:bg-[#D4A373]']">Hari Ini</button>
                        <button @click="setPreset('last7')" :class="['px-4 py-2 text-xs font-bold rounded-xl transition', activePreset === 'last7' ? 'bg-[#D4A373] text-white' : 'bg-[#3B2314] text-white hover:bg-[#D4A373]']">7 Hari Terakhir</button>
                        <button @click="setPreset('thisMonth')" :class="['px-4 py-2 text-xs font-bold rounded-xl transition', activePreset === 'thisMonth' ? 'bg-[#D4A373] text-white' : 'bg-[#3B2314] text-white hover:bg-[#D4A373]']">Bulan Ini</button>
                    </div>
                </div>

                <!-- Custom Range Filters -->
                <div class="w-full md:w-auto">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Pilih Rentang Tanggal</label>
                    <div class="flex items-center gap-3">
                        <VueDatePicker 
                            v-if="isMounted"
                            v-model="dateRange" 
                            range 
                            :enable-time-picker="false" 
                            :multi-calendars="false" 
                            :max-date="new Date()"
                            format="dd MMM yyyy"
                            input-class-name="!bg-white !border-gray-300 !rounded-xl !text-sm !font-semibold !text-gray-700 !px-4 !py-2.5 shadow-sm hover:!border-[#D4A373] focus:!ring-[#D4A373] focus:!border-[#D4A373]"
                            placeholder="Pilih Tanggal Mulai - Selesai"
                        >
                            <template #trigger>
                                <button class="flex items-center gap-3 bg-white border border-gray-300 rounded-xl px-4 py-2 text-sm font-semibold text-[#3B2314] shadow-sm hover:border-[#D4A373] transition">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#3B2314" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" /></svg>
                                        <span>{{ dateRange?.[0] ? new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }).format(dateRange[0]) : 'Pilih Tanggal' }}</span>
                                    </div>
                                    <span v-if="dateRange?.[1]" class="text-gray-300">|</span>
                                    <div v-if="dateRange?.[1]" class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#3B2314" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" /></svg>
                                        <span>{{ new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }).format(dateRange[1]) }}</span>
                                    </div>
                                </button>
                            </template>
                        </VueDatePicker>
                    </div>
                </div>
            </div>
            <!-- Clear Filter Indicator -->
            <div v-if="filters.start_date || filters.end_date || filters.search" class="border-t border-gray-100 pt-3 flex items-center justify-between">
                <p class="text-xs font-medium text-gray-500">
                    <span v-if="filters.start_date && filters.end_date">Menampilkan data dari <span class="font-bold text-[#3B2314]">{{ displayFormatDate(filters.start_date!) }}</span> - <span class="font-bold text-[#3B2314]">{{ displayFormatDate(filters.end_date!) }}</span></span>
                    <span v-if="(filters.start_date && filters.end_date) && filters.search"> | </span>
                    <span v-if="filters.search">Pencarian: <span class="font-bold text-[#3B2314]">"{{ filters.search }}"</span></span>
                </p>
                <button @click="clearFilter" class="px-3 py-1 text-xs font-bold rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition inline-flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    Hapus Filter
                </button>
            </div>
        </div>

        <!-- Main Content Box -->
        <div class="bg-white rounded-2xl shadow-sm border border-[#D4A373]/20 overflow-hidden">
            
            <!-- Tabs and Search -->
            <div class="flex flex-col md:flex-row justify-between md:items-center border-b border-gray-100 bg-gray-50/50 pr-0 md:pr-4">
                <div class="flex overflow-x-auto hide-scrollbar">
                    <button 
                        @click="activeTab = 'pembelian'" 
                        :class="['px-6 py-4 text-sm font-bold border-b-2 transition-all duration-300 whitespace-nowrap', activeTab === 'pembelian' ? 'border-[#3B2314] text-[#3B2314] bg-white' : 'border-transparent text-gray-500 hover:bg-[#FAEDCD]/30 hover:text-[#3B2314] hover:border-[#D4A373]/30']"
                    >
                        Riwayat Pembelian (Sales)
                    </button>
                    <button 
                        @click="activeTab = 'pembayaran'" 
                        :class="['px-6 py-4 text-sm font-bold border-b-2 transition-all duration-300 whitespace-nowrap', activeTab === 'pembayaran' ? 'border-[#3B2314] text-[#3B2314] bg-white' : 'border-transparent text-gray-500 hover:bg-[#FAEDCD]/30 hover:text-[#3B2314] hover:border-[#D4A373]/30']"
                    >
                        Riwayat Pembayaran (Payments)
                    </button>
                </div>
                <div class="p-3 md:p-0">
                    <div class="relative">
                        <input 
                            v-model="searchQuery" 
                            @keyup.enter="applyFilter"
                            type="text" 
                            :placeholder="activeTab === 'pembelian' ? 'Cari No. Pesanan / Pelanggan...' : 'Cari ID Transaksi...'" 
                            class="w-full md:w-72 pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#D4A373] focus:border-[#D4A373] outline-none transition-colors"
                        >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Table Riwayat Pembelian -->
            <div class="overflow-x-auto" v-if="activeTab === 'pembelian'">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-600 font-semibold border-b border-gray-100 uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Nomor Pesanan</th>
                            <th class="px-6 py-4">Item yang Dibeli</th>
                            <th class="px-6 py-4">Pelanggan / Meja</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="props.orders.data.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">Belum ada riwayat transaksi pada rentang waktu ini.</td>
                        </tr>
                        <tr v-else v-for="order in props.orders.data" :key="order.id" class="hover:bg-orange-50/30 transition-colors">
                            <td class="px-6 py-4 font-bold text-[#3B2314]">#{{ order.id }}</td>
                            <td class="px-6 py-4">
                                <span class="text-gray-700 truncate max-w-xs block" :title="order.items_summary">{{ order.items_summary || '-' }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-700 font-medium">{{ order.customer_name }}</td>
                            <td class="px-6 py-4">
                                <span :class="['px-2.5 py-1 text-xs font-bold rounded-md border', getStatusColor(order.order_status)]">
                                    {{ getStatusLabel(order.order_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs font-medium">{{ order.created_at }}</td>
                            <td class="px-6 py-4 text-right">
                                <button @click="openDetailsModal(order)" class="text-xs font-bold text-[#3B2314] hover:text-[#D4A373] transition inline-flex items-center gap-1">
                                    Lihat Detail
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table Riwayat Pembayaran -->
            <div class="overflow-x-auto" v-if="activeTab === 'pembayaran'">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-600 font-semibold border-b border-gray-100 uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-6 py-4">ID Transaksi</th>
                            <th class="px-6 py-4">Metode Pembayaran</th>
                            <th class="px-6 py-4 text-right">Total Bayar</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4">Waktu Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="props.orders.data.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">Belum ada riwayat pembayaran pada rentang waktu ini.</td>
                        </tr>
                        <tr v-else v-for="order in props.orders.data" :key="'pay-'+order.id" class="hover:bg-orange-50/30 transition-colors">
                            <td class="px-6 py-4 font-bold text-[#3B2314]">TRX-{{ order.id }}</td>
                            <td class="px-6 py-4 font-medium text-gray-700">{{ order.payment_method }}</td>
                            <td class="px-6 py-4 text-right font-extrabold text-[#D4A373]">{{ formatIDR(order.total_price) }}</td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-3">
                                    <span :class="['px-2.5 py-1 text-xs font-bold rounded-md border', getStatusColor(order.payment_status)]">
                                        {{ getStatusLabel(order.payment_status) }}
                                    </span>
                                    <button v-if="order.payment_method === 'QRIS_MANUAL' && order.payment_proof" @click="openProofModal(order.payment_proof)" class="text-xs font-bold bg-[#FAEDCD] text-[#3B2314] px-3 py-1 rounded hover:bg-[#D4A373] hover:text-white transition cursor-pointer">
                                        Lihat Bukti
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs font-medium">{{ order.created_at }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-5 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4 bg-gray-50/50" v-if="props.orders.data.length > 0">
                <span class="text-sm text-gray-500 text-center w-full md:w-auto md:text-left">
                    Menampilkan <span class="font-bold text-gray-800">{{ props.orders.from || 0 }}</span> - <span class="font-bold text-gray-800">{{ props.orders.to || 0 }}</span> dari total <span class="font-bold text-gray-800">{{ props.orders.total }}</span> riwayat
                </span>
                
                <div class="flex items-center justify-center gap-1 w-full md:w-auto mx-auto md:mx-0">
                    <Link v-for="(link, i) in props.orders.links" :key="i"
                        :href="link.url || '#'"
                        class="px-4 py-1.5 text-sm rounded-lg transition-colors border shadow-sm"
                        :class="[
                            link.active ? 'bg-[#3B2314] text-white border-[#3B2314] font-bold' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-200 font-medium',
                            !link.url ? 'opacity-50 cursor-not-allowed shadow-none' : ''
                        ]"
                        :preserve-state="true"
                    >
                        <span v-if="String(link.label).includes('Previous')">&lt;</span>
                        <span v-else-if="String(link.label).includes('Next')">&gt;</span>
                        <span v-else v-html="String(link.label)"></span>
                    </Link>
                </div>
            </div>

        </div>

        <!-- Payment Proof Modal -->
        <div v-if="showProofModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showProofModal = false">
            <div class="bg-white rounded-3xl overflow-hidden shadow-2xl max-w-lg w-full transform transition-all duration-300">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="text-lg font-bold text-[#3B2314]">Bukti Pembayaran QRIS</h3>
                    <button @click="showProofModal = false" class="p-2 hover:bg-gray-200 rounded-full transition-colors text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-6 flex justify-center bg-gray-100">
                    <img :src="currentProofImage" class="max-h-[60vh] object-contain rounded-xl shadow-sm border border-gray-200" alt="Bukti Pembayaran" />
                </div>
            </div>
        </div>

        <!-- Order Details Modal -->
        <div v-if="showDetailsModal && selectedOrder" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showDetailsModal = false">
            <div class="bg-white rounded-3xl overflow-hidden shadow-2xl max-w-2xl w-full transform transition-all duration-300 flex flex-col max-h-[90vh]">
                <!-- Header -->
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-[#3B2314] text-white">
                    <div>
                        <h3 class="text-lg font-bold">Rincian Transaksi #{{ selectedOrder.id }}</h3>
                        <p class="text-xs text-[#FAEDCD] opacity-90 mt-0.5">ID Transaksi: TRX-{{ selectedOrder.id }} • {{ selectedOrder.created_at }}</p>
                    </div>
                    <button @click="showDetailsModal = false" class="p-2 hover:bg-white/10 rounded-full transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <!-- Body -->
                <div class="p-6 overflow-y-auto bg-gray-50 flex-1">
                    <!-- Customer & Order Info -->
                    <div class="grid grid-cols-2 gap-4 mb-6 bg-white p-4 rounded-xl border border-gray-300 shadow-sm">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Pelanggan / Meja</p>
                            <p class="text-sm font-bold text-[#3B2314]">{{ selectedOrder.customer_name }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tipe Pesanan</p>
                            <p class="text-sm font-bold text-gray-700 capitalize">{{ selectedOrder.order_type || 'Dine-In' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Status Pesanan</p>
                            <span :class="['px-2 py-0.5 text-xs font-bold rounded-md border inline-block mt-1', getStatusColor(selectedOrder.order_status)]">
                                {{ getStatusLabel(selectedOrder.order_status) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Status Pembayaran</p>
                            <span :class="['px-2 py-0.5 text-xs font-bold rounded-md border inline-block mt-1', getStatusColor(selectedOrder.payment_status)]">
                                {{ getStatusLabel(selectedOrder.payment_status) }}
                            </span>
                            <span v-if="selectedOrder.payment_method" class="text-xs text-gray-500 font-medium ml-2 block mt-1">Via {{ selectedOrder.payment_method }}</span>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 px-1">Daftar Pesanan</h4>
                    <div class="bg-white rounded-xl border border-gray-300 shadow-sm overflow-hidden mb-6">
                        <div v-for="(item, index) in selectedOrder.items" :key="index" class="p-4 border-b border-gray-200 last:border-0 flex justify-between gap-4">
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-800">{{ item.quantity }}x {{ item.name }}</p>
                                <!-- Addons -->
                                <ul v-if="item.addons && item.addons.length > 0" class="mt-1.5 space-y-0.5">
                                    <li v-for="(addon, aIndex) in item.addons" :key="aIndex" class="text-xs text-gray-500 flex items-center before:content-[''] before:w-1 before:h-1 before:bg-gray-300 before:rounded-full before:mr-2">
                                        {{ addon.name }} (+{{ formatIDR(addon.price) }})
                                    </li>
                                </ul>
                                <p v-if="item.notes" class="text-xs text-[#D4A373] italic mt-1.5 flex items-start gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 mt-0.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.89 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.89l13.416-13.416zm0 0L19.5 7.125" /></svg>
                                    {{ item.notes }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-[#3B2314]">{{ formatIDR(item.price_at_sale * item.quantity + (item.addons?.reduce((sum: number, a: any) => sum + a.price, 0) || 0) * item.quantity) }}</p>
                                <p class="text-[10px] text-gray-400 mt-1">{{ formatIDR(item.price_at_sale) }} / item</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notes (Order Level) -->
                    <div v-if="selectedOrder.notes" class="mb-6 bg-white border border-gray-300 p-4 rounded-xl shadow-sm">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Catatan Pesanan</p>
                        <p class="text-sm font-bold text-gray-800">{{ selectedOrder.notes }}</p>
                    </div>

                    <!-- Payment Summary -->
                    <div class="bg-white rounded-xl border border-gray-300 shadow-sm p-4">
                        <div class="space-y-2 text-sm text-gray-600">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>{{ formatIDR(selectedOrder.total_price + (selectedOrder.discount_amount || 0) + (selectedOrder.promo_discount_amount || 0)) }}</span>
                            </div>
                            <div v-if="selectedOrder.discount_amount > 0" class="flex justify-between text-red-500">
                                <span>Diskon Manual</span>
                                <span>- {{ formatIDR(selectedOrder.discount_amount) }}</span>
                            </div>
                            <div v-if="selectedOrder.promo_discount_amount > 0" class="flex justify-between text-red-500">
                                <span>Diskon Promo</span>
                                <span>- {{ formatIDR(selectedOrder.promo_discount_amount) }}</span>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-100 flex justify-between items-center">
                            <span class="text-sm font-bold text-gray-800">Total Bayar</span>
                            <span class="text-xl font-black text-[#3B2314]">{{ formatIDR(selectedOrder.total_price) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </ZunoiAdminLayout>
</template>

<style>
.dp__theme_light {
    --dp-primary-color: #3B2314;
    --dp-primary-text-color: #ffffff;
    --dp-hover-color: #FAEDCD;
    --dp-hover-text-color: #3B2314;
}
</style>
