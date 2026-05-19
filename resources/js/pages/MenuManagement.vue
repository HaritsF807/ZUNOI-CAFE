<script setup>
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';

const triggerToast = (message, type = 'success') => {
    window.dispatchEvent(new CustomEvent('zunoi-toast', {
        detail: { message, type }
    }));
};

const props = defineProps({
    categories: Array,
    products: Array
});

// State Data Lokal untuk Kecepatan & Reaktivitas Instan
const localProducts = ref([...props.products]);
const localCategories = ref([...props.categories]);

// Search & Filter State
const searchProductQuery = ref('');
const selectedCategoryFilter = ref('all');

// Modals State
const showProductModal = ref(false);
const showCategoryModal = ref(false);
const isEditingProduct = ref(false);
const isEditingCategory = ref(false);

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

// Image Source Selector & Files
const imageInputType = ref('file'); // 'file' or 'url'
const productImageFile = ref(null);
const productImagePreview = ref(null);

const onProductFileSelected = (event) => {
    const file = event.target.files[0];
    if (file) {
        productImageFile.value = file;
        productImagePreview.value = URL.createObjectURL(file);
    }
};

// Form States
const productForm = ref({
    id: null,
    category_id: '',
    name: '',
    price: '',
    description: '',
    image: '',
    is_available: true
});

const categoryForm = ref({
    id: null,
    name: ''
});

// Sync data lokal jika props diperbarui dari server
const syncData = () => {
    router.reload({
        only: ['products', 'categories'],
        onSuccess: (page) => {
            localProducts.value = [...page.props.products];
            localCategories.value = [...page.props.categories];
        }
    });
};

// --- CRUD KATEGORI ---

const openAddCategory = () => {
    categoryForm.value = { id: null, name: '' };
    isEditingCategory.value = false;
    showCategoryModal.value = true;
};

const openEditCategory = (category) => {
    categoryForm.value = { ...category };
    isEditingCategory.value = true;
    showCategoryModal.value = true;
};

const saveCategory = async () => {
    if (!categoryForm.value.name) return;
    try {
        if (isEditingCategory.value) {
            const response = await axios.put(`/api/categories/${categoryForm.value.id}`, { name: categoryForm.value.name });
            if (response.data.success) {
                triggerToast("Kategori berhasil diperbarui!", "success");
                showCategoryModal.value = false;
                syncData();
            }
        } else {
            const response = await axios.post('/api/categories', { name: categoryForm.value.name });
            if (response.data.success) {
                triggerToast("Kategori berhasil ditambahkan!", "success");
                showCategoryModal.value = false;
                syncData();
            }
        }
    } catch (error) {
        console.error("Gagal menyimpan kategori", error);
        triggerToast(error.response?.data?.message || "Gagal menyimpan kategori.", "error");
    }
};

const deleteCategory = (id, categoryName) => {
    triggerConfirm(
        'Hapus Kategori',
        `Apakah Anda yakin ingin menghapus Kategori "${categoryName}"? Seluruh menu di bawah kategori ini juga akan terhapus!`,
        async () => {
            try {
                const response = await axios.delete(`/api/categories/${id}`);
                if (response.data.success) {
                    triggerToast(response.data.message, "success");
                    syncData();
                }
            } catch (error) {
                console.error("Gagal menghapus kategori", error);
                triggerToast("Gagal menghapus kategori.", "error");
            }
        }
    );
};

// --- CRUD PRODUK ---

const openAddProduct = () => {
    productForm.value = {
        id: null,
        category_id: localCategories.value[0]?.id || '',
        name: '',
        price: '',
        description: '',
        image: '',
        is_available: true
    };
    imageInputType.value = 'file';
    productImageFile.value = null;
    productImagePreview.value = null;
    isEditingProduct.value = false;
    showProductModal.value = true;
};

