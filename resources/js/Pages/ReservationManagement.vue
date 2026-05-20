<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';

const props = defineProps({
    reservations: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    tables: { type: Array, default: () => [] },
});

const reservationList = ref([...props.reservations]);

const triggerToast = (message, type = 'success') => {
    window.dispatchEvent(
        new CustomEvent('zunoi-toast', { detail: { message, type } }),
    );
};

// ===================== TAB FILTER =====================
const currentTab = ref('all');
const filteredReservations = computed(() => {
    if (currentTab.value === 'all') return reservationList.value;
    return reservationList.value.filter(r => r.status === currentTab.value);
});
const tabs = [
    { key: 'all', label: 'Semua' },
    { key: 'pending', label: 'Pending' },
    { key: 'confirmed', label: 'Dikonfirmasi' },
    { key: 'cancelled', label: 'Dibatalkan' },
];

// ===================== DETAIL MODAL =====================
const showDetailModal = ref(false);
const selectedReservation = ref(null);
const openDetail = (reservation) => {
    selectedReservation.value = reservation;
    showDetailModal.value = true;
};

// ===================== STATUS & DELETE =====================
const updateStatus = async (id, newStatus) => {
    try {
        const response = await axios.patch(`/api/reservations/${id}/status`, { status: newStatus });
        if (response.data.success) {
            const idx = reservationList.value.findIndex(r => r.id === id);
            if (idx !== -1) reservationList.value[idx].status = newStatus;
            if (selectedReservation.value?.id === id) selectedReservation.value.status = newStatus;
            const msgs = { confirmed: 'Reservasi dikonfirmasi! Notifikasi WA dikirim.', cancelled: 'Reservasi dibatalkan.', pending: 'Status dikembalikan ke pending.' };
            triggerToast(msgs[newStatus] || 'Status diperbarui!');
        }
    } catch (err) { triggerToast('Gagal memperbarui status.', 'error'); }
};

const deleteReservation = async (id) => {
    if (!confirm('Yakin ingin menghapus reservasi ini?')) return;
    try {
        await axios.delete(`/api/reservations/${id}`);
        reservationList.value = reservationList.value.filter(r => r.id !== id);
        showDetailModal.value = false;
        triggerToast('Reservasi berhasil dihapus.');
    } catch (err) { triggerToast('Gagal menghapus reservasi.', 'error'); }
};

// ===================== CREATE MODAL =====================
const showCreateModal = ref(false);
const isSubmitting = ref(false);
const createForm = ref({
    customer_name: '',
    customer_phone: '',
    reservation_date: '',
    reservation_time: '',
    num_guests: 1,
    table_id: null,
    notes: '',
});
const createErrors = ref({});

const resetCreateForm = () => {
    createForm.value = {
        customer_name: '', customer_phone: '', reservation_date: '',
        reservation_time: '', num_guests: 1, table_id: null, notes: '',
    };
    createErrors.value = {};
    preorderCart.value = [];
    showMenuPicker.value = false;
};

const openCreateModal = () => {
    resetCreateForm();
    showCreateModal.value = true;
};

const today = new Date().toISOString().split('T')[0];

const timeSlots = [
    '08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30',
    '12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30',
    '16:00','16:30','17:00','17:30','18:00','18:30','19:00','19:30',
    '20:00','20:30','21:00',
];

// ===================== PRE-ORDER MENU PICKER =====================
const showMenuPicker = ref(false);
const preorderCart = ref([]);
const menuSearch = ref('');
const menuCategoryFilter = ref('all');

const filteredMenuProducts = computed(() => {
    let list = props.products;
    if (menuCategoryFilter.value !== 'all') {
        list = list.filter(p => p.category_id === menuCategoryFilter.value);
    }
    if (menuSearch.value.trim()) {
        const q = menuSearch.value.toLowerCase();
        list = list.filter(p => p.name.toLowerCase().includes(q));
    }
    return list;
});

// Addon selection modal
const showAddonModal = ref(false);
const addonProduct = ref(null);
const addonSelectedAddons = ref([]);
const addonNotes = ref('');
const addonQty = ref(1);

