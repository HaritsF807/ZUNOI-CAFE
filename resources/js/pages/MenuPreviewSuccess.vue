<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    customer_name: String,
    order_type: String,
    payment_method: String,
    total_price: [Number, String],
    items: String, // JSON string
    voucher_code: String,
    discount_amount: [Number, String],
});

const subtotal = computed(() => {
    return parsedItems.value.reduce((sum, item) => sum + (item.quantity * item.price), 0);
});

let originalBgColor = '';

onMounted(() => {
    originalBgColor = document.documentElement.style.backgroundColor;
    document.documentElement.style.backgroundColor = '#3B2314';
});

onUnmounted(() => {
    document.documentElement.style.backgroundColor = originalBgColor;
});

const parsedItems = computed(() => {
    try {
        return JSON.parse(props.items || '[]');
    } catch (e) {
        return [];
    }
});

const mockOrderId = computed(() => {
    return Math.floor(100000 + Math.random() * 900000);
});
</script>

<template>
    <Head title="Preview Pesanan Berhasil - Zunoi Caffe" />

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
                class="mx-auto flex max-w-7xl items-center justify-center px-4 sm:px-6 lg:px-8"
            >
                <h1 class="text-xl font-black tracking-widest uppercase">
                    Zunoi Caffe (Preview)
                </h1>
            </div>
            <!-- Animated Gradient Border -->
            <div class="header-border"></div>
        </header>

        <!-- Scrollable Receipt Area -->
        <main
            class="mx-auto w-full max-w-7xl flex-1 px-4 pt-4 pb-24 sm:px-6 lg:px-8"
        >
            <div
                class="grid grid-cols-1 items-start gap-6 md:grid-cols-2 md:gap-8"
            >
                <!-- Left Column: Status, WhatsApp Warning, Back Button -->
                <div class="space-y-5">
                    <!-- Status Card -->
                    <div
                        class="relative overflow-hidden rounded-2xl border border-[#D4A373]/10 bg-white p-5 text-center shadow-sm"
                    >
                        <div
                            class="absolute -top-8 -right-8 h-20 w-20 rounded-full bg-green-500/5 blur-lg"
                        ></div>

                        <div
                            class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full border border-green-100 bg-green-50 text-green-500"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="3.5"
                                stroke="currentColor"
                                class="h-6 w-6 animate-pulse"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m4.5 12.75 6 6 9-13.5"
                                />
                            </svg>
                        </div>

                        <h2
                            class="text-lg font-black tracking-tight text-[#3B2314]"
                        >
                            Pesanan Terkirim! (Simulasi)
                        </h2>
                        <p class="mt-0.5 text-[10px] font-bold text-gray-400">
                            ID PESANAN DEMO: #{{ mockOrderId }}
                        </p>

                        <div
                            class="mt-4 grid grid-cols-2 gap-2 border-t border-gray-100 pt-3 text-left text-[10px] font-bold text-gray-500"
                        >
                            <div>
                                <span class="block font-normal text-gray-400"
                                    >Nama Pemesan</span
                                >
                                <span
                                    class="text-xs font-black text-[#3B2314]"
                                    >{{
                                        customer_name || 'Pelanggan Demo'
                                    }}</span
                                >
                            </div>
                            <div>
                                <span class="block font-normal text-gray-400"
                                    >Meja / Tipe</span
                                >
                                <span class="text-xs font-black text-[#3B2314]">
                                    Meja Preview ({{
                                        order_type === 'dine_in'
                                            ? 'Dine In'
                                            : 'Takeaway'
                                    }})
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Status Warning -->
                    <div
                        v-if="payment_method === 'qris_tokopay'"
                        class="flex items-start gap-2.5 rounded-xl border border-[#D4A373]/20 bg-amber-50/50 p-4.5 text-[10px] leading-relaxed font-black text-[#3B2314]"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                            class="mt-0.5 h-4 w-4 shrink-0 animate-pulse text-[#D4A373]"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
                            />
                        </svg>
                        <span
                            >Notifikasi WhatsApp akan dikirimkan otomatis jika
                            pembayaran QRIS Anda sukses terkonfirmasi.
                            (Dinonaktifkan dalam mode preview)</span
                        >
                    </div>

                    <!-- Back to Menu Button -->
                    <Link
                        :href="'/dashboard/menu-preview'"
                        class="block w-full rounded-xl bg-[#3B2314] py-4 text-center text-xs font-black tracking-wider text-[#FAEDCD] uppercase shadow-md transition hover:scale-[1.02] active:scale-95"
                    >
                        Kembali ke Menu Utama (Preview)
                    </Link>
                </div>

                <!-- Right Column: Receipt Invoice Details -->
                <div
                    class="relative rounded-2xl border border-[#D4A373]/10 bg-white p-6 shadow-sm"
                >
                    <div
                        class="absolute top-1/2 -left-3 h-6 w-6 -translate-y-1/2 transform rounded-full bg-white"
                    ></div>
                    <div
                        class="absolute top-1/2 -right-3 h-6 w-6 -translate-y-1/2 transform rounded-full bg-white"
                    ></div>

                    <h3
                        class="mb-4 border-b border-dashed border-gray-200 pb-2 text-xs font-extrabold tracking-wider text-[#3B2314] uppercase"
                    >
                        Rincian Tagihan (Preview)
                    </h3>

                    <!-- Order Items List -->
                    <div class="mb-5 space-y-3.5">
                        <div
                            v-for="item in parsedItems"
                            :key="item.id"
                            class="flex items-start justify-between text-xs"
                        >
                            <div class="max-w-[70%]">
                                <span class="font-black text-[#3B2314]">{{
                                    item.name
                                }}</span>
                                <p
                                    class="text-[10px] font-semibold text-gray-400"
                                >
                                    {{ item.quantity }}x &bull; Rp
                                    {{
                                        Number(item.price || item.basePrice || 0).toLocaleString('id-ID')
                                    }}
                                </p>
                                <span
                                    v-if="item.notes"
                                    class="mt-1 block text-[9px] font-black text-[#D4A373]"
                                >
                                    {{ item.notes }}
                                </span>
                            </div>
                            <span class="font-black text-[#3B2314]">
                                Rp
                                {{
                                    (item.quantity * item.price).toLocaleString(
                                        'id-ID',
                                    )
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Dashed Divider -->
                    <div
                        class="my-4 border-t border-dashed border-gray-200"
                    ></div>

                    <!-- Info Bayar & Total -->
                    <div class="space-y-2.5">
                        <div
                            class="flex justify-between text-[10px] font-bold text-gray-500"
                        >
                            <span>Metode Pembayaran</span>
                            <span class="text-[#3B2314] uppercase">
                                {{
                                    payment_method === 'cashier'
                                        ? 'Bayar di Kasir'
                                        : payment_method === 'qris_tokopay'
                                          ? 'QRIS Otomatis'
                                          : 'QRIS Manual'
                                }}
                            </span>
                        </div>
                        <div
                            v-if="discount_amount > 0"
                            class="flex justify-between text-[10px] font-bold text-gray-500"
                        >
                            <span>Subtotal</span>
                            <span class="text-[#3B2314]">
                                Rp {{ subtotal.toLocaleString('id-ID') }}
                            </span>
                        </div>
                        <div
                            v-if="discount_amount > 0"
                            class="flex justify-between text-[10px] font-bold text-emerald-600"
                        >
                            <span>Voucher ({{ voucher_code }})</span>
                            <span>
                                - Rp {{ parseInt(discount_amount).toLocaleString('id-ID') }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between pt-1.5 text-xs border-t border-gray-100"
                        >
                            <span class="font-bold text-[#3B2314]"
                                >Total Pembayaran</span
                            >
                            <span class="text-base font-black text-[#D4A373]">
                                Rp
                                {{
                                    parseInt(total_price).toLocaleString(
                                        'id-ID',
                                    )
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
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
</style>
