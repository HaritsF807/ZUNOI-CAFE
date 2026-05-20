<script setup>
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, computed, watch } from 'vue';
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
    if (currentTab.value === 'all') {
return reservationList.value;
}

    return reservationList.value.filter((r) => r.status === currentTab.value);
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
        const response = await axios.patch(`/api/reservations/${id}/status`, {
            status: newStatus,
        });

        if (response.data.success) {
            const idx = reservationList.value.findIndex((r) => r.id === id);

            if (idx !== -1) {
reservationList.value[idx].status = newStatus;
}

            if (selectedReservation.value?.id === id) {
selectedReservation.value.status = newStatus;
}

            const msgs = {
                confirmed: 'Reservasi dikonfirmasi! Notifikasi WA dikirim.',
                cancelled: 'Reservasi dibatalkan.',
                pending: 'Status dikembalikan ke pending.',
            };
            triggerToast(msgs[newStatus] || 'Status diperbarui!');
        }
    } catch (err) {
        triggerToast('Gagal memperbarui status.', 'error');
    }
};

const deleteReservation = async (id) => {
    if (!confirm('Yakin ingin menghapus reservasi ini?')) {
return;
}

    try {
        await axios.delete(`/api/reservations/${id}`);
        reservationList.value = reservationList.value.filter(
            (r) => r.id !== id,
        );
        showDetailModal.value = false;
        triggerToast('Reservasi berhasil dihapus.');
    } catch (err) {
        triggerToast('Gagal menghapus reservasi.', 'error');
    }
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
        customer_name: '',
        customer_phone: '',
        reservation_date: '',
        reservation_time: '',
        num_guests: 1,
        table_id: null,
        notes: '',
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
    '08:00',
    '08:30',
    '09:00',
    '09:30',
    '10:00',
    '10:30',
    '11:00',
    '11:30',
    '12:00',
    '12:30',
    '13:00',
    '13:30',
    '14:00',
    '14:30',
    '15:00',
    '15:30',
    '16:00',
    '16:30',
    '17:00',
    '17:30',
    '18:00',
    '18:30',
    '19:00',
    '19:30',
    '20:00',
    '20:30',
    '21:00',
];

// ===================== PRE-ORDER MENU PICKER =====================
const showMenuPicker = ref(false);
const preorderCart = ref([]);
const menuSearch = ref('');
const menuCategoryFilter = ref('all');

