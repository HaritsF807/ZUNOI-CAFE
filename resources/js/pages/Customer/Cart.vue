<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    qris_manual_url: String
});

const isQrZoomed = ref(false);

const form = useForm({
    customer_name: '',
    customer_phone: '',
    order_type: 'dine_in',
    payment_method: 'qris_tokopay',
    cart_items: [],
    payment_proof: null
});

onMounted(() => {
    const savedCart = localStorage.getItem('zunoi_cart');
    if (savedCart) {
        form.cart_items = JSON.parse(savedCart);
    }
});

const cartTotal = computed(() => {
    return form.cart_items.reduce((total, item) => total + (item.price * item.quantity), 0);
});

const handleFileChange = (e) => {
    form.payment_proof = e.target.files[0];
};

const submitOrder = () => {
    form.post('/order/store', {
        onSuccess: () => {
            localStorage.removeItem('zunoi_cart');
        }
    });
};
</script>

<template>
    <Head title="Checkout Zunoi Caffe" />

    <!-- Outer Desktop Background (Cozy Coffee Shop) -->
    <div class="min-h-screen bg-[#3B2314]/5 md:bg-[#1E100A] md:bg-[url('https://images.unsplash.com/photo-1554118811-1e0d58224f24?q=80&w=1600')] md:bg-cover md:bg-center md:bg-blend-multiply flex items-center justify-center p-0 md:p-6 font-sans">
        
        <!-- Smartphone Mockup Container -->
        <div class="w-full h-screen md:h-[88vh] md:max-h-[850px] md:max-w-md md:rounded-[44px] md:shadow-[0_30px_70px_rgba(0,0,0,0.8)] bg-[#FAEDCD] overflow-hidden md:border-[10px] md:border-[#3B2314] relative flex flex-col">
            
            <!-- Sticky Header -->
            <header class="bg-[#3B2314] text-[#FAEDCD] p-5 flex items-center gap-4 shadow-md rounded-b-[28px] sticky top-0 z-40">
                <Link :href="'/order'" class="bg-[#FAEDCD] text-[#3B2314] p-2 rounded-full hover:scale-105 active:scale-95 transition transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </Link>
                <h1 class="text-lg font-black tracking-wide">Checkout Pesanan</h1>
            </header>

            <!-- Scrollable Form Area -->
            <main class="flex-1 overflow-y-auto p-6 scrollbar-none">
                <form @submit.prevent="submitOrder" class="space-y-6">
                    <!-- Order Type Toggle -->
                    <div class="bg-white p-1 rounded-2xl flex shadow-sm border border-[#D4A373]/10">
                        <button type="button" @click="form.order_type = 'dine_in'" :class="{'bg-[#3B2314] text-white': form.order_type === 'dine_in', 'text-gray-500': form.order_type !== 'dine_in'}" class="flex-1 py-2.5 rounded-xl font-bold text-xs transition-all duration-300">Dine In</button>
                        <button type="button" @click="form.order_type = 'takeaway'" :class="{'bg-[#3B2314] text-white': form.order_type === 'takeaway', 'text-gray-500': form.order_type !== 'takeaway'}" class="flex-1 py-2.5 rounded-xl font-bold text-xs transition-all duration-300">Takeaway</button>
                    </div>

                    <!-- Customer Details -->
                    <div class="bg-white p-5 rounded-[24px] shadow-sm space-y-4 border border-[#D4A373]/10">
                        <h2 class="font-extrabold text-[#3B2314] border-b pb-2 text-sm flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-[#D4A373]">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            Informasi Pemesan
                        </h2>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Nama Lengkap</label>
                            <input v-model="form.customer_name" type="text" class="w-full text-xs rounded-xl border-gray-200 shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373] px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Nomor WhatsApp Aktif</label>
                            <input v-model="form.customer_phone" type="text" placeholder="Contoh: 08123456789" class="w-full text-xs rounded-xl border-gray-200 shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373] px-3 py-2" required>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="bg-white p-5 rounded-[24px] shadow-sm space-y-3 border border-[#D4A373]/10">
                        <h2 class="font-extrabold text-[#3B2314] border-b pb-2 text-sm flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-[#D4A373]">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                            </svg>
                            Metode Pembayaran
                        </h2>
                        
                        <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer text-xs transition duration-200" :class="{'border-[#D4A373] bg-[#FAEDCD]/30': form.payment_method === 'qris_tokopay'}">
                            <input type="radio" v-model="form.payment_method" value="qris_tokopay" class="text-[#3B2314] focus:ring-[#3B2314]">
                            <span class="font-bold">QRIS Otomatis (Tokopay)</span>
                        </label>

                        <label class="flex flex-col gap-2 p-3 border rounded-xl cursor-pointer text-xs transition duration-200" :class="{'border-[#D4A373] bg-[#FAEDCD]/30': form.payment_method === 'qris_manual'}">
                            <div class="flex items-center gap-3">
                                <input type="radio" v-model="form.payment_method" value="qris_manual" class="text-[#3B2314] focus:ring-[#3B2314]">
                                <span class="font-bold">QRIS Toko (Manual Verifikasi)</span>
                            </div>
                            <div v-if="form.payment_method === 'qris_manual'" class="mt-2 text-center bg-white p-3 rounded-xl border border-gray-100 space-y-3">
                                <p class="text-[10px] text-gray-500 mb-1">Scan QR di bawah ini, lalu unggah bukti pembayaran.</p>
                                
                                <div class="relative group cursor-zoom-in inline-block" @click="isQrZoomed = true">
                                    <img :src="qris_manual_url" alt="QRIS Toko" class="w-28 h-28 mx-auto border p-1 rounded-xl transition hover:opacity-90">
                                    <div class="absolute inset-0 bg-[#3B2314]/30 rounded-xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5 text-white">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637zM10.5 7.5v6m3-3h-6" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-[9px] text-gray-400 mt-1 font-semibold">Klik QR untuk memperbesar</p>
                                
                                <div class="text-left mt-3">
                                    <label class="block text-[10px] font-black text-gray-600 mb-1">Unggah Bukti Pembayaran (Struk/Screenshot)</label>
                                    <input type="file" @change="handleFileChange" accept="image/*" class="w-full text-[10px] text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-black file:bg-[#FAEDCD] file:text-[#3B2314] hover:file:bg-[#D4A373] file:cursor-pointer" :required="form.payment_method === 'qris_manual'">
                                </div>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer text-xs transition duration-200" :class="{'border-[#D4A373] bg-[#FAEDCD]/30': form.payment_method === 'cashier'}">
                            <input type="radio" v-model="form.payment_method" value="cashier" class="text-[#3B2314] focus:ring-[#3B2314]">
                            <span class="font-bold">Bayar Langsung di Kasir</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-[#D4A373] text-[#3B2314] font-black py-4 rounded-2xl shadow-md hover:scale-[1.02] active:scale-95 transition-all text-xs uppercase tracking-wider mt-8" :disabled="form.cart_items.length === 0" :class="{'opacity-50 cursor-not-allowed': form.cart_items.length === 0}">
                        Bayar Sekarang &bull; Rp {{ cartTotal.toLocaleString('id-ID') }}
                    </button>
                </form>
            </main>

            <!-- QR Code Zoom Modal -->
            <div v-if="isQrZoomed" class="absolute inset-0 bg-black/80 z-50 flex flex-col items-center justify-center p-6 transition-all duration-300" @click.self="isQrZoomed = false">
                <div class="bg-white p-5 rounded-[28px] max-w-[90%] shadow-2xl relative transition-transform duration-300 scale-100">
                    <button type="button" @click="isQrZoomed = false" class="absolute -top-3 -right-3 bg-red-600 text-white rounded-full p-2 shadow-lg hover:scale-105 active:scale-95 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <img :src="qris_manual_url" alt="QRIS Toko Zoomed" class="w-64 h-64 mx-auto rounded-xl border p-1">
                    <p class="text-center text-xs font-black text-[#3B2314] mt-4">Scan QRIS Toko Zunoi</p>
                    <p class="text-center text-[10px] text-gray-400 mt-1">Silakan scan kode QR di atas untuk menyelesaikan transfer.</p>
                </div>
            </div>
        </div>
    </div>
</template>
