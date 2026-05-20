<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    products: { type: Array, default: () => [] },
});

const form = useForm({
    customer_name: '',
    customer_phone: '',
    reservation_date: '',
    reservation_time: '',
    num_guests: 1,
    notes: '',
    preorder_items: [],
});

const showPreorder = ref(false);
const isSubmitting = ref(false);

// Pre-order state
const preorderCart = ref([]);

const addToPreorder = (product) => {
    const existing = preorderCart.value.find(item => item.id === product.id);
    if (existing) {
        existing.quantity++;
    } else {
        preorderCart.value.push({
            id: product.id,
            name: product.name,
            price: product.price,
            image: product.image,
            quantity: 1,
            notes: '',
        });
    }
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
    return preorderCart.value.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(price);
};

// Time slots for reservation
const timeSlots = [
    '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
    '11:00', '11:30', '12:00', '12:30', '13:00', '13:30',
    '14:00', '14:30', '15:00', '15:30', '16:00', '16:30',
    '17:00', '17:30', '18:00', '18:30', '19:00', '19:30',
    '20:00', '20:30', '21:00',
];

// Minimum date = today
const today = new Date().toISOString().split('T')[0];

const submitReservation = () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;

    form.preorder_items = preorderCart.value.map(item => ({
        id: item.id,
        quantity: item.quantity,
        notes: item.notes,
    }));

    form.post('/reservasi', {
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

// Search for preorder menu
const preorderSearch = ref('');
const filteredProducts = computed(() => {
    if (!preorderSearch.value.trim()) return props.products;
    const q = preorderSearch.value.toLowerCase();
    return props.products.filter(p => p.name.toLowerCase().includes(q));
});
</script>

<template>
    <Head title="Reservasi Meja - Zunoi Caffe" />

    <div class="min-h-screen bg-gradient-to-br from-[#3B2314] via-[#4A2E1B] to-[#2A1A0E] flex items-center justify-center p-4">
        <div class="w-full max-w-2xl">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-[#D4A373] rounded-full shadow-xl mb-4">
                    <span class="text-3xl font-black text-[#3B2314]">Z</span>
                </div>
                <h1 class="text-3xl font-black text-[#FAEDCD] tracking-wider">ZUNOI CAFFE</h1>
                <p class="text-[#D4A373] mt-2 text-sm font-medium tracking-widest uppercase">Reservasi Meja</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl overflow-hidden border border-[#D4A373]/20">
                <!-- Form Header -->
                <div class="bg-gradient-to-r from-[#3B2314] to-[#5A3E2A] p-6">
                    <h2 class="text-xl font-extrabold text-[#FAEDCD] tracking-wide flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                        Buat Reservasi Baru
                    </h2>
                    <p class="text-white/60 text-xs mt-1">Isi form di bawah ini untuk memesan meja Anda.</p>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Nama Lengkap -->
                    <div>
                        <label class="block text-xs font-extrabold text-[#3B2314] uppercase tracking-wider mb-1.5">Nama Lengkap *</label>
                        <input
                            v-model="form.customer_name"
                            type="text"
                            placeholder="Masukkan nama lengkap Anda"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-[#D4A373] focus:border-transparent transition"
                        />
                        <p v-if="form.errors.customer_name" class="text-red-500 text-xs mt-1">{{ form.errors.customer_name }}</p>
                    </div>

                    <!-- No WhatsApp -->
                    <div>
                        <label class="block text-xs font-extrabold text-[#3B2314] uppercase tracking-wider mb-1.5">No. WhatsApp *</label>
                        <input
                            v-model="form.customer_phone"
                            type="tel"
                            placeholder="Contoh: 08123456789"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-[#D4A373] focus:border-transparent transition"
                        />
                        <p v-if="form.errors.customer_phone" class="text-red-500 text-xs mt-1">{{ form.errors.customer_phone }}</p>
                    </div>

                    <!-- Date & Time Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-extrabold text-[#3B2314] uppercase tracking-wider mb-1.5">Tanggal Reservasi *</label>
                            <input
                                v-model="form.reservation_date"
                                type="date"
                                :min="today"
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-[#D4A373] focus:border-transparent transition"
                            />
                            <p v-if="form.errors.reservation_date" class="text-red-500 text-xs mt-1">{{ form.errors.reservation_date }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-extrabold text-[#3B2314] uppercase tracking-wider mb-1.5">Waktu *</label>
                            <select
                                v-model="form.reservation_time"
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-[#D4A373] focus:border-transparent transition bg-white"
                            >
                                <option value="" disabled>Pilih waktu...</option>
                                <option v-for="slot in timeSlots" :key="slot" :value="slot">{{ slot }}</option>
                            </select>
                            <p v-if="form.errors.reservation_time" class="text-red-500 text-xs mt-1">{{ form.errors.reservation_time }}</p>
                        </div>
                    </div>

                    <!-- Jumlah Tamu -->
                    <div>
                        <label class="block text-xs font-extrabold text-[#3B2314] uppercase tracking-wider mb-1.5">Jumlah Tamu *</label>
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="form.num_guests = Math.max(1, form.num_guests - 1)"
                                class="w-10 h-10 bg-[#FAEDCD] text-[#3B2314] rounded-xl font-bold text-lg flex items-center justify-center hover:bg-[#D4A373] hover:text-white transition shadow-sm"
                            >−</button>
                            <span class="text-2xl font-black text-[#3B2314] w-16 text-center">{{ form.num_guests }}</span>
                            <button
                                type="button"
                                @click="form.num_guests = Math.min(50, form.num_guests + 1)"
                                class="w-10 h-10 bg-[#FAEDCD] text-[#3B2314] rounded-xl font-bold text-lg flex items-center justify-center hover:bg-[#D4A373] hover:text-white transition shadow-sm"
                            >+</button>
                            <span class="text-xs text-gray-400 ml-2">orang</span>
                        </div>
                    </div>

                    <!-- Catatan -->
                    <div>
                        <label class="block text-xs font-extrabold text-[#3B2314] uppercase tracking-wider mb-1.5">Catatan Tambahan</label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            placeholder="Contoh: Meja dekat jendela, ada anak kecil, dll."
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-[#D4A373] focus:border-transparent resize-none transition"
                        ></textarea>
                    </div>

                    <!-- Divider: Pre-order Toggle -->
                    <div class="border-t border-dashed border-[#D4A373]/30 pt-5">
                        <button
                            type="button"
                            @click="showPreorder = !showPreorder"
                            class="w-full flex items-center justify-between bg-[#FAEDCD]/50 hover:bg-[#FAEDCD] px-4 py-3.5 rounded-xl transition border border-[#D4A373]/20"
                        >
                            <div class="flex items-center gap-2.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5 text-[#D4A373]">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                                </svg>
                                <div class="text-left">
                                    <span class="text-sm font-extrabold text-[#3B2314]">Pre-Order Menu (Opsional)</span>
                                    <p class="text-[10px] text-gray-500">Pesan menu di muka agar siap saat Anda datang</p>
                                </div>
                            </div>
                            <svg :class="{'rotate-180': showPreorder}" class="w-5 h-5 text-[#D4A373] transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Pre-order Section -->
                        <div v-if="showPreorder" class="mt-4 space-y-4">
                            <!-- Search -->
                            <div class="relative">
                                <input
                                    v-model="preorderSearch"
                                    type="text"
                                    placeholder="Cari menu kopi, kue..."
                                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#D4A373] bg-gray-50"
                                />
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="absolute left-3 top-2.5 size-5 text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                            </div>

                            <!-- Menu Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-72 overflow-y-auto pr-1">
                                <button
                                    v-for="product in filteredProducts"
                                    :key="product.id"
                                    type="button"
                                    @click="addToPreorder(product)"
                                    class="bg-white border border-gray-200 rounded-xl p-3 hover:border-[#D4A373] hover:shadow-md transition text-left group"
                                >
                                    <div class="w-full h-20 bg-[#FAEDCD]/30 rounded-lg overflow-hidden mb-2">
                                        <img v-if="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex items-center justify-center text-[#D4A373]/40">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <h4 class="text-xs font-bold text-gray-800 line-clamp-1">{{ product.name }}</h4>
                                    <p class="text-[10px] font-bold text-[#D4A373] mt-0.5">{{ formatPrice(product.price) }}</p>

                                    <!-- Add icon on hover -->
                                    <div class="mt-1.5 flex justify-center">
                                        <span class="text-[10px] font-bold text-[#3B2314] bg-[#FAEDCD] px-3 py-1 rounded-full group-hover:bg-[#D4A373] group-hover:text-white transition">+ Tambah</span>
                                    </div>
                                </button>
                            </div>

                            <!-- Pre-order Cart Summary -->
                            <div v-if="preorderCart.length > 0" class="bg-[#FAEDCD]/30 rounded-xl p-4 border border-[#D4A373]/20 space-y-3">
                                <h4 class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider">Menu Pre-Order Anda</h4>

                                <div v-for="(item, index) in preorderCart" :key="item.id" class="flex items-center justify-between bg-white rounded-lg px-3 py-2 shadow-sm border border-gray-100">
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <div class="w-8 h-8 bg-[#FAEDCD]/50 rounded-lg overflow-hidden shrink-0">
                                            <img v-if="item.image" :src="item.image" class="w-full h-full object-cover" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-gray-800 truncate">{{ item.name }}</p>
                                            <p class="text-[10px] text-gray-500">{{ formatPrice(item.price * item.quantity) }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button type="button" @click="decreasePreorder(index)" class="w-6 h-6 bg-gray-100 hover:bg-red-100 rounded-lg flex items-center justify-center text-xs font-bold text-gray-600 hover:text-red-600 transition">−</button>
                                        <span class="text-xs font-bold w-6 text-center">{{ item.quantity }}</span>
                                        <button type="button" @click="increasePreorder(index)" class="w-6 h-6 bg-gray-100 hover:bg-green-100 rounded-lg flex items-center justify-center text-xs font-bold text-gray-600 hover:text-green-600 transition">+</button>
                                        <button type="button" @click="removePreorderItem(index)" class="w-6 h-6 bg-red-50 hover:bg-red-100 rounded-lg flex items-center justify-center text-red-400 hover:text-red-600 transition ml-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5">
                                                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022 1.005 11.36A2.75 2.75 0 0 0 7.765 20h4.47a2.75 2.75 0 0 0 2.746-2.689l1.005-11.36.149.022a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5Z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center pt-2 border-t border-[#D4A373]/20">
                                    <span class="text-xs font-bold text-gray-500">Total Pre-Order:</span>
                                    <span class="text-sm font-extrabold text-[#3B2314]">{{ formatPrice(preorderTotal) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="button"
                        @click="submitReservation"
                        :disabled="isSubmitting || !form.customer_name || !form.customer_phone || !form.reservation_date || !form.reservation_time"
                        class="w-full py-4 bg-gradient-to-r from-[#3B2314] to-[#5A3E2A] hover:from-[#D4A373] hover:to-[#C69C6D] text-[#FAEDCD] hover:text-[#3B2314] rounded-xl text-sm font-extrabold tracking-widest uppercase transition-all duration-300 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <svg v-if="isSubmitting" class="animate-spin size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                        {{ isSubmitting ? 'Memproses...' : 'Konfirmasi Reservasi' }}
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center text-[#D4A373]/60 text-xs mt-6 tracking-wider">
                &copy; {{ new Date().getFullYear() }} Zunoi Caffe &mdash; Powered by Zunoi.id
            </p>
        </div>
    </div>
</template>
