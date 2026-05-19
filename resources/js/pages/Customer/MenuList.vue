<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    products: Array,
    categories: Array,
});

const cart = ref([]);
const selectedCategoryId = ref('all');

onMounted(() => {
    const savedCart = localStorage.getItem('zunoi_cart');

    if (savedCart) {
        cart.value = JSON.parse(savedCart);
    }
});

const addToCart = (product) => {
    const existing = cart.value.find((item) => item.id === product.id);

    if (existing) {
        existing.quantity++;
    } else {
        cart.value.push({
            id: product.id,
            name: product.name,
            price: product.price,
            image: product.image,
            quantity: 1,
        });
    }

    localStorage.setItem('zunoi_cart', JSON.stringify(cart.value));
};

const totalCartItems = computed(() => {
    return cart.value.reduce((total, item) => total + item.quantity, 0);
});

const getCartItemQuantity = (productId) => {
    const item = cart.value.find((i) => i.id === productId);

    return item ? item.quantity : 0;
};

const removeFromCart = (product) => {
    const existing = cart.value.find((item) => item.id === product.id);

    if (existing) {
        existing.quantity--;

        if (existing.quantity === 0) {
            cart.value = cart.value.filter((item) => item.id !== product.id);
        }

        localStorage.setItem('zunoi_cart', JSON.stringify(cart.value));
    }
};

const filteredProducts = computed(() => {
    if (selectedCategoryId.value === 'all') {
        return props.products;
    }

    return props.products.filter(
        (product) => product.category_id === selectedCategoryId.value,
    );
});
</script>

