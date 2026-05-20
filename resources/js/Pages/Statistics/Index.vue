<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';

const props = defineProps<{
    monthlyRevenue: number;
    dailyRevenue: number;
}>();

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};
</script>

<template>
    <Head title="Statistik & Analitik" />

    <ZunoiAdminLayout>
        <div class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-bold text-[#3B2314]">Statistik Pendapatan</h1>
                <p class="text-sm font-medium text-[#D4A373]">
                    Ringkasan performa penjualan bulanan dan harian Anda.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Card Pendapatan Bulanan -->
            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm border border-[#D4A373]/20 transition hover:shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Pendapatan Bulanan</h3>
                    <div class="p-2 bg-[#FAEDCD] rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-[#3B2314]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                </div>
                
                <div class="flex flex-col gap-2">
                    <p class="text-3xl font-extrabold text-[#3B2314]">
                        {{ formatCurrency(props.monthlyRevenue) }}
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
                            </svg>
                            + {{ formatCurrency(props.dailyRevenue) }} Hari Ini
                        </span>
                    </div>
                </div>
                
                <!-- Decoration -->
                <div class="absolute -right-6 -bottom-6 opacity-5 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-32 h-32 text-[#3B2314]">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </div>
    </ZunoiAdminLayout>
</template>
