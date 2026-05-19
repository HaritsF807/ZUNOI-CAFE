<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    order: Object
});
</script>

<template>
    <Head title="Pesanan Berhasil" />

    <!-- Main Customer Area -->
    <div class="min-h-screen bg-gradient-to-b from-[#FAEDCD] via-white to-white font-sans flex flex-col relative">
        
        <!-- Sticky Header -->
        <header class="bg-[#3B2314] text-[#FAEDCD] py-5 shadow-md sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-center">
                <h1 class="text-xl font-black tracking-widest uppercase">Zunoi Caffe</h1>
            </div>
        </header>

        <!-- Scrollable Receipt Area -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 items-start">
                <!-- Left Column: Status, WhatsApp Warning, Back Button -->
                <div class="space-y-5">
                    <!-- Status Card -->
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-[#D4A373]/10 text-center relative overflow-hidden">
                        <div class="absolute -right-8 -top-8 w-20 h-20 bg-green-500/5 rounded-full blur-lg"></div>
                        
                        <div class="w-14 h-14 bg-green-50 text-green-500 rounded-full flex items-center justify-center mx-auto mb-3 border border-green-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor" class="w-6 h-6 animate-pulse">
                              <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </div>
                        
                        <h2 class="text-lg font-black text-[#3B2314] tracking-tight">Pesanan Terkirim!</h2>
                        <p class="text-[10px] text-gray-400 font-bold mt-0.5">ID PESANAN: #{{ order.id }}</p>
                        
                        <div class="mt-4 pt-3 border-t border-gray-100 grid grid-cols-2 gap-2 text-left text-[10px] text-gray-500 font-bold">
                            <div>
                                <span class="block text-gray-400 font-normal">Nama Pemesan</span>
                                <span class="text-[#3B2314] text-xs font-black">{{ order.customer_name }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-400 font-normal">Meja / Tipe</span>
                                <span class="text-[#3B2314] text-xs font-black">
                                    {{ order.table?.table_name || 'Takeaway' }} ({{ order.order_type === 'dine_in' ? 'Dine In' : 'Takeaway' }})
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Status Warning -->
                    <div v-if="order.payment_method === 'qris_tokopay'" class="bg-amber-50/50 text-[#3B2314] border border-[#D4A373]/20 p-4.5 rounded-xl text-[10px] font-black leading-relaxed flex items-start gap-2.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-[#D4A373] shrink-0 mt-0.5 animate-pulse">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <span>Notifikasi WhatsApp akan dikirimkan otomatis jika pembayaran QRIS Anda sukses terkonfirmasi.</span>
                    </div>

                    <!-- Back to Menu Button -->
                    <Link :href="'/order'" class="block w-full bg-[#3B2314] text-[#FAEDCD] py-4 rounded-xl font-black text-center hover:scale-[1.02] active:scale-95 transition text-xs uppercase tracking-wider shadow-md">
                        Kembali ke Menu Utama
                    </Link>
                </div>

                <!-- Right Column: Receipt Invoice Details -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-[#D4A373]/10 relative">
                    <div class="absolute -left-3 top-1/2 transform -translate-y-1/2 w-6 h-6 bg-white rounded-full"></div>
                    <div class="absolute -right-3 top-1/2 transform -translate-y-1/2 w-6 h-6 bg-white rounded-full"></div>
                    
                    <h3 class="font-extrabold text-[#3B2314] text-xs uppercase tracking-wider mb-4 pb-2 border-b border-dashed border-gray-200">
                        Rincian Tagihan
                    </h3>

                    <!-- Order Items List -->
                    <div class="space-y-3.5 mb-5">
                        <div v-for="item in order.items" :key="item.id" class="flex justify-between items-start text-xs">
                            <div class="max-w-[70%]">
                                <span class="font-black text-[#3B2314]">{{ item.product?.name || 'Menu Kopi' }}</span>
                                <span class="block text-[9px] text-gray-400 font-bold mt-0.5">
                                    {{ item.quantity }} x Rp {{ parseInt(item.price_at_sale).toLocaleString('id-ID') }}
                                </span>
                            </div>
                            <span class="font-black text-[#3B2314]">
                                Rp {{ (item.quantity * item.price_at_sale).toLocaleString('id-ID') }}
                            </span>
                        </div>
                    </div>

                    <!-- Dashed Divider -->
                    <div class="border-t border-dashed border-gray-200 my-4"></div>

                    <!-- Info Bayar & Total -->
                    <div class="space-y-2.5">
                        <div class="flex justify-between text-[10px] text-gray-500 font-bold">
                            <span>Metode Pembayaran</span>
                            <span class="text-[#3B2314] uppercase">
                                {{ order.payment_method === 'cashier' ? 'Bayar di Kasir' : order.payment_method === 'qris_tokopay' ? 'QRIS Otomatis' : 'QRIS Manual' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-xs pt-1.5">
                            <span class="font-bold text-[#3B2314]">Total Pembayaran</span>
                            <span class="text-base font-black text-[#D4A373]">
                                Rp {{ parseInt(order.total_price).toLocaleString('id-ID') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
