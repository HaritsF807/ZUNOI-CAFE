<script setup lang="ts">
import { ref, computed } from 'vue';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';
import { Line, Bar } from 'vue-chartjs';
import ChartDataLabels from 'chartjs-plugin-datalabels';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler,
  ChartDataLabels
);

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
}>();

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

// Trend Calculation
const calculateTrend = (current: number, previous: number) => {
    if (previous === 0) return current > 0 ? 100 : 0;
    return ((current - previous) / previous) * 100;
};

const revenueTrendMonth = computed(() => calculateTrend(props.monthlyRevenue, props.lastMonthRevenue));
const revenueTrendWeek = computed(() => calculateTrend(props.thisWeekRevenue, props.lastWeekRevenue));
const revenueTrendDay = computed(() => calculateTrend(props.dailyRevenue, props.yesterdayRevenue));

const orderTrendMonth = computed(() => calculateTrend(props.totalOrders, props.lastMonthOrders));
const orderTrendWeek = computed(() => calculateTrend(props.thisWeekOrders, props.lastWeekOrders));
const orderTrendDay = computed(() => calculateTrend(props.dailyOrders, props.yesterdayOrders));

// Sub-tabs state
const activeChartTab = ref<'harian' | 'mingguan' | 'bulanan'>('harian');

// Dynamic Chart Data mapping
const harianChartData = computed(() => ({
  labels: props.harianData.labels,
  datasets: [
    {
      label: 'Pendapatan (Jam)',
      backgroundColor: 'rgba(212, 163, 115, 0.2)',
      borderColor: '#D4A373',
      pointBackgroundColor: '#3B2314',
      borderWidth: 2,
      fill: true,
      tension: 0.4,
      data: props.harianData.series
    }
  ]
}));

const mingguanChartData = computed(() => ({
  labels: props.mingguanData.labels,
  datasets: [
    {
      label: 'Pendapatan (Hari)',
      backgroundColor: '#D4A373',
      borderRadius: 4,
      data: props.mingguanData.series
    }
  ]
}));

const bulananChartData = computed(() => ({
  labels: props.bulananData.labels,
  datasets: [
    {
      label: 'Pendapatan (Minggu)',
      backgroundColor: 'rgba(59, 35, 20, 0.8)',
      borderRadius: 4,
      data: props.bulananData.series
    }
  ]
}));

const commonOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      backgroundColor: '#3B2314',
      titleColor: '#FAEDCD',
      bodyColor: '#FAEDCD',
      padding: 12,
      cornerRadius: 8,
      callbacks: {
        label: function(context: any) {
          let label = context.dataset.label || '';
          if (label) {
            label += ': ';
          }
          if (context.parsed.y !== null) {
            label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
          }
          return label;
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(0,0,0,0.05)',
      },
      ticks: {
        callback: function(value: any) {
            if(value >= 1000000) return (value / 1000000) + 'M';
            if(value >= 1000) return (value / 1000) + 'k';
            return value;
        }
      }
    },
    x: {
      grid: {
        display: false
      }
    }
  }
};

const lineChartOptions = {
  ...commonOptions,
  plugins: {
    ...commonOptions.plugins,
    datalabels: {
      display: false
    }
  }
};

const barChartOptions = {
  ...commonOptions,
  layout: {
    padding: {
      top: 24
    }
  },
  plugins: {
    ...commonOptions.plugins,
    datalabels: {
      display: true,
      anchor: 'end',
      align: 'top',
      color: '#3B2314',
      font: {
        weight: 'bold',
        size: 11
      },
      formatter: function(value: any) {
        if(value >= 1000000) return (value / 1000000).toFixed(1).replace('.0', '') + 'M';
        if(value >= 1000) return (value / 1000) + 'k';
        return value;
      }
    }
  }
};
</script>

