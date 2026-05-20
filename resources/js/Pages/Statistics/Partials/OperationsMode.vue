<script setup lang="ts">
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps<{
    operationsData: {
        category: { labels: string[], series: number[] };
        payment: { labels: string[], series: number[] };
        orderType: { labels: string[], series: number[] };
    };
}>();

// Helper to calculate total from series
const getTotal = (series: number[]) => series.reduce((a, b) => a + b, 0);

// Base Radial Bar Configuration
const createRadialOptions = (labels: string[], colors: string[]) => {
    return {
        chart: {
            type: 'radialBar',
            fontFamily: 'Inter, sans-serif',
        },
        plotOptions: {
            radialBar: {
                hollow: {
                    margin: 15,
                    size: '50%',
                },
                track: {
                    background: '#FAEDCD',
                    margin: 8,
                },
                dataLabels: {
                    name: {
                        fontSize: '14px',
                        color: '#3B2314',
                        fontWeight: 600,
                    },
                    value: {
                        fontSize: '18px',
                        color: '#D4A373',
                        fontWeight: 700,
                        formatter: function (val: number) {
                            return val + "%";
                        }
                    },
                    total: {
                        show: true,
                        label: 'Total',
                        color: '#3B2314',
                        formatter: function (w: any) {
                            return "100%";
                        }
                    }
                }
            }
        },
        labels: labels,
        colors: colors,
        stroke: {
            lineCap: 'round'
        }
    }
};

// Colors matching the UI theme
const themeColors = ['#3B2314', '#D4A373', '#FAEDCD'];

// Operations Data computed
const categorySalesOptions = computed(() => createRadialOptions(props.operationsData.category.labels, themeColors));
const paymentMethodsOptions = computed(() => createRadialOptions(props.operationsData.payment.labels, themeColors));
const orderTypeOptions = computed(() => createRadialOptions(props.operationsData.orderType.labels, themeColors));

</script>

<template>
  <div class="flex flex-col gap-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Category Sales -->
        <div class="rounded-2xl bg-white p-6 shadow-sm border border-[#D4A373]/20 flex flex-col items-center">
            <h2 class="text-md font-bold text-[#3B2314] mb-2 w-full text-center">Distribusi Kategori</h2>
            <div class="w-full flex justify-center">
                <VueApexCharts type="radialBar" height="280" :options="categorySalesOptions" :series="props.operationsData.category.series"></VueApexCharts>
            </div>
            <div class="w-full mt-4 flex flex-col gap-2">
                <div v-for="(label, index) in props.operationsData.category.labels" :key="index" class="flex justify-between items-center text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: themeColors[index % themeColors.length] }"></div>
                        <span class="text-gray-600 font-medium">{{ label }}</span>
                    </div>
                    <span class="font-bold text-[#3B2314]">{{ props.operationsData.category.series[index] }}%</span>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="rounded-2xl bg-white p-6 shadow-sm border border-[#D4A373]/20 flex flex-col items-center">
            <h2 class="text-md font-bold text-[#3B2314] mb-2 w-full text-center">Metode Pembayaran</h2>
            <div class="w-full flex justify-center">
                <VueApexCharts type="radialBar" height="280" :options="paymentMethodsOptions" :series="props.operationsData.payment.series"></VueApexCharts>
            </div>
            <div class="w-full mt-4 flex flex-col gap-2">
                <div v-for="(label, index) in props.operationsData.payment.labels" :key="index" class="flex justify-between items-center text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: themeColors[index % themeColors.length] }"></div>
                        <span class="text-gray-600 font-medium">{{ label }}</span>
                    </div>
                    <span class="font-bold text-[#3B2314]">{{ props.operationsData.payment.series[index] }}%</span>
                </div>
            </div>
        </div>

        <!-- Order Type -->
        <div class="rounded-2xl bg-white p-6 shadow-sm border border-[#D4A373]/20 flex flex-col items-center">
            <h2 class="text-md font-bold text-[#3B2314] mb-2 w-full text-center">Tipe Pesanan</h2>
            <div class="w-full flex justify-center">
                <VueApexCharts type="radialBar" height="280" :options="orderTypeOptions" :series="props.operationsData.orderType.series"></VueApexCharts>
            </div>
            <div class="w-full mt-4 flex flex-col gap-2">
                <div v-for="(label, index) in props.operationsData.orderType.labels" :key="index" class="flex justify-between items-center text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: themeColors[index % themeColors.length] }"></div>
                        <span class="text-gray-600 font-medium">{{ label }}</span>
                    </div>
                    <span class="font-bold text-[#3B2314]">{{ props.operationsData.orderType.series[index] }}%</span>
                </div>
            </div>
        </div>

    </div>

  </div>
</template>
