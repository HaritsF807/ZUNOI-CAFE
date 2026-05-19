<script setup>
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const triggerToast = (message, type = 'success') => {
    window.dispatchEvent(new CustomEvent('zunoi-toast', {
        detail: { message, type }
    }));
};

const user = usePage().props.auth.user;
const tables = ref([]);
const newTableName = ref('');

// State Modal QR Code HD
const showQrModal = ref(false);
const selectedTableForQr = ref(null);

// State Custom Confirm Modal
const showConfirmModal = ref(false);
const confirmTitle = ref('');
const confirmMessage = ref('');
const confirmCallback = ref(null);

const triggerConfirm = (title, message, callback) => {
    confirmTitle.value = title;
    confirmMessage.value = message;
    confirmCallback.value = callback;
    showConfirmModal.value = true;
};

const handleConfirmYes = () => {
    if (confirmCallback.value) {
        confirmCallback.value();
    }
    showConfirmModal.value = false;
};

// Get Dynamic Base URL
const appUrl = typeof window !== 'undefined' ? window.location.origin : '';

const fetchTables = async () => {
    try {
        const response = await axios.get('/api/tables');
        tables.value = response.data;
    } catch (error) {
        console.error("Gagal mengambil data meja", error);
    }
};

const addTable = async () => {
    if(!newTableName.value) return;
    try {
        await axios.post('/api/tables', { table_name: newTableName.value });
        newTableName.value = '';
        fetchTables(); // Refresh list meja
        triggerToast("Meja baru berhasil ditambahkan!", "success");
    } catch (error) {
        console.error("Gagal tambah meja", error);
    }
};
const deleteTable = (id, tableName) => {
    triggerConfirm(
        'Hapus Meja',
        `Apakah Anda yakin ingin menghapus meja "${tableName}"? QR Code meja ini tidak akan bisa discan lagi oleh pelanggan!`,
        async () => {
            try {
                const response = await axios.delete(`/api/tables/${id}`);
                if(response.data.success) {
                    triggerToast("Meja berhasil dihapus!", "success");
                    fetchTables(); // Refresh list meja
                }
            } catch (error) {
                console.error("Gagal menghapus meja", error);
                triggerToast("Gagal menghapus meja.", "error");
            }
        }
    );
};// Fitur Unduh QR Code sebagai PNG asli
const downloadQr = async (table) => {
    try {
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${appUrl}/meja/${table.secure_token}`;
        const response = await fetch(qrUrl);
        const blob = await response.blob();
        const blobUrl = window.URL.createObjectURL(blob);
        
        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = `QR_${table.table_name.replace(/\s+/g, '_')}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(blobUrl);
    } catch (error) {
        console.error("Gagal mengunduh QR Code", error);
        triggerToast("Gagal mengunduh QR Code. Silakan klik kanan pada gambar untuk menyimpannya.", "error");
    }
};

// Buka modal QR
const openQrModal = (table) => {
    selectedTableForQr.value = table;
    showQrModal.value = true;
};

const closeQrModal = () => {
    showQrModal.value = false;
    selectedTableForQr.value = null;
};

onMounted(() => {
    fetchTables();
});
</script>