<template>
  <div class="flex flex-col gap-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <!-- Ringkasan Tren (Performance Metrics) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Card Pendapatan -->
      <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm border border-[#D4A373]/20 transition hover:shadow-md">
          <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Total Pendapatan</h3>
              <div class="p-2 bg-[#FAEDCD] rounded-lg">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-[#3B2314]">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                  </svg>
              </div>
          </div>
          
          <div class="flex flex-col gap-3">
              <p class="text-3xl font-extrabold text-[#3B2314]">
                  {{ formatCurrency(props.monthlyRevenue) }}
              </p>
              <div class="flex flex-wrap items-center gap-2">
                  <span :class="['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold', revenueTrendDay >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                      <svg v-if="revenueTrendDay >= 0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
                      </svg>
                      <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" />
                      </svg>
                      {{ revenueTrendDay > 0 ? '+' : '' }}{{ revenueTrendDay.toFixed(1) }}% Hari Lalu
                  </span>
                  <span :class="['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold', revenueTrendWeek >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                      <svg v-if="revenueTrendWeek >= 0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
                      </svg>
                      <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" />
                      </svg>
                      {{ revenueTrendWeek > 0 ? '+' : '' }}{{ revenueTrendWeek.toFixed(1) }}% Minggu Lalu
                  </span>
                  <span :class="['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold', revenueTrendMonth >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                      <svg v-if="revenueTrendMonth >= 0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
                      </svg>
                      <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" />
                      </svg>
                      {{ revenueTrendMonth > 0 ? '+' : '' }}{{ revenueTrendMonth.toFixed(1) }}% Bulan Lalu
                  </span>
              </div>
          </div>
      </div>

      <!-- Card Pesanan -->
      <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm border border-[#D4A373]/20 transition hover:shadow-md">
          <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Total Pesanan</h3>
              <div class="p-2 bg-[#FAEDCD] rounded-lg">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-[#3B2314]">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                  </svg>
              </div>
          </div>
          
          <div class="flex flex-col gap-3">
              <p class="text-3xl font-extrabold text-[#3B2314]">
                  {{ props.totalOrders }} <span class="text-lg font-medium text-gray-400">Order</span>
              </p>
              <div class="flex flex-wrap items-center gap-2">
                  <span :class="['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold', orderTrendDay >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                      <svg v-if="orderTrendDay >= 0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
                      </svg>
                      <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" />
                      </svg>
                      {{ orderTrendDay > 0 ? '+' : '' }}{{ orderTrendDay.toFixed(1) }}% Hari Lalu
                  </span>
                  <span :class="['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold', orderTrendWeek >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                      <svg v-if="orderTrendWeek >= 0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
                      </svg>
                      <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" />
                      </svg>
                      {{ orderTrendWeek > 0 ? '+' : '' }}{{ orderTrendWeek.toFixed(1) }}% Minggu Lalu
                  </span>
                  <span :class="['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold', orderTrendMonth >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                      <svg v-if="orderTrendMonth >= 0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
                      </svg>
                      <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" />
                      </svg>
                      {{ orderTrendMonth > 0 ? '+' : '' }}{{ orderTrendMonth.toFixed(1) }}% Bulan Lalu
                  </span>
              </div>
          </div>
      </div>
    </div>

    <!-- Grafik Interaktif Section -->
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-[#D4A373]/20">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
          <h2 class="text-lg font-bold text-[#3B2314]">Grafik Pendapatan</h2>
          <p class="text-sm text-gray-500">Analisis tren waktu sibuk dan performa harian.</p>
        </div>
        
        <!-- Sub-tabs Control -->
        <div class="flex p-1 bg-gray-100 rounded-lg">
          <button 
            @click="activeChartTab = 'harian'"
            :class="['px-4 py-1.5 text-sm font-medium rounded-md transition-all', activeChartTab === 'harian' ? 'bg-white shadow-sm text-[#3B2314]' : 'text-gray-500 hover:text-gray-700']"
          >
            Harian
          </button>
          <button 
            @click="activeChartTab = 'mingguan'"
            :class="['px-4 py-1.5 text-sm font-medium rounded-md transition-all', activeChartTab === 'mingguan' ? 'bg-white shadow-sm text-[#3B2314]' : 'text-gray-500 hover:text-gray-700']"
          >
            Mingguan
          </button>
          <button 
            @click="activeChartTab = 'bulanan'"
            :class="['px-4 py-1.5 text-sm font-medium rounded-md transition-all', activeChartTab === 'bulanan' ? 'bg-white shadow-sm text-[#3B2314]' : 'text-gray-500 hover:text-gray-700']"
          >
            Bulanan
          </button>
        </div>
      </div>

      <!-- Charts -->
      <div class="h-[300px] w-full relative">
        <Line v-if="activeChartTab === 'harian'" :data="harianChartData" :options="lineChartOptions" />
        <Bar v-else-if="activeChartTab === 'mingguan'" :data="mingguanChartData" :options="barChartOptions" />
        <Bar v-else-if="activeChartTab === 'bulanan'" :data="bulananChartData" :options="barChartOptions" />
      </div>
    </div>

  </div>
</template>