const openEditProduct = (product) => {
    productForm.value = { ...product };
    if (product.image && (product.image.startsWith('http://') || product.image.startsWith('https://'))) {
        imageInputType.value = 'url';
        productImagePreview.value = null;
    } else {
        imageInputType.value = 'file';
        productImagePreview.value = product.image || null;
    }
    productImageFile.value = null;
    isEditingProduct.value = true;
    showProductModal.value = true;
};

const saveProduct = async () => {
    if (!productForm.value.name || !productForm.value.price || !productForm.value.category_id) {
        triggerToast("Mohon isi field wajib (Nama, Harga, Kategori)!", "error");
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('category_id', productForm.value.category_id);
        formData.append('name', productForm.value.name);
        formData.append('price', productForm.value.price);
        formData.append('description', productForm.value.description || '');
        formData.append('is_available', productForm.value.is_available ? '1' : '0');
        
        if (imageInputType.value === 'file') {
            if (productImageFile.value) {
                formData.append('image_file', productImageFile.value);
            } else if (isEditingProduct.value && productForm.value.image) {
                formData.append('image', productForm.value.image);
            }
        } else {
            formData.append('image', productForm.value.image || '');
        }

        if (isEditingProduct.value) {
            formData.append('_method', 'PUT');
            const response = await axios.post(`/api/products/${productForm.value.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            if (response.data.success) {
                triggerToast("Produk berhasil diperbarui!", "success");
                showProductModal.value = false;
                syncData();
            }
        } else {
            const response = await axios.post('/api/products', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            if (response.data.success) {
                triggerToast("Produk berhasil ditambahkan!", "success");
                showProductModal.value = false;
                syncData();
            }
        }
    } catch (error) {
        console.error("Gagal menyimpan produk", error);
        triggerToast("Gagal menyimpan produk. Periksa kembali inputan Anda.", "error");
    }
};

const deleteProduct = (id, productName) => {
    triggerConfirm(
        'Hapus Menu',
        `Apakah Anda yakin ingin menghapus menu "${productName}"?`,
        async () => {
            try {
                const response = await axios.delete(`/api/products/${id}`);
                if (response.data.success) {
                    triggerToast("Produk berhasil dihapus!", "success");
                    syncData();
                }
            } catch (error) {
                console.error("Gagal menghapus produk", error);
                triggerToast("Gagal menghapus produk.", "error");
            }
        }
    );
};

// Toggle Availability Instan (Tanpa modal, reaktif!)
const toggleAvailability = async (product) => {
    // Optimistic Update
    product.is_available = !product.is_available;
    try {
        await axios.patch(`/api/products/${product.id}/toggle-availability`);
    } catch (error) {
        // Rollback if failed
        product.is_available = !product.is_available;
        console.error("Gagal memperbarui status ketersediaan", error);
        triggerToast("Koneksi gagal, status tidak dapat diperbarui.", "error");
    }
};

// --- REACTIVE FILTERING ---

const filteredProducts = computed(() => {
    let result = localProducts.value;

    // Filter Kategori
    if (selectedCategoryFilter.value !== 'all') {
        result = result.filter(p => p.category_id === parseInt(selectedCategoryFilter.value));
    }

    // Filter Search Query
    if (searchProductQuery.value.trim() !== '') {
        const query = searchProductQuery.value.toLowerCase();
        result = result.filter(p => 
            p.name.toLowerCase().includes(query) || 
            (p.description && p.description.toLowerCase().includes(query))
        );
    }

    return result;
});
</script>

<template>
    <Head title="Kelola Menu Kafe" />

    <ZunoiAdminLayout>
        <div class="max-w-7xl mx-auto space-y-8 text-gray-800">
            
            <!-- HEADER SECTION -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-[#3B2314] text-[#FAEDCD] p-6 rounded-3xl shadow-xl border border-[#D4A373]/30 relative overflow-hidden">
                <div class="absolute -right-16 -top-16 w-48 h-48 bg-[#D4A373]/10 rounded-full blur-2xl"></div>
                <div class="z-10">
                    <h2 class="font-extrabold text-2xl tracking-wide flex items-center gap-2">
                        Kelola Menu & Kategori
                        <span class="text-[10px] bg-[#D4A373] text-[#3B2314] font-black uppercase px-2 py-0.5 rounded-full tracking-widest align-middle">Menu Admin</span>
                    </h2>
                    <p class="text-xs text-gray-300 mt-1">Perbarui daftar makanan, kopi, atur stok habis, serta tambahkan kategori menu baru.</p>
                </div>
                
                <div class="mt-4 md:mt-0 flex gap-2 z-10">
                    <button @click="openAddCategory" class="bg-[#FAEDCD]/10 hover:bg-[#FAEDCD]/20 text-[#FAEDCD] border border-[#D4A373]/30 px-4 py-2.5 rounded-xl text-xs font-bold shadow-md transition-all duration-300 transform active:scale-95 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-[#D4A373]">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Tambah Kategori
                    </button>
                    <button @click="openAddProduct" class="bg-[#D4A373] hover:bg-[#FAEDCD] text-[#3B2314] px-5 py-2.5 rounded-xl text-xs font-bold shadow-md transition-all duration-300 hover:shadow-lg transform active:scale-95 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-[#3B2314]">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Tambah Menu Baru
                    </button>
                </div>
            </div>

            <!-- LAYOUT SPLIT: KATEGORI (Kiri) & PRODUK (Kanan) -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">
                
                <!-- KOLOM KATEGORI (KIRI) -->
                <div class="bg-white rounded-3xl border border-[#D4A373]/20 shadow-sm p-5 space-y-4">
                    <div class="border-b pb-3 flex justify-between items-center">
                        <h3 class="font-black text-sm text-[#3B2314] uppercase tracking-wider">Kategori Menu</h3>
                        <span class="text-[10px] bg-[#FAEDCD] text-[#3B2314] font-bold px-2 py-0.5 rounded-full border border-[#D4A373]/30">
                            {{ localCategories.length }} Kategori
                        </span>
                    </div>

                    <!-- List Filter Kategori -->
                    <div class="space-y-2">
                        <!-- Pilihan 'Semua Kategori' -->
                        <button @click="selectedCategoryFilter = 'all'" 
                                class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold transition flex justify-between items-center"
                                :class="selectedCategoryFilter === 'all' ? 'bg-[#3B2314] text-white shadow-sm' : 'hover:bg-gray-50 text-gray-600'">
                            <span class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.008 1.24l.885 1.77a2.25 2.25 0 0 0 2.007 1.24h1.98a2.25 2.25 0 0 0 2.007-1.24l.885-1.77a2.25 2.25 0 0 1 2.007-1.24h3.86m-18 0h18a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v4.5A2.25 2.25 0 0 0 2.25 13.5Z" />
                                </svg>
                                Semua Menu
                            </span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full" 
                                  :class="selectedCategoryFilter === 'all' ? 'bg-white/10 text-white' : 'bg-gray-100 text-gray-500'">
                                {{ localProducts.length }}
                            </span>
                        </button>
 
                        <!-- Per Kategori -->
                        <div v-for="category in localCategories" :key="category.id" 
                             class="flex items-center group relative rounded-xl hover:bg-gray-50 transition">
                            
                            <button @click="selectedCategoryFilter = category.id" 
                                    class="flex-1 text-left px-4 py-2.5 text-xs font-bold transition flex justify-between items-center"
                                    :class="selectedCategoryFilter === category.id ? 'bg-[#D4A373] text-[#3B2314] rounded-xl shadow-sm' : 'text-gray-600'">
                                <span class="truncate pr-4 flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                    {{ category.name }}
                                </span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full shrink-0" 
                                      :class="selectedCategoryFilter === category.id ? 'bg-[#3B2314]/10 text-[#3B2314]' : 'bg-gray-100 text-gray-500'">
                                    {{ localProducts.filter(p => p.category_id === category.id).length }}
                                </span>
                            </button>

                            <!-- Hover Actions untuk Kategori (Edit/Hapus) -->
                            <div class="absolute right-2 top-1.5 hidden group-hover:flex items-center gap-1 bg-white p-0.5 rounded-lg shadow-sm border border-gray-100 z-10"
                                 v-if="selectedCategoryFilter !== category.id">
                                <button @click.stop="openEditCategory(category)" class="p-1 text-blue-500 hover:bg-gray-50 rounded" title="Edit Kategori">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.83 20.82a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </button>
                                <button @click.stop="deleteCategory(category.id, category.name)" class="p-1 text-red-500 hover:bg-gray-50 rounded" title="Hapus Kategori">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KOLOM PRODUK (KANAN) -->
                <div class="bg-white rounded-3xl border border-[#D4A373]/20 shadow-sm p-6 lg:col-span-3 space-y-6">
                    <!-- Pencarian & Kontrol -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-between items-center">
                        <div class="relative w-full sm:max-w-xs">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.602 10.602Z" />
                                </svg>
                            </span>
                            <input v-model="searchProductQuery" type="text" placeholder="Cari nama menu..." 
                                   class="w-full text-xs rounded-xl border-gray-200 pl-9 pr-4 py-2.5 focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]">
                        </div>
                        
                        <div class="text-xs font-bold text-gray-400 flex items-center gap-2">
                            <span>Menampilkan</span>
                            <span class="text-[#3B2314] font-black px-2 py-0.5 bg-gray-100 rounded border border-[#D4A373]/20">
                                {{ filteredProducts.length }} dari {{ localProducts.length }}
                            </span>
                            <span>Menu Kopi & Kuliner</span>
                        </div>
                    </div>

                    <!-- Grid List Produk -->
                    <div v-if="filteredProducts.length === 0" class="text-center py-16 text-gray-400">
                        <div class="w-12 h-12 bg-gray-50 rounded-full border flex items-center justify-center mx-auto mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-gray-400">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                        <h4 class="font-bold text-gray-500 text-sm">Tidak Ada Menu</h4>
                        <p class="text-xs text-gray-400 mt-1">Menu yang Anda cari tidak ditemukan. Coba ganti kata kunci atau tambahkan baru.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" v-else>
                        <div v-for="product in filteredProducts" :key="product.id" 
                             class="flex gap-4 border border-[#D4A373]/20 p-4 rounded-2xl bg-gradient-to-br from-white to-[#FAEDCD]/5 hover:shadow-md hover:border-[#D4A373]/60 transition-all duration-300 relative"
                             :class="{'opacity-60 bg-gray-50 border-gray-200': !product.is_available}">
                            
                            <!-- Foto Produk -->
                            <div class="w-20 h-20 rounded-xl overflow-hidden border border-gray-100 bg-gray-50 shrink-0">
                                <img :src="product.image || 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=300&auto=format&fit=crop'" 
                                     alt="Menu" class="w-full h-full object-cover">
                            </div>

                            <!-- Detail Produk -->
                            <div class="flex-1 min-w-0 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center gap-1.5 flex-wrap mb-1">
                                        <span class="text-[9px] font-black bg-[#FAEDCD] text-[#3B2314] border border-[#D4A373]/30 px-1.5 py-0.5 rounded-full uppercase tracking-wider">
                                            {{ product.category?.name || 'Kopi' }}
                                        </span>
                                        
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full uppercase tracking-wider"
                                              :class="product.is_available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'">
                                            {{ product.is_available ? 'Tersedia' : 'Habis' }}
                                        </span>
                                    </div>
                                    <h4 class="font-bold text-[#3B2314] text-sm truncate" :title="product.name">{{ product.name }}</h4>
                                    <p class="text-[11px] text-gray-400 line-clamp-1 mt-0.5">{{ product.description || 'Seduhan kopi nikmat khas Zunoi.' }}</p>
                                </div>
                                <div class="flex justify-between items-center mt-2 border-t pt-2 border-[#D4A373]/10">
                                    <span class="font-black text-xs text-[#3B2314]">Rp {{ parseInt(product.price).toLocaleString('id-ID') }}</span>
                                    
                                    <div class="flex items-center gap-2">
                                        <!-- Toggle Ketersediaan Instan -->
                                        <button @click="toggleAvailability(product)" 
                                                class="px-2 py-1 rounded-lg border text-[10px] font-bold transition transform active:scale-95"
                                                :class="product.is_available ? 'bg-red-50 text-red-500 border-red-200 hover:bg-red-100' : 'bg-green-50 text-green-600 border-green-200 hover:bg-green-100'">
                                            {{ product.is_available ? 'Set Habis' : 'Set Ada' }}
                                        </button>
                                        
                                        <!-- Edit -->
                                        <button @click="openEditProduct(product)" class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg border border-transparent hover:border-blue-100" title="Edit Menu">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.83 20.82a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                        </button>

                                        <!-- Hapus -->
                                        <button @click="deleteProduct(product.id, product.name)" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-100" title="Hapus Menu">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </ZunoiAdminLayout>

    <!-- MODAL PRODUCT (ADD / EDIT) -->
    <div v-if="showProductModal" 
         class="fixed inset-0 bg-[#3B2314]/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        
        <div class="bg-white rounded-[32px] border border-[#D4A373]/30 shadow-2xl max-w-md w-full overflow-hidden transform scale-100 transition-all duration-300">
            <div class="bg-[#3B2314] text-[#FAEDCD] p-5 text-center relative border-b border-[#D4A373]/20">
                <button @click="showProductModal = false" class="absolute right-4 top-4 text-gray-300 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
                <h3 class="font-extrabold text-md">{{ isEditingProduct ? 'Edit Menu Kopi/Makanan' : 'Tambah Menu Baru' }}</h3>
                <p class="text-[9px] text-[#D4A373] font-bold tracking-widest uppercase">Zunoi Caffe Menu Setup</p>
            </div>

            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <!-- Name -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Nama Menu *</label>
                    <input v-model="productForm.name" type="text" placeholder="Contoh: Es Latte Gula Aren"
                           class="w-full text-xs rounded-xl border-gray-200 px-3 py-2.5 focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]">
                </div>

                <!-- Price -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Harga Jual (Rupiah) *</label>
                    <input v-model="productForm.price" type="number" placeholder="Contoh: 22000"
                           class="w-full text-xs rounded-xl border-gray-200 px-3 py-2.5 focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]">
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Kategori Menu *</label>
                    <select v-model="productForm.category_id"
                            class="w-full text-xs rounded-xl border-gray-200 px-3 py-2.5 focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373] bg-white">
                        <option v-for="category in localCategories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <!-- Image Source Selection -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-2">Gambar Menu *</label>
                    
                    <!-- Tab Selector -->
                    <div class="flex bg-gray-50 border rounded-xl p-1 mb-3 text-xs font-bold w-full">
                        <button type="button" @click="imageInputType = 'file'" 
                                class="flex-1 py-1.5 rounded-lg text-center transition"
                                :class="imageInputType === 'file' ? 'bg-[#3B2314] text-white shadow-sm' : 'text-gray-500 hover:text-[#3B2314]'">
                            Upload Berkas
                        </button>
                        <button type="button" @click="imageInputType = 'url'" 
                                class="flex-1 py-1.5 rounded-lg text-center transition"
                                :class="imageInputType === 'url' ? 'bg-[#3B2314] text-white shadow-sm' : 'text-gray-500 hover:text-[#3B2314]'">
                            Alamat Link URL
                        </button>
                    </div>

                    <!-- File Upload Input -->
                    <div v-if="imageInputType === 'file'" class="space-y-3">
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-2xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors duration-200">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-gray-400 mb-2">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <p class="text-xs text-gray-500 font-bold">Klik untuk unggah foto menu</p>
                                    <p class="text-[10px] text-gray-400 mt-1">PNG, JPG, JPEG, WEBP (Maks. 2MB)</p>
                                </div>
                                <input type="file" @change="onProductFileSelected" accept="image/*" class="hidden">
                            </label>
                        </div>
                        
                        <!-- File Preview -->
                        <div v-if="productImagePreview" class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
                            <img :src="productImagePreview" class="w-12 h-12 object-cover rounded-lg border">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-gray-700 truncate">{{ productImageFile ? productImageFile.name : 'Gambar saat ini' }}</p>
                                <p class="text-[10px] text-gray-400">{{ productImageFile ? (productImageFile.size / 1024).toFixed(1) + ' KB' : 'Disimpan di server' }}</p>
                            </div>
                            <button type="button" @click="productImageFile = null; productImagePreview = null; productForm.image = ''" 
                                    class="text-red-500 hover:text-red-700 p-1.5 rounded-lg hover:bg-red-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- URL Link Input -->
                    <div v-else>
                        <input v-model="productForm.image" type="text" placeholder="Contoh: https://images.unsplash.com/..."
                               class="w-full text-xs rounded-xl border-gray-200 px-3 py-2.5 focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]">
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Deskripsi Singkat</label>
                    <textarea v-model="productForm.description" rows="3" placeholder="Contoh: Perpaduan espresso murni, susu segar, dan sirup kelapa pilihan."
                              class="w-full text-xs rounded-xl border-gray-200 px-3 py-2.5 focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"></textarea>
                </div>

                <!-- Availability -->
                <div class="flex items-center gap-2 pt-2">
                    <input v-model="productForm.is_available" type="checkbox" id="is_available"
                           class="rounded text-[#3B2314] focus:ring-[#D4A373] border-gray-300">
                    <label for="is_available" class="text-xs font-bold text-gray-600 cursor-pointer">Menu langsung siap dipesan oleh pelanggan</label>
                </div>
            </div>

            <div class="p-6 bg-gray-50 border-t flex justify-end gap-3">
                <button @click="showProductModal = false" class="px-4 py-2 border rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-100 transition">
                    Batal
                </button>
                <button @click="saveProduct" 
                        class="bg-[#3B2314] hover:bg-[#25150c] text-white px-5 py-2 rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition">
                    Simpan Menu
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL CATEGORY (ADD / EDIT) -->
    <div v-if="showCategoryModal" 
         class="fixed inset-0 bg-[#3B2314]/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        
        <div class="bg-white rounded-[32px] border border-[#D4A373]/30 shadow-2xl max-w-sm w-full overflow-hidden transform scale-100 transition-all duration-300">
            <div class="bg-[#3B2314] text-[#FAEDCD] p-5 text-center relative border-b border-[#D4A373]/20">
                <button @click="showCategoryModal = false" class="absolute right-4 top-4 text-gray-300 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
                <h3 class="font-extrabold text-md">{{ isEditingCategory ? 'Edit Kategori Menu' : 'Tambah Kategori' }}</h3>
                <p class="text-[9px] text-[#D4A373] font-bold tracking-widest uppercase">Zunoi Caffe Category Setup</p>
            </div>

            <div class="p-6 space-y-4">
                <!-- Name -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Nama Kategori *</label>
                    <input v-model="categoryForm.name" @keyup.enter="saveCategory" type="text" placeholder="Contoh: Coffee, Non-Coffee, Pastry, Snack"
                           class="w-full text-xs rounded-xl border-gray-200 px-3 py-2.5 focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]">
                </div>
            </div>

            <div class="p-6 bg-gray-50 border-t flex justify-end gap-3">
                <button @click="showCategoryModal = false" class="px-4 py-2 border rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-100 transition">
                    Batal
                </button>
                <button @click="saveCategory" 
                        class="bg-[#3B2314] hover:bg-[#25150c] text-white px-5 py-2 rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition">
                    Simpan Kategori
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
