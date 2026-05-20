<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash || {});
</script>

<template>
    <Head title="Reservasi Berhasil - Zunoi Caffe" />

    <div
        class="flex min-h-screen items-center justify-center bg-gradient-to-br from-[#3B2314] via-[#4A2E1B] to-[#2A1A0E] p-4"
    >
        <div class="w-full max-w-md text-center">
            <!-- Success Icon -->
            <div
                class="mb-6 inline-flex h-24 w-24 animate-bounce items-center justify-center rounded-full bg-gradient-to-br from-green-400 to-emerald-600 shadow-2xl"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="3"
                    stroke="currentColor"
                    class="size-12 text-white"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m4.5 12.75 6 6 9-13.5"
                    />
                </svg>
            </div>

            <h1 class="mb-2 text-3xl font-black tracking-wider text-[#FAEDCD]">
                Reservasi Diterima!
            </h1>
            <p class="mb-8 text-sm text-[#D4A373]">
                Kami sudah mengirimkan detail reservasi ke WhatsApp Anda.
            </p>

            <!-- Detail Card -->
            <div
                class="mb-6 space-y-4 rounded-3xl border border-[#D4A373]/20 bg-white/95 p-6 shadow-2xl backdrop-blur-xl"
            >
                <div class="space-y-2 rounded-xl bg-[#FAEDCD]/30 p-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Status:</span>
                        <span
                            class="flex items-center gap-1 font-extrabold text-amber-600"
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
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                />
                            </svg>
                            Menunggu Konfirmasi
                        </span>
                    </div>
                </div>

                <p class="text-xs leading-relaxed text-gray-500">
                    Tim kami akan segera mengkonfirmasi reservasi Anda melalui
                    WhatsApp. Silakan pantau pesan masuk di WhatsApp Anda.
                </p>
            </div>

            <!-- Buttons -->
            <div class="space-y-3">
                <a
                    href="/reservasi"
                    class="block w-full rounded-xl bg-[#D4A373] py-3.5 text-sm font-extrabold tracking-wider text-[#3B2314] uppercase shadow-lg transition hover:bg-[#C69C6D]"
                >
                    Buat Reservasi Lagi
                </a>
                <a
                    href="/"
                    class="block w-full rounded-xl border border-[#D4A373]/20 bg-white/10 py-3.5 text-sm font-bold tracking-wider text-[#FAEDCD] transition hover:bg-white/20"
                >
                    Kembali ke Beranda
                </a>
            </div>

            <!-- Footer -->
            <p class="mt-8 text-xs tracking-wider text-[#D4A373]/60">
                &copy; {{ new Date().getFullYear() }} Zunoi Caffe &mdash;
                Powered by Zunoi.id
            </p>
        </div>
    </div>
</template>
