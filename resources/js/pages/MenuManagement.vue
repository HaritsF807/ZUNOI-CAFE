<script setup>
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, computed, watch } from 'vue';
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';

const triggerToast = (message, type = 'success') => {
    window.dispatchEvent(
        new CustomEvent('zunoi-toast', {
            detail: { message, type },
        }),
    );
};

const props = defineProps({
    categories: Array,
    products: Array,
    globalAddons: Array,
});

// State Data Lokal untuk Kecepatan & Reaktivitas Instan
const localProducts = ref([...props.products]);
const localCategories = ref([...props.categories]);
const localGlobalAddons = ref([...(props.globalAddons || [])]);

// Mode Active Tab
const activeTab = ref('beverages');

// Search & Filter State
const searchProductQuery = ref('');
const selectedCategoryFilter = ref('all');

// Modals State
const showProductModal = ref(false);
const showCategoryModal = ref(false);
const showEditAddonModal = ref(false);
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
    is_available: true,
});

const categoryForm = ref({
    id: null,
    name: '',
});

// Assigned Addons State
const assignedAddonIds = ref([]);
const isSyncingAddons = ref(false);

// Pagination for Assign Addons Modal
const addonAssignmentPage = ref(1);
const addonsPerAssignmentPage = 5;

const totalAddonAssignmentPages = computed(() => {
    return Math.ceil(localGlobalAddons.value.length / addonsPerAssignmentPage);
});

const paginatedAssignmentAddons = computed(() => {
    const start = (addonAssignmentPage.value - 1) * addonsPerAssignmentPage;
    const end = start + addonsPerAssignmentPage;

    return localGlobalAddons.value.slice(start, end);
});

watch(showProductModal, (newVal) => {
    if (newVal) {
        addonAssignmentPage.value = 1;
    }
});

// Sync data lokal jika props diperbarui dari server
const syncData = () => {
    router.reload({
        only: ['products', 'categories', 'globalAddons'],
        onSuccess: (page) => {
            localProducts.value = [...page.props.products];
            localCategories.value = [...page.props.categories];
            localGlobalAddons.value = [...(page.props.globalAddons || [])];
        },
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
    if (!categoryForm.value.name) {
        return;
    }

    try {
        if (isEditingCategory.value) {
            const response = await axios.put(
                `/api/categories/${categoryForm.value.id}`,
                { name: categoryForm.value.name },
            );

            if (response.data.success) {
                triggerToast('Kategori berhasil diperbarui!', 'success');
                showCategoryModal.value = false;
                syncData();
            }
        } else {
            const response = await axios.post('/api/categories', {
                name: categoryForm.value.name,
            });

            if (response.data.success) {
                triggerToast('Kategori berhasil ditambahkan!', 'success');
                showCategoryModal.value = false;
                syncData();
            }
        }
    } catch (error) {
        console.error('Gagal menyimpan kategori', error);
        triggerToast(
            error.response?.data?.message || 'Gagal menyimpan kategori.',
            'error',
        );
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
                    triggerToast(response.data.message, 'success');
                    syncData();
                }
            } catch (error) {
                console.error('Gagal menghapus kategori', error);
                triggerToast('Gagal menghapus kategori.', 'error');
            }
        },
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
        is_available: true,
    };
    imageInputType.value = 'file';
    productImageFile.value = null;
    productImagePreview.value = null;
    isEditingProduct.value = false;
    assignedAddonIds.value = [];
    showProductModal.value = true;
};

const openEditProduct = (product) => {
    productForm.value = { ...product };

    if (
        product.image &&
        (product.image.startsWith('http://') ||
            product.image.startsWith('https://'))
    ) {
        imageInputType.value = 'url';
        productImagePreview.value = null;
    } else {
        imageInputType.value = 'file';
        productImagePreview.value = product.image || null;
    }

    productImageFile.value = null;
    isEditingProduct.value = true;

    // Populate assigned addons
    assignedAddonIds.value = product.assigned_addons
        ? product.assigned_addons.map((a) => a.id)
        : [];

    showProductModal.value = true;
};

const saveProduct = async () => {
    if (
        !productForm.value.name ||
        !productForm.value.price ||
        !productForm.value.category_id
    ) {
        triggerToast('Mohon isi field wajib (Nama, Harga, Kategori)!', 'error');

        return;
    }

    try {
        const formData = new FormData();
        formData.append('category_id', productForm.value.category_id);
        formData.append('name', productForm.value.name);
        formData.append('price', productForm.value.price);
        formData.append('description', productForm.value.description || '');
        formData.append(
            'is_available',
            productForm.value.is_available ? '1' : '0',
        );

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
            const response = await axios.post(
                `/api/products/${productForm.value.id}`,
                formData,
                {
                    headers: { 'Content-Type': 'multipart/form-data' },
                },
            );

            if (response.data.success) {
                // Sync addons as well
                await syncProductAddons();

                triggerToast('Produk berhasil diperbarui!', 'success');
                showProductModal.value = false;
                syncData();
            }
        } else {
            const response = await axios.post('/api/products', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (response.data.success) {
                triggerToast('Produk berhasil ditambahkan!', 'success');
                showProductModal.value = false;
                syncData();
            }
        }
    } catch (error) {
        console.error('Gagal menyimpan produk', error);
        triggerToast(
            'Gagal menyimpan produk. Periksa kembali inputan Anda.',
            'error',
        );
    }
};

const syncProductAddons = async () => {
    if (!productForm.value.id) {
return;
}

    isSyncingAddons.value = true;

    try {
        await axios.post(`/api/products/${productForm.value.id}/sync-addons`, {
            addon_ids: assignedAddonIds.value,
        });
    } catch (error) {
        console.error('Gagal sinkronisasi add-ons', error);
    } finally {
        isSyncingAddons.value = false;
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
                    triggerToast('Produk berhasil dihapus!', 'success');
                    syncData();
                }
            } catch (error) {
                console.error('Gagal menghapus produk', error);
                triggerToast('Gagal menghapus produk.', 'error');
            }
        },
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
        console.error('Gagal memperbarui status ketersediaan', error);
        triggerToast('Koneksi gagal, status tidak dapat diperbarui.', 'error');
    }
};

