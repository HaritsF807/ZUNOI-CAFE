<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';

interface Addon {
    id: number;
    name: string;
    price: number;
}

interface Product {
    id: number;
    name: string;
    price: number;
    description: string | null;
    image: string | null;
    is_available: boolean;
    category_id: number;
    addons: Addon[];
}

interface Category {
    id: number;
    name: string;
}

interface Table {
    id: number;
    table_name: string;
}

interface CartItem extends Product {
    quantity: number;
    notes: string;
}

interface Promotion {
    id: number;
    name: string;
    type: string;
    buy_product_id: number;
    buy_quantity: number;
    bundling_product_id: number | null;
    get_product_id: number | null;
    get_quantity: number | null;
    discount_type: string;
    discount_value: string | number;
    is_active: boolean;
    buy_product?: any;
    bundling_product?: any;
    get_product?: any;
}

const props = defineProps<{
    products: Product[];
    categories: Category[];
    tables: Table[];
    promotions?: Promotion[];
}>();

// Search & Category Filter State
const searchCatalogQuery = ref('');
const selectedCategoryFilter = ref<number | 'all'>('all');

// Cart State
const cart = ref<CartItem[]>([]);

// Order Form State
const customerName = ref('');
const customerPhone = ref('');
const orderType = ref<'dine_in' | 'takeaway'>('dine_in');
const selectedTableId = ref<number | ''>('');
const selectedPaymentMethod = ref<'cash' | 'qris_manual' | 'qris_tokopay'>('cash');
const generalNotes = ref('');

// Voucher state
const voucherCodeInput = ref('');
const appliedVoucher = ref<any>(null);
const discountAmount = ref(0);
const voucherError = ref('');
const isCheckingVoucher = ref(false);

const applyVoucher = async () => {
    if (!voucherCodeInput.value.trim()) return;

    isCheckingVoucher.value = true;
    voucherError.value = '';

    try {
        const response = await axios.post('/api/vouchers/validate', {
            code: voucherCodeInput.value.trim(),
            subtotal: subtotal.value
        });

        if (response.data.success) {
            appliedVoucher.value = response.data.voucher;
            discountAmount.value = response.data.discount_amount;
            voucherError.value = '';
            triggerToast('Voucher berhasil diterapkan!');
        }
    } catch (error: any) {
        voucherError.value = error.response?.data?.message || 'Kode voucher tidak valid!';
        appliedVoucher.value = null;
        discountAmount.value = 0;
    } finally {
        isCheckingVoucher.value = false;
    }
};

const removeVoucher = () => {
    appliedVoucher.value = null;
    discountAmount.value = 0;
    voucherCodeInput.value = '';
    voucherError.value = '';
    triggerToast('Voucher dihapus.', 'error');
};

// Reset table selection when order type changes to takeaway
watch(orderType, (newType) => {
    if (newType === 'takeaway') {
        selectedTableId.value = '';
    }
});

// Custom Note Popover Modal State
const showNoteModal = ref(false);
const activeProductForNote = ref<Product | null>(null);
const currentItemNote = ref('');

// Receipt / Success Modal State
const showSuccessModal = ref(false);
const createdOrder = ref<any>(null);

// Loading State
const isSubmitting = ref(false);

// Filtered Products
const filteredProducts = computed(() => {
    return props.products.filter((product) => {
        const matchesSearch = product.name.toLowerCase().includes(searchCatalogQuery.value.toLowerCase()) ||
            (product.description && product.description.toLowerCase().includes(searchCatalogQuery.value.toLowerCase()));
        
        const matchesCategory = selectedCategoryFilter.value === 'all' || product.category_id === selectedCategoryFilter.value;
        
        return matchesSearch && matchesCategory;
    });
});

// Calculate Totals
const subtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const activePromotions = computed(() => props.promotions || []);

