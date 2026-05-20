<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';
import MenuAnalysisMode from './Partials/MenuAnalysisMode.vue';
import OperationsMode from './Partials/OperationsMode.vue';
import PerformanceMode from './Partials/PerformanceMode.vue';

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
    harianData: { labels: string[]; series: number[] };
    mingguanData: { labels: string[]; series: number[] };
    bulananData: { labels: string[]; series: number[] };
    topProducts: Array<{
        id: number;
        name: string;
        category: string;
        sales: number;
    }>;
    slowMovers: Array<{
        id: number;
        name: string;
        category: string;
        sales: number;
        suggestion: string;
    }>;
    addonsData: { labels: string[]; series: number[] };
    operationsData: {
        category: { labels: string[]; series: number[] };
        payment: { labels: string[]; series: number[] };
        orderType: { labels: string[]; series: number[] };
    };
}>();

// Switch Mode State
const activeMode = ref<'performa' | 'menu' | 'operasional'>('performa');
</script>

<template>
    <Head title="Statistik & Analitik" />

    <ZunoiAdminLayout>
        <div
            class="mb-6 flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center"
        >
            <div>
                <h1 class="text-2xl font-bold text-[#3B2314]">
                    Statistik & Analisis
                </h1>
                <p class="text-sm font-medium text-[#D4A373]">
                    Pantau performa, menu, dan operasional kafe Anda.
                </p>
            </div>

            <!-- Tabs Switcher -->
            <div
                class="flex max-w-full overflow-x-auto rounded-xl border border-[#D4A373]/20 bg-white p-1 shadow-sm"
            >
                <button
                    @click="activeMode = 'performa'"
                    :class="[
                        'rounded-lg px-4 py-2 text-sm font-semibold whitespace-nowrap transition-all',
                        activeMode === 'performa'
                            ? 'bg-[#3B2314] text-white shadow-md'
                            : 'text-gray-500 hover:bg-[#FAEDCD]/50 hover:text-[#3B2314]',
                    ]"
                >
                    Performa & Waktu
                </button>
                <button
                    @click="activeMode = 'menu'"
                    :class="[
                        'rounded-lg px-4 py-2 text-sm font-semibold whitespace-nowrap transition-all',
                        activeMode === 'menu'
                            ? 'bg-[#3B2314] text-white shadow-md'
                            : 'text-gray-500 hover:bg-[#FAEDCD]/50 hover:text-[#3B2314]',
                    ]"
                >
                    Analisis Menu
                </button>
                <button
                    @click="activeMode = 'operasional'"
                    :class="[
                        'rounded-lg px-4 py-2 text-sm font-semibold whitespace-nowrap transition-all',
                        activeMode === 'operasional'
                            ? 'bg-[#3B2314] text-white shadow-md'
                            : 'text-gray-500 hover:bg-[#FAEDCD]/50 hover:text-[#3B2314]',
                    ]"
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