<template>
    <Head title="Kelola Meja Kafe" />

    <ZunoiAdminLayout>
        <div class="max-w-7xl mx-auto space-y-8 text-gray-800">
            
            <!-- HEADER SECTION -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-[#3B2314] text-[#FAEDCD] p-6 rounded-3xl shadow-xl border border-[#D4A373]/30 relative overflow-hidden">
                <div class="absolute -right-16 -top-16 w-48 h-48 bg-[#D4A373]/10 rounded-full blur-2xl"></div>
                <div class="z-10">
                    <h2 class="font-extrabold text-2xl tracking-wide flex items-center gap-2">
                        Kelola Meja & QR Code
                        <span class="text-[10px] bg-[#D4A373] text-[#3B2314] font-black uppercase px-2 py-0.5 rounded-full tracking-widest align-middle">Meja Admin</span>
                    </h2>
                    <p class="text-xs text-gray-300 mt-1">Tambahkan meja baru, lihat dan unduh QR Code pemesanan untuk ditempel di meja fisik.</p>
                </div>
            </div>

            <!-- MEJA & QR CODES LIST SECTION -->
            <div class="bg-white rounded-3xl border border-[#D4A373]/20 shadow-sm overflow-hidden p-6 space-y-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-100 pb-5">
                    <div>
                        <h3 class="text-md font-bold text-[#3B2314]">Daftar Meja Aktif</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Total saat ini: <span class="font-black text-[#3B2314]">{{ tables.length }} Meja</span></p>
                    </div>
                    
                    <!-- Tambah Meja Form -->
                    <div class="flex gap-2 w-full sm:w-auto">
                        <input v-model="newTableName" type="text" placeholder="Nama Meja (Contoh: Meja 05)" 
                               class="text-xs rounded-xl border-gray-200 shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373] px-3 py-2 w-full sm:w-48">
                        <button @click="addTable" class="bg-[#3B2314] hover:bg-[#25150c] text-white px-4 py-2 rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition shrink-0">
                            + Tambah Meja
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="tables.length === 0" class="text-center py-16 text-gray-400">
                    <div class="w-12 h-12 bg-gray-50 rounded-full border flex items-center justify-center mx-auto mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-gray-400">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 8.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                        </svg>
                    </div>
                    <h4 class="font-bold text-gray-500 text-sm">Belum Ada Meja Terdaftar</h4>
                    <p class="text-xs text-gray-400 mt-1">Masukkan nama meja di form kanan atas untuk membuat meja dan QR Code secara otomatis.</p>
                </div>
                
                <!-- Tables Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6" v-else>
                    <div v-for="table in tables" :key="table.id" 
                          class="border border-[#D4A373]/20 rounded-3xl p-5 flex flex-col items-center justify-center text-center bg-gradient-to-b from-white to-[#FAEDCD]/10 relative group hover:shadow-lg hover:border-[#D4A373]/60 transition-all duration-300 transform hover:-translate-y-1">
                        
                        <!-- Delete Button (Top-Right) -->
                        <button @click="deleteTable(table.id, table.table_name)" 
                                class="absolute top-3 right-3 text-red-400 hover:text-red-600 transition-colors duration-200 p-1.5 rounded-lg hover:bg-red-50"
                                title="Hapus Meja">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                              <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                        
                        <h4 class="font-extrabold text-[#3B2314] text-md mb-3 flex items-center gap-1.5 justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-[#D4A373]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 8.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                            </svg>
                            {{ table.table_name }}
                        </h4>
                        
                        <!-- QR Code Preview -->
                        <div class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 mb-3 group-hover:scale-105 transition-transform duration-300 relative">
                            <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${appUrl}/meja/${table.secure_token}`" 
                                 alt="QR Code" class="w-28 h-28 object-contain">
                        </div>
                        
                        <p class="text-[9px] font-mono text-gray-400 break-all w-full leading-tight mb-4 select-all">/meja/{{ table.secure_token }}</p>

                        <!-- Actions Buttons -->
                        <div class="flex gap-2 w-full">
                            <button @click="openQrModal(table)" class="flex-1 text-center text-[10px] font-extrabold bg-[#3B2314] hover:bg-[#25150c] text-white py-2 rounded-xl transition">
                                Lihat QR
                            </button>
                            <button @click="downloadQr(table)" class="flex-1 text-center text-[10px] font-extrabold bg-[#D4A373] text-[#3B2314] hover:bg-[#FAEDCD] py-2 rounded-xl transition border border-[#D4A373]/20 flex items-center justify-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Unduh
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </ZunoiAdminLayout>

    <!-- PREMIUM HD QR CODE POPUP MODAL -->
    <div v-if="showQrModal && selectedTableForQr" 
         class="fixed inset-0 bg-[#3B2314]/70 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-all duration-300">
        
        <div class="bg-white rounded-[32px] border border-[#D4A373]/30 shadow-2xl max-w-sm w-full overflow-hidden transform scale-100 transition-all duration-300">
            <!-- Modal Header -->
            <div class="bg-[#3B2314] text-[#FAEDCD] p-6 text-center relative border-b border-[#D4A373]/20">
                <button @click="closeQrModal" class="absolute right-4 top-4 text-gray-300 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
                <div class="mx-auto w-12 h-12 rounded-full bg-[#FAEDCD]/10 border border-[#D4A373]/30 flex items-center justify-center text-white mb-2">
                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 8.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                     </svg>
                 </div>
                <h3 class="font-extrabold text-lg">{{ selectedTableForQr.table_name }}</h3>
                <p class="text-[10px] text-[#D4A373] font-bold tracking-widest uppercase">Zunoi Caffe QR Code</p>
            </div>

            <!-- Modal Body -->
            <div class="p-8 flex flex-col items-center justify-center bg-gradient-to-b from-white to-[#FAEDCD]/10">
                <div class="bg-white p-4 rounded-3xl shadow-md border border-[#D4A373]/25 mb-4">
                    <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${appUrl}/meja/${selectedTableForQr.secure_token}`" 
                          alt="QR Code HD" class="w-48 h-48 object-contain">
                </div>
                
                <p class="text-center text-[10px] text-gray-400 break-all select-all font-mono leading-normal bg-gray-50 border px-3 py-1.5 rounded-full mb-6 w-full">
                    {{ appUrl }}/meja/{{ selectedTableForQr.secure_token }}
                </p>

                <button @click="downloadQr(selectedTableForQr)" 
                        class="w-full bg-[#3B2314] hover:bg-[#25150c] text-white py-3 rounded-2xl font-bold text-xs shadow-md hover:shadow-lg transition transform active:scale-95 flex items-center justify-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Unduh Gambar QR (.png)
                </button>
            </div>
        </div>
    </div>

    <!-- PREMIUM CONFIRMATION MODAL -->
    <div v-if="showConfirmModal" 
         class="fixed inset-0 bg-[#3B2314]/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        
        <div class="bg-white rounded-[32px] border border-[#D4A373]/30 shadow-2xl max-w-sm w-full overflow-hidden transform scale-100 transition-all duration-300">
            <div class="bg-[#3B2314] text-[#FAEDCD] p-5 text-center relative border-b border-[#D4A373]/20">
                <button @click="showConfirmModal = false" class="absolute right-4 top-4 text-gray-300 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
                <h3 class="font-extrabold text-md">{{ confirmTitle }}</h3>
                <p class="text-[9px] text-[#D4A373] font-bold tracking-widest uppercase">Konfirmasi Aksi</p>
            </div>

            <div class="p-6 text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-red-50 border border-red-200 flex items-center justify-center text-red-500 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <p class="text-xs font-bold text-gray-600 leading-relaxed">{{ confirmMessage }}</p>
            </div>

            <div class="p-6 bg-gray-50 border-t flex justify-center gap-3">
                <button @click="showConfirmModal = false" class="px-5 py-2 border rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-100 transition">
                    Batal
                </button>
                <button @click="handleConfirmYes" 
                        class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</template>