const appliedPromotionsList = computed(() => {
    const list: Array<{ id: number; name: string; discount: number }> = [];
    if (!activePromotions.value || activePromotions.value.length === 0) {
        return list;
    }

    // Map cart items by product id for easy lookup
    const cartMap: Record<number, { quantity: number; price: number; basePrice: number }> = {};
    cart.value.forEach((item) => {
        const prodId = item.id;
        if (!cartMap[prodId]) {
            cartMap[prodId] = {
                quantity: 0,
                price: Number(item.price),
                basePrice: Number(item.price)
            };
        }
        cartMap[prodId].quantity += Number(item.quantity);
    });

    activePromotions.value.forEach((promo) => {
        const buyProductId = Number(promo.buy_product_id);
        const buyQtyRequired = Number(promo.buy_quantity);
        const bundlingProductId = promo.bundling_product_id ? Number(promo.bundling_product_id) : null;
        const getProductId = promo.get_product_id ? Number(promo.get_product_id) : null;
        const getQtyRequired = promo.get_quantity ? Number(promo.get_quantity) : 1;

        if (!cartMap[buyProductId]) {
            return;
        }

        const buyCartQty = cartMap[buyProductId].quantity;

        if (promo.type === 'bundling' && bundlingProductId) {
            if (!cartMap[bundlingProductId]) {
                return;
            }

            const bundCartQty = cartMap[bundlingProductId].quantity;
            const numBundles = Math.min(Math.floor(buyCartQty / buyQtyRequired), bundCartQty);

            if (numBundles > 0) {
                let discountVal = 0;
                if (promo.discount_type === 'nominal') {
                    discountVal = Number(promo.discount_value) * numBundles;
                } else if (promo.discount_type === 'percentage') {
                    const buyUnitPrice = cartMap[buyProductId].basePrice;
                    const bundUnitPrice = cartMap[bundlingProductId].basePrice;
                    const singleBundlePrice = (buyUnitPrice * buyQtyRequired) + bundUnitPrice;
                    discountVal = (Number(promo.discount_value) / 100) * singleBundlePrice * numBundles;
                }

                if (discountVal > 0) {
                    list.push({
                        id: promo.id,
                        name: promo.name,
                        discount: discountVal
                    });
                }
            }
        } else if (promo.type === 'buy_get' && getProductId) {
            if (!cartMap[getProductId]) {
                return;
            }

            const getCartQty = cartMap[getProductId].quantity;

            if (buyProductId === getProductId) {
                // Buy X Get Y of same product
                const requiredCombo = buyQtyRequired + getQtyRequired;
                const numCombos = Math.floor(buyCartQty / requiredCombo);

                if (numCombos > 0) {
                    const itemUnitPrice = cartMap[getProductId].basePrice;
                    const discountedQty = numCombos * getQtyRequired;
                    let discountVal = 0;

                    if (promo.discount_type === 'free') {
                        discountVal = itemUnitPrice * discountedQty;
                    } else if (promo.discount_type === 'percentage') {
                        discountVal = (Number(promo.discount_value) / 100) * itemUnitPrice * discountedQty;
                    } else if (promo.discount_type === 'nominal') {
                        discountVal = Number(promo.discount_value) * discountedQty;
                    }

                    if (discountVal > 0) {
                        list.push({
                            id: promo.id,
                            name: promo.name,
                            discount: discountVal
                        });
                    }
                }
            } else {
                // Buy X Get Y of different product
                const numCombos = Math.floor(buyCartQty / buyQtyRequired);

                if (numCombos > 0) {
                    const maxDiscountedQty = numCombos * getQtyRequired;
                    const actualDiscountedQty = Math.min(maxDiscountedQty, getCartQty);

                    if (actualDiscountedQty > 0) {
                        const itemUnitPrice = cartMap[getProductId].basePrice;
                        let discountVal = 0;

                        if (promo.discount_type === 'free') {
                            discountVal = itemUnitPrice * actualDiscountedQty;
                        } else if (promo.discount_type === 'percentage') {
                            discountVal = (Number(promo.discount_value) / 100) * itemUnitPrice * actualDiscountedQty;
                        } else if (promo.discount_type === 'nominal') {
                            discountVal = Number(promo.discount_value) * actualDiscountedQty;
                        }

                        if (discountVal > 0) {
                            list.push({
                                id: promo.id,
                                name: promo.name,
                                discount: discountVal
                            });
                        }
                    }
                }
            }
        }
    });

    return list;
});

const promoDiscountTotal = computed(() => {
    return appliedPromotionsList.value.reduce((sum, item) => sum + item.discount, 0);
});

const tax = computed(() => 0); // Can be set if needed
const total = computed(() => {
    const afterDiscount = Math.max(0, subtotal.value - discountAmount.value - promoDiscountTotal.value);
    return afterDiscount + tax.value;
});

// Cart Actions
const openNoteModal = (product: Product) => {
    activeProductForNote.value = product;
    
    // Start with empty notes for a new customized order of this product
    currentItemNote.value = '';
    
    showNoteModal.value = true;
};

