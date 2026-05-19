<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Scan QR Meja Diperlukan - Zunoi Caffe" />

    <!-- Main Customer Area -->
    <div class="min-h-screen bg-gradient-to-b from-[#FAEDCD] via-white to-white font-sans flex flex-col items-center justify-center p-6 relative">
        <div class="bg-white p-8 rounded-2xl shadow-sm max-w-sm w-full border border-[#D4A373]/10 relative overflow-hidden text-center">
            <!-- Decorative circle -->
            <div class="absolute -right-12 -top-12 w-32 h-32 bg-[#FAEDCD]/40 rounded-full blur-xl"></div>
            <div class="absolute -left-12 -bottom-12 w-32 h-32 bg-[#D4A373]/10 rounded-full blur-xl"></div>

            <!-- Animated QR SVG Container -->
            <div class="w-20 h-20 bg-[#FAEDCD] rounded-full flex items-center justify-center mx-auto mb-6 relative group border border-[#D4A373]/30">
                <div class="absolute inset-0 bg-[#D4A373]/20 rounded-full animate-ping opacity-75"></div>
                
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-[#3B2314] relative z-10 animate-pulse">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 15.75h.008v.008H13.5v-.008Zm0 3h.008v.008H13.5v-.008Zm3-3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm3-3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
            </div>

            <!-- Headings -->
            <h1 class="text-xl font-black text-[#3B2314] tracking-tight mb-2">Scan Meja Diperlukan!</h1>
            <div class="w-10 h-1 bg-[#D4A373] mx-auto rounded-full mb-4"></div>
            
            <!-- Message -->
            <p class="text-gray-500 text-xs leading-relaxed mb-6 font-bold">
                Pemesanan di <span class="text-[#3B2314]">Zunoi Caffe</span> sepenuhnya terintegrasi pintar dengan barcode meja Anda.
                <br><br>
                Silakan pindai <span class="text-[#3B2314]">QR Code</span> yang tertera di meja Anda untuk mulai memilih menu dan melakukan pemesanan instan!
            </p>

            <!-- Coffee shop themed decorative bar -->
            <div class="bg-[#FAEDCD]/70 text-[#3B2314] py-3.5 px-5 rounded-xl text-[10px] font-black border border-[#D4A373]/20 flex items-center justify-center gap-2">
                <span class="flex h-2 w-2 rounded-full bg-[#D4A373]"></span>
                Nikmati kemudahan pesan tanpa antre!
            </div>
        </div>
    </div>
</template>
