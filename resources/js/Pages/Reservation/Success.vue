<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash || {});
</script>

<template>
    <Head title="Reservasi Berhasil - Zunoi Caffe" />

    <div class="min-h-screen bg-gradient-to-br from-[#3B2314] via-[#4A2E1B] to-[#2A1A0E] flex items-center justify-center p-4">
        <div class="w-full max-w-md text-center">
            <!-- Success Icon -->
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-green-400 to-emerald-600 rounded-full shadow-2xl mb-6 animate-bounce">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="size-12 text-white">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>

            <h1 class="text-3xl font-black text-[#FAEDCD] tracking-wider mb-2">Reservasi Diterima!</h1>
            <p class="text-[#D4A373] text-sm mb-8">Kami sudah mengirimkan detail reservasi ke WhatsApp Anda.</p>

            <!-- Detail Card -->
            <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-6 space-y-4 border border-[#D4A373]/20 mb-6">
                <div class="bg-[#FAEDCD]/30 rounded-xl p-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Status:</span>
                        <span class="font-extrabold text-amber-600 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Menunggu Konfirmasi
                        </span>
                    </div>
                </div>

                <p class="text-xs text-gray-500 leading-relaxed">
                    Tim kami akan segera mengkonfirmasi reservasi Anda melalui WhatsApp. Silakan pantau pesan masuk di WhatsApp Anda.
                </p>
            </div>

            <!-- Buttons -->
            <div class="space-y-3">
                <a
                    href="/reservasi"
                    class="block w-full py-3.5 bg-[#D4A373] hover:bg-[#C69C6D] text-[#3B2314] rounded-xl text-sm font-extrabold tracking-wider uppercase transition shadow-lg"
                >
                    Buat Reservasi Lagi
                </a>
                <a
                    href="/"
                    class="block w-full py-3.5 bg-white/10 hover:bg-white/20 text-[#FAEDCD] rounded-xl text-sm font-bold tracking-wider transition border border-[#D4A373]/20"
                >
                    Kembali ke Beranda
                </a>
            </div>

            <!-- Footer -->
            <p class="text-[#D4A373]/60 text-xs mt-8 tracking-wider">
                &copy; {{ new Date().getFullYear() }} Zunoi Caffe &mdash; Powered by Zunoi.id
            </p>
        </div>
    </div>
</template>