const handleSaveNote = () => {
    if (!activeProductForNote.value) return;
    
    const product = activeProductForNote.value;
    const noteText = currentItemNote.value.trim();
    
    // Find if there is already a cart item with the same product ID and exact same notes
    const existingIndex = cart.value.findIndex(item => item.id === product.id && item.notes === noteText);
    
    if (existingIndex !== -1) {
        // Increment quantity of the exact matching item
        cart.value[existingIndex].quantity += 1;
    } else {
        // Add as a brand new line item in the cart
        cart.value.push({
            ...product,
            quantity: 1,
            notes: noteText
        });
    }
    
    showNoteModal.value = false;
    activeProductForNote.value = null;
    currentItemNote.value = '';
    
    triggerToast('Catatan menu berhasil disimpan!');
};

const addToCart = (product: Product) => {
    // Find if there is already a default cart item with the same ID and empty notes
    const existingIndex = cart.value.findIndex(item => item.id === product.id && item.notes === '');
    
    if (existingIndex !== -1) {
        cart.value[existingIndex].quantity += 1;
    } else {
        cart.value.push({
            ...product,
            quantity: 1,
            notes: ''
        });
    }
    triggerToast(`Ditambahkan: ${product.name}`);
};

const decreaseQuantity = (index: number) => {
    if (cart.value[index].quantity > 1) {
        cart.value[index].quantity -= 1;
    } else {
        cart.value.splice(index, 1);
    }
};

const increaseQuantity = (index: number) => {
    cart.value[index].quantity += 1;
};

const removeCartItem = (index: number) => {
    const name = cart.value[index].name;
    cart.value.splice(index, 1);
    triggerToast(`Dihapus: ${name}`, 'error');
};

const clearCart = () => {
    cart.value = [];
    triggerToast('Keranjang kasir dikosongkan!');
};

// Global Toast Dispatch
const triggerToast = (message: string, type: 'success' | 'error' = 'success') => {
    window.dispatchEvent(
        new CustomEvent('zunoi-toast', {
            detail: { message, type }
        })
    );
};

// Order Submission
const submitCashierOrder = async () => {
    if (cart.value.length === 0) {
        triggerToast('Keranjang belanja kosong!', 'error');
        return;
    }
    
    if (!customerName.value.trim()) {
        triggerToast('Nama pelanggan wajib diisi!', 'error');
        return;
    }
    
    if (orderType.value === 'dine_in' && !selectedTableId.value) {
        triggerToast('Pilih Meja untuk pesanan Dine In!', 'error');
        return;
    }
    
    isSubmitting.value = ref(true).value;
    
    try {
        const response = await axios.post('/api/orders/cashier', {
            customer_name: customerName.value,
            customer_phone: customerPhone.value,
            order_type: orderType.value,
            table_id: orderType.value === 'dine_in' ? selectedTableId.value : null,
            payment_method: selectedPaymentMethod.value,
            cart_items: cart.value.map(item => ({
                id: item.id,
                quantity: item.quantity,
                price: item.price,
                notes: item.notes
            })),
            notes: generalNotes.value,
            voucher_code: appliedVoucher.value ? appliedVoucher.value.code : null
        });
        
        if (response.data.success) {
            createdOrder.value = response.data.order;
            showSuccessModal.value = true;
            triggerToast('Pesanan kasir berhasil dibuat!');
            
            // Clear form and cart
            cart.value = [];
            customerName.value = '';
            customerPhone.value = '';
            selectedTableId.value = '';
            generalNotes.value = '';
            appliedVoucher.value = null;
            discountAmount.value = 0;
            voucherCodeInput.value = '';
            voucherError.value = '';
        }
    } catch (error: any) {
        const errMsg = error.response?.data?.message || 'Gagal menyimpan pesanan.';
        triggerToast(errMsg, 'error');
    } finally {
        isSubmitting.value = false;
    }
};

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(price);
};
</script>

