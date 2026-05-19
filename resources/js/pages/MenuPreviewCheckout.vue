<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    qris_manual_url: String,
});

const isQrZoomed = ref(false);

const form = ref({
    customer_name: '',
    customer_phone: '',
    order_type: 'dine_in',
    payment_method: 'qris_tokopay',
    cart_items: [],
    payment_proof: null,
    notes: '',
});

let originalBgColor = '';

onMounted(() => {
    const savedCart = localStorage.getItem('zunoi_preview_cart');

    if (savedCart) {
        form.value.cart_items = JSON.parse(savedCart);
    }

    originalBgColor = document.documentElement.style.backgroundColor;
    document.documentElement.style.backgroundColor = '#3B2314';
});

onUnmounted(() => {
    document.documentElement.style.backgroundColor = originalBgColor;
});

const voucherCodeInput = ref('');
const appliedVoucher = ref(null);
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
            subtotal: cartTotal.value
        });

        if (response.data.success) {
            appliedVoucher.value = response.data.voucher;
            discountAmount.value = response.data.discount_amount;
            voucherError.value = '';
            triggerToast('Voucher berhasil diterapkan!');
        }
    } catch (error) {
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

const cartTotal = computed(() => {
    return form.value.cart_items.reduce(
        (total, item) => total + item.price * item.quantity,
        0,
    );
});

const taxTotal = computed(() => {
    return 0;
});

const finalTotal = computed(() => {
    return Math.max(0, cartTotal.value - discountAmount.value);
});

// State Custom Toast Notification
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('success');

const triggerToast = (message, type = 'success') => {
    toastMessage.value = message;
    toastType.value = type;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 4500);
};

const handleFileChange = (e) => {
    form.value.payment_proof = e.target.files[0];
};

const submitOrder = () => {
    form.value.post('/order/store-cashier', {
        onSuccess: () => {
            localStorage.removeItem('zunoi_preview_cart');
        },
    });
};

const removeCartItem = (index) => {
    form.value.cart_items.splice(index, 1);
    localStorage.setItem(
        'zunoi_preview_cart',
        JSON.stringify(form.value.cart_items),
    );
};

const defaultAdditions = [
    { name: 'Gula', price: 0 },
    { name: 'Es Batu', price: 0 },
    { name: 'Whipped Cream', price: 5000 },
    { name: 'Espresso Shot', price: 7000 },
];

const getAdditionsForCartItem = (item) => {
    if (item.additions && item.additions.length > 0) {
        return JSON.parse(JSON.stringify(item.additions)).map((a) => {
            if (a.selected === undefined) {
                a.selected = a.selection !== null && a.selection !== undefined;
            }

            return a;
        });
    }

    // Fallback parsing from notes
    const parsedAdditions = defaultAdditions.map((a) => {
        let selected = false;

        if (item.notes) {
            const regex = new RegExp(a.name, 'i');
            selected = regex.test(item.notes);
        }

        return { ...a, selected };
    });

    return parsedAdditions;
};

const editingIndex = ref(-1);
const isModalOpen = ref(false);
const selectedProduct = ref(null);
const selectedQuantity = ref(1);
const additions = ref([]);

const computedTotalPrice = computed(() => {
    if (!selectedProduct.value) {
return 0;
}

    const base = parseInt(selectedProduct.value.price) * selectedQuantity.value;

    let addonsTotal = 0;
    additions.value.forEach((add) => {
        if (add.selected) {
            addonsTotal += parseInt(add.price) * selectedQuantity.value;
        }
    });

    return base + addonsTotal;
});

const editCartItem = (index) => {
    const item = form.value.cart_items[index];
    editingIndex.value = index;
    selectedProduct.value = {
        id: item.id,
        name: item.name,
        price: item.basePrice || item.price,
        image: item.image,
        description: '',
    };
    selectedQuantity.value = item.quantity;
    additions.value = getAdditionsForCartItem(item);
    isModalOpen.value = true;
};

const closeSelectionModal = () => {
    isModalOpen.value = false;
    selectedProduct.value = null;
    editingIndex.value = -1;
};