const filteredMenuProducts = computed(() => {
    let list = props.products;

    if (menuCategoryFilter.value !== 'all') {
        list = list.filter((p) => p.category_id === menuCategoryFilter.value);
    }

    if (menuSearch.value.trim()) {
        const q = menuSearch.value.toLowerCase();
        list = list.filter((p) => p.name.toLowerCase().includes(q));
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
    const idx = addonSelectedAddons.value.findIndex((a) => a.id === addon.id);

    if (idx >= 0) {
addonSelectedAddons.value.splice(idx, 1);
} else {
addonSelectedAddons.value.push({ ...addon });
}
};

const isAddonSelected = (addonId) => {
    return addonSelectedAddons.value.some((a) => a.id === addonId);
};

const addonTotalPrice = computed(() => {
    if (!addonProduct.value) {
return 0;
}

    const base = addonProduct.value.price;
    const addonSum = addonSelectedAddons.value.reduce((s, a) => s + a.price, 0);

    return (base + addonSum) * addonQty.value;
});

const confirmAddToPreorder = () => {
    if (!addonProduct.value) {
return;
}

    const base = addonProduct.value.price;
    const addonSum = addonSelectedAddons.value.reduce((s, a) => s + a.price, 0);
    const unitPrice = base + addonSum;
    const addonNames = addonSelectedAddons.value
        .map((a) => `+${a.name}`)
        .join(', ');
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
    if (preorderCart.value[index].quantity > 1) {
preorderCart.value[index].quantity--;
} else {
preorderCart.value.splice(index, 1);
}
};
const increasePreorder = (index) => {
    preorderCart.value[index].quantity++;
};
const removePreorderItem = (index) => {
    preorderCart.value.splice(index, 1);
};

const preorderTotal = computed(() => {
    return preorderCart.value.reduce(
        (sum, item) => sum + item.price * item.quantity,
        0,
    );
});

// ===================== SUBMIT RESERVATION =====================
const submitReservation = async () => {
    if (isSubmitting.value) {
return;
}

    isSubmitting.value = true;
    createErrors.value = {};

    const payload = {
        ...createForm.value,
        preorder_items: preorderCart.value.map((item) => ({
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
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(price);
};

const statusBadge = (status) => {
    return (
        {
            pending: 'bg-amber-100 text-amber-700 border-amber-200',
            confirmed: 'bg-green-100 text-green-700 border-green-200',
            cancelled: 'bg-red-100 text-red-700 border-red-200',
        }[status] || 'bg-gray-100 text-gray-600 border-gray-200'
    );
};

const statusLabel = (status) => {
    return (
        {
            pending: 'Pending',
            confirmed: 'Dikonfirmasi',
            cancelled: 'Dibatalkan',
        }[status] || status
    );
};

const statusIcon = (status) => {
    return (
        { pending: 'clock', confirmed: 'check', cancelled: 'x-mark' }[status] ||
        'clock'
    );
};
</script>

<template>
    <Head title="Kelola Reservasi - Zunoi Caffe" />

    <ZunoiAdminLayout>
        <div class="p-1">
            <!-- Header -->
            <div
                class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center"
            >
                <div>
                    <h1
                        class="text-2xl font-extrabold tracking-wide text-gray-800"
                    >
                        Kelola Reservasi
                    </h1>
                    <p class="mt-0.5 text-sm text-gray-500">
                        Buat, konfirmasi, batalkan, atau hapus reservasi meja.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span
                        class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-600"
                    >
                        {{
                            reservationList.filter(
                                (r) => r.status === 'pending',
                            ).length
                        }}
                        Pending
                    </span>
                    <button
                        @click="openCreateModal"
                        class="flex items-center gap-2 rounded-xl bg-[#3B2314] px-4 py-2.5 text-xs font-bold tracking-widest text-[#FAEDCD] uppercase shadow-md transition hover:bg-[#D4A373] hover:text-[#3B2314]"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                            class="size-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 4.5v15m7.5-7.5h-15"
                            />
                        </svg>
                        Buat Reservasi
                    </button>
                </div>
            </div>

            <!-- Tab Filters -->
            <div class="mb-6 flex gap-2 overflow-x-auto">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="currentTab = tab.key"
                    class="shrink-0 rounded-xl px-4 py-2 text-xs font-bold shadow-sm transition"
                    :class="
                        currentTab === tab.key
                            ? 'bg-[#3B2314] text-[#FAEDCD]'
                            : 'border border-[#D4A373]/10 bg-white text-gray-600 hover:bg-[#FAEDCD]/40'
                    "
                >
                    {{ tab.label }}
                    <span
                        v-if="tab.key !== 'all'"
                        class="ml-1 rounded-full px-1.5 py-0.5 text-[10px]"
                        :class="
                            currentTab === tab.key
                                ? 'bg-white/20'
                                : 'bg-gray-100'
                        "
                    >
                        {{
                            reservationList.filter((r) => r.status === tab.key)
                                .length
                        }}
                    </span>
                </button>
            </div>

            <!-- Empty State -->
            <div
                v-if="filteredReservations.length === 0"
                class="flex flex-col items-center justify-center py-20 text-gray-400"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1"
                    stroke="currentColor"
                    class="mb-4 size-20 text-[#D4A373]/40"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"
                    />
                </svg>
                <p class="text-lg font-bold">Belum ada reservasi</p>
                <p class="mt-1 text-xs">
                    Klik "Buat Reservasi" untuk menambahkan reservasi baru.
                </p>
            </div>

            <!-- Reservation Cards Grid -->
            <div
                v-else
                class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    v-for="reservation in filteredReservations"
                    :key="reservation.id"
                    @click="openDetail(reservation)"
                    class="group cursor-pointer rounded-xl border border-[#D4A373]/10 bg-white p-5 shadow-md transition hover:border-[#D4A373]/40 hover:shadow-lg"
                >
                    <div class="mb-3 flex items-start justify-between">
                        <div>
                            <h3
                                class="text-base font-extrabold text-gray-800 transition group-hover:text-[#3B2314]"
                            >
                                {{ reservation.customer_name }}
                            </h3>
                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ reservation.customer_phone }}
                            </p>
                        </div>
                        <span
                            class="inline-flex shrink-0 items-center gap-1 rounded-lg border px-2.5 py-1 text-[10px] font-bold"
                            :class="statusBadge(reservation.status)"
                        >
                            <svg
                                v-if="reservation.status === 'pending'"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="size-3"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                />
                            </svg>
                            <svg
                                v-else-if="reservation.status === 'confirmed'"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="size-3"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                />
                            </svg>
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="size-3"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                />
                            </svg>
                            {{ statusLabel(reservation.status) }}
                        </span>
                    </div>
                    <div class="space-y-2 text-xs text-gray-600">
                        <div class="flex items-center gap-2">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="size-4 text-[#D4A373]"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"
                                />
                            </svg>
                            <span class="font-bold">{{
                                formatDate(reservation.reservation_date)
                            }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="size-4 text-[#D4A373]"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                />
                            </svg>
                            <span class="font-bold">{{
                                reservation.reservation_time
                            }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="size-4 text-[#D4A373]"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"
                                />
                            </svg>
                            <span class="font-bold"
                                >{{ reservation.num_guests }} tamu</span
                            >
                        </div>
                    </div>
                    <div v-if="reservation.order_id" class="mt-3">
                        <span
                            class="inline-flex items-center gap-1 rounded-lg border border-blue-100 bg-blue-50 px-2.5 py-1 text-[10px] font-bold text-blue-600"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="size-3"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"
                                />
                            </svg>
                            Pre-Order (Order #{{ reservation.order_id }})
                        </span>
                    </div>
                    <div
                        v-if="reservation.status === 'pending'"
                        class="mt-4 flex gap-2 border-t border-gray-100 pt-3"
                    >
                        <button
                            @click.stop="
                                updateStatus(reservation.id, 'confirmed')
                            "
                            class="flex flex-1 items-center justify-center gap-1 rounded-xl bg-green-600 py-2 text-[10px] font-extrabold tracking-wider text-white uppercase shadow-sm transition hover:bg-green-700"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="size-3"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m4.5 12.75 6 6 9-13.5"
                                />
                            </svg>
                            Konfirmasi
                        </button>
                        <button
                            @click.stop="
                                updateStatus(reservation.id, 'cancelled')
                            "
                            class="flex flex-1 items-center justify-center gap-1 rounded-xl bg-red-500 py-2 text-[10px] font-extrabold tracking-wider text-white uppercase shadow-sm transition hover:bg-red-600"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="size-3"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18 18 6M6 6l12 12"
                                />
                            </svg>
                            Batalkan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== DETAIL MODAL ==================== -->
        <div
            v-if="showDetailModal && selectedReservation"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @click.self="showDetailModal = false"
        >
            <div
                class="w-full max-w-lg overflow-hidden rounded-2xl border border-[#D4A373]/20 bg-white shadow-2xl"
            >
                <div
                    class="flex items-center justify-between bg-gradient-to-r from-[#3B2314] to-[#5A3E2A] p-6 text-white"
                >
                    <div>
                        <h3 class="text-lg font-extrabold tracking-wide">
                            Detail Reservasi
                        </h3>
                        <p class="mt-0.5 text-xs text-white/60">
                            ID: #{{ selectedReservation.id }}
                        </p>
                    </div>
                    <button
                        @click="showDetailModal = false"
                        class="text-white/60 transition hover:text-white"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                            class="size-6"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
                <div class="max-h-[60vh] space-y-4 overflow-y-auto p-6">
                    <div class="flex justify-center">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-xl border px-4 py-2 text-sm font-bold"
                            :class="statusBadge(selectedReservation.status)"
                        >
                            <svg
                                v-if="selectedReservation.status === 'pending'"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="size-4"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                />
                            </svg>
                            <svg
                                v-else-if="
                                    selectedReservation.status === 'confirmed'
                                "
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="size-4"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                />
                            </svg>
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="size-4"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                />
                            </svg>
                            {{ statusLabel(selectedReservation.status) }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl bg-gray-50 p-3">
                            <p
                                class="text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                            >
                                Nama
                            </p>
                            <p class="mt-0.5 font-bold text-gray-800">
                                {{ selectedReservation.customer_name }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3">
                            <p
                                class="text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                            >
                                WhatsApp
                            </p>
                            <p class="mt-0.5 font-bold text-gray-800">
                                {{ selectedReservation.customer_phone }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3">
                            <p
                                class="text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                            >
                                Tanggal
                            </p>
                            <p class="mt-0.5 font-bold text-gray-800">
                                {{
                                    formatDate(
                                        selectedReservation.reservation_date,
                                    )
                                }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3">
                            <p
                                class="text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                            >
                                Waktu
                            </p>
                            <p class="mt-0.5 font-bold text-gray-800">
                                {{ selectedReservation.reservation_time }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3">
                            <p
                                class="text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                            >
                                Jumlah Tamu
                            </p>
                            <p class="mt-0.5 font-bold text-gray-800">
                                {{ selectedReservation.num_guests }} orang
                            </p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3">
                            <p
                                class="text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                            >
                                Dibuat
                            </p>
                            <p class="mt-0.5 font-bold text-gray-800">
                                {{ selectedReservation.created_at }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="selectedReservation.notes"
                        class="rounded-xl border border-[#D4A373]/20 bg-[#FAEDCD]/30 p-3"
                    >
                        <p
                            class="mb-1 text-[10px] font-bold tracking-wider text-[#3B2314] uppercase"
                        >
                            Catatan
                        </p>
                        <p class="text-sm text-gray-700">
                            {{ selectedReservation.notes }}
                        </p>
                    </div>
                    <!-- Pre-order items from order -->
                    <div
                        v-if="
                            selectedReservation.order &&
                            selectedReservation.order.items?.length > 0
                        "
                        class="rounded-xl border border-blue-100 bg-blue-50/50 p-4"
                    >
                        <p
                            class="mb-2 flex items-center gap-1 text-[10px] font-bold tracking-wider text-blue-600 uppercase"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="size-3.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"
                                />
                            </svg>
                            Pre-Order Menu (Order #{{
                                selectedReservation.order_id
                            }})
                        </p>
                        <div class="space-y-2">
                            <div
                                v-for="item in selectedReservation.order.items"
                                :key="item.id"
                                class="flex items-center justify-between rounded-lg border border-gray-100 bg-white px-3 py-2 text-xs"
                            >
                                <div>
                                    <span class="font-bold text-gray-800"
                                        >{{ item.name }} ×
                                        {{ item.quantity }}</span
                                    >
                                    <span
                                        v-if="item.notes"
                                        class="mt-0.5 block text-[10px] text-gray-400 italic"
                                        >{{ item.notes }}</span
                                    >
                                </div>
                                <span class="font-bold text-gray-600">{{
                                    formatPrice(item.price * item.quantity)
                                }}</span>
                            </div>
                        </div>
                        <div
                            class="mt-2 flex items-center justify-between border-t border-blue-200 pt-2"
                        >
                            <span class="text-xs font-bold text-gray-500"
                                >Total Pre-Order:</span
                            >
                            <span
                                class="text-sm font-extrabold text-[#3B2314]"
                                >{{
                                    formatPrice(
                                        selectedReservation.order.total_price,
                                    )
                                }}</span
                            >
                        </div>
                    </div>
                </div>
                <div
                    class="flex flex-wrap gap-2 border-t border-gray-100 bg-gray-50 p-6"
                >
                    <button
                        v-if="selectedReservation.status === 'pending'"
                        @click="
                            updateStatus(selectedReservation.id, 'confirmed')
                        "
                        class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-green-600 py-2.5 text-xs font-bold text-white transition hover:bg-green-700"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                            class="size-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m4.5 12.75 6 6 9-13.5"
                            />
                        </svg>
                        Konfirmasi
                    </button>
                    <button
                        v-if="selectedReservation.status === 'pending'"
                        @click="
                            updateStatus(selectedReservation.id, 'cancelled')
                        "
                        class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-red-500 py-2.5 text-xs font-bold text-white transition hover:bg-red-600"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                            class="size-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                        Batalkan
                    </button>
                    <button
                        v-if="selectedReservation.status !== 'pending'"
                        @click="updateStatus(selectedReservation.id, 'pending')"
                        class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-amber-500 py-2.5 text-xs font-bold text-white transition hover:bg-amber-600"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                            class="size-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"
                            />
                        </svg>
                        Kembalikan ke Pending
                    </button>
                    <button
                        @click="deleteReservation(selectedReservation.id)"
                        class="flex items-center gap-1.5 rounded-xl bg-gray-200 px-4 py-2.5 text-xs font-bold text-gray-600 transition hover:bg-red-100 hover:text-red-600"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                            class="size-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                            />
                        </svg>
                        Hapus
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================== CREATE MODAL (Full Page Overlay) ==================== -->
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 px-4 py-4 backdrop-blur-sm"
        >
            <div
                class="my-auto w-full max-w-3xl overflow-hidden rounded-2xl border border-[#D4A373]/20 bg-white shadow-2xl"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between bg-gradient-to-r from-[#3B2314] to-[#5A3E2A] p-5 text-white"
                >
                    <div class="flex items-center gap-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            class="size-6"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"
                            />
                        </svg>
                        <div>
                            <h3 class="text-lg font-extrabold tracking-wide">
                                Buat Reservasi Baru
                            </h3>
                            <p class="text-xs text-white/60">
                                Isi data pelanggan dan opsional pilih menu
                                pre-order.
                            </p>
                        </div>
                    </div>
                    <button
                        @click="showCreateModal = false"
                        class="p-1 text-white/60 transition hover:text-white"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                            class="size-6"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <div class="max-h-[70vh] space-y-5 overflow-y-auto p-6">
                    <!-- Customer Info -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-[#3B2314] uppercase"
                                >Nama Pelanggan *</label
                            >
                            <input
                                v-model="createForm.customer_name"
                                type="text"
                                placeholder="Nama lengkap"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-transparent focus:ring-2 focus:ring-[#D4A373] focus:outline-none"
                            />
                            <p
                                v-if="createErrors.customer_name"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ createErrors.customer_name[0] }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-[#3B2314] uppercase"
                                >No. WhatsApp *</label
                            >
                            <input
                                v-model="createForm.customer_phone"
                                type="tel"
                                placeholder="08xxx"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-transparent focus:ring-2 focus:ring-[#D4A373] focus:outline-none"
                            />
                            <p
                                v-if="createErrors.customer_phone"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ createErrors.customer_phone[0] }}
                            </p>
                        </div>
                    </div>

                    <!-- Date, Time, Guests -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-[#3B2314] uppercase"
                                >Tanggal *</label
                            >
                            <input
                                v-model="createForm.reservation_date"
                                type="date"
                                :min="today"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-transparent focus:ring-2 focus:ring-[#D4A373] focus:outline-none"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-[#3B2314] uppercase"
                                >Waktu *</label
                            >
                            <select
                                v-model="createForm.reservation_time"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-transparent focus:ring-2 focus:ring-[#D4A373] focus:outline-none"
                            >
                                <option value="" disabled>
                                    Pilih waktu...
                                </option>
                                <option
                                    v-for="slot in timeSlots"
                                    :key="slot"
                                    :value="slot"
                                >
                                    {{ slot }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-[#3B2314] uppercase"
                                >Jumlah Tamu *</label
                            >
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="
                                        createForm.num_guests = Math.max(
                                            1,
                                            createForm.num_guests - 1,
                                        )
                                    "
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#FAEDCD] text-lg font-bold text-[#3B2314] transition hover:bg-[#D4A373] hover:text-white"
                                >
                                    −
                                </button>
                                <span
                                    class="w-10 text-center text-xl font-black text-[#3B2314]"
                                    >{{ createForm.num_guests }}</span
                                >
                                <button
                                    type="button"
                                    @click="
                                        createForm.num_guests = Math.min(
                                            50,
                                            createForm.num_guests + 1,
                                        )
                                    "
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#FAEDCD] text-lg font-bold text-[#3B2314] transition hover:bg-[#D4A373] hover:text-white"
                                >
                                    +
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table & Notes -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-[#3B2314] uppercase"
                                >Meja (Opsional)</label
                            >
                            <select
                                v-model="createForm.table_id"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-transparent focus:ring-2 focus:ring-[#D4A373] focus:outline-none"
                            >
                                <option :value="null">Belum ditentukan</option>
                                <option
                                    v-for="table in tables"
                                    :key="table.id"
                                    :value="table.id"
                                >
                                    {{ table.table_name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-[#3B2314] uppercase"
                                >Catatan</label
                            >
                            <input
                                v-model="createForm.notes"
                                type="text"
                                placeholder="Catatan tambahan..."
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-transparent focus:ring-2 focus:ring-[#D4A373] focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- ========== PRE-ORDER SECTION ========== -->
                    <div
                        class="border-t border-dashed border-[#D4A373]/30 pt-5"
                    >
                        <button
                            type="button"
                            @click="showMenuPicker = !showMenuPicker"
                            class="flex w-full items-center justify-between rounded-xl border border-[#D4A373]/20 bg-[#FAEDCD]/50 px-4 py-3.5 transition hover:bg-[#FAEDCD]"
                        >
                            <div class="flex items-center gap-2.5">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="size-5 text-[#D4A373]"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"
                                    />
                                </svg>
                                <div class="text-left">
                                    <span
                                        class="text-sm font-extrabold text-[#3B2314]"
                                        >Pre-Order Menu (Opsional)</span
                                    >
                                    <p class="text-[10px] text-gray-500">
                                        Pilih menu beserta addons & catatan
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="preorderCart.length > 0"
                                    class="rounded-full bg-[#3B2314] px-2 py-0.5 text-[10px] font-bold text-white"
                                    >{{ preorderCart.length }} item</span
                                >
                                <svg
                                    :class="{ 'rotate-180': showMenuPicker }"
                                    class="h-5 w-5 text-[#D4A373] transition-transform duration-200"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </div>
                        </button>

                        <div v-if="showMenuPicker" class="mt-4 space-y-4">
                            <!-- Search & Category Filter -->
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input
                                        v-model="menuSearch"
                                        type="text"
                                        placeholder="Cari menu..."
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pr-4 pl-9 text-sm focus:ring-2 focus:ring-[#D4A373] focus:outline-none"
                                    />
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                        class="absolute top-2.5 left-3 size-4 text-gray-400"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                                        />
                                    </svg>
                                </div>
                                <select
                                    v-model="menuCategoryFilter"
                                    class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-[#D4A373] focus:outline-none"
                                >
                                    <option value="all">Semua Kategori</option>
                                    <option
                                        v-for="cat in categories"
                                        :key="cat.id"
                                        :value="cat.id"
                                    >
                                        {{ cat.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Menu Grid -->
                            <div
                                class="grid max-h-60 grid-cols-2 gap-3 overflow-y-auto pr-1 sm:grid-cols-3 md:grid-cols-4"
                            >
                                <button
                                    v-for="product in filteredMenuProducts"
                                    :key="product.id"
                                    type="button"
                                    @click="openAddonModal(product)"
                                    class="group rounded-xl border border-gray-200 bg-white p-2.5 text-left transition hover:border-[#D4A373] hover:shadow-md"
                                >
                                    <div
                                        class="mb-1.5 h-16 w-full overflow-hidden rounded-lg bg-[#FAEDCD]/30"
                                    >
                                        <img
                                            v-if="product.image"
                                            :src="product.image"
                                            :alt="product.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="flex h-full w-full items-center justify-center text-[#D4A373]/40"
                                        >
                                            ☕
                                        </div>
                                    </div>
                                    <h4
                                        class="line-clamp-1 text-[11px] font-bold text-gray-800"
                                    >
                                        {{ product.name }}
                                    </h4>
                                    <p
                                        class="text-[10px] font-bold text-[#D4A373]"
                                    >
                                        {{ formatPrice(product.price) }}
                                    </p>
                                    <div
                                        v-if="product.addons?.length > 0"
                                        class="mt-0.5"
                                    >
                                        <span class="text-[9px] text-gray-400"
                                            >{{
                                                product.addons.length
                                            }}
                                            addon</span
                                        >
                                    </div>
                                </button>
                            </div>

                            <!-- Pre-order Cart -->
                            <div
                                v-if="preorderCart.length > 0"
                                class="space-y-3 rounded-xl border border-[#D4A373]/20 bg-[#FAEDCD]/30 p-4"
                            >
                                <h4
                                    class="text-xs font-extrabold tracking-wider text-[#3B2314] uppercase"
                                >
                                    Menu Pre-Order
                                </h4>
                                <div
                                    v-for="(item, index) in preorderCart"
                                    :key="index"
                                    class="flex items-center justify-between rounded-lg border border-gray-100 bg-white px-3 py-2 shadow-sm"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-xs font-bold text-gray-800"
                                        >
                                            {{ item.name }}
                                        </p>
                                        <p class="text-[10px] text-gray-500">
                                            {{ formatPrice(item.price) }} ×
                                            {{ item.quantity }} =
                                            {{
                                                formatPrice(
                                                    item.price * item.quantity,
                                                )
                                            }}
                                        </p>
                                        <p
                                            v-if="item.notes"
                                            class="mt-0.5 truncate text-[9px] text-gray-400 italic"
                                        >
                                            {{ item.notes }}
                                        </p>
                                    </div>
                                    <div
                                        class="ml-2 flex shrink-0 items-center gap-1.5"
                                    >
                                        <button
                                            type="button"
                                            @click="decreasePreorder(index)"
                                            class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-xs font-bold text-gray-600 transition hover:bg-red-100 hover:text-red-600"
                                        >
                                            −
                                        </button>
                                        <span
                                            class="w-6 text-center text-xs font-bold"
                                            >{{ item.quantity }}</span
                                        >
                                        <button
                                            type="button"
                                            @click="increasePreorder(index)"
                                            class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-xs font-bold text-gray-600 transition hover:bg-green-100 hover:text-green-600"
                                        >
                                            +
                                        </button>
                                        <button
                                            type="button"
                                            @click="removePreorderItem(index)"
                                            class="ml-1 flex h-6 w-6 items-center justify-center rounded-lg bg-red-50 text-red-400 transition hover:bg-red-100 hover:text-red-600"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center justify-between border-t border-[#D4A373]/20 pt-2"
                                >
                                    <span
                                        class="text-xs font-bold text-gray-500"
                                        >Total Pre-Order:</span
                                    >
                                    <span
                                        class="text-sm font-extrabold text-[#3B2314]"
                                        >{{ formatPrice(preorderTotal) }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex gap-3 border-t border-gray-100 bg-gray-50 p-5">
                    <button
                        @click="showCreateModal = false"
                        class="flex-1 rounded-xl bg-gray-200 py-3 text-xs font-bold tracking-widest text-gray-700 uppercase transition hover:bg-gray-300"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitReservation"
                        :disabled="
                            isSubmitting ||
                            !createForm.customer_name ||
                            !createForm.customer_phone ||
                            !createForm.reservation_date ||
                            !createForm.reservation_time
                        "
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-[#3B2314] py-3 text-xs font-bold tracking-widest text-[#FAEDCD] uppercase shadow-md transition hover:bg-[#D4A373] hover:text-[#3B2314] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg
                            v-if="isSubmitting"
                            class="size-4 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                            />
                        </svg>
                        {{ isSubmitting ? 'Memproses...' : 'Simpan Reservasi' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================== ADDON SELECTION MODAL ==================== -->
        <div
            v-if="showAddonModal && addonProduct"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @click.self="showAddonModal = false"
        >
            <div
                class="w-full max-w-md overflow-hidden rounded-2xl border border-[#D4A373]/20 bg-white shadow-2xl"
            >
                <!-- Product Header -->
                <div class="relative">
                    <div class="h-40 overflow-hidden bg-[#FAEDCD]/30">
                        <img
                            v-if="addonProduct.image"
                            :src="addonProduct.image"
                            :alt="addonProduct.name"
                            class="h-full w-full object-cover"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center text-5xl text-[#D4A373]/30"
                        >
                            ☕
                        </div>
                    </div>
                    <button
                        @click="showAddonModal = false"
                        class="absolute top-3 right-3 flex h-8 w-8 items-center justify-center rounded-full bg-black/40 text-white transition hover:bg-black/60"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                            class="size-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4 p-5">
                    <div>
                        <h3 class="text-lg font-extrabold text-gray-800">
                            {{ addonProduct.name }}
                        </h3>
                        <p class="text-sm font-bold text-[#D4A373]">
                            {{ formatPrice(addonProduct.price) }}
                        </p>
                        <p
                            v-if="addonProduct.description"
                            class="mt-1 text-xs text-gray-500"
                        >
                            {{ addonProduct.description }}
                        </p>
                    </div>

                    <!-- Addons -->
                    <div v-if="addonProduct.addons?.length > 0">
                        <h4
                            class="mb-2 text-xs font-extrabold tracking-wider text-[#3B2314] uppercase"
                        >
                            Pilih Addons (Opsional)
                        </h4>
                        <div class="space-y-2">
                            <button
                                v-for="addon in addonProduct.addons"
                                :key="addon.id"
                                type="button"
                                @click="toggleAddon(addon)"
                                class="flex w-full items-center justify-between rounded-xl border px-3 py-2.5 text-sm transition"
                                :class="
                                    isAddonSelected(addon.id)
                                        ? 'border-[#D4A373] bg-[#FAEDCD]/30 text-[#3B2314]'
                                        : 'border-gray-200 bg-white text-gray-700 hover:border-[#D4A373]/50'
                                "
                            >
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border-2"
                                        :class="
                                            isAddonSelected(addon.id)
                                                ? 'border-[#D4A373] bg-[#D4A373]'
                                                : 'border-gray-300'
                                        "
                                    >
                                        <svg
                                            v-if="isAddonSelected(addon.id)"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                            class="size-3 text-white"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                    <span class="font-bold">{{
                                        addon.name
                                    }}</span>
                                </div>
                                <span class="text-xs font-bold text-[#D4A373]"
                                    >+{{ formatPrice(addon.price) }}</span
                                >
                            </button>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-extrabold tracking-wider text-[#3B2314] uppercase"
                            >Catatan Khusus</label
                        >
                        <input
                            v-model="addonNotes"
                            type="text"
                            placeholder="Contoh: less sugar, extra ice..."
                            class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-transparent focus:ring-2 focus:ring-[#D4A373] focus:outline-none"
                        />
                    </div>

                    <!-- Qty -->
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-extrabold tracking-wider text-[#3B2314] uppercase"
                            >Jumlah</span
                        >
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="addonQty = Math.max(1, addonQty - 1)"
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#FAEDCD] text-lg font-bold text-[#3B2314] transition hover:bg-[#D4A373] hover:text-white"
                            >
                                −
                            </button>
                            <span
                                class="w-8 text-center text-xl font-black text-[#3B2314]"
                                >{{ addonQty }}</span
                            >
                            <button
                                type="button"
                                @click="addonQty++"
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#FAEDCD] text-lg font-bold text-[#3B2314] transition hover:bg-[#D4A373] hover:text-white"
                            >
                                +
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Add Button -->
                <div class="border-t border-gray-100 bg-gray-50 p-5">
                    <button
                        @click="confirmAddToPreorder"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#3B2314] py-3.5 text-sm font-extrabold tracking-wider text-[#FAEDCD] uppercase shadow-md transition hover:bg-[#D4A373] hover:text-[#3B2314]"
                    >
                        <span
                            >Tambahkan —
                            {{ formatPrice(addonTotalPrice) }}</span
                        >
                    </button>
                </div>
            </div>
        </div>
    </ZunoiAdminLayout>
</template>