<template>
    <Head title="Kasir POS - Zunoi Caffe" />

    <ZunoiAdminLayout>
        <div class="flex flex-col h-auto lg:h-[calc(100vh-185px)] lg:flex-row gap-6 p-1 min-w-0">
            <!-- LEFT PANEL: Menu Catalog -->
            <div class="flex flex-col flex-1 bg-white rounded-xl shadow-xl overflow-hidden border border-[#D4A373]/20 min-w-0">
                <!-- Search & Header -->
                <div class="p-6 bg-[#3B2314] text-white flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-extrabold tracking-wide">Kasir / Pemesanan Langsung</h2>
                        <p class="text-xs text-[#FAEDCD]/80 mt-1">Pilih menu, isi meja & nama pelanggan, lalu simpan pesanan dengan cepat.</p>
                    </div>
                    
                    <!-- Search Input -->
                    <div class="relative w-full md:w-72">
                        <input
                            v-model="searchCatalogQuery"
                            type="text"
                            placeholder="Cari menu kopi, kue..."
                            class="w-full bg-white/10 text-white placeholder-white/50 text-sm pl-10 pr-4 py-2.5 rounded-xl border border-white/20 focus:outline-none focus:ring-2 focus:ring-[#D4A373] focus:border-transparent transition"
                        />
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            class="absolute left-3.5 top-3 h-4.5 w-4.5 text-white/50"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.604 10.604Z" />
                        </svg>
                    </div>
                </div>

                <!-- Category Tabs Selector -->
                <div class="px-6 py-4 bg-[#FAEDCD]/20 border-b border-[#D4A373]/10 flex gap-2 overflow-x-auto scrollbar-none">
                    <button
                        @click="selectedCategoryFilter = 'all'"
                        class="px-4 py-2 rounded-xl text-sm font-bold transition duration-200 shrink-0 shadow-sm flex items-center gap-1.5"
                        :class="selectedCategoryFilter === 'all'
                            ? 'bg-[#3B2314] text-[#FAEDCD]'
                            : 'bg-white text-gray-600 hover:bg-[#FAEDCD]/40 border border-[#D4A373]/10'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                        </svg>
                        Semua Menu
                    </button>
                    
                    <button
                        v-for="category in categories"
                        :key="category.id"
                        @click="selectedCategoryFilter = category.id"
                        class="px-4 py-2 rounded-xl text-sm font-bold transition duration-200 shrink-0 shadow-sm"
                        :class="selectedCategoryFilter === category.id
                            ? 'bg-[#3B2314] text-[#FAEDCD]'
                            : 'bg-white text-gray-600 hover:bg-[#FAEDCD]/40 border border-[#D4A373]/10'"
                    >
                        {{ category.name }}
                    </button>
                </div>

                <!-- Product Catalog Grid -->
                <div class="flex-1 p-6 overflow-y-auto bg-gray-50/50">
                    <div v-if="filteredProducts.length === 0" class="flex flex-col items-center justify-center h-64 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-16 mb-4 text-[#D4A373]/60">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 16.318A4.486 4.486 0 0 0 12.016 15a4.486 4.486 0 0 0-3.197 1.318M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                        </svg>
                        <p class="text-lg font-bold">Menu tidak ditemukan</p>
                        <p class="text-xs mt-1">Coba kata kunci lain atau pilih kategori yang berbeda.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        <div
                            v-for="product in filteredProducts"
                            :key="product.id"
                            class="bg-white rounded-lg p-4 shadow-md border border-[#D4A373]/10 hover:border-[#D4A373]/40 transition hover:shadow-lg flex flex-col justify-between"
                        >
                            <div>
                                <!-- Image or placeholder -->
                                <div class="w-full h-36 rounded-xl overflow-hidden bg-[#FAEDCD]/30 relative mb-3">
                                    <img
                                        v-if="product.image"
                                        :src="product.image"
                                        :alt="product.name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-[#3B2314]/30">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
                                        </svg>
                                    </div>
                                    <span class="absolute top-2.5 right-2.5 px-2 py-1 bg-white/95 backdrop-blur-sm text-[10px] font-extrabold text-[#3B2314] rounded-lg shadow-sm border border-[#D4A373]/20">
                                        {{ formatPrice(product.price) }}
                                    </span>
                                </div>

                                <h3 class="font-bold text-gray-800 text-base line-clamp-1">{{ product.name }}</h3>
                                <p class="text-xs text-gray-500 line-clamp-2 mt-1.5 leading-relaxed min-h-[2rem]">
                                    {{ product.description || 'Tidak ada deskripsi.' }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="mt-4 flex gap-2 pt-3 border-t border-gray-100 shrink-0">
                                <button
                                    @click="openNoteModal(product)"
                                    class="w-10 h-10 bg-gray-100 hover:bg-[#FAEDCD]/40 text-gray-600 rounded-xl transition flex items-center justify-center border border-transparent hover:border-[#D4A373]/20 shrink-0"
                                    title="Tambah Catatan Kustom"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4.5 text-[#3B2314]">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                    </svg>
                                </button>
                                
                                <button
                                    @click="addToCart(product)"
                                    class="flex-1 h-10 bg-[#3B2314] hover:bg-[#D4A373] text-[#FAEDCD] hover:text-[#3B2314] rounded-xl text-xs font-extrabold transition flex items-center gap-1.5 justify-center shadow-sm"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    Tambah
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANEL: Cart & Form -->
            <div class="w-full lg:w-96 flex flex-col bg-white rounded-xl shadow-xl overflow-hidden border border-[#D4A373]/20 shrink-0 min-w-0">
                <!-- Header -->
                <div class="p-6 border-b border-[#D4A373]/10 flex items-center justify-between bg-gray-50 shrink-0">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-6 text-[#3B2314]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <h3 class="font-extrabold text-gray-800 text-lg">Keranjang Kasir</h3>
                    </div>
                    
                    <button
                        v-if="cart.length > 0"
                        @click="clearCart"
                        class="text-xs font-bold text-red-500 hover:text-red-700 hover:underline"
                    >
                        Kosongkan
                    </button>
                </div>

                <!-- Scrollable Body containing both Cart and Form -->
                <div class="flex-1 overflow-y-auto flex flex-col">
                    <!-- Cart Items List (No internal scrollbar) -->
                    <div class="p-6 border-b border-gray-100">
                    <div v-if="cart.length === 0" class="flex flex-col items-center justify-center py-12 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="size-16 mb-4 text-[#D4A373]/50">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <p class="text-sm font-bold text-gray-500">Keranjang masih kosong</p>
                        <p class="text-[10px] mt-1">Tambahkan menu dari daftar di samping kiri.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="(item, index) in cart"
                            :key="item.id"
                            class="flex gap-3 pb-4 border-b border-gray-100 last:border-0 last:pb-0"
                        >
                            <!-- Mini image -->
                            <div class="w-12 h-12 bg-[#FAEDCD]/30 rounded-xl overflow-hidden shrink-0 border border-gray-100">
                                <img
                                    v-if="item.image"
                                    :src="item.image"
                                    :alt="item.name"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            
                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-1">
                                    <h4 class="text-sm font-bold text-gray-800 line-clamp-1">{{ item.name }}</h4>
                                    <button
                                        @click="removeCartItem(index)"
                                        class="text-gray-400 hover:text-red-500 transition shrink-0"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.755.033-1.508.074-2.258.122A.75.75 0 0 0 3 5.062v.225c0 .41.33.75.743.748l12.51-.074A.75.75 0 0 0 17 5.21v-.225a.75.75 0 0 0-.742-.746 47.064 47.064 0 0 0-2.258-.122V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4V3.75a1.25 1.25 0 0 0-2.5 0v.443c.828.016 1.662.028 2.5.038Z" clip-rule="evenodd" />
                                            <path d="M4 7.22 4.103 16a2.5 2.5 0 0 0 2.5 2.495h6.794a2.5 2.5 0 0 0 2.5-2.495L16 7.22a46.21 46.21 0 0 0-12 0ZM7.25 9.75a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5a.75.75 0 0 1 .75-.75Zm5.5 0a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5a.75.75 0 0 1 .75-.75Z" />
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">{{ formatPrice(item.price) }}</p>
                                <span v-if="item.notes" class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-[#FAEDCD] text-[10px] font-bold text-[#3B2314] rounded-lg mt-1 max-w-full truncate">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-3 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                    </svg>
                                    Catatan: {{ item.notes }}
                                </span>
                                
                                <!-- Quantity controls -->
                                <div class="flex items-center justify-between mt-2.5">
                                    <span class="text-xs font-extrabold text-[#3B2314]">{{ formatPrice(item.price * item.quantity) }}</span>
                                    
                                    <div class="flex items-center bg-gray-100 rounded-xl overflow-hidden p-0.5 border border-gray-200">
                                        <button
                                            @click="decreaseQuantity(index)"
                                            class="w-6 h-6 flex items-center justify-center hover:bg-white text-gray-600 rounded-lg transition"
                                        >
                                            -
                                        </button>
                                        <span class="w-8 text-center text-xs font-bold text-gray-800">{{ item.quantity }}</span>
                                        <button
                                            @click="increaseQuantity(index)"
                                            class="w-6 h-6 flex items-center justify-center hover:bg-white text-gray-600 rounded-lg transition"
                                        >
                                            +
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Details Form (No internal scrollbar) -->
                <div class="p-6 bg-gray-50/50 space-y-4 flex-1">
                    <!-- Order Type Toggle -->
                    <div class="grid grid-cols-2 bg-gray-200 p-1 rounded-lg border border-gray-300">
                        <button
                            @click="orderType = 'dine_in'"
                            class="py-2 text-xs font-bold text-center rounded-xl transition duration-200 flex items-center justify-center gap-1.5"
                            :class="orderType === 'dine_in' ? 'bg-[#3B2314] text-[#FAEDCD] shadow-sm' : 'text-gray-600 hover:text-gray-800'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5M8.25 21V12a2.25 2.25 0 0 1 2.25-2.25h3a2.25 2.25 0 0 1 2.25 2.25v9M3 9.75V5.25A2.25 2.25 0 0 1 5.25 3h13.5A2.25 2.25 0 0 1 21 5.25v4.5m-18 0a1.5 1.5 0 0 0 1.5 1.5h15a1.5 1.5 0 0 0 1.5-1.5M12 6.75h.008v.008H12V6.75Z" />
                            </svg>
                            Dine In
                        </button>
                        
                        <button
                            @click="orderType = 'takeaway'"
                            class="py-2 text-xs font-bold text-center rounded-xl transition duration-200 flex items-center justify-center gap-1.5"
                            :class="orderType === 'takeaway' ? 'bg-[#3B2314] text-[#FAEDCD] shadow-sm' : 'text-gray-600 hover:text-gray-800'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            Takeaway
                        </button>
                    </div>

                    <!-- Dine-In Table Select -->
                    <div v-if="orderType === 'dine_in'" class="space-y-1.5">
                        <label class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider block">Pilih Nomor Meja *</label>
                        <select
                            v-model="selectedTableId"
                            class="w-full bg-white text-gray-700 text-sm px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#D4A373]"
                        >
                            <option value="">-- Pilih Meja --</option>
                            <option v-for="table in tables" :key="table.id" :value="table.id">
                                {{ table.table_name }}
                            </option>
                        </select>
                    </div>

                    <!-- Customer Name -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider block">Nama Pelanggan *</label>
                        <input
                            v-model="customerName"
                            type="text"
                            placeholder="Nama pelanggan kasir..."
                            class="w-full bg-white text-gray-700 text-sm px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#D4A373]"
                        />
                    </div>

                    <!-- Customer Phone -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider block">Nomor HP/WA (Opsional)</label>
                        <input
                            v-model="customerPhone"
                            type="text"
                            placeholder="Contoh: 08123456789"
                            class="w-full bg-white text-gray-700 text-sm px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#D4A373]"
                        />
                    </div>

                    <!-- Payment Method -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider block">Metode Pembayaran</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                @click="selectedPaymentMethod = 'cash'"
                                class="py-2.5 text-[10px] font-extrabold text-center rounded-xl border transition"
                                :class="selectedPaymentMethod === 'cash'
                                    ? 'bg-[#3B2314] text-[#FAEDCD] border-[#3B2314] shadow-sm'
                                    : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'"
                            >
                                Tunai (Cash)
                            </button>
                            <button
                                @click="selectedPaymentMethod = 'qris_manual'"
                                class="py-2.5 text-[10px] font-extrabold text-center rounded-xl border transition"
                                :class="selectedPaymentMethod === 'qris_manual'
                                    ? 'bg-[#3B2314] text-[#FAEDCD] border-[#3B2314] shadow-sm'
                                    : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'"
                            >
                                QRIS Manual
                            </button>
                            <button
                                @click="selectedPaymentMethod = 'qris_tokopay'"
                                class="py-2.5 text-[10px] font-extrabold text-center rounded-xl border transition"
                                :class="selectedPaymentMethod === 'qris_tokopay'
                                    ? 'bg-[#3B2314] text-[#FAEDCD] border-[#3B2314] shadow-sm'
                                    : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'"
                            >
                                QRIS Tokopay
                            </button>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider block">Catatan Tambahan Kasir</label>
                        <textarea
                            v-model="generalNotes"
                            rows="2"
                            placeholder="Catatan umum transaksi kasir..."
                            class="w-full bg-white text-gray-700 text-sm px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#D4A373] resize-none"
                        ></textarea>
                    </div>

                    <!-- Voucher Promo -->
                    <div class="space-y-1.5 pt-2 border-t border-dashed">
                        <label class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider block">Voucher Promo</label>
                        <div class="flex gap-2">
                            <input
                                v-model="voucherCodeInput"
                                type="text"
                                :disabled="appliedVoucher !== null"
                                placeholder="Masukkan kode voucher..."
                                class="flex-1 bg-white text-gray-700 text-xs px-3.5 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#D4A373] uppercase font-bold placeholder-normal disabled:bg-gray-100 disabled:text-gray-400"
                            />
                            <button
                                v-if="!appliedVoucher"
                                type="button"
                                @click="applyVoucher"
                                :disabled="isCheckingVoucher || !voucherCodeInput.trim()"
                                class="px-4 py-2.5 bg-[#3B2314] hover:bg-[#2A180E] disabled:bg-gray-300 text-white rounded-xl text-xs font-bold transition shrink-0 active:scale-95"
                            >
                                {{ isCheckingVoucher ? '...' : 'Terapkan' }}
                            </button>
                            <button
                                v-else
                                type="button"
                                @click="removeVoucher"
                                class="px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-xs font-bold transition shrink-0 border border-red-200 active:scale-95"
                            >
                                Hapus
                            </button>
                        </div>
                        <p v-if="voucherError" class="text-[10px] font-bold text-red-500 mt-1">
                            {{ voucherError }}
                        </p>
                        <p v-if="appliedVoucher" class="text-[10px] font-bold text-emerald-600 mt-1 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                            </svg>
                            Voucher "{{ appliedVoucher.name }}" berhasil diterapkan!
                        </p>
                    </div>

                    <!-- Receipt Summary -->
                    <div class="pt-4 border-t border-gray-200/80 space-y-2">
                        <div class="flex justify-between text-sm text-gray-500 font-bold">
                            <span>Subtotal</span>
                            <span>{{ formatPrice(subtotal) }}</span>
                        </div>
                        <div v-if="discountAmount > 0" class="flex justify-between text-sm text-emerald-600 font-bold">
                            <span>Diskon Voucher ({{ appliedVoucher?.code }})</span>
                            <span>-{{ formatPrice(discountAmount) }}</span>
                        </div>
                        <!-- Auto Promotions Row -->
                        <div
                            v-for="promo in appliedPromotionsList"
                            :key="promo.id"
                            class="flex justify-between text-sm text-emerald-600 font-bold"
                        >
                            <span>Potongan Promo ({{ promo.name }})</span>
                            <span>-{{ formatPrice(promo.discount) }}</span>
                        </div>
                        <div class="flex justify-between text-base text-gray-800 font-extrabold">
                            <span>Total Tagihan</span>
                            <span class="text-[#3B2314]">{{ formatPrice(total) }}</span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button
                        @click="submitCashierOrder"
                        :disabled="isSubmitting"
                        class="w-full py-4.5 bg-[#3B2314] hover:bg-[#D4A373] disabled:bg-gray-400 disabled:cursor-not-allowed text-[#FAEDCD] hover:text-[#3B2314] disabled:text-white rounded-xl text-sm font-extrabold tracking-widest uppercase transition flex items-center justify-center gap-2 shadow-md"
                    >
                        <span v-if="isSubmitting">Menyimpan...</span>
                        <span v-else class="flex items-center gap-2">
                            Buat Pesanan Kasir
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

        <!-- MODAL: Custom Notes -->
        <div v-if="showNoteModal && activeProductForNote" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden border border-[#D4A373]/20 transition-all duration-300">
                <div class="p-6 bg-[#3B2314] text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-extrabold tracking-wide">Tambahkan Catatan Menu</h3>
                        <p class="text-xs text-[#FAEDCD]/80 mt-0.5">{{ activeProductForNote.name }}</p>
                    </div>
                    <button
                        @click="showNoteModal = false"
                        class="text-white/60 hover:text-white transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="p-6 space-y-4">
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-[#3B2314] uppercase tracking-wider block">Catatan Masak / Request</label>
                        <textarea
                            v-model="currentItemNote"
                            rows="4"
                            placeholder="Contoh: Tanpa gula, Extra es batu, Sendok plastik..."
                            class="w-full bg-white text-gray-700 text-sm px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#D4A373] resize-none"
                        ></textarea>
                    </div>
                </div>
                
                <div class="p-6 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                    <button
                        @click="showNoteModal = false"
                        class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl text-xs font-bold transition"
                    >
                        Batal
                    </button>
                    <button
                        @click="handleSaveNote"
                        class="px-5 py-2.5 bg-[#3B2314] hover:bg-[#D4A373] text-[#FAEDCD] hover:text-[#3B2314] rounded-xl text-xs font-extrabold transition shadow-md"
                    >
                        Simpan Catatan
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: Success Receipt -->
        <div v-if="showSuccessModal && createdOrder" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full overflow-hidden border border-[#D4A373]/20 transition-all duration-300">
                <div class="p-6 bg-green-700 text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-extrabold tracking-wide">Pemesanan Kasir Berhasil!</h3>
                        <p class="text-xs text-white/80 mt-0.5">Struk digital kasir telah berhasil diterbitkan.</p>
                    </div>
                    <button
                        @click="showSuccessModal = false"
                        class="text-white/60 hover:text-white transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- Receipt Body -->
                <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto font-mono text-xs text-gray-700 leading-relaxed">
                    <div class="text-center border-b border-dashed border-gray-300 pb-4 space-y-1">
                        <h4 class="text-base font-extrabold text-gray-800 uppercase tracking-widest">ZUNOI CAFFE</h4>
                        <p>Laragon Local Server System</p>
                        <p class="text-[10px] text-gray-400">Order ID: #{{ createdOrder.id }}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-y-1 border-b border-dashed border-gray-300 pb-4">
                        <span class="text-gray-400">Tanggal:</span>
                        <span class="text-right font-bold">{{ new Date(createdOrder.created_at).toLocaleString('id-ID') }}</span>
                        
                        <span class="text-gray-400">Pelanggan:</span>
                        <span class="text-right font-bold">{{ createdOrder.customer_name }}</span>
                        
                        <span class="text-gray-400">Tipe:</span>
                        <span class="text-right font-bold">{{ createdOrder.order_type === 'dine_in' ? 'Dine In' : 'Takeaway' }}</span>
                        
                        <span v-if="createdOrder.table" class="text-gray-400">Meja:</span>
                        <span v-if="createdOrder.table" class="text-right font-bold">{{ createdOrder.table.table_name }}</span>
                        
                        <span class="text-gray-400">Pembayaran:</span>
                        <span class="text-right font-bold uppercase">{{ createdOrder.payment_method }}</span>
                        
                        <span class="text-gray-400">Status Pembayaran:</span>
                        <span class="text-right font-bold uppercase text-green-600">{{ createdOrder.payment_status }}</span>
                    </div>

                    <!-- Items -->
                    <div class="border-b border-dashed border-gray-300 pb-4 space-y-3">
                        <div v-for="item in createdOrder.items" :key="item.id" class="space-y-0.5">
                            <div class="flex justify-between font-bold">
                                <span>{{ item.product?.name }} x{{ item.quantity }}</span>
                                <span>{{ formatPrice(item.price_at_sale * item.quantity) }}</span>
                            </div>
                            <p v-if="item.notes" class="text-gray-400 text-[10px] italic pl-2">Catatan: {{ item.notes }}</p>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between font-bold">
                            <span>Subtotal:</span>
                            <span>{{ formatPrice(createdOrder.items?.reduce((sum: number, item: any) => sum + (item.price_at_sale * item.quantity), 0) || createdOrder.total_price) }}</span>
                        </div>
                        <div v-if="createdOrder.discount_amount > 0" class="flex justify-between text-emerald-600 font-bold">
                            <span>Voucher ({{ createdOrder.voucher_code || 'Voucher' }}):</span>
                            <span>-{{ formatPrice(createdOrder.discount_amount) }}</span>
                        </div>
                        <div v-if="createdOrder.promo_discount_amount > 0" class="flex justify-between text-emerald-600 font-bold">
                            <span>Potongan Promo:</span>
                            <span>-{{ formatPrice(createdOrder.promo_discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-extrabold text-gray-800 border-t border-dashed border-gray-300 pt-2">
                            <span>Total Akhir:</span>
                            <span>{{ formatPrice(createdOrder.total_price) }}</span>
                        </div>
                    </div>

                    <div class="text-center pt-4 text-[10px] text-gray-400">
                        <p>Terima kasih atas kunjungan Anda!</p>
                        <p>Zunoi Caffe - Local POS System</p>
                    </div>
                </div>
                
                <div class="p-6 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                    <button
                        @click="showSuccessModal = false"
                        class="w-full py-3.5 bg-green-700 hover:bg-green-800 text-white rounded-xl text-xs font-bold tracking-widest uppercase transition flex items-center justify-center gap-2 shadow-md"
                    >
                        Tutup Struk
                    </button>
                </div>
            </div>
        </div>
    </ZunoiAdminLayout>
</template>

<style scoped>
/* Remove custom scrollbars for category tabs */
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