<template>
    <Head title="Menu Zunoi Caffe" />

    <!-- Main Customer Area -->
    <div
        class="relative flex min-h-screen flex-col bg-gradient-to-b from-[#FAEDCD] via-white to-white font-sans"
    >
        <!-- Sticky Header -->
        <header
            class="sticky top-0 z-40 bg-[#3B2314] py-3 text-[#FAEDCD] shadow-md"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"
            >
                <div>
                    <h1 class="text-lg font-black tracking-wide md:text-xl">
                        Zunoi Caffe
                    </h1>
                </div>
                <span
                    class="shrink-0 rounded-lg bg-[#D4A373] px-3 py-1 text-xs font-black tracking-wider text-[#3B2314] uppercase shadow-sm"
                >
                    {{ $page.props.active_table_name || 'Meja -' }}
                </span>
            </div>
        </header>

        <!-- Menu Content -->
        <main
            class="mx-auto w-full max-w-7xl flex-1 px-4 pt-2.5 pb-24 sm:px-6 lg:px-8"
        >
            <h2
                class="mb-1.5 text-lg font-black tracking-tight text-[#3B2314] md:text-xl"
            >
                Daftar Menu Kafe
            </h2>

            <!-- Category Filter Bar -->
            <div
                class="-mx-4 mb-2 flex scrollbar-none items-center gap-2 overflow-x-auto px-4 pb-1 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
            >
                <!-- All Category Pill -->
                <button
                    @click="selectedCategoryId = 'all'"
                    :class="{
                        'border-[#3B2314] bg-[#3B2314] text-white shadow-md':
                            selectedCategoryId === 'all',
                        'border-gray-300 bg-white text-[#3B2314] hover:bg-[#FAEDCD]/50':
                            selectedCategoryId !== 'all',
                    }"
                    class="rounded-xl border px-4 py-2 text-xs font-black tracking-wide whitespace-nowrap transition duration-200"
                >
                    Semua Menu
                </button>

                <!-- Dynamic Category Pills -->
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="selectedCategoryId = cat.id"
                    :class="{
                        'border-[#3B2314] bg-[#3B2314] text-white shadow-md':
                            selectedCategoryId === cat.id,
                        'border-gray-300 bg-white text-[#3B2314] hover:bg-[#FAEDCD]/50':
                            selectedCategoryId !== cat.id,
                    }"
                    class="rounded-xl border px-4 py-2 text-xs font-black tracking-wide whitespace-nowrap transition duration-200"
                >
                    {{ cat.name }}
                </button>
            </div>

            <div
                class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-2 md:gap-3 lg:grid-cols-3 xl:grid-cols-4"
            >
                <div
                    v-for="product in filteredProducts"
                    :key="product.id"
                    class="flex overflow-hidden rounded-2xl border border-[#D4A373]/10 bg-white shadow-sm transition-all duration-300 hover:border-[#D4A373]/30"
                    :class="{ 'opacity-50 grayscale': !product.is_available }"
                >
                    <img
                        :src="
                            product.image ||
                            'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=400'
                        "
                        :alt="product.name"
                        class="h-28 w-28 object-cover"
                    />

                    <div class="flex flex-1 flex-col justify-between p-2.5">
                        <div>
                            <h3 class="text-sm font-extrabold text-[#3B2314]">
                                {{ product.name }}
                            </h3>
                            <p
                                class="mt-1 line-clamp-2 text-[10px] leading-normal text-gray-500"
                            >
                                {{ product.description }}
                            </p>
                        </div>
                        <div class="mt-0.5 flex items-center justify-between">
                            <span class="text-xs font-black text-[#D4A373]"
                                >Rp
                                {{
                                    parseInt(product.price).toLocaleString(
                                        'id-ID',
                                    )
                                }}</span
                            >
                            <div
                                v-if="product.is_available"
                                class="flex items-center"
                            >
                                <!-- If not in cart, show single (+) Add button -->
                                <button
                                    v-if="getCartItemQuantity(product.id) === 0"
                                    @click="addToCart(product)"
                                    class="flex h-7 w-7 transform items-center justify-center rounded-lg bg-[#3B2314] text-white shadow-md transition hover:bg-[#25150c] active:scale-95"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="3"
                                        stroke="currentColor"
                                        class="h-3.5 w-3.5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 4.5v15m7.5-7.5h-15"
                                        />
                                    </svg>
                                </button>

                                <!-- If in cart, show [-] Qty [+] selector -->
                                <div
                                    v-else
                                    class="flex items-center gap-2 rounded-lg bg-[#3B2314] px-1.5 py-0.5 text-white shadow-md"
                                >
                                    <button
                                        @click="removeFromCart(product)"
                                        class="flex h-6 w-6 items-center justify-center rounded-md text-sm font-black text-[#FAEDCD] transition hover:bg-[#25150c] active:scale-95"
                                    >
                                        -
                                    </button>
                                    <span
                                        class="min-w-[12px] px-0.5 text-center text-[11px] font-black text-[#FAEDCD]"
                                    >
                                        {{ getCartItemQuantity(product.id) }}
                                    </span>
                                    <button
                                        @click="addToCart(product)"
                                        class="flex h-6 w-6 items-center justify-center rounded-md text-sm font-black text-[#FAEDCD] transition hover:bg-[#25150c] active:scale-95"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>
                            <span
                                v-else
                                class="rounded-lg bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-600"
                                >Habis</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Bottom Gradient Overlay -->
        <div
            class="pointer-events-none fixed right-0 -bottom-1 left-0 z-40 h-20 bg-gradient-to-t from-[#3B2314] via-[#3B2314]/80 to-transparent"
        ></div>

        <!-- Floating Cart Container (Fixed at viewport bottom) -->
        <div
            class="pointer-events-none fixed right-0 bottom-4 left-0 z-50 flex flex-col items-center justify-end px-6"
        >
            <!-- Main Floating Cart Button -->
            <Link
                :href="'/checkout'"
                class="pointer-events-auto flex w-full max-w-sm items-center justify-between rounded-2xl bg-gradient-to-br from-[#5C3E26] via-[#3B2314] to-[#24150c] px-6 py-4 font-black text-[#FAEDCD] shadow-[0_10px_30px_rgba(59,35,20,0.45)] transition-all duration-300 hover:scale-105 hover:shadow-[0_10px_35px_rgba(59,35,20,0.6)] active:scale-95 md:max-w-md lg:max-w-lg"
            >
                <span class="flex items-center gap-2 text-sm">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        stroke="currentColor"
                        class="h-5 w-5 shrink-0"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"
                        />
                    </svg>
                    Keranjang Belanja
                </span>

                <span
                    class="flex shrink-0 items-center justify-center rounded-xl bg-[#D4A373] px-4 py-2 text-sm font-black tracking-wider text-[#3B2314] shadow-inner"
                >
                    {{ totalCartItems }} Item
                </span>
            </Link>
        </div>
    </div>
</template>