// --- MANAJEMEN ADDON GLOBAL ---
const addonForm = ref({
    id: null,
    addon_name: '',
    extra_price: '',
    category: 'topping',
});
const isEditingAddon = ref(false);

const resetAddonForm = () => {
    addonForm.value = {
        id: null,
        addon_name: '',
        extra_price: '',
        category: 'topping',
    };
    isEditingAddon.value = false;
};

const handleEditAddon = (addon) => {
    addonForm.value = {
        id: addon.id,
        addon_name: addon.name,
        extra_price: addon.price,
        category: addon.category || 'topping',
    };
    isEditingAddon.value = true;
    showEditAddonModal.value = true;
};

const saveAddon = async () => {
    if (!addonForm.value.addon_name || addonForm.value.extra_price === '') {
        triggerToast('Mohon isi Nama Add-on dan Harga!', 'error');

        return;
    }

    try {
        if (isEditingAddon.value) {
            const response = await axios.put(
                `/api/addons/${addonForm.value.id}`,
                {
                    addon_name: addonForm.value.addon_name,
                    extra_price: addonForm.value.extra_price,
                    category: addonForm.value.category,
                },
            );

            if (response.data.success) {
                triggerToast('Add-on berhasil diperbarui!', 'success');
                showEditAddonModal.value = false;
                resetAddonForm();
                syncData();
            }
        } else {
            const response = await axios.post(`/api/addons`, {
                addon_name: addonForm.value.addon_name,
                extra_price: addonForm.value.extra_price,
                category: addonForm.value.category,
            });

            if (response.data.success) {
                triggerToast('Add-on berhasil ditambahkan!', 'success');
                resetAddonForm();
                syncData();
            }
        }
    } catch (error) {
        console.error('Gagal menyimpan addon', error);
        triggerToast('Gagal menyimpan add-on.', 'error');
    }
};

const deleteAddon = async (addonId) => {
    triggerConfirm(
        'Hapus Add-on',
        'Apakah Anda yakin ingin menghapus add-on ini secara permanen?',
        async () => {
            try {
                const response = await axios.delete(`/api/addons/${addonId}`);

                if (response.data.success) {
                    triggerToast('Add-on berhasil dihapus!', 'success');
                    syncData();
                }
            } catch (error) {
                console.error('Gagal menghapus addon', error);
                triggerToast('Gagal menghapus add-on.', 'error');
            }
        },
    );
};

// --- REACTIVE FILTERING ---

const filteredProducts = computed(() => {
    let result = localProducts.value;

    // Filter Kategori
    if (selectedCategoryFilter.value !== 'all') {
        result = result.filter(
            (p) => p.category_id === parseInt(selectedCategoryFilter.value),
        );
    }

    // Filter Search Query
    if (searchProductQuery.value.trim() !== '') {
        const query = searchProductQuery.value.toLowerCase();
        result = result.filter(
            (p) =>
                p.name.toLowerCase().includes(query) ||
                (p.description && p.description.toLowerCase().includes(query)),
        );
    }

    return result;
});

// --- PAGINATION FOR PRODUCTS ---
const currentPage = ref(1);
const itemsPerPage = 4;

watch([selectedCategoryFilter, searchProductQuery], () => {
    currentPage.value = 1;
});

const totalPages = computed(() => {
    return Math.ceil(filteredProducts.value.length / itemsPerPage);
});

const paginatedProducts = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;

    return filteredProducts.value.slice(start, end);
});
</script>

