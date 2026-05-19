<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    order: Object,
});
</script>

<template>
    <Head title="Pesanan Berhasil" />

    <!-- Main Customer Area -->
    <div
        class="relative flex min-h-screen flex-col bg-gradient-to-b from-[#FAEDCD] via-white to-white font-sans"
    >
        <!-- Sticky Header -->
        <header
            class="sticky top-0 z-40 bg-[#3B2314] py-3 text-[#FAEDCD] shadow-md"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-center px-4 sm:px-6 lg:px-8"
            >
                <h1 class="text-xl font-black tracking-widest uppercase">
                    Zunoi Caffe
                </h1>
            </div>
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
                            Pesanan Terkirim!
                        </h2>
                        <p class="mt-0.5 text-[10px] font-bold text-gray-400">
                            ID PESANAN: #{{ order.id }}
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
                                    >{{ order.customer_name }}</span
                                >
                            </div>
                            <div>
                                <span class="block font-normal text-gray-400"
                                    >Meja / Tipe</span
                                >
                                <span class="text-xs font-black text-[#3B2314]">
                                    {{ order.table?.table_name || 'Takeaway' }}
                                    ({{
                                        order.order_type === 'dine_in'
                                            ? 'Dine In'
                                            : 'Takeaway'
                                    }})
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Status Warning -->
                    <div
                        v-if="order.payment_method === 'qris_tokopay'"
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
                            pembayaran QRIS Anda sukses terkonfirmasi.</span
                        >
                    </div>

                    <!-- Back to Menu Button -->
                    <Link
                        :href="'/order'"
                        class="block w-full rounded-xl bg-[#3B2314] py-4 text-center text-xs font-black tracking-wider text-[#FAEDCD] uppercase shadow-md transition hover:scale-[1.02] active:scale-95"
                    >
                        Kembali ke Menu Utama
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
                        Rincian Tagihan
                    </h3>

                    <!-- Order Items List -->
                    <div class="mb-5 space-y-3.5">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex items-start justify-between text-xs"
                        >
                            <div class="max-w-[70%]">
                                <span class="font-black text-[#3B2314]">{{
                                    item.product?.name || 'Menu Kopi'
                                }}</span>
                                <span
                                    class="mt-0.5 block text-[9px] font-bold text-gray-400"
                                >
                                    {{ item.quantity }} x Rp
                                    {{
                                        parseInt(
                                            item.price_at_sale,
                                        ).toLocaleString('id-ID')
                                    }}
                                </span>
                            </div>
                            <span class="font-black text-[#3B2314]">
                                Rp
                                {{
                                    (
                                        item.quantity * item.price_at_sale
                                    ).toLocaleString('id-ID')
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
                                    order.payment_method === 'cashier'
                                        ? 'Bayar di Kasir'
                                        : order.payment_method ===
                                            'qris_tokopay'
                                          ? 'QRIS Otomatis'
                                          : 'QRIS Manual'
                                }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between pt-1.5 text-xs"
                        >
                            <span class="font-bold text-[#3B2314]"
                                >Total Pembayaran</span
                            >
                            <span class="text-base font-black text-[#D4A373]">
                                Rp
                                {{
                                    parseInt(order.total_price).toLocaleString(
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
