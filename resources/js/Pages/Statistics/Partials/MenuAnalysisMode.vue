<script setup lang="ts">
import { computed } from 'vue';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
);

const props = defineProps<{
    topProducts: Array<{ id: number, name: string, category: string, sales: number }>;
    slowMovers: Array<{ id: number, name: string, category: string, sales: number, suggestion: string }>;
    addonsData: { labels: string[], series: number[] };
}>();

// Dynamic Add-ons Chart
const addonsChartData = computed(() => ({
  labels: props.addonsData.labels,
  datasets: [
    {
      label: 'Jumlah Terjual',
      backgroundColor: '#D4A373',
      borderRadius: 4,
      data: props.addonsData.series
    }
  ]
}));

const addonsChartOptions = {
  indexAxis: 'y', // This makes it a horizontal bar chart
  responsive: true,
  maintainAspectRatio: false,
  layout: {
    padding: {
      right: 30
    }
  },
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
    },
    datalabels: {
      anchor: 'end',
      align: 'right',
      color: '#3B2314',
      font: {
        weight: 'bold',
        size: 11
      },
      offset: 4
    }
  },
  scales: {
    x: {
      beginAtZero: true,
      grid: {
        color: 'rgba(0,0,0,0.05)',
      }
    },
    y: {
      grid: {
        display: false
      }
    }
  }
};
</script>

<template>
  <div class="flex flex-col gap-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Top 5 Products -->
        <div class="rounded-2xl bg-white p-6 shadow-sm border border-[#D4A373]/20">
            <h2 class="text-lg font-bold text-[#3B2314] mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-green-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                </svg>
                Top 5 Menu Terlaris
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-gray-500">
                            <th class="py-3 font-semibold">Produk</th>
                            <th class="py-3 font-semibold">Kategori</th>
                            <th class="py-3 font-semibold text-right">Terjual</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-if="props.topProducts.length === 0">
                            <td colspan="3" class="py-4 text-center text-gray-500 italic">Belum ada data penjualan produk.</td>
                        </tr>
                        <tr v-else v-for="item in props.topProducts" :key="item.id" class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 font-medium text-gray-800">{{ item.name }}</td>
                            <td class="py-3 text-gray-500">
                                <span class="px-2 py-1 bg-gray-100 rounded-md text-xs">{{ item.category }}</span>
                            </td>
                            <td class="py-3 text-right font-bold text-[#3B2314]">{{ item.sales }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Slow Movers -->
        <div class="rounded-2xl bg-white p-6 shadow-sm border border-[#D4A373]/20">
            <h2 class="text-lg font-bold text-[#3B2314] mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-red-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z" />
                </svg>
                Menu Kurang Laku (Slow Movers)
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-gray-500">
                            <th class="py-3 font-semibold">Produk</th>
                            <th class="py-3 font-semibold text-right">Terjual</th>
                            <th class="py-3 font-semibold pl-4">Rekomendasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-if="props.slowMovers.length === 0">
                            <td colspan="3" class="py-4 text-center text-gray-500 italic">Belum ada data penjualan produk.</td>
                        </tr>
                        <tr v-else v-for="item in props.slowMovers" :key="item.id" class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 font-medium text-gray-800">{{ item.name }}</td>
                            <td class="py-3 text-right font-bold text-red-600">{{ item.sales }}</td>
                            <td class="py-3 text-gray-500 pl-4 text-xs">{{ item.suggestion }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Add-ons Chart -->
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-[#D4A373]/20">
        <h2 class="text-lg font-bold text-[#3B2314] mb-1">Popularitas Add-ons / Topping</h2>
        <p class="text-sm text-gray-500 mb-6">Melihat tambahan produk apa yang paling sering dipesan oleh pelanggan.</p>
        
        <div class="h-[300px] w-full relative">
            <Bar :data="addonsChartData" :options="addonsChartOptions" />
        </div>
    </div>

  </div>
</template>