const saveCartItem = () => {
    if (editingIndex.value === -1) {
return;
}

    const selectedAddons = additions.value.filter((a) => a.selected);

    let addonsTotal = 0;
    selectedAddons.forEach((add) => {
        addonsTotal += parseInt(add.price);
    });

    const basePrice = selectedProduct.value.price;
    const unitPrice = basePrice + addonsTotal;

    const notesStr =
        selectedAddons.length > 0
            ? '+ ' +
              selectedAddons
                  .map((a) => {
                      const priceText =
                          a.price > 0
                              ? ` (+Rp ${parseInt(a.price).toLocaleString('id-ID')})`
                              : '';

                      return `${a.name}${priceText}`;
                  })
                  .join(', ')
            : null;

    // Update the item in cart_items
    const item = form.value.cart_items[editingIndex.value];
    item.quantity = selectedQuantity.value;
    item.notes = notesStr;
    item.price = unitPrice;
    item.basePrice = basePrice;
    item.addonPrice = addonsTotal;
    item.additions = JSON.parse(JSON.stringify(additions.value));

    // Save to localStorage
    localStorage.setItem(
        'zunoi_preview_cart',
        JSON.stringify(form.value.cart_items),
    );

    closeSelectionModal();
};

const submitPreviewOrder = () => {
    triggerToast(
        'Mode Preview: Pesanan berhasil dibuat! Anda akan dialihkan ke rincian invoice.',
        'success',
    );

    // Clear preview cart
    localStorage.removeItem('zunoi_preview_cart');

    setTimeout(() => {
        // Send to success preview route with simulated data
        router.visit('/dashboard/menu-preview/success', {
            method: 'get',
            data: {
                customer_name: form.value.customer_name || 'Pelanggan Demo',
                order_type: form.value.order_type,
                payment_method: form.value.payment_method,
                total_price: finalTotal.value,
                items: JSON.stringify(form.value.cart_items),
                voucher_code: appliedVoucher.value ? appliedVoucher.value.code : '',
                discount_amount: discountAmount.value,
            },
        });
    }, 1500);
};
</script>