const openAddonModal = (product) => {
    addonProduct.value = product;
    addonSelectedAddons.value = [];
    addonNotes.value = '';
    addonQty.value = 1;
    showAddonModal.value = true;
};

const toggleAddon = (addon) => {
    const idx = addonSelectedAddons.value.findIndex(a => a.id === addon.id);
    if (idx >= 0) addonSelectedAddons.value.splice(idx, 1);
    else addonSelectedAddons.value.push({ ...addon });
};

const isAddonSelected = (addonId) => {
    return addonSelectedAddons.value.some(a => a.id === addonId);
};

const addonTotalPrice = computed(() => {
    if (!addonProduct.value) return 0;
    const base = addonProduct.value.price;
    const addonSum = addonSelectedAddons.value.reduce((s, a) => s + a.price, 0);
    return (base + addonSum) * addonQty.value;
});

const confirmAddToPreorder = () => {
    if (!addonProduct.value) return;
    const base = addonProduct.value.price;
    const addonSum = addonSelectedAddons.value.reduce((s, a) => s + a.price, 0);
    const unitPrice = base + addonSum;
    const addonNames = addonSelectedAddons.value.map(a => `+${a.name}`).join(', ');
    const noteText = [addonNames, addonNotes.value].filter(Boolean).join(' | ');

    preorderCart.value.push({
        id: addonProduct.value.id,
        name: addonProduct.value.name,
        image: addonProduct.value.image,
        basePrice: base,
        price: unitPrice,
        quantity: addonQty.value,
        notes: noteText,
        addons: [...addonSelectedAddons.value],
    });

    showAddonModal.value = false;
};

const decreasePreorder = (index) => {
    if (preorderCart.value[index].quantity > 1) preorderCart.value[index].quantity--;
    else preorderCart.value.splice(index, 1);
};
const increasePreorder = (index) => { preorderCart.value[index].quantity++; };
const removePreorderItem = (index) => { preorderCart.value.splice(index, 1); };

const preorderTotal = computed(() => {
    return preorderCart.value.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

// ===================== SUBMIT RESERVATION =====================
const submitReservation = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    createErrors.value = {};

    const payload = {
        ...createForm.value,
        preorder_items: preorderCart.value.map(item => ({
            id: item.id,
            quantity: item.quantity,
            price: item.price,
            notes: item.notes || null,
        })),
    };

    try {
        const response = await axios.post('/api/reservations', payload);
        if (response.data.success) {
            // Reload page to get fresh data
            window.location.reload();
        }
    } catch (err) {
        if (err.response?.status === 422) {
            createErrors.value = err.response.data.errors || {};
            triggerToast('Mohon lengkapi data reservasi.', 'error');
        } else {
            triggerToast('Gagal membuat reservasi.', 'error');
        }
    } finally {
        isSubmitting.value = false;
    }
};

// ===================== HELPERS =====================
const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
    }).format(price);
};

const statusBadge = (status) => {
    return { pending: 'bg-amber-100 text-amber-700 border-amber-200', confirmed: 'bg-green-100 text-green-700 border-green-200', cancelled: 'bg-red-100 text-red-700 border-red-200' }[status] || 'bg-gray-100 text-gray-600 border-gray-200';
};

const statusLabel = (status) => {
    return { pending: 'Pending', confirmed: 'Dikonfirmasi', cancelled: 'Dibatalkan' }[status] || status;
};

const statusIcon = (status) => {
    return { pending: 'clock', confirmed: 'check', cancelled: 'x-mark' }[status] || 'clock';
};
</script>

