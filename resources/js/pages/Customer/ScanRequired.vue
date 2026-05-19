<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Scan QR Meja Diperlukan - Zunoi Caffe" />

    <!-- Main Customer Area -->
    <div
        class="relative flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-[#FAEDCD] via-white to-white p-6 font-sans"
    >
        <div
            class="relative w-full max-w-sm overflow-hidden rounded-2xl border border-[#D4A373]/10 bg-white p-8 text-center shadow-sm"
        >
            <!-- Decorative circle -->
            <div
                class="absolute -top-12 -right-12 h-32 w-32 rounded-full bg-[#FAEDCD]/40 blur-xl"
            ></div>
            <div
                class="absolute -bottom-12 -left-12 h-32 w-32 rounded-full bg-[#D4A373]/10 blur-xl"
            ></div>

            <!-- Animated QR SVG Container -->
            <div
                class="group relative mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full border border-[#D4A373]/30 bg-[#FAEDCD]"
            >
                <div
                    class="absolute inset-0 animate-ping rounded-full bg-[#D4A373]/20 opacity-75"
                ></div>

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="relative z-10 h-10 w-10 animate-pulse text-[#3B2314]"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M13.5 15.75h.008v.008H13.5v-.008Zm0 3h.008v.008H13.5v-.008Zm3-3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm3-3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"
                    />
                </svg>
            </div>

            <!-- Headings -->
            <h1 class="mb-2 text-xl font-black tracking-tight text-[#3B2314]">
                Scan Meja Diperlukan!
            </h1>
            <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-[#D4A373]"></div>

            <!-- Message -->
            <p class="mb-6 text-xs leading-relaxed font-bold text-gray-500">
                Pemesanan di
                <span class="text-[#3B2314]">Zunoi Caffe</span> sepenuhnya
                terintegrasi pintar dengan barcode meja Anda. <br /><br />
                Silakan pindai <span class="text-[#3B2314]">QR Code</span> yang
                tertera di meja Anda untuk mulai memilih menu dan melakukan
                pemesanan instan!
            </p>

            <!-- Coffee shop themed decorative bar -->
            <div
                class="flex items-center justify-center gap-2 rounded-xl border border-[#D4A373]/20 bg-[#FAEDCD]/70 px-5 py-3.5 text-[10px] font-black text-[#3B2314]"
            >
                <span class="flex h-2 w-2 rounded-full bg-[#D4A373]"></span>
                Nikmati kemudahan pesan tanpa antre!
            </div>
        </div>
    </div>
</template>