<template>
    <Head title="Kelola Menu Kafe" />

    <ZunoiAdminLayout>
        <div class="mx-auto max-w-7xl space-y-8 text-gray-800">
            <!-- HEADER SECTION -->
            <div
                class="relative flex flex-col items-start justify-between overflow-hidden rounded-3xl border border-[#D4A373]/30 bg-[#3B2314] p-6 text-[#FAEDCD] shadow-xl md:flex-row md:items-center"
            >
                <div
                    class="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-[#D4A373]/10 blur-2xl"
                ></div>
                <div class="z-10">
                    <h2
                        class="flex items-center gap-2 text-2xl font-extrabold tracking-wide"
                    >
                        Kelola Menu & Kategori
                    </h2>
                    <p class="mt-1 text-xs text-gray-300">
                        Perbarui daftar makanan, kopi, atur stok habis, serta
                        tambahkan kategori menu baru.
                    </p>
                </div>

                <div class="z-10 mt-4 flex gap-2 md:mt-0">
                    <button
                        @click="openAddCategory"
                        class="flex transform items-center gap-1.5 rounded-xl border border-[#D4A373]/30 bg-[#FAEDCD]/10 px-4 py-2.5 text-xs font-bold text-[#FAEDCD] shadow-md transition-all duration-300 hover:bg-[#FAEDCD]/20 active:scale-95"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                            class="h-4 w-4 text-[#D4A373]"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 4.5v15m7.5-7.5h-15"
                            />
                        </svg>
                        Tambah Kategori
                    </button>
                    <button
                        @click="openAddProduct"
                        class="flex transform items-center gap-1.5 rounded-xl bg-[#D4A373] px-5 py-2.5 text-xs font-bold text-[#3B2314] shadow-md transition-all duration-300 hover:bg-[#FAEDCD] hover:shadow-lg active:scale-95"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                            class="h-4 w-4 text-[#3B2314]"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 4.5v15m7.5-7.5h-15"
                            />
                        </svg>
                        Tambah Menu Baru
                    </button>
                </div>
            </div>

            <!-- TAB SELECTOR -->
            <div class="mb-6 flex items-center justify-center">
                <div
                    class="flex rounded-xl border border-[#D4A373]/20 bg-white p-1 shadow-sm"
                >
                    <button
                        @click="activeTab = 'beverages'"
                        :class="
                            activeTab === 'beverages'
                                ? 'bg-[#3B2314] text-white shadow-md'
                                : 'text-gray-500 hover:text-[#3B2314]'
                        "
                        class="btn-bouncy rounded-lg px-6 py-2.5 text-xs font-bold transition"
                    >
                        Beverages
                    </button>
                    <button
                        @click="activeTab = 'addons'"
                        :class="
                            activeTab === 'addons'
                                ? 'bg-[#3B2314] text-white shadow-md'
                                : 'text-gray-500 hover:text-[#3B2314]'
                        "
                        class="btn-bouncy rounded-lg px-6 py-2.5 text-xs font-bold transition"
                    >
                        Add-ons
                    </button>
                </div>
            </div>

            <!-- LAYOUT SPLIT DENGAN TRANSITION -->
            <div class="relative min-h-[500px] overflow-hidden">
                <Transition name="slide-fade" mode="out-in">
                    <!-- BEVERAGES VIEW -->
                    <div
                        v-if="activeTab === 'beverages'"
                        key="beverages"
                        class="grid w-full grid-cols-1 items-start gap-8 lg:grid-cols-4"
                    >
                        <!-- KOLOM KATEGORI (KIRI) -->
                        <div
                            class="card-abstract-border h-full space-y-4 rounded-3xl bg-white p-5 shadow-sm"
                        >
                            <div
                                class="flex items-center justify-between gap-2 border-b pb-3"
                            >
                                <h3
                                    class="text-xs font-black tracking-wider whitespace-nowrap text-[#3B2314] uppercase"
                                >
                                    Kategori Menu
                                </h3>
                                <span
                                    class="shrink-0 rounded-full border border-[#D4A373]/30 bg-[#FAEDCD] px-2 py-0.5 text-[9px] font-bold whitespace-nowrap text-[#3B2314]"
                                >
                                    {{ localCategories.length }} Kategori
                                </span>
                            </div>

                            <!-- List Filter Kategori -->
                            <div class="space-y-2">
                                <!-- Pilihan 'Semua Kategori' -->
                                <button
                                    @click="selectedCategoryFilter = 'all'"
                                    class="flex w-full items-center justify-between rounded-xl px-4 py-2.5 text-left text-xs font-bold transition"
                                    :class="
                                        selectedCategoryFilter === 'all'
                                            ? 'bg-[#3B2314] text-white shadow-sm'
                                            : 'text-gray-600 hover:bg-gray-50'
                                    "
                                >
                                    <span class="flex items-center gap-1.5">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="2"
                                            stroke="currentColor"
                                            class="h-4 w-4"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.008 1.24l.885 1.77a2.25 2.25 0 0 0 2.007 1.24h1.98a2.25 2.25 0 0 0 2.007-1.24l.885-1.77a2.25 2.25 0 0 1 2.007-1.24h3.86m-18 0h18a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v4.5A2.25 2.25 0 0 0 2.25 13.5Z"
                                            />
                                        </svg>
                                        Semua Menu
                                    </span>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px]"
                                        :class="
                                            selectedCategoryFilter === 'all'
                                                ? 'bg-white/10 text-white'
                                                : 'bg-gray-100 text-gray-500'
                                        "
                                    >
                                        {{ localProducts.length }}
                                    </span>
                                </button>

                                <!-- Per Kategori -->
                                <div
                                    v-for="category in localCategories"
                                    :key="category.id"
                                    class="group relative flex items-center rounded-xl transition hover:bg-gray-50"
                                >
                                    <button
                                        @click="
                                            selectedCategoryFilter = category.id
                                        "
                                        class="flex flex-1 items-center justify-between px-4 py-2.5 text-left text-xs font-bold transition"
                                        :class="
                                            selectedCategoryFilter ===
                                            category.id
                                                ? 'rounded-xl bg-[#3B2314] text-white shadow-sm'
                                                : 'text-gray-600'
                                        "
                                    >
                                        <span
                                            class="flex items-center gap-1.5 truncate pr-4"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="2"
                                                stroke="currentColor"
                                                class="h-4 w-4"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"
                                                />
                                            </svg>
                                            {{ category.name }}
                                        </span>
                                        <span
                                            class="shrink-0 rounded-full px-2 py-0.5 text-[10px]"
                                            :class="
                                                selectedCategoryFilter ===
                                                category.id
                                                    ? 'bg-white/10 text-white'
                                                    : 'bg-gray-100 text-gray-500'
                                            "
                                        >
                                            {{
                                                localProducts.filter(
                                                    (p) =>
                                                        p.category_id ===
                                                        category.id,
                                                ).length
                                            }}
                                        </span>
                                    </button>

                                    <!-- Hover Actions untuk Kategori (Edit/Hapus) -->
                                    <div
                                        class="absolute top-1.5 right-2 z-10 hidden items-center gap-1 rounded-lg border border-gray-100 bg-white p-0.5 shadow-sm group-hover:flex"
                                        v-if="
                                            selectedCategoryFilter !==
                                            category.id
                                        "
                                    >
                                        <button
                                            @click.stop="
                                                openEditCategory(category)
                                            "
                                            class="rounded p-1 text-blue-500 hover:bg-gray-50"
                                            title="Edit Kategori"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="2.5"
                                                stroke="currentColor"
                                                class="h-3.5 w-3.5"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.83 20.82a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            @click.stop="
                                                deleteCategory(
                                                    category.id,
                                                    category.name,
                                                )
                                            "
                                            class="rounded p-1 text-red-500 hover:bg-gray-50"
                                            title="Hapus Kategori"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="2.5"
                                                stroke="currentColor"
                                                class="h-3.5 w-3.5"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KOLOM PRODUK (KANAN) -->
                        <div
                            class="card-abstract-border h-full space-y-6 rounded-3xl bg-white p-6 shadow-sm lg:col-span-3"
                        >
                            <!-- Pencarian & Kontrol -->
                            <div
                                class="flex flex-col items-center justify-between gap-4 sm:flex-row"
                            >
                                <div class="relative w-full sm:max-w-xs">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="2"
                                            stroke="currentColor"
                                            class="h-4 w-4"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.602 10.602Z"
                                            />
                                        </svg>
                                    </span>
                                    <input
                                        v-model="searchProductQuery"
                                        type="text"
                                        placeholder="Cari nama menu..."
                                        class="w-full rounded-xl border border-gray-300 py-2.5 pr-4 pl-9 text-xs font-bold text-gray-900 focus:border-[#3B2314] focus:ring-1 focus:ring-[#3B2314]"
                                    />
                                </div>

                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-gray-400"
                                >
                                    <span>Menampilkan</span>
                                    <span
                                        class="rounded border border-[#D4A373]/20 bg-gray-100 px-2 py-0.5 font-black text-[#3B2314]"
                                    >
                                        {{ filteredProducts.length }} dari
                                        {{ localProducts.length }}
                                    </span>
                                    <span>Menu Kopi & Kuliner</span>
                                </div>
                            </div>

                            <!-- Grid List Produk -->
                            <div
                                v-if="filteredProducts.length === 0"
                                class="py-16 text-center text-gray-400"
                            >
                                <div
                                    class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full border bg-gray-50"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                        class="h-6 w-6 text-gray-400"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"
                                        />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-gray-500">
                                    Tidak Ada Menu
                                </h4>
                                <p class="mt-1 text-xs text-gray-400">
                                    Menu yang Anda cari tidak ditemukan. Coba
                                    ganti kata kunci atau tambahkan baru.
                                </p>
                            </div>

                            <div class="space-y-6" v-else>
                                <div
                                    class="grid grid-cols-1 gap-6 md:grid-cols-2"
                                >
                                    <div
                                        v-for="product in paginatedProducts"
                                        :key="product.id"
                                        class="relative flex flex-col justify-between rounded-2xl border border-[#D4A373]/20 bg-gradient-to-br from-white to-[#FAEDCD]/5 p-4 transition-all duration-300 hover:border-[#D4A373]/60 hover:shadow-md"
                                        :class="{
                                            'border-gray-200 bg-gray-50/80':
                                                !product.is_available,
                                        }"
                                    >
                                        <!-- Top Content: Image & Detail -->
                                        <div class="flex gap-4">
                                            <!-- Foto Produk -->
                                            <div
                                                class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-gray-100 bg-gray-50"
                                            >
                                                <img
                                                    :src="
                                                        product.image ||
                                                        'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=300&auto=format&fit=crop'
                                                    "
                                                    alt="Menu"
                                                    class="h-full w-full object-cover"
                                                />
                                            </div>

                                            <!-- Detail Produk -->
                                            <div
                                                class="flex min-w-0 flex-1 flex-col justify-between"
                                            >
                                                <div>
                                                    <div
                                                        class="mb-1 flex flex-wrap items-center gap-1.5"
                                                    >
                                                        <span
                                                            class="rounded-full border border-[#D4A373]/30 bg-[#FAEDCD] px-1.5 py-0.5 text-[9px] font-black tracking-wider text-[#3B2314] uppercase"
                                                        >
                                                            {{
                                                                product.category
                                                                    ?.name ||
                                                                'Kopi'
                                                            }}
                                                        </span>

                                                        <span
                                                            class="rounded-full px-1.5 py-0.5 text-[9px] font-bold tracking-wider uppercase"
                                                            :class="
                                                                product.is_available
                                                                    ? 'bg-green-100 text-green-700'
                                                                    : 'bg-red-100 text-red-600'
                                                            "
                                                        >
                                                            {{
                                                                product.is_available
                                                                    ? 'Tersedia'
                                                                    : 'Habis'
                                                            }}
                                                        </span>
                                                    </div>
                                                    <h4
                                                        class="truncate text-sm font-bold text-[#3B2314]"
                                                        :title="product.name"
                                                    >
                                                        {{ product.name }}
                                                    </h4>
                                                    <p
                                                        class="mt-0.5 line-clamp-1 text-[11px] text-gray-400"
                                                    >
                                                        {{
                                                            product.description ||
                                                            'Seduhan kopi nikmat khas Zunoi.'
                                                        }}
                                                    </p>
                                                </div>
                                                <div
                                                    class="mt-2 flex items-center justify-between border-t border-[#D4A373]/10 pt-2"
                                                >
                                                    <span
                                                        class="text-xs font-black text-[#3B2314]"
                                                        >Rp
                                                        {{
                                                            parseInt(
                                                                product.price,
                                                            ).toLocaleString(
                                                                'id-ID',
                                                            )
                                                        }}</span
                                                    >

                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <!-- Edit -->
                                                        <button
                                                            @click="
                                                                openEditProduct(
                                                                    product,
                                                                )
                                                            "
                                                            class="rounded-lg border border-transparent p-1.5 text-blue-500 hover:border-blue-100 hover:bg-blue-50"
                                                            title="Edit Menu"
                                                        >
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                fill="none"
                                                                viewBox="0 0 24 24"
                                                                stroke-width="2.5"
                                                                stroke="currentColor"
                                                                class="h-3.5 w-3.5"
                                                            >
                                                                <path
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.83 20.82a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                                                />
                                                            </svg>
                                                        </button>

                                                        <!-- Hapus -->
                                                        <button
                                                            @click="
                                                                deleteProduct(
                                                                    product.id,
                                                                    product.name,
                                                                )
                                                            "
                                                            class="rounded-lg border border-transparent p-1.5 text-red-500 hover:border-red-100 hover:bg-red-50"
                                                            title="Hapus Menu"
                                                        >
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                fill="none"
                                                                viewBox="0 0 24 24"
                                                                stroke-width="2.5"
                                                                stroke="currentColor"
                                                                class="h-3.5 w-3.5"
                                                            >
                                                                <path
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                                                />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Bottom Content: Set Habis / Set Ada spanning full width -->
                                        <div
                                            class="mt-3 border-t border-[#D4A373]/10 pt-3"
                                        >
                                            <button
                                                @click="
                                                    toggleAvailability(product)
                                                "
                                                class="w-full transform rounded-xl border py-2 text-center text-xs font-black tracking-wider transition active:scale-[0.98]"
                                                :class="
                                                    product.is_available
                                                        ? 'border-red-200 bg-red-50 text-red-600 hover:bg-red-100/70'
                                                        : 'border-green-200 bg-green-50 text-green-600 hover:bg-green-100/70'
                                                "
                                            >
                                                {{
                                                    product.is_available
                                                        ? 'Set Habis (Tandai Kosong)'
                                                        : 'Set Ada (Tandai Tersedia)'
                                                }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- PAGINATION CONTROLS -->
                                <div
                                    v-if="totalPages > 1"
                                    class="flex flex-col items-center justify-between gap-4 border-t border-[#D4A373]/10 pt-6 sm:flex-row"
                                >
                                    <span
                                        class="text-xs font-bold text-gray-500"
                                    >
                                        Menampilkan
                                        {{
                                            (currentPage - 1) * itemsPerPage + 1
                                        }}
                                        -
                                        {{
                                            Math.min(
                                                currentPage * itemsPerPage,
                                                filteredProducts.length,
                                            )
                                        }}
                                        dari {{ filteredProducts.length }} menu
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <!-- Prev -->
                                        <button
                                            @click="
                                                currentPage > 1
                                                    ? currentPage--
                                                    : null
                                            "
                                            :disabled="currentPage === 1"
                                            class="rounded-xl border border-[#D4A373]/20 bg-white px-3 py-1.5 text-xs font-bold text-[#3B2314] transition hover:bg-[#FAEDCD]/20 active:scale-95 disabled:pointer-events-none disabled:opacity-40"
                                        >
                                            Sebelumnya
                                        </button>

                                        <!-- Page numbers -->
                                        <button
                                            v-for="page in totalPages"
                                            :key="page"
                                            @click="currentPage = page"
                                            class="h-8 w-8 rounded-xl text-xs font-black transition active:scale-95"
                                            :class="
                                                currentPage === page
                                                    ? 'bg-[#3B2314] text-white shadow-sm'
                                                    : 'border border-[#D4A373]/20 bg-white text-[#3B2314] hover:bg-[#FAEDCD]/10'
                                            "
                                        >
                                            {{ page }}
                                        </button>

                                        <!-- Next -->
                                        <button
                                            @click="
                                                currentPage < totalPages
                                                    ? currentPage++
                                                    : null
                                            "
                                            :disabled="
                                                currentPage === totalPages
                                            "
                                            class="rounded-xl border border-[#D4A373]/20 bg-white px-3 py-1.5 text-xs font-bold text-[#3B2314] transition hover:bg-[#FAEDCD]/20 active:scale-95 disabled:pointer-events-none disabled:opacity-40"
                                        >
                                            Berikutnya
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ADD-ONS VIEW -->
                    <div
                        v-else
                        key="addons"
                        class="grid w-full grid-cols-1 items-start gap-8 lg:grid-cols-4"
                    >
                        <!-- ADD/EDIT FORM (KIRI) -->
                        <div
                            class="card-abstract-border h-full space-y-4 rounded-3xl bg-white p-5 shadow-sm lg:col-span-1"
                        >
                            <h4
                                class="text-xs font-black tracking-wider text-[#3B2314] uppercase"
                            >
                                {{
                                    isEditingAddon
                                        ? 'Edit Add-on'
                                        : 'Tambah Add-on Baru'
                                }}
                            </h4>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label
                                        class="mb-1 block text-xs font-extrabold text-[#3B2314]/80"
                                        >Nama Add-on *</label
                                    >
                                    <input
                                        v-model="addonForm.addon_name"
                                        type="text"
                                        placeholder="Contoh: Caramel Sauce"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-bold text-[#3B2314] placeholder-gray-400 shadow-inner focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block text-xs font-extrabold text-[#3B2314]/80"
                                        >Harga Tambahan (Rp) *</label
                                    >
                                    <input
                                        v-model="addonForm.extra_price"
                                        type="number"
                                        placeholder="Contoh: 4000"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-bold text-[#3B2314] placeholder-gray-400 shadow-inner focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                                    />
                                </div>
                                <div
                                    class="flex justify-end gap-2 border-t border-[#D4A373]/10 pt-4"
                                >
                                    <button
                                        v-if="isEditingAddon"
                                        @click="resetAddonForm"
                                        type="button"
                                        class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-bold text-gray-600 shadow-sm transition hover:bg-gray-50 active:scale-95"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        @click="saveAddon"
                                        type="button"
                                        class="rounded-xl bg-[#3B2314] px-5 py-2 text-xs font-bold text-[#FAEDCD] shadow-sm transition hover:bg-[#2A180E] active:scale-95"
                                    >
                                        {{
                                            isEditingAddon ? 'Simpan' : 'Tambah'
                                        }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ADDONS LIST (KANAN) -->
                        <div
                            class="card-abstract-border h-full space-y-6 rounded-3xl bg-white p-6 shadow-sm lg:col-span-3"
                        >
                            <h4
                                class="border-b border-[#D4A373]/10 pb-4 text-sm font-black tracking-wider text-[#3B2314] uppercase"
                            >
                                Daftar Add-on Aktif ({{
                                    localGlobalAddons.length
                                }})
                            </h4>

                            <div
                                v-if="localGlobalAddons.length === 0"
                                class="py-8 text-center text-xs text-gray-400"
                            >
                                Belum ada add-on yang ditambahkan.
                            </div>

                            <div v-else class="divide-y divide-gray-100">
                                <div
                                    v-for="addon in localGlobalAddons"
                                    :key="addon.id"
                                    class="flex items-center justify-between rounded-xl px-4 py-4 transition hover:bg-gray-50/50"
                                >
                                    <div>
                                        <span
                                            class="text-sm font-bold text-[#3B2314]"
                                            >{{ addon.name }}</span
                                        >
                                        <div
                                            class="mt-1 flex items-center gap-1.5"
                                        >
                                            <span
                                                class="font-mono text-xs font-extrabold text-green-600"
                                                >+ Rp
                                                {{
                                                    parseInt(
                                                        addon.price,
                                                    ).toLocaleString('id-ID')
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="handleEditAddon(addon)"
                                            class="rounded-lg border border-transparent p-2 text-blue-500 transition hover:border-blue-100 hover:bg-blue-50"
                                            title="Edit Addon"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="2.5"
                                                stroke="currentColor"
                                                class="h-4 w-4"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.83 20.82a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteAddon(addon.id)"
                                            class="rounded-lg border border-transparent p-2 text-red-500 transition hover:border-red-100 hover:bg-red-50"
                                            title="Hapus Addon"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="2.5"
                                                stroke="currentColor"
                                                class="h-4 w-4"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </ZunoiAdminLayout>

    <!-- MODAL PRODUCT (ADD / EDIT) -->
    <div
        v-if="showProductModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#3B2314]/70 p-4 backdrop-blur-sm"
    >
        <div
            class="w-full max-w-4xl scale-100 transform overflow-hidden rounded-[32px] border border-[#D4A373]/30 bg-white shadow-2xl transition-all duration-300"
        >
            <div
                class="relative border-b border-[#D4A373]/20 bg-[#3B2314] p-5 text-center text-[#FAEDCD]"
            >
                <button
                    @click="showProductModal = false"
                    class="absolute top-4 right-4 text-gray-300 transition hover:text-white"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        stroke="currentColor"
                        class="h-5 w-5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>
                </button>
                <h3 class="text-md font-extrabold">
                    {{
                        isEditingProduct
                            ? 'Edit Menu Kopi/Makanan'
                            : 'Tambah Menu Baru'
                    }}
                </h3>
                <p
                    class="text-[9px] font-bold tracking-widest text-[#D4A373] uppercase"
                >
                    Zunoi Caffe Menu Setup
                </p>
            </div>

            <div
                class="grid grid-cols-1 divide-x divide-gray-100 md:grid-cols-2"
            >
                <!-- SISI KIRI: INFORMASI DASAR -->
                <div
                    class="max-h-[70vh] space-y-4 overflow-y-auto p-6 text-gray-800"
                >
                    <h4
                        class="mb-2 text-[10px] font-black tracking-widest text-gray-400 uppercase"
                    >
                        Informasi Produk
                    </h4>

                    <!-- Preview Gambar Menu (Besar - 1:1 Aspect Ratio) -->
                    <div class="group relative">
                        <div
                            class="aspect-square w-full overflow-hidden rounded-[24px] border border-gray-200 bg-gray-50 shadow-inner"
                        >
                            <img
                                v-if="
                                    productImagePreview ||
                                    (imageInputType === 'url' &&
                                        productForm.image)
                                "
                                :src="
                                    imageInputType === 'file'
                                        ? productImagePreview
                                        : productForm.image
                                "
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            />
                            <div
                                v-else
                                class="flex h-full w-full flex-col items-center justify-center space-y-2 opacity-40"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1"
                                    stroke="currentColor"
                                    class="h-12 w-12"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6.75a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6.75v12.75a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"
                                    />
                                </svg>
                                <p
                                    class="px-4 text-center text-[9px] font-black tracking-widest uppercase"
                                >
                                    Pratinjau Gambar (1:1)
                                </p>
                            </div>
                        </div>
                        <span
                            v-if="productForm.is_available"
                            class="absolute top-3 left-3 rounded-full bg-green-500 px-2.5 py-0.5 text-[9px] font-black text-white shadow-sm"
                            >TERSEDIA</span
                        >
                        <span
                            v-else
                            class="absolute top-3 left-3 rounded-full bg-red-500 px-2.5 py-0.5 text-[9px] font-black text-white shadow-sm"
                            >HABIS</span
                        >
                    </div>

                    <!-- Name -->
                    <div class="pt-2">
                        <label
                            class="mb-1 block text-xs font-bold tracking-tighter text-gray-600 uppercase"
                            >Nama Menu *</label
                        >
                        <input
                            v-model="productForm.name"
                            type="text"
                            placeholder="Contoh: Es Latte Gula Aren"
                            class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-xs font-bold text-gray-900 focus:border-[#3B2314] focus:ring-1 focus:ring-[#3B2314]"
                        />
                    </div>

                    <!-- Price & Category -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-tighter text-gray-600 uppercase"
                                >Harga Jual *</label
                            >
                            <input
                                v-model="productForm.price"
                                type="number"
                                placeholder="22000"
                                class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-xs font-bold text-gray-900 focus:border-[#3B2314] focus:ring-1 focus:ring-[#3B2314]"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-tighter text-gray-600 uppercase"
                                >Kategori *</label
                            >
                            <select
                                v-model="productForm.category_id"
                                class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-xs font-bold text-gray-900 focus:border-[#3B2314] focus:ring-1 focus:ring-[#3B2314]"
                            >
                                <option
                                    v-for="category in localCategories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Image Source Selection -->
                    <div>
                        <label
                            class="mb-2 block text-xs font-bold tracking-tighter text-gray-600 uppercase"
                            >Pilih Gambar *</label
                        >

                        <!-- Tab Selector -->
                        <div
                            class="mb-3 flex w-full rounded-xl border bg-gray-50 p-1 text-[9px] font-black"
                        >
                            <button
                                type="button"
                                @click="imageInputType = 'file'"
                                class="flex-1 rounded-lg py-1.5 text-center tracking-tighter uppercase transition"
                                :class="
                                    imageInputType === 'file'
                                        ? 'bg-[#3B2314] text-white shadow-sm'
                                        : 'text-gray-400 hover:text-[#3B2314]'
                                "
                            >
                                Upload Berkas
                            </button>
                            <button
                                type="button"
                                @click="imageInputType = 'url'"
                                class="flex-1 rounded-lg py-1.5 text-center tracking-tighter uppercase transition"
                                :class="
                                    imageInputType === 'url'
                                        ? 'bg-[#3B2314] text-white shadow-sm'
                                        : 'text-gray-400 hover:text-[#3B2314]'
                                "
                            >
                                Alamat Link URL
                            </button>
                        </div>

                        <!-- File Upload -->
                        <div v-if="imageInputType === 'file'" class="space-y-3">
                            <div
                                class="flex w-full items-center justify-center"
                            >
                                <label
                                    class="flex h-16 w-full cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 transition-colors duration-200 hover:bg-gray-100"
                                >
                                    <div
                                        class="flex flex-col items-center justify-center p-2 text-center"
                                    >
                                        <p
                                            class="text-[10px] font-bold text-gray-500"
                                        >
                                            Klik untuk ganti foto
                                        </p>
                                    </div>
                                    <input
                                        type="file"
                                        @change="onProductFileSelected"
                                        accept="image/*"
                                        class="hidden"
                                    />
                                </label>
                            </div>
                        </div>

                        <!-- URL Input -->
                        <div v-else>
                            <input
                                v-model="productForm.image"
                                type="text"
                                placeholder="https://images.unsplash.com/..."
                                class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-[10px] font-bold text-gray-900 focus:border-[#3B2314] focus:ring-1 focus:ring-[#3B2314]"
                            />
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label
                            class="mb-1 block text-xs font-bold tracking-tighter text-gray-600 uppercase"
                            >Deskripsi Singkat</label
                        >
                        <textarea
                            v-model="productForm.description"
                            rows="2"
                            placeholder="Perpaduan espresso murni, susu segar, dan sirup kelapa..."
                            class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-xs font-bold text-gray-900 focus:border-[#3B2314] focus:ring-1 focus:ring-[#3B2314]"
                        ></textarea>
                    </div>

                    <!-- Availability -->
                    <div class="flex items-center gap-2 pt-2">
                        <input
                            v-model="productForm.is_available"
                            type="checkbox"
                            id="is_available"
                            class="rounded border-gray-300 text-[#3B2314] focus:ring-[#3B2314]"
                        />
                        <label
                            for="is_available"
                            class="cursor-pointer text-xs font-bold text-gray-600"
                            >Menu langsung siap dipesan oleh pelanggan</label
                        >
                    </div>
                </div>

                <!-- SISI KANAN: ASSIGN ADDONS -->
                <div class="flex max-h-[70vh] flex-col bg-gray-50/50 p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h4
                            class="text-[10px] font-black tracking-widest text-gray-400 uppercase"
                        >
                            Pasangkan Add-ons
                        </h4>
                        <span
                            class="rounded-full bg-[#3B2314] px-2 py-0.5 text-[9px] font-black text-white"
                            >{{ assignedAddonIds.length }} Terpilih</span
                        >
                    </div>

                    <div class="flex-1 space-y-2 overflow-y-auto pr-1">
                        <div
                            v-if="localGlobalAddons.length === 0"
                            class="py-10 text-center"
                        >
                            <p class="text-[10px] font-bold text-gray-400">
                                Belum ada addon global.<br />Tambahkan di tab
                                Add-ons.
                            </p>
                        </div>

                        <label
                            v-for="addon in paginatedAssignmentAddons"
                            :key="addon.id"
                            class="group relative flex cursor-pointer items-center justify-between rounded-xl border bg-white p-3 transition hover:border-[#D4A373]/50 hover:shadow-sm"
                            :class="
                                assignedAddonIds.includes(addon.id)
                                    ? 'border-[#3B2314] bg-[#3B2314]/5'
                                    : 'border-gray-200'
                            "
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    :value="addon.id"
                                    v-model="assignedAddonIds"
                                    class="h-4 w-4 rounded border-gray-300 text-[#3B2314] focus:ring-[#3B2314]"
                                />
                                <div>
                                    <p
                                        class="text-xs font-black text-[#3B2314]"
                                    >
                                        {{ addon.name }}
                                    </p>
                                    <p
                                        class="text-[10px] font-bold text-green-600"
                                    >
                                        + Rp
                                        {{
                                            addon.price.toLocaleString('id-ID')
                                        }}
                                    </p>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Pagination for Addon Assignment (Revised) -->
                    <div
                        v-if="totalAddonAssignmentPages > 1"
                        class="mt-4 flex items-center justify-center gap-4 border-t border-gray-200 pt-4"
                    >
                        <button
                            type="button"
                            @click="
                                addonAssignmentPage > 1
                                    ? addonAssignmentPage--
                                    : null
                            "
                            :disabled="addonAssignmentPage === 1"
                            class="flex h-7 w-7 items-center justify-center rounded-full border border-gray-300 bg-white text-[#3B2314] shadow-sm transition hover:bg-[#3B2314] hover:text-white active:scale-90 disabled:opacity-30"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="3"
                                stroke="currentColor"
                                class="h-3 w-3"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15.75 19.5 8.25 12l7.5-7.5"
                                />
                            </svg>
                        </button>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="text-[10px] font-black tracking-widest text-[#3B2314]"
                                >{{ addonAssignmentPage }}</span
                            >
                            <span class="text-[10px] font-bold text-gray-400"
                                >/</span
                            >
                            <span class="text-[10px] font-bold text-gray-400">{{
                                totalAddonAssignmentPages
                            }}</span>
                        </div>
                        <button
                            type="button"
                            @click="
                                addonAssignmentPage < totalAddonAssignmentPages
                                    ? addonAssignmentPage++
                                    : null
                            "
                            :disabled="
                                addonAssignmentPage ===
                                totalAddonAssignmentPages
                            "
                            class="flex h-7 w-7 items-center justify-center rounded-full border border-gray-300 bg-white text-[#3B2314] shadow-sm transition hover:bg-[#3B2314] hover:text-white active:scale-90 disabled:opacity-30"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="3"
                                stroke="currentColor"
                                class="h-3 w-3"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m8.25 4.5 7.5 7.5-7.5 7.5"
                                />
                            </svg>
                        </button>
                    </div>

                    <p
                        class="mt-4 text-[9px] leading-relaxed font-bold text-gray-400 italic"
                    >
                        * Add-ons yang dicentang akan muncul sebagai pilihan
                        ekstra saat pelanggan memesan menu ini.
                    </p>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t bg-gray-50 p-6">
                <button
                    @click="showProductModal = false"
                    class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-xs font-black text-gray-500 transition hover:bg-gray-100"
                >
                    Batal
                </button>
                <button
                    @click="saveProduct"
                    class="flex items-center gap-2 rounded-xl bg-[#3B2314] px-6 py-2.5 text-xs font-black text-[#FAEDCD] shadow-md transition hover:bg-[#25150c] hover:shadow-lg active:scale-95"
                >
                    <svg
                        v-if="isSyncingAddons"
                        class="h-3 w-3 animate-spin text-white"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    Simpan Menu & Add-ons
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT ADDON (DEDICATED) -->
    <div
        v-if="showEditAddonModal"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-[#3B2314]/70 p-4 backdrop-blur-sm"
    >
        <div
            class="w-full max-w-sm scale-100 transform overflow-hidden rounded-[32px] border border-[#D4A373]/30 bg-white shadow-2xl transition-all duration-300"
        >
            <div
                class="relative border-b border-[#D4A373]/20 bg-[#3B2314] p-5 text-center text-[#FAEDCD]"
            >
                <button
                    @click="showEditAddonModal = false"
                    class="absolute top-4 right-4 text-gray-300 transition hover:text-white"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        stroke="currentColor"
                        class="h-5 w-5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>
                </button>
                <h3 class="text-md font-extrabold">Edit Add-on</h3>
                <p
                    class="text-[9px] font-bold tracking-widest text-[#D4A373] uppercase"
                >
                    Zunoi Caffe Addon Setup
                </p>
            </div>

            <div class="space-y-4 p-6 text-gray-800">
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600"
                        >Nama Add-on *</label
                    >
                    <input
                        v-model="addonForm.addon_name"
                        type="text"
                        class="w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-xs font-bold text-gray-900 focus:border-[#3B2314] focus:ring-1 focus:ring-[#3B2314]"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600"
                        >Harga Tambahan (Rp) *</label
                    >
                    <input
                        v-model="addonForm.extra_price"
                        type="number"
                        class="w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-xs font-bold text-gray-900 focus:border-[#3B2314] focus:ring-1 focus:ring-[#3B2314]"
                    />
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t bg-gray-50 p-6">
                <button
                    @click="showEditAddonModal = false"
                    class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-xs font-black text-gray-500 transition hover:bg-gray-100"
                >
                    Batal
                </button>
                <button
                    @click="saveAddon"
                    class="rounded-xl bg-[#3B2314] px-6 py-2.5 text-xs font-black text-white shadow-md transition hover:bg-[#25150c] active:scale-95"
                >
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL CATEGORY (ADD / EDIT) -->
    <div
        v-if="showCategoryModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#3B2314]/70 p-4 backdrop-blur-sm"
    >
        <div
            class="w-full max-w-sm scale-100 transform overflow-hidden rounded-[32px] border border-[#D4A373]/30 bg-white shadow-2xl transition-all duration-300"
        >
            <div
                class="relative border-b border-[#D4A373]/20 bg-[#3B2314] p-5 text-center text-[#FAEDCD]"
            >
                <button
                    @click="showCategoryModal = false"
                    class="absolute top-4 right-4 text-gray-300 transition hover:text-white"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        stroke="currentColor"
                        class="h-5 w-5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>
                </button>
                <h3 class="text-md font-extrabold">
                    {{
                        isEditingCategory
                            ? 'Edit Kategori Menu'
                            : 'Tambah Kategori'
                    }}
                </h3>
                <p
                    class="text-[9px] font-bold tracking-widest text-[#D4A373] uppercase"
                >
                    Zunoi Caffe Category Setup
                </p>
            </div>

            <div class="space-y-4 p-6">
                <!-- Name -->
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600"
                        >Nama Kategori *</label
                    >
                    <input
                        v-model="categoryForm.name"
                        @keyup.enter="saveCategory"
                        type="text"
                        placeholder="Contoh: Coffee, Non-Coffee, Pastry, Snack"
                        class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-xs font-bold text-gray-900 focus:border-[#3B2314] focus:ring-1 focus:ring-[#3B2314]"
                    />
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t bg-gray-50 p-6">
                <button
                    @click="showCategoryModal = false"
                    class="rounded-xl border px-4 py-2 text-xs font-bold text-gray-500 transition hover:bg-gray-100"
                >
                    Batal
                </button>
                <button
                    @click="saveCategory"
                    class="rounded-xl bg-[#3B2314] px-5 py-2 text-xs font-bold text-white shadow-md transition hover:bg-[#25150c] hover:shadow-lg"
                >
                    Simpan Kategori
                </button>
            </div>
        </div>
    </div>

    <!-- PREMIUM CONFIRMATION MODAL -->
    <div
        v-if="showConfirmModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#3B2314]/70 p-4 backdrop-blur-sm"
    >
        <div
            class="w-full max-w-sm scale-100 transform overflow-hidden rounded-[32px] border border-[#D4A373]/30 bg-white shadow-2xl transition-all duration-300"
        >
            <div
                class="relative border-b border-[#D4A373]/20 bg-[#3B2314] p-5 text-center text-[#FAEDCD]"
            >
                <button
                    @click="showConfirmModal = false"
                    class="absolute top-4 right-4 text-gray-300 transition hover:text-white"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        stroke="currentColor"
                        class="h-5 w-5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>
                </button>
                <h3 class="text-md font-extrabold">{{ confirmTitle }}</h3>
                <p
                    class="text-[9px] font-bold tracking-widest text-[#D4A373] uppercase"
                >
                    Konfirmasi Aksi
                </p>
            </div>

            <div class="space-y-3 p-6 text-center">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-red-200 bg-red-50 text-red-500"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        stroke="currentColor"
                        class="h-6 w-6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                        />
                    </svg>
                </div>
                <p class="text-xs leading-relaxed font-bold text-gray-600">
                    {{ confirmMessage }}
                </p>
            </div>

            <div class="flex justify-center gap-3 border-t bg-gray-50 p-6">
                <button
                    @click="showConfirmModal = false"
                    class="rounded-xl border px-5 py-2 text-xs font-bold text-gray-500 transition hover:bg-gray-100"
                >
                    Batal
                </button>
                <button
                    @click="handleConfirmYes"
                    class="rounded-xl bg-red-600 px-6 py-2 text-xs font-bold text-white shadow-md transition hover:bg-red-700 hover:shadow-lg"
                >
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.3s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
}

.btn-bouncy:active {
    animation: bounceBtn 0.3s ease-in-out;
}

@keyframes bounceBtn {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(0.9);
    }
}

.card-abstract-border {
    border: 4px solid transparent;
    background:
        linear-gradient(white, white) padding-box,
        linear-gradient(45deg, #3b2314, #d4a373, #8b5e34, #3b2314) border-box;
    background-size:
        100% 100%,
        300% 300%;
    animation: abstractGradientBg 4s ease infinite;
}

@keyframes abstractGradientBg {
    0% {
        background-position:
            0% 0%,
            0% 50%;
    }
    50% {
        background-position:
            0% 0%,
            100% 50%;
    }
    100% {
        background-position:
            0% 0%,
            0% 50%;
    }
}
</style>
