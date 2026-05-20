<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';
import PerformanceMode from './Partials/PerformanceMode.vue';
import MenuAnalysisMode from './Partials/MenuAnalysisMode.vue';
import OperationsMode from './Partials/OperationsMode.vue';

const props = defineProps<{
    monthlyRevenue: number;
    dailyRevenue: number;
    lastMonthRevenue: number;
    yesterdayRevenue: number;
    thisWeekRevenue: number;
    lastWeekRevenue: number;
    totalOrders: number;
    lastMonthOrders: number;
    dailyOrders: number;
    yesterdayOrders: number;
    thisWeekOrders: number;
    lastWeekOrders: number;
    harianData: { labels: string[], series: number[] };
    mingguanData: { labels: string[], series: number[] };
    bulananData: { labels: string[], series: number[] };
    topProducts: Array<{ id: number, name: string, category: string, sales: number }>;
    slowMovers: Array<{ id: number, name: string, category: string, sales: number, suggestion: string }>;
    addonsData: { labels: string[], series: number[] };
    operationsData: {
        category: { labels: string[], series: number[] };
        payment: { labels: string[], series: number[] };
        orderType: { labels: string[], series: number[] };
    };
}>();

// Switch Mode State
const activeMode = ref<'performa' | 'menu' | 'operasional'>('performa');

</script>

<template>
    <Head title="Statistik & Analitik" />

    <ZunoiAdminLayout>
        <div class="mb-6 flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
            <div>
                <h1 class="text-2xl font-bold text-[#3B2314]">Statistik & Analisis</h1>
                <p class="text-sm font-medium text-[#D4A373]">
                    Pantau performa, menu, dan operasional kafe Anda.
                </p>
            </div>
            
            <!-- Tabs Switcher -->
            <div class="flex p-1 bg-white rounded-xl shadow-sm border border-[#D4A373]/20 overflow-x-auto max-w-full">
                <button 
                    @click="activeMode = 'performa'"
                    :class="['whitespace-nowrap px-4 py-2 text-sm font-semibold rounded-lg transition-all', activeMode === 'performa' ? 'bg-[#3B2314] text-white shadow-md' : 'text-gray-500 hover:text-[#3B2314] hover:bg-[#FAEDCD]/50']"
                >
                    Performa & Waktu
                </button>
                <button 
                    @click="activeMode = 'menu'"
                    :class="['whitespace-nowrap px-4 py-2 text-sm font-semibold rounded-lg transition-all', activeMode === 'menu' ? 'bg-[#3B2314] text-white shadow-md' : 'text-gray-500 hover:text-[#3B2314] hover:bg-[#FAEDCD]/50']"
                >
                    Analisis Menu
                </button>
                <button 
                    @click="activeMode = 'operasional'"
                    :class="['whitespace-nowrap px-4 py-2 text-sm font-semibold rounded-lg transition-all', activeMode === 'operasional' ? 'bg-[#3B2314] text-white shadow-md' : 'text-gray-500 hover:text-[#3B2314] hover:bg-[#FAEDCD]/50']"
                >
                    Operasional & Transaksi
                </button>
            </div>
        </div>

        <!-- Render Dynamic Component based on activeMode -->
        <div class="mt-6">
            <PerformanceMode 
                v-if="activeMode === 'performa'" 
                :monthlyRevenue="props.monthlyRevenue" 
                :dailyRevenue="props.dailyRevenue" 
                :lastMonthRevenue="props.lastMonthRevenue"
                :yesterdayRevenue="props.yesterdayRevenue"
                :thisWeekRevenue="props.thisWeekRevenue"
                :lastWeekRevenue="props.lastWeekRevenue"
                :totalOrders="props.totalOrders"
                :lastMonthOrders="props.lastMonthOrders"
                :dailyOrders="props.dailyOrders"
                :yesterdayOrders="props.yesterdayOrders"
                :thisWeekOrders="props.thisWeekOrders"
                :lastWeekOrders="props.lastWeekOrders"
                :harianData="props.harianData"
                :mingguanData="props.mingguanData"
                :bulananData="props.bulananData"
            />
            <MenuAnalysisMode 
                v-if="activeMode === 'menu'" 
                :topProducts="props.topProducts"
                :slowMovers="props.slowMovers"
                :addonsData="props.addonsData"
            />
            <OperationsMode 
                v-if="activeMode === 'operasional'" 
                :operationsData="props.operationsData"
            />
        </div>

    </ZunoiAdminLayout>
</template>
