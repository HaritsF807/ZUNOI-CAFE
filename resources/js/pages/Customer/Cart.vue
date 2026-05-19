<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    qris_manual_url: String,
});

const isQrZoomed = ref(false);

const form = useForm({
    customer_name: '',
    customer_phone: '',
    order_type: 'dine_in',
    payment_method: 'qris_tokopay',
    cart_items: [],
    payment_proof: null,
    notes: '',
});

onMounted(() => {
    const savedCart = localStorage.getItem('zunoi_cart');
    if (savedCart) {
        form.cart_items = JSON.parse(savedCart);
    }
});

const cartTotal = computed(() => {
    return form.cart_items.reduce(
        (total, item) => total + item.price * item.quantity,
        0,
    );
});

const taxTotal = computed(() => {
    return Math.round(cartTotal.value * 0.02);
});

const finalTotal = computed(() => {
    return cartTotal.value + taxTotal.value;
});

const handleFileChange = (e) => {
    form.payment_proof = e.target.files[0];
};

const submitOrder = () => {
    form.post('/order/store', {
        onSuccess: () => {
            localStorage.removeItem('zunoi_cart');
        },
    });
};
</script>

<template>
    <Head title="Checkout Zunoi Caffe" />

    <!-- Main Customer Area -->
    <div
        class="relative flex min-h-screen flex-col bg-gradient-to-b from-[#FAEDCD] via-white to-white font-sans"
    >
        <!-- Sticky Header -->
        <header
            class="sticky top-0 z-40 bg-[#3B2314] py-3 text-[#FAEDCD] shadow-md"
        >
            <div
                class="mx-auto flex max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8"
            >
                <Link
                    :href="'/order'"
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
                    Checkout Pesanan
                </h1>
            </div>
        </header>

        <!-- Scrollable Form Area -->
        <main
            class="mx-auto w-full max-w-7xl flex-1 px-4 pt-4 pb-24 sm:px-6 lg:px-8"
        >
            <form
                @submit.prevent="submitOrder"
                class="grid grid-cols-1 items-start gap-4 md:grid-cols-2 md:gap-6"
            >
                <!-- Left Column: Order Type, Customer Details, Order Details, Notes -->
                <div class="space-y-4">
                    <!-- Order Type Toggle -->
                    <div
                        class="space-y-3 rounded-2xl border border-[#D4A373]/10 bg-white p-4 shadow-sm"
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

                    <!-- Customer Details -->
                    <div
                        class="space-y-4 rounded-2xl border border-[#D4A373]/10 bg-white p-4 shadow-sm"
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
                            Informasi Pemesan
                        </h2>
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold text-gray-600"
                                >Nama Lengkap</label
                            >
                            <input
                                v-model="form.customer_name"
                                type="text"
                                class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
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
                                class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
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

                    <!-- Detail Pesanan -->
                    <div
                        class="space-y-3 rounded-2xl border border-[#D4A373]/10 bg-white p-4 shadow-sm"
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
                            class="max-h-[220px] space-y-3 overflow-y-auto pr-1"
                        >
                            <div
                                v-for="item in form.cart_items"
                                :key="item.id"
                                class="flex items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50 p-2"
                            >
                                <div class="flex items-center gap-2">
                                    <img
                                        :src="
                                            item.image ||
                                            'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=400'
                                        "
                                        alt="Product"
                                        class="h-10 w-10 rounded-lg border object-cover"
                                    />
                                    <div class="text-left">
                                        <p
                                            class="text-xs font-bold text-gray-800"
                                        >
                                            {{ item.name }}
                                        </p>
                                        <p
                                            class="text-[10px] font-semibold text-gray-400"
                                        >
                                            {{ item.quantity }}x &bull; Rp
                                            {{
                                                item.price.toLocaleString(
                                                    'id-ID',
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <span
                                    class="text-xs font-extrabold text-[#3B2314]"
                                    >Rp
                                    {{
                                        (
                                            item.price * item.quantity
                                        ).toLocaleString('id-ID')
                                    }}</span
                                >
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
                                class="flex justify-between font-medium text-gray-600"
                            >
                                <span>Pajak (2%)</span>
                                <span
                                    >Rp
                                    {{ taxTotal.toLocaleString('id-ID') }}</span
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
                        class="space-y-3 rounded-2xl border border-[#D4A373]/10 bg-white p-4 shadow-sm"
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
                        class="space-y-3 rounded-2xl border border-[#D4A373]/10 bg-white p-4 shadow-sm"
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
                                class="text-[#3B2314] focus:ring-[#3B2314]"
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
                                    class="text-[#3B2314] focus:ring-[#3B2314]"
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
                                        :src="qris_manual_url"
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
                                class="text-[#3B2314] focus:ring-[#3B2314]"
                            />
                            <span class="font-extrabold text-[#3B2314]"
                                >Bayar Langsung di Kasir</span
                            >
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-[#D4A373] py-4 text-xs font-black tracking-wider text-[#3B2314] uppercase shadow-md transition-all hover:scale-[1.02] active:scale-95"
                        :disabled="form.cart_items.length === 0"
                        :class="{
                            'cursor-not-allowed opacity-50':
                                form.cart_items.length === 0,
                        }"
                    >
                        Bayar Sekarang &bull; Rp
                        {{ finalTotal.toLocaleString('id-ID') }}
                    </button>
                </div>
            </form>
        </main>

        <!-- QR Code Zoom Modal (Beautiful animated modal covering entire screen) -->
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
                        :src="qris_manual_url"
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
    </div>
</template>