<template>
    <Head title="Preview Checkout - Zunoi Caffe" />

    <!-- Main Customer Area -->
    <div class="relative z-0 flex min-h-screen flex-col font-sans">
        <!-- Fixed Background Gradient (Seals tablet scroll behavior) -->
        <div
            class="pointer-events-none fixed inset-x-0 -top-24 bottom-0 -z-10 bg-gradient-to-b from-[#FAEDCD] via-white to-white"
        ></div>
        <!-- Preview Banner -->
        <div
            class="z-50 bg-red-500 py-1.5 text-center text-xs font-black tracking-widest text-white uppercase shadow-sm"
        >
            Mode Preview - Tampilan Pelanggan
        </div>

        <!-- Sticky Header -->
        <header class="glass-header sticky z-40 pt-[22px] pb-3 text-[#FAEDCD]">
            <div
                class="mx-auto flex max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8"
            >
                <Link
                    :href="'/dashboard/menu-preview'"
                    class="shrink-0 transform rounded-lg bg-[#FAEDCD] p-2 text-[#3B2314] transition hover:scale-105 active:scale-95"
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
                            d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
                        />
                    </svg>
                </Link>
                <h1 class="text-xl font-black tracking-wide">
                    Checkout Pesanan (Preview)
                </h1>
            </div>
            <!-- Animated Gradient Border -->
            <div class="header-border"></div>
        </header>

        <!-- Scrollable Form Area -->
        <main
            class="mx-auto w-full max-w-7xl flex-1 px-4 pt-4 pb-24 sm:px-6 lg:px-8"
        >
            <form
                @submit.prevent="submitPreviewOrder"
                class="grid grid-cols-1 items-start gap-4 md:grid-cols-2 md:gap-6"
            >
                <!-- Left Column: Order Type, Customer Details, Order Details, Notes -->
                <div class="space-y-4">
                    <!-- Customer Details -->
                    <div
                        class="space-y-4 rounded-2xl border border-[#D4A373]/40 bg-white p-4 shadow-sm"
                    >
                        <h2
                            class="mb-1.5 flex items-center gap-1.5 text-sm font-extrabold text-[#3B2314]"
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
                                    d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"
                                />
                            </svg>
                            Informasi Pemesan (Demo)
                        </h2>
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold text-gray-600"
                                >Nama Lengkap</label
                            >
                            <input
                                v-model="form.customer_name"
                                type="text"
                                class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs text-[#3B2314] shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                                required
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold text-gray-600"
                                >Nomor WhatsApp Aktif</label
                            >
                            <input
                                v-model="form.customer_phone"
                                type="text"
                                placeholder="Contoh: 08123456789"
                                class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs text-[#3B2314] shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                                required
                            />
                            <p
                                class="mt-1.5 text-[10px] leading-normal font-medium text-gray-500 italic"
                            >
                                *Invoice digital akan dikirimkan ke nomor
                                whatsapp yang diinput, mohon untuk menginput
                                nomor whatsapp aktif anda.
                            </p>
                        </div>
                    </div>

                    <!-- Order Type Toggle -->
                    <div
                        class="space-y-3 rounded-2xl border border-[#D4A373]/40 bg-white p-4 shadow-sm"
                    >
                        <h2
                            class="mb-1.5 flex items-center gap-1.5 text-sm font-extrabold text-[#3B2314]"
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
                                    d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.03 0 1.9.693 2.166 1.638m-7.377 2.24c-.09.53-.139 1.078-.139 1.638 0 1.22.496 2.323 1.3 3.123m0 0L9 12"
                                />
                            </svg>
                            Pilih Metode Pemesanan Anda
                        </h2>
                        <div
                            class="flex gap-2 rounded-xl border border-[#D4A373]/5 bg-gray-50 p-1"
                        >
                            <button
                                type="button"
                                @click="form.order_type = 'dine_in'"
                                :class="{
                                    'border-[#3B2314] bg-[#3B2314] text-white':
                                        form.order_type === 'dine_in',
                                    'border-gray-300 bg-white text-gray-500':
                                        form.order_type !== 'dine_in',
                                }"
                                class="flex-1 rounded-lg border py-2.5 text-xs font-bold shadow-sm transition-all duration-300"
                            >
                                Dine In
                            </button>
                            <button
                                type="button"
                                @click="form.order_type = 'takeaway'"
                                :class="{
                                    'border-[#3B2314] bg-[#3B2314] text-white':
                                        form.order_type === 'takeaway',
                                    'border-gray-300 bg-white text-gray-500':
                                        form.order_type !== 'takeaway',
                                }"
                                class="flex-1 rounded-lg border py-2.5 text-xs font-bold shadow-sm transition-all duration-300"
                            >
                                Takeaway
                            </button>
                        </div>
                    </div>

                    <!-- Detail Pesanan -->
                    <div
                        class="space-y-3 rounded-2xl border border-[#D4A373]/40 bg-white p-4 shadow-sm"
                    >
                        <h2
                            class="mb-1.5 flex items-center gap-1.5 text-sm font-extrabold text-[#3B2314]"
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
                                    d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"
                                />
                            </svg>
                            Detail Pesanan
                        </h2>

                        <div
                            v-if="form.cart_items.length > 0"
                            class="space-y-3"
                        >
                            <div
                                v-for="(item, index) in form.cart_items"
                                :key="item.id + index"
                                class="flex flex-col gap-2 rounded-xl border border-gray-100 bg-gray-50 p-3"
                            >
                                <!-- Top Section: Image, Name, Price, and Actions -->
                                <div class="flex w-full items-start gap-3">
                                    <img
                                        :src="
                                            item.image ||
                                            'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=400'
                                        "
                                        alt="Product"
                                        class="h-12 w-12 shrink-0 rounded-lg border object-cover"
                                    />
                                    <div
                                        class="flex flex-1 flex-col justify-center text-left"
                                    >
                                        <p
                                            class="text-xs font-bold text-gray-800"
                                        >
                                            {{ item.name }}
                                        </p>
                                        <div class="mt-0.5 flex flex-col">
                                            <p
                                                class="text-[10px] font-semibold text-gray-400"
                                            >
                                                {{ item.quantity }}x &bull; Rp
                                                {{
                                                    (
                                                        item.basePrice ||
                                                        item.price
                                                    ).toLocaleString('id-ID')
                                                }}
                                            </p>
                                            <p
                                                class="mt-0.5 text-[11px] font-extrabold text-[#3B2314]"
                                            >
                                                = Rp
                                                {{
                                                    (
                                                        item.price *
                                                        item.quantity
                                                    ).toLocaleString('id-ID')
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <!-- Action Buttons -->
                                    <div
                                        class="ml-2 flex shrink-0 gap-1.5 pt-0.5"
                                    >
                                        <button
                                            @click="editCartItem(index)"
                                            type="button"
                                            class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-[#D4A373] hover:bg-[#FAEDCD] hover:text-[#D4A373] active:scale-95"
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
                                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            @click="removeCartItem(index)"
                                            type="button"
                                            class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-red-200 hover:bg-red-50 hover:text-red-500 active:scale-95"
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
                                                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <!-- Bottom Section: Full Width Addons -->
                                <div
                                    v-if="item.notes"
                                    class="mt-0.5 w-full border-t border-gray-200/60 pt-2"
                                >
                                    <p
                                        class="text-[11px] leading-snug font-black text-[#D4A373]"
                                    >
                                        {{ item.notes }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="rounded-xl border border-dashed border-gray-200 bg-gray-50/50 px-4 py-6 text-center"
                        >
                            <p
                                class="text-xs leading-relaxed font-semibold text-gray-400"
                            >
                                Keranjang belanja Anda masih kosong. Silakan
                                pilih menu pesanan Anda terlebih dahulu.
                            </p>
                        </div>

                        <!-- Voucher Promo -->
                        <div v-if="form.cart_items.length > 0" class="space-y-2 border-t border-dashed pt-3">
                            <label class="text-[11px] font-black text-gray-500 uppercase tracking-wider block">Voucher Promo</label>
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

                        <!-- Price Details Summary -->
                        <div
                            v-if="form.cart_items.length > 0"
                            class="space-y-2 border-t border-gray-100 pt-3 text-xs text-[#3B2314]"
                        >
                            <div
                                class="flex justify-between font-medium text-gray-600"
                            >
                                <span>Subtotal</span>
                                <span
                                    >Rp
                                    {{
                                        cartTotal.toLocaleString('id-ID')
                                    }}</span
                                >
                            </div>
                            <div
                                v-if="discountAmount > 0"
                                class="flex justify-between font-bold text-emerald-600"
                            >
                                <span>Diskon Voucher ({{ appliedVoucher?.code }})</span>
                                <span
                                    >-Rp
                                    {{
                                        discountAmount.toLocaleString('id-ID')
                                    }}</span
                                >
                            </div>
                            <div
                                class="flex justify-between border-t border-dashed pt-1.5 text-sm font-black text-[#3B2314]"
                            >
                                <span>Total Pembayaran</span>
                                <span
                                    >Rp
                                    {{
                                        finalTotal.toLocaleString('id-ID')
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Pesanan -->
                    <div
                        class="space-y-3 rounded-2xl border border-[#D4A373]/40 bg-white p-4 shadow-sm"
                    >
                        <h2
                            class="mb-1.5 flex items-center gap-1.5 text-sm font-extrabold text-[#3B2314]"
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
                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                />
                            </svg>
                            Catatan untuk Barista
                        </h2>
                        <div>
                            <input
                                v-model="form.notes"
                                type="text"
                                placeholder="Beri catatan di sini"
                                class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-xs text-[#3B2314] placeholder-[#3B2314]/50 shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                            />
                        </div>
                    </div>
                </div>

                <!-- Right Column: Payment Method & Submit -->
                <div class="space-y-4">
                    <!-- Payment Method -->
                    <div
                        class="space-y-3 rounded-2xl border border-[#D4A373]/40 bg-white p-4 shadow-sm"
                    >
                        <h2
                            class="mb-1.5 flex items-center gap-1.5 text-sm font-extrabold text-[#3B2314]"
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
                                    d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"
                                />
                            </svg>
                            Metode Pembayaran
                        </h2>

                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-300 p-3 text-xs text-[#3B2314] transition duration-200"
                            :class="{
                                'border-[#D4A373] bg-[#FAEDCD]/30':
                                    form.payment_method === 'qris_tokopay',
                            }"
                        >
                            <input
                                type="radio"
                                v-model="form.payment_method"
                                value="qris_tokopay"
                                class="text-[#3B2314] accent-[#3B2314] focus:ring-[#3B2314]"
                            />
                            <span class="font-extrabold text-[#3B2314]"
                                >QRIS Otomatis (Tokopay)</span
                            >
                        </label>

                        <label
                            class="flex cursor-pointer flex-col gap-2 rounded-xl border border-gray-300 p-3 text-xs text-[#3B2314] transition duration-200"
                            :class="{
                                'border-[#D4A373] bg-[#FAEDCD]/30':
                                    form.payment_method === 'qris_manual',
                            }"
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    type="radio"
                                    v-model="form.payment_method"
                                    value="qris_manual"
                                    class="text-[#3B2314] accent-[#3B2314] focus:ring-[#3B2314]"
                                />
                                <span class="font-extrabold text-[#3B2314]"
                                    >QRIS Toko (Manual Verifikasi)</span
                                >
                            </div>
                            <div
                                v-if="form.payment_method === 'qris_manual'"
                                class="mt-2 space-y-3 rounded-xl border border-gray-100 bg-white p-3 text-center"
                            >
                                <p class="mb-1 text-[10px] text-gray-500">
                                    Scan QR di bawah ini, lalu unggah bukti
                                    pembayaran.
                                </p>

                                <div
                                    class="group relative inline-block cursor-zoom-in"
                                    @click="isQrZoomed = true"
                                >
                                    <img
                                        :src="
                                            qris_manual_url ||
                                            'https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg'
                                        "
                                        alt="QRIS Toko"
                                        class="mx-auto h-28 w-28 rounded-xl border p-1 transition hover:opacity-90"
                                    />
                                    <div
                                        class="absolute inset-0 flex items-center justify-center rounded-xl bg-[#3B2314]/30 opacity-0 transition duration-200 group-hover:opacity-100"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="2.5"
                                            stroke="currentColor"
                                            class="h-5 w-5 text-white"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637zM10.5 7.5v6m3-3h-6"
                                            />
                                        </svg>
                                    </div>
                                </div>
                                <p
                                    class="mt-1 text-[9px] font-semibold text-gray-400"
                                >
                                    Klik QR untuk memperbesar
                                </p>

                                <div class="mt-3 text-left">
                                    <label
                                        class="mb-1 block text-[10px] font-black text-gray-600"
                                        >Unggah Bukti Pembayaran
                                        (Struk/Screenshot)</label
                                    >
                                    <input
                                        type="file"
                                        @change="handleFileChange"
                                        accept="image/*"
                                        class="w-full text-[10px] text-gray-500 file:mr-2 file:cursor-pointer file:rounded-lg file:border-0 file:bg-[#FAEDCD] file:px-3 file:py-1.5 file:text-[10px] file:font-black file:text-[#3B2314] hover:file:bg-[#D4A373]"
                                        :required="
                                            form.payment_method ===
                                            'qris_manual'
                                        "
                                    />
                                </div>
                            </div>
                        </label>

                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-300 p-3 text-xs text-[#3B2314] transition duration-200"
                            :class="{
                                'border-[#D4A373] bg-[#FAEDCD]/30':
                                    form.payment_method === 'cashier',
                            }"
                        >
                            <input
                                type="radio"
                                v-model="form.payment_method"
                                value="cashier"
                                class="text-[#3B2314] accent-[#3B2314] focus:ring-[#3B2314]"
                            />
                            <span class="font-extrabold text-[#3B2314]"
                                >Bayar Langsung di Kasir</span
                            >
                        </label>
                    </div>

                    <!-- Animated Border Wrapper -->
                    <div
                        class="relative overflow-hidden rounded-2xl p-[3px] shadow-[0_5px_20px_rgba(21,11,5,0.6)] transition-all duration-300 hover:scale-[1.02] active:scale-95"
                        :class="
                            form.cart_items.length === 0
                                ? 'cursor-not-allowed opacity-50'
                                : ''
                        "
                    >
                        <!-- Rotating/Abstract Gradient Border -->
                        <div
                            class="animated-border absolute inset-0 opacity-50"
                        ></div>

                        <!-- Main Button -->
                        <button
                            type="submit"
                            class="shine-effect relative flex w-full items-center justify-between gap-3 overflow-hidden rounded-[13px] bg-[#25150B] p-3.5 pl-5"
                            :disabled="form.cart_items.length === 0"
                        >
                            <!-- Dark Brown Bottom-Right Radial Gradient -->
                            <div
                                class="pointer-events-none absolute -right-10 -bottom-10 h-24 w-24 rounded-full bg-[#150B05]/95 blur-md"
                            ></div>

                            <!-- Content -->
                            <div
                                class="relative z-10 flex items-center gap-2.5"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2.5"
                                    stroke="currentColor"
                                    class="h-4 w-4 text-[#FAEDCD]"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"
                                    />
                                </svg>
                                <span
                                    class="text-xs font-black tracking-wider text-[#FAEDCD] uppercase"
                                    >Bayar Sekarang</span
                                >
                            </div>
                            <div
                                class="relative z-10 flex items-center justify-center rounded-xl bg-[#D4A373] px-4 py-2 text-xs font-black text-[#3B2314]"
                            >
                                {{
                                    form.cart_items.reduce(
                                        (total, item) => total + item.quantity,
                                        0,
                                    )
                                }}
                                Item
                            </div>
                        </button>
                    </div>
                </div>
            </form>
        </main>

        <!-- QR Code Zoom Modal -->
        <Transition
            enter-active-class="ease-out duration-300 transition"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="ease-in duration-200 transition"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isQrZoomed"
                class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-black/80 p-6 backdrop-blur-sm"
                @click.self="isQrZoomed = false"
            >
                <div
                    class="relative max-w-[90%] scale-100 rounded-2xl bg-white p-5 shadow-2xl transition-transform duration-300"
                >
                    <button
                        type="button"
                        @click="isQrZoomed = false"
                        class="absolute -top-3 -right-3 rounded-full bg-red-600 p-2 text-white shadow-lg transition hover:scale-105 active:scale-95"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="3"
                            stroke="currentColor"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                    <img
                        :src="
                            qris_manual_url ||
                            'https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg'
                        "
                        alt="QRIS Toko Zoomed"
                        class="mx-auto h-64 w-64 rounded-xl border p-1"
                    />
                    <p
                        class="mt-4 text-center text-xs font-black text-[#3B2314]"
                    >
                        Scan QRIS Toko Zunoi
                    </p>
                    <p class="mt-1 text-center text-[10px] text-gray-400">
                        Silakan scan kode QR di atas untuk menyelesaikan
                        transfer.
                    </p>
                </div>
            </div>
        </Transition>
        <!-- Custom Toast Notification Popup -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showToast"
                class="pointer-events-auto fixed top-6 right-6 z-50 flex w-full max-w-sm overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-[0_20px_50px_rgba(0,0,0,0.15)]"
            >
                <div class="flex w-full items-center justify-between gap-4 p-4">
                    <div class="flex items-center gap-3">
                        <!-- Icon Success -->
                        <div
                            v-if="toastType === 'success'"
                            class="rounded-xl bg-green-50 p-2 text-green-600"
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
                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                />
                            </svg>
                        </div>
                        <!-- Icon Error -->
                        <div
                            v-if="toastType === 'error'"
                            class="rounded-xl bg-red-50 p-2 text-red-600"
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
                                    d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-black text-gray-800">
                                {{ toastMessage }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="showToast = false"
                        class="shrink-0 text-gray-400 transition hover:text-gray-600"
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
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Selection Modal with smooth slide animations for Edit -->
        <Transition name="modal-slide">
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-end justify-center"
            >
                <!-- Backdrop overlay -->
                <div
                    @click="closeSelectionModal"
                    class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
                ></div>

                <!-- Modal content container (Minimalist Glassmorphism) -->
                <div
                    class="relative flex h-[85vh] max-h-[900px] w-full flex-col overflow-hidden rounded-t-[2.5rem] bg-gradient-to-b from-[#d5b497]/95 to-[#f3e6d8]/95 text-[#3B2314] shadow-[0_-10px_40px_rgba(0,0,0,0.2)] backdrop-blur-2xl"
                >
                    <!-- Animated Gradient Border -->
                    <div
                        class="animated-gradient-border pointer-events-none absolute -top-[2px] -right-[2px] bottom-0 -left-[2px] z-50 rounded-t-[2.5rem] pt-[6px]"
                    ></div>

                    <!-- Content area -->
                    <div class="flex-1 overflow-y-auto">
                        <!-- Top Section -->
                        <div class="p-8 pb-4">
                            <!-- Back Button -->
                            <button
                                type="button"
                                @click="closeSelectionModal"
                                class="mb-4 flex h-8 items-center justify-center gap-1.5 rounded-[10px] border border-white/40 bg-white/50 px-3.5 text-[#3B2314] shadow-sm backdrop-blur-md transition-all duration-300 ease-out hover:bg-white/70 active:scale-75"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                                    />
                                </svg>
                                <span
                                    class="text-[13px] font-extrabold tracking-wide"
                                    >Back</span
                                >
                            </button>

                            <!-- Item Info -->
                            <div class="flex items-start gap-5">
                                <img
                                    :src="
                                        selectedProduct?.image ||
                                        'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=400'
                                    "
                                    class="h-24 w-24 shrink-0 rounded-2xl border border-[#3B2314]/10 object-cover shadow-sm"
                                />
                                <div class="flex flex-col pt-1">
                                    <h4
                                        class="text-2xl leading-tight font-extrabold text-[#3B2314]"
                                    >
                                        {{ selectedProduct?.name }}
                                    </h4>
                                </div>
                            </div>

                            <!-- Price Block (No Card) -->
                            <div
                                class="mt-4 flex items-center justify-between px-1"
                            >
                                <span
                                    class="text-sm font-bold text-[#3B2314]/70"
                                    >Harga</span
                                >
                                <p class="text-lg font-black text-[#3B2314]">
                                    Rp
                                    {{
                                        parseInt(
                                            selectedProduct?.price,
                                        ).toLocaleString('id-ID')
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Add-ons Section -->
                        <div
                            v-if="additions && additions.length > 0"
                            class="space-y-3 px-8 pt-4 pb-8"
                        >
                            <h5
                                class="text-sm font-extrabold tracking-wide text-[#3B2314]"
                            >
                                Pilih Add-on
                            </h5>
                            <div class="space-y-2.5">
                                <div
                                    v-for="addition in additions"
                                    :key="addition.name"
                                    @click="
                                        addition.selected = !addition.selected
                                    "
                                    class="flex cursor-pointer items-center justify-between rounded-2xl border bg-white/30 px-5 py-3.5 shadow-sm backdrop-blur-md transition-all duration-300 select-none"
                                    :class="
                                        addition.selected
                                            ? 'translate-x-1 border-[#3B2314] bg-white/70 shadow-md'
                                            : 'border-white/40 hover:border-white/60 hover:bg-white/50'
                                    "
                                >
                                    <div class="flex items-center gap-3">
                                        <!-- Custom Checkbox -->
                                        <div
                                            class="pointer-events-none flex h-5 w-5 items-center justify-center rounded-md border transition-all duration-300"
                                            :class="
                                                addition.selected
                                                    ? 'border-[#3B2314] bg-[#3B2314]'
                                                    : 'border-gray-300 bg-white'
                                            "
                                        >
                                            <svg
                                                v-if="addition.selected"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="3"
                                                stroke="currentColor"
                                                class="h-3.5 w-3.5 text-white"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="m4.5 12.75 6 6 9-13.5"
                                                />
                                            </svg>
                                        </div>
                                        <span
                                            class="text-base font-bold text-[#3B2314]"
                                            >{{ addition.name }}</span
                                        >
                                    </div>

                                    <span
                                        v-if="addition.price > 0"
                                        class="text-xs font-black text-[#D4A373]"
                                    >
                                        +Rp
                                        {{
                                            addition.price.toLocaleString(
                                                'id-ID',
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Bar -->
                    <div
                        class="flex w-full items-center justify-between border-t border-[#3B2314]/10 bg-[#F9EFE3]/90 px-6 py-4 shadow-[0_-4px_15px_rgba(0,0,0,0.05)] backdrop-blur-2xl"
                    >
                        <div class="flex flex-col items-start">
                            <span
                                class="mb-1 text-[11px] leading-none font-bold tracking-widest text-[#3B2314]/60 uppercase"
                                >Total</span
                            >
                            <span
                                class="text-xl leading-none font-black text-[#3B2314]"
                                >Rp
                                {{
                                    computedTotalPrice.toLocaleString('id-ID')
                                }}</span
                            >
                        </div>
                        <div class="flex items-center gap-4">
                            <button
                                type="button"
                                @click="saveCartItem"
                                class="rounded-xl bg-[#3B2314] px-8 py-3 text-center text-[14px] leading-tight font-bold text-[#FAEDCD] shadow-md transition hover:bg-[#2A180E] active:scale-95"
                            >
                                Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.glass-header {
    position: sticky;
    top: -10px; /* Pulls header up to cover safe area/subpixel gaps when stuck */
    margin-top: -10px; /* Pulls header up in normal flow */
    z-index: 40;
    background: transparent;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    box-shadow:
        0 10px 30px 0 rgba(59, 35, 20, 0.25),
        inset 0 0 8px 0 rgba(255, 255, 255, 0.25),
        inset 0 1px 0 0 rgba(255, 255, 255, 0.3),
        inset 0 -1px 0 0 rgba(0, 0, 0, 0.3);
}

.header-border {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 4.5px;
    background: linear-gradient(
        90deg,
        #1e1008 0%,
        #e1af7d 25%,
        #3b2314 50%,
        #faedcd 75%,
        #1e1008 100%
    );
    background-size: 200% 100%;
    animation: border-flow 8s linear infinite;
    opacity: 0.9;
}

@keyframes border-flow {
    0% {
        background-position: 0% 0%;
    }
    100% {
        background-position: -200% 0%;
    }
}

.glass-header::before {
    content: '';
    position: absolute;
    top: -100px; /* Overscroll bleed: extends background 100px above header */
    left: 0;
    right: 0;
    bottom: 0;
    z-index: -2;
    background: linear-gradient(
        -45deg,
        rgba(36, 21, 12, 0.8) 0%,
        rgba(85, 52, 30, 0.85) 30%,
        rgba(125, 85, 55, 0.75) 60%,
        rgba(46, 27, 16, 0.85) 100%
    );
    background-size: 300% 300%;
    animation: abstract-gradient 12s ease infinite;
}

.glass-header::after {
    content: '';
    position: absolute;
    top: -100px; /* Overscroll bleed: extends background 100px above header */
    left: 0;
    right: 0;
    bottom: 0;
    z-index: -1;
    background: linear-gradient(
        to bottom,
        rgba(26, 15, 8, 0.95) 0%,
        rgba(36, 21, 12, 0.3) 100%
    );
    pointer-events: none;
}

@keyframes abstract-gradient {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

@keyframes borderGradient {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

@keyframes shine {
    0% {
        transform: translateX(-150%) skewX(-25deg);
    }
    50% {
        transform: translateX(150%) skewX(-25deg);
    }
    100% {
        transform: translateX(150%) skewX(-25deg);
    }
}

.animated-border {
    background: linear-gradient(270deg, #3b2314, #1c0f07, #25150b, #523522);
    background-size: 400% 400%;
    animation: borderGradient 6s ease infinite;
}

.shine-effect::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 200%;
    height: 100%;
    background: linear-gradient(
        90deg,
        rgba(255, 255, 255, 0) 0%,
        rgba(255, 255, 255, 0.3) 50%,
        rgba(255, 255, 255, 0) 100%
    );
    transform: translateX(-150%) skewX(-25deg);
    animation: shine 4.5s infinite ease-in-out;
    pointer-events: none;
}

/* Modal Slide up/down transition */
.modal-slide-enter-active,
.modal-slide-leave-active {
    transition: opacity 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.modal-slide-enter-active .relative,
.modal-slide-leave-active .relative {
    transition:
        transform 0.5s cubic-bezier(0.25, 1, 0.5, 1),
        opacity 0.5s ease;
}

.modal-slide-enter-from {
    opacity: 0;
}
.modal-slide-enter-from .relative {
    transform: translateY(100vh);
    opacity: 0;
}

.modal-slide-leave-to {
    opacity: 0;
}
.modal-slide-leave-to .relative {
    transform: translateY(100vh);
    opacity: 0;
}

@keyframes gradientMove {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

.animated-gradient-border {
    background: linear-gradient(60deg, #3b2314, #d4a373, #5c3a21, #faedcd);
    background-size: 300% 300%;
    animation: gradientMove 4s ease infinite;
    -webkit-mask:
        linear-gradient(#fff 0 0) content-box,
        linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
}
</style>