<template>
    <Head title="Kelola Reservasi - Zunoi Caffe" />

    <ZunoiAdminLayout>
        <div class="p-1">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-800 tracking-wide">Kelola Reservasi</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Buat, konfirmasi, batalkan, atau hapus reservasi meja.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1.5 bg-amber-50 text-amber-600 font-bold rounded-lg border border-amber-200 text-xs">
                        {{ reservationList.filter(r => r.status === 'pending').length }} Pending
                    </span>
                    <button
                        @click="openCreateModal"
                        class="flex items-center gap-2 px-4 py-2.5 bg-[#3B2314] hover:bg-[#D4A373] text-[#FAEDCD] hover:text-[#3B2314] rounded-xl text-xs font-bold tracking-widest uppercase transition shadow-md"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Buat Reservasi
                    </button>
                </div>
            </div>

            <!-- Tab Filters -->
            <div class="flex gap-2 mb-6 overflow-x-auto">
                <button v-for="tab in tabs" :key="tab.key" @click="currentTab = tab.key"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition shrink-0 shadow-sm"
                    :class="currentTab === tab.key ? 'bg-[#3B2314] text-[#FAEDCD]' : 'bg-white text-gray-600 hover:bg-[#FAEDCD]/40 border border-[#D4A373]/10'"
                >
                    {{ tab.label }}
                    <span v-if="tab.key !== 'all'" class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full" :class="currentTab === tab.key ? 'bg-white/20' : 'bg-gray-100'">
                        {{ reservationList.filter(r => r.status === tab.key).length }}
                    </span>
                </button>
            </div>

            <!-- Empty State -->
            <div v-if="filteredReservations.length === 0" class="flex flex-col items-center justify-center py-20 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="size-20 mb-4 text-[#D4A373]/40">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <p class="text-lg font-bold">Belum ada reservasi</p>
                <p class="text-xs mt-1">Klik "Buat Reservasi" untuk menambahkan reservasi baru.</p>
            </div>

            <!-- Reservation Cards Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <div v-for="reservation in filteredReservations" :key="reservation.id" @click="openDetail(reservation)"
                    class="bg-white rounded-xl shadow-md border border-[#D4A373]/10 hover:border-[#D4A373]/40 hover:shadow-lg transition p-5 cursor-pointer group"
                >
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h3 class="text-base font-extrabold text-gray-800 group-hover:text-[#3B2314] transition">{{ reservation.customer_name }}</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ reservation.customer_phone }}</p>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border shrink-0" :class="statusBadge(reservation.status)">
                            <svg v-if="reservation.status === 'pending'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            <svg v-else-if="reservation.status === 'confirmed'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            {{ statusLabel(reservation.status) }}
                        </span>
                    </div>
                    <div class="space-y-2 text-xs text-gray-600">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4 text-[#D4A373]"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                            <span class="font-bold">{{ formatDate(reservation.reservation_date) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4 text-[#D4A373]"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            <span class="font-bold">{{ reservation.reservation_time }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4 text-[#D4A373]"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                            <span class="font-bold">{{ reservation.num_guests }} tamu</span>
                        </div>
                    </div>
                    <div v-if="reservation.order_id" class="mt-3">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-600 text-[10px] font-bold rounded-lg border border-blue-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                            Pre-Order (Order #{{ reservation.order_id }})
                        </span>
                    </div>
                    <div v-if="reservation.status === 'pending'" class="mt-4 flex gap-2 pt-3 border-t border-gray-100">
                        <button @click.stop="updateStatus(reservation.id, 'confirmed')" class="flex-1 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl text-[10px] font-extrabold tracking-wider uppercase transition shadow-sm flex items-center justify-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            Konfirmasi
                        </button>
                        <button @click.stop="updateStatus(reservation.id, 'cancelled')" class="flex-1 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl text-[10px] font-extrabold tracking-wider uppercase transition shadow-sm flex items-center justify-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            Batalkan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== DETAIL MODAL ==================== -->
        <div v-if="showDetailModal && selectedReservation" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showDetailModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-[#D4A373]/20">
                <div class="p-6 bg-gradient-to-r from-[#3B2314] to-[#5A3E2A] text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-extrabold tracking-wide">Detail Reservasi</h3>
                        <p class="text-xs text-white/60 mt-0.5">ID: #{{ selectedReservation.id }}</p>
                    </div>
                    <button @click="showDetailModal = false" class="text-white/60 hover:text-white transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                    <div class="flex justify-center">
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold rounded-xl border" :class="statusBadge(selectedReservation.status)">
                            <svg v-if="selectedReservation.status === 'pending'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            <svg v-else-if="selectedReservation.status === 'confirmed'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            {{ statusLabel(selectedReservation.status) }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="bg-gray-50 rounded-xl p-3"><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Nama</p><p class="font-bold text-gray-800 mt-0.5">{{ selectedReservation.customer_name }}</p></div>
                        <div class="bg-gray-50 rounded-xl p-3"><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">WhatsApp</p><p class="font-bold text-gray-800 mt-0.5">{{ selectedReservation.customer_phone }}</p></div>
                        <div class="bg-gray-50 rounded-xl p-3"><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tanggal</p><p class="font-bold text-gray-800 mt-0.5">{{ formatDate(selectedReservation.reservation_date) }}</p></div>
                        <div class="bg-gray-50 rounded-xl p-3"><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Waktu</p><p class="font-bold text-gray-800 mt-0.5">{{ selectedReservation.reservation_time }}</p></div>
                        <div class="bg-gray-50 rounded-xl p-3"><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Jumlah Tamu</p><p class="font-bold text-gray-800 mt-0.5">{{ selectedReservation.num_guests }} orang</p></div>
                        <div class="bg-gray-50 rounded-xl p-3"><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Dibuat</p><p class="font-bold text-gray-800 mt-0.5">{{ selectedReservation.created_at }}</p></div>
                    </div>
                    <div v-if="selectedReservation.notes" class="bg-[#FAEDCD]/30 rounded-xl p-3 border border-[#D4A373]/20">
                        <p class="text-[10px] font-bold text-[#3B2314] uppercase tracking-wider mb-1">Catatan</p>
                        <p class="text-sm text-gray-700">{{ selectedReservation.notes }}</p>
                    </div>
                    <!-- Pre-order items from order -->
                    <div v-if="selectedReservation.order && selectedReservation.order.items?.length > 0" class="bg-blue-50/50 rounded-xl p-4 border border-blue-100">
                        <p class="text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-2 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                            Pre-Order Menu (Order #{{ selectedReservation.order_id }})
                        </p>
                        <div class="space-y-2">
                            <div v-for="item in selectedReservation.order.items" :key="item.id" class="flex justify-between items-center text-xs bg-white rounded-lg px-3 py-2 border border-gray-100">
                                <div>
                                    <span class="font-bold text-gray-800">{{ item.name }} × {{ item.quantity }}</span>
                                    <span v-if="item.notes" class="block text-[10px] text-gray-400 italic mt-0.5">{{ item.notes }}</span>
                                </div>
                                <span class="font-bold text-gray-600">{{ formatPrice(item.price * item.quantity) }}</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center pt-2 mt-2 border-t border-blue-200">
                            <span class="text-xs font-bold text-gray-500">Total Pre-Order:</span>
                            <span class="text-sm font-extrabold text-[#3B2314]">{{ formatPrice(selectedReservation.order.total_price) }}</span>
                        </div>
                    </div>
                </div>
                <div class="p-6 bg-gray-50 flex flex-wrap gap-2 border-t border-gray-100">
                    <button v-if="selectedReservation.status === 'pending'" @click="updateStatus(selectedReservation.id, 'confirmed')" class="flex-1 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        Konfirmasi
                    </button>
                    <button v-if="selectedReservation.status === 'pending'" @click="updateStatus(selectedReservation.id, 'cancelled')" class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        Batalkan
                    </button>
                    <button v-if="selectedReservation.status !== 'pending'" @click="updateStatus(selectedReservation.id, 'pending')" class="flex-1 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
                        Kembalikan ke Pending
                    </button>
                    <button @click="deleteReservation(selectedReservation.id)" class="py-2.5 px-4 bg-gray-200 hover:bg-red-100 text-gray-600 hover:text-red-600 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        Hapus
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================== CREATE MODAL (Full Page Overlay) ==================== -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 backdrop-blur-sm overflow-y-auto py-4 px-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden border border-[#D4A373]/20 my-auto">
                <!-- Header -->
                <div class="p-5 bg-gradient-to-r from-[#3B2314] to-[#5A3E2A] text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        <div>
                            <h3 class="text-lg font-extrabold tracking-wide">Buat Reservasi Baru</h3>
                            <p class="text-xs text-white/60">Isi data pelanggan dan opsional pilih menu pre-order.</p>
                        </div>
                    </div>
                    <button @click="showCreateModal = false" class="text-white/60 hover:text-white transition p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                    <!-- Customer Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-extrabold text-[#3B2314] uppercase tracking-wider mb-1.5">Nama Pelanggan *</label>
                            <input v-model="createForm.customer_name" type="text" placeholder="Nama lengkap" class="w-full px-4 py-3 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-[#D4A373] focus:border-transparent" />
                            <p v-if="createErrors.customer_name" class="text-red-500 text-xs mt-1">{{ createErrors.customer_name[0] }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-extrabold text-[#3B2314] uppercase tracking-wider mb-1.5">No. WhatsApp *</label>
                            <input v-model="createForm.customer_phone" type="tel" placeholder="08xxx" class="w-full px-4 py-3 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-[#D4A373] focus:border-transparent" />
                            <p v-if="createErrors.customer_phone" class="text-red-500 text-xs mt-1">{{ createErrors.customer_phone[0] }}</p>
                        </div>
                    </div>

                    <!-- Date, Time, Guests -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-extrabold text-[#3B2314] uppercase tracking-wider mb-1.5">Tanggal *</label>
                            <input v-model="createForm.reservation_date" type="date" :min="today" class="w-full px-4 py-3 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-[#D4A373] focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-xs font-extrabold text-[#3B2314] uppercase tracking-wider mb-1.5">Waktu *</label>
                            <select v-model="createForm.reservation_time" class="w-full px-4 py-3 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-[#D4A373] focus:border-transparent bg-white">
                                <option value="" disabled>Pilih waktu...</option>
                                <option v-for="slot in timeSlots" :key="slot" :value="slot">{{ slot }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-extrabold text-[#3B2314] uppercase tracking-wider mb-1.5">Jumlah Tamu *</label>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="createForm.num_guests = Math.max(1, createForm.num_guests - 1)" class="w-10 h-10 bg-[#FAEDCD] text-[#3B2314] rounded-xl font-bold text-lg flex items-center justify-center hover:bg-[#D4A373] hover:text-white transition">−</button>
                                <span class="text-xl font-black text-[#3B2314] w-10 text-center">{{ createForm.num_guests }}</span>
                                <button type="button" @click="createForm.num_guests = Math.min(50, createForm.num_guests + 1)" class="w-10 h-10 bg-[#FAEDCD] text-[#3B2314] rounded-xl font-bold text-lg flex items-center justify-center hover:bg-[#D4A373] hover:text-white transition">+</button>
                            </div>
                        </div>
                    </div>

                    <!-- Table & Notes -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-extrabold text-[#3B2314] uppercase tracking-wider mb-1.5">Meja (Opsional)</label>
                            <select v-model="createForm.table_id" class="w-full px-4 py-3 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-[#D4A373] focus:border-transparent bg-white">
                                <option :value="null">Belum ditentukan</option>
                                <option v-for="table in tables" :key="table.id" :value="table.id">{{ table.table_name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-extrabold text-[#3B2314] uppercase tracking-wider mb-1.5">Catatan</label>
                            <input v-model="createForm.notes" type="text" placeholder="Catatan tambahan..." class="w-full px-4 py-3 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-[#D4A373] focus:border-transparent" />
                        </div>
                    </div>

                    <!-- ========== PRE-ORDER SECTION ========== -->
                    <div class="border-t border-dashed border-[#D4A373]/30 pt-5">
                        <button type="button" @click="showMenuPicker = !showMenuPicker"
                            class="w-full flex items-center justify-between bg-[#FAEDCD]/50 hover:bg-[#FAEDCD] px-4 py-3.5 rounded-xl transition border border-[#D4A373]/20"
                        >
                            <div class="flex items-center gap-2.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5 text-[#D4A373]"><path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" /></svg>
                                <div class="text-left">
                                    <span class="text-sm font-extrabold text-[#3B2314]">Pre-Order Menu (Opsional)</span>
                                    <p class="text-[10px] text-gray-500">Pilih menu beserta addons & catatan</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span v-if="preorderCart.length > 0" class="px-2 py-0.5 bg-[#3B2314] text-white text-[10px] font-bold rounded-full">{{ preorderCart.length }} item</span>
                                <svg :class="{'rotate-180': showMenuPicker}" class="w-5 h-5 text-[#D4A373] transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </button>

                        <div v-if="showMenuPicker" class="mt-4 space-y-4">
                            <!-- Search & Category Filter -->
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input v-model="menuSearch" type="text" placeholder="Cari menu..." class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#D4A373] bg-gray-50" />
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="absolute left-3 top-2.5 size-4 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                                </div>
                                <select v-model="menuCategoryFilter" class="px-3 py-2 rounded-xl border border-gray-200 text-xs font-bold bg-white focus:outline-none focus:ring-2 focus:ring-[#D4A373]">
                                    <option value="all">Semua Kategori</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>

                            <!-- Menu Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 max-h-60 overflow-y-auto pr-1">
                                <button v-for="product in filteredMenuProducts" :key="product.id" type="button" @click="openAddonModal(product)"
                                    class="bg-white border border-gray-200 rounded-xl p-2.5 hover:border-[#D4A373] hover:shadow-md transition text-left group"
                                >
                                    <div class="w-full h-16 bg-[#FAEDCD]/30 rounded-lg overflow-hidden mb-1.5">
                                        <img v-if="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex items-center justify-center text-[#D4A373]/40">☕</div>
                                    </div>
                                    <h4 class="text-[11px] font-bold text-gray-800 line-clamp-1">{{ product.name }}</h4>
                                    <p class="text-[10px] font-bold text-[#D4A373]">{{ formatPrice(product.price) }}</p>
                                    <div v-if="product.addons?.length > 0" class="mt-0.5">
                                        <span class="text-[9px] text-gray-400">{{ product.addons.length }} addon</span>
                                    </div>
                                </button>
                            </div>

                            <!-- Pre-order Cart -->
                            <div v-if="preorderCart.length > 0" class="bg-[#FAEDCD]/30 rounded-xl p-4 border border-[#D4A373]/20 space-y-3">
                                <h4 class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider">Menu Pre-Order</h4>
                                <div v-for="(item, index) in preorderCart" :key="index" class="flex items-center justify-between bg-white rounded-lg px-3 py-2 shadow-sm border border-gray-100">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-gray-800 truncate">{{ item.name }}</p>
                                        <p class="text-[10px] text-gray-500">{{ formatPrice(item.price) }} × {{ item.quantity }} = {{ formatPrice(item.price * item.quantity) }}</p>
                                        <p v-if="item.notes" class="text-[9px] text-gray-400 italic truncate mt-0.5">{{ item.notes }}</p>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                        <button type="button" @click="decreasePreorder(index)" class="w-6 h-6 bg-gray-100 hover:bg-red-100 rounded-lg flex items-center justify-center text-xs font-bold text-gray-600 hover:text-red-600 transition">−</button>
                                        <span class="text-xs font-bold w-6 text-center">{{ item.quantity }}</span>
                                        <button type="button" @click="increasePreorder(index)" class="w-6 h-6 bg-gray-100 hover:bg-green-100 rounded-lg flex items-center justify-center text-xs font-bold text-gray-600 hover:text-green-600 transition">+</button>
                                        <button type="button" @click="removePreorderItem(index)" class="w-6 h-6 bg-red-50 hover:bg-red-100 rounded-lg flex items-center justify-center text-red-400 hover:text-red-600 transition ml-1">✕</button>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center pt-2 border-t border-[#D4A373]/20">
                                    <span class="text-xs font-bold text-gray-500">Total Pre-Order:</span>
                                    <span class="text-sm font-extrabold text-[#3B2314]">{{ formatPrice(preorderTotal) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-5 bg-gray-50 flex gap-3 border-t border-gray-100">
                    <button @click="showCreateModal = false" class="flex-1 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl text-xs font-bold tracking-widest uppercase transition">Batal</button>
                    <button @click="submitReservation" :disabled="isSubmitting || !createForm.customer_name || !createForm.customer_phone || !createForm.reservation_date || !createForm.reservation_time"
                        class="flex-1 py-3 bg-[#3B2314] hover:bg-[#D4A373] text-[#FAEDCD] hover:text-[#3B2314] rounded-xl text-xs font-bold tracking-widest uppercase transition shadow-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <svg v-if="isSubmitting" class="animate-spin size-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                        {{ isSubmitting ? 'Memproses...' : 'Simpan Reservasi' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================== ADDON SELECTION MODAL ==================== -->
        <div v-if="showAddonModal && addonProduct" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showAddonModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-[#D4A373]/20">
                <!-- Product Header -->
                <div class="relative">
                    <div class="h-40 bg-[#FAEDCD]/30 overflow-hidden">
                        <img v-if="addonProduct.image" :src="addonProduct.image" :alt="addonProduct.name" class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center text-5xl text-[#D4A373]/30">☕</div>
                    </div>
                    <button @click="showAddonModal = false" class="absolute top-3 right-3 w-8 h-8 bg-black/40 hover:bg-black/60 rounded-full flex items-center justify-center text-white transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-gray-800">{{ addonProduct.name }}</h3>
                        <p class="text-sm font-bold text-[#D4A373]">{{ formatPrice(addonProduct.price) }}</p>
                        <p v-if="addonProduct.description" class="text-xs text-gray-500 mt-1">{{ addonProduct.description }}</p>
                    </div>

                    <!-- Addons -->
                    <div v-if="addonProduct.addons?.length > 0">
                        <h4 class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider mb-2">Pilih Addons (Opsional)</h4>
                        <div class="space-y-2">
                            <button v-for="addon in addonProduct.addons" :key="addon.id" type="button" @click="toggleAddon(addon)"
                                class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl border transition text-sm"
                                :class="isAddonSelected(addon.id) ? 'border-[#D4A373] bg-[#FAEDCD]/30 text-[#3B2314]' : 'border-gray-200 bg-white text-gray-700 hover:border-[#D4A373]/50'"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div class="w-5 h-5 rounded-md border-2 flex items-center justify-center shrink-0"
                                        :class="isAddonSelected(addon.id) ? 'border-[#D4A373] bg-[#D4A373]' : 'border-gray-300'"
                                    >
                                        <svg v-if="isAddonSelected(addon.id)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3 text-white"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                                    </div>
                                    <span class="font-bold">{{ addon.name }}</span>
                                </div>
                                <span class="text-xs font-bold text-[#D4A373]">+{{ formatPrice(addon.price) }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-extrabold text-[#3B2314] uppercase tracking-wider mb-1.5">Catatan Khusus</label>
                        <input v-model="addonNotes" type="text" placeholder="Contoh: less sugar, extra ice..." class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-[#D4A373] focus:border-transparent" />
                    </div>

                    <!-- Qty -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider">Jumlah</span>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="addonQty = Math.max(1, addonQty - 1)" class="w-9 h-9 bg-[#FAEDCD] text-[#3B2314] rounded-xl font-bold text-lg flex items-center justify-center hover:bg-[#D4A373] hover:text-white transition">−</button>
                            <span class="text-xl font-black text-[#3B2314] w-8 text-center">{{ addonQty }}</span>
                            <button type="button" @click="addonQty++" class="w-9 h-9 bg-[#FAEDCD] text-[#3B2314] rounded-xl font-bold text-lg flex items-center justify-center hover:bg-[#D4A373] hover:text-white transition">+</button>
                        </div>
                    </div>
                </div>

                <!-- Add Button -->
                <div class="p-5 bg-gray-50 border-t border-gray-100">
                    <button @click="confirmAddToPreorder"
                        class="w-full py-3.5 bg-[#3B2314] hover:bg-[#D4A373] text-[#FAEDCD] hover:text-[#3B2314] rounded-xl text-sm font-extrabold tracking-wider uppercase transition shadow-md flex items-center justify-center gap-2"
                    >
                        <span>Tambahkan — {{ formatPrice(addonTotalPrice) }}</span>
                    </button>
                </div>
            </div>
        </div>
    </ZunoiAdminLayout>
</template>
