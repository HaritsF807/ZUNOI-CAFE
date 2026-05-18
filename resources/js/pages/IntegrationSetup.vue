<script setup>
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const user = usePage().props.auth.user;

// Form Integrasi
const integrationForm = ref({
    fonnte_token: 'TokenFonnteAnda123',
    tokopay_merchant_id: 'M-123456',
    tokopay_secret: 'SecretKey...'
});

const saveIntegration = () => {
    alert("Pengaturan Integrasi berhasil disimpan! (Mockup)");
};
</script>

<template>
    <Head title="Pengaturan Integrasi API" />

    <ZunoiAdminLayout>
        <div class="max-w-7xl mx-auto space-y-8 text-gray-800">
            
            <!-- HEADER SECTION -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-[#3B2314] text-[#FAEDCD] p-6 rounded-3xl shadow-xl border border-[#D4A373]/30 relative overflow-hidden">
                <div class="absolute -right-16 -top-16 w-48 h-48 bg-[#D4A373]/10 rounded-full blur-2xl"></div>
                <div class="z-10">
                    <h2 class="font-extrabold text-2xl tracking-wide flex items-center gap-2">
                        Pengaturan Integrasi API
                        <span class="text-[10px] bg-[#D4A373] text-[#3B2314] font-black uppercase px-2 py-0.5 rounded-full tracking-widest align-middle">Owner Gateway</span>
                    </h2>
                    <p class="text-xs text-gray-300 mt-1">Konfigurasikan notifikasi WhatsApp Fonnte dan Payment Gateway Tokopay untuk pembayaran QRIS otomatis.</p>
                </div>
            </div>

            <!-- GATEWAY INTEGRATIONS BLOCK (Owner Only) -->
            <div class="bg-white rounded-3xl border border-[#D4A373]/20 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-[#3B2314] flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-[#D4A373]">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        API Credentials Setup
                    </h3>
                    <p class="text-xs text-gray-400 mt-1">Hanya pengguna dengan peran <b>Owner</b> yang memiliki otorisasi penuh untuk mengubah kredensial ini.</p>
                </div>
                
                <div class="p-6">
                    <form @submit.prevent="saveIntegration" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Fonnte Setup -->
                        <div class="bg-gray-50 p-5 rounded-2xl border flex flex-col justify-between">
                            <div>
                                <h4 class="font-extrabold text-gray-700 text-sm mb-3 flex items-center gap-1.5">
                                    <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-[10px] font-black uppercase">WA Gateway</span> 
                                    Fonnte API Setup
                                </h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">API Token</label>
                                        <input v-model="integrationForm.fonnte_token" type="password" 
                                               class="w-full text-sm rounded-xl border-gray-200 shadow-sm focus:border-green-500 focus:ring-1 focus:ring-green-500 px-3 py-2">
                                    </div>
                                    <p class="text-[11px] text-gray-400">Digunakan untuk mengirim pemberitahuan otomatis secara instan kepada pelanggan saat pesanan masuk/selesai.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tokopay Setup -->
                        <div class="bg-gray-50 p-5 rounded-2xl border flex flex-col justify-between">
                            <div>
                                <h4 class="font-extrabold text-gray-700 text-sm mb-3 flex items-center gap-1.5">
                                    <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] font-black uppercase">Auto QRIS</span> 
                                    Tokopay API Setup
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Merchant ID</label>
                                        <input v-model="integrationForm.tokopay_merchant_id" type="text" 
                                               class="w-full text-sm rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 px-3 py-2">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Secret Key</label>
                                        <input v-model="integrationForm.tokopay_secret" type="password" 
                                               class="w-full text-sm rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 px-3 py-2">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- QRIS Statis Manual -->
                        <div class="bg-gray-50 p-5 rounded-2xl border md:col-span-2">
                            <h4 class="font-extrabold text-gray-700 text-sm mb-3 flex items-center gap-1.5">
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-[10px] font-black uppercase">Manual QRIS</span> 
                                QRIS Statis Toko
                            </h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">URL Gambar QRIS (Upload ke Hosting/Imgur)</label>
                                    <input type="text" value="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" 
                                           class="w-full text-sm rounded-xl border-gray-200 shadow-sm focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500 px-3 py-2">
                                </div>
                                <p class="text-[11px] text-gray-400">Gambar QRIS statis ini akan ditampilkan langsung di HP pelanggan jika mereka memilih pembayaran manual 'QRIS Toko'. Pelanggan wajib menunjukkan bukti transfer fisik ke Barista.</p>
                            </div>
                        </div>

                        <div class="md:col-span-2 flex justify-end">
                            <button type="submit" 
                                    class="bg-[#3B2314] hover:bg-[#25150c] text-white px-6 py-2.5 rounded-xl font-bold text-xs shadow-md hover:shadow-lg transition transform active:scale-95 flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                </svg>
                                Simpan Kredensial Integrasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </ZunoiAdminLayout>
</template>
