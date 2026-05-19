<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    products: Array,
    categories: Array,
});

const cart = ref([]);
const selectedCategoryId = ref('all');

let originalBgColor = '';

onMounted(() => {
    const savedCart = localStorage.getItem('zunoi_cart');
    if (savedCart) {
        cart.value = JSON.parse(savedCart);
    }
    originalBgColor = document.documentElement.style.backgroundColor;
    document.documentElement.style.backgroundColor = '#3B2314';
});

onUnmounted(() => {
    document.documentElement.style.backgroundColor = originalBgColor;
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
        class="relative z-0 flex min-h-screen flex-col font-sans"
    >
        <!-- Fixed Background Gradient (Seals tablet scroll behavior) -->
        <div class="fixed inset-x-0 -top-24 bottom-0 -z-10 bg-gradient-to-b from-[#FAEDCD] via-white to-white pointer-events-none"></div>
        <!-- Sticky Header -->
        <header
            class="glass-header sticky z-40 pt-[22px] pb-3 text-[#FAEDCD]"
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
                    class="shrink-0 rounded-lg bg-white/95 backdrop-blur-sm border border-white/40 px-3 py-1 text-xs font-black tracking-wider text-[#3B2314] uppercase shadow-sm"
                >
                    {{ $page.props.active_table_name || 'Meja -' }}
                </span>
            </div>
            <!-- Animated Gradient Border -->
            <div class="header-border"></div>
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
                        'glass-filter-btn-active': selectedCategoryId === 'all',
                        'glass-filter-btn-inactive': selectedCategoryId !== 'all',
                    }"
                    class="category-btn rounded-xl px-4 py-2 text-xs font-black tracking-wide whitespace-nowrap"
                >
                    <span class="relative z-10">Semua Menu</span>
                </button>

                <!-- Dynamic Category Pills -->
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="selectedCategoryId = cat.id"
                    :class="{
                        'glass-filter-btn-active': selectedCategoryId === cat.id,
                        'glass-filter-btn-inactive': selectedCategoryId !== cat.id,
                    }"
                    class="category-btn rounded-xl px-4 py-2 text-xs font-black tracking-wide whitespace-nowrap"
                >
                    <span class="relative z-10">{{ cat.name }}</span>
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
                                class="relative flex h-8 items-center justify-end"
                            >
                                <Transition name="expand-bounce" mode="out-in">
                                    <!-- If not in cart, show single (+) Add button -->
                                    <button
                                        v-if="getCartItemQuantity(product.id) === 0"
                                        @click="addToCart(product)"
                                        class="add-btn flex h-8 w-8 items-center justify-center rounded-xl shadow-sm"
                                        key="add"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="3.2"
                                            stroke="currentColor"
                                            class="h-4.5 w-4.5 text-[#3B2314]"
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
                                        class="qty-selector flex items-center gap-2.5 rounded-xl px-2 py-1 shadow-sm"
                                        key="qty"
                                    >
                                        <button
                                            @click="removeFromCart(product)"
                                            class="flex h-6 w-6 items-center justify-center rounded-lg text-base font-black text-[#FAEDCD] transition active:scale-75"
                                        >
                                            -
                                        </button>
                                        <span
                                            class="min-w-[14px] text-center text-xs font-black text-[#FAEDCD]"
                                        >
                                            {{ getCartItemQuantity(product.id) }}
                                        </span>
                                        <button
                                            @click="addToCart(product)"
                                            class="flex h-6 w-6 items-center justify-center rounded-lg text-base font-black text-[#FAEDCD] transition active:scale-75"
                                        >
                                            +
                                        </button>
                                    </div>
                                </Transition>
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
            class="pointer-events-none fixed right-0 -bottom-4 left-0 z-40 h-20 bg-gradient-to-t from-[#3B2314] via-[#3B2314]/45 to-transparent"
        ></div>

        <!-- Floating Cart Container (Fixed at viewport bottom) -->
        <div
            class="pointer-events-none fixed right-0 bottom-4 md:bottom-16 left-0 z-50 flex flex-col items-center justify-end px-6"
        >
            <!-- Main Floating Cart Button -->
            <Link
                :href="'/checkout'"
                class="glass-glossy-btn pointer-events-auto flex w-full max-w-sm items-center justify-between rounded-2xl px-6 py-4 font-black text-[#FAEDCD] transition-all duration-300 hover:scale-105 active:scale-95 md:max-w-md lg:max-w-lg"
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

<style scoped>
.glass-glossy-btn {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, rgba(92, 62, 38, 0.75) 0%, rgba(59, 35, 20, 0.8) 50%, rgba(36, 21, 12, 0.85) 100%);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 2.5px solid rgba(250, 237, 205, 0.35);
    box-shadow: 
        0 10px 30px 0 rgba(59, 35, 20, 0.45),
        inset 0 0 8px 0 rgba(255, 255, 255, 0.25),
        inset 0 1px 0 0 rgba(255, 255, 255, 0.3),
        inset 0 -1px 0 0 rgba(0, 0, 0, 0.3);
}

/* 3D Convex Gloss reflection on upper half */
.glass-glossy-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 50%;
    background: linear-gradient(to bottom, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
    z-index: 1;
}

/* Sweeping glossy light beam sheen */
.glass-glossy-btn::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 60%;
    height: 100%;
    background: linear-gradient(to right, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.22) 50%, rgba(255, 255, 255, 0) 100%);
    transform: translate3d(-180%, 0, 0) skewX(-25deg);
    pointer-events: none;
    z-index: 2;
    will-change: transform;
    animation: btn-shine 6s infinite ease-in-out;
}

.glass-glossy-btn:hover {
    border-color: rgba(250, 237, 205, 0.45);
    box-shadow: 
        0 10px 35px 0 rgba(59, 35, 20, 0.55),
        inset 0 0 10px 0 rgba(255, 255, 255, 0.35),
        inset 0 1px 0 0 rgba(255, 255, 255, 0.4),
        inset 0 -1px 0 0 rgba(0, 0, 0, 0.3);
}

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
    background: linear-gradient(90deg, 
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
    background: linear-gradient(-45deg, 
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
    background: linear-gradient(to bottom, rgba(26, 15, 8, 0.95) 0%, rgba(36, 21, 12, 0.3) 100%);
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



@keyframes btn-shine {
    0% {
        transform: translate3d(-180%, 0, 0) skewX(-25deg);
    }
    12% {
        transform: translate3d(180%, 0, 0) skewX(-25deg);
    }
    100% {
        transform: translate3d(180%, 0, 0) skewX(-25deg);
    }
}

/* Category Filter Glassmorphism styles */
.glass-filter-btn-active {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, rgba(92, 62, 38, 0.85) 0%, rgba(59, 35, 20, 0.9) 50%, rgba(36, 21, 12, 0.95) 100%);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    color: #FAEDCD;
    box-shadow: 
        inset 0 0 8px 0 rgba(255, 255, 255, 0.2),
        inset 0 1px 0 0 rgba(255, 255, 255, 0.25),
        inset 0 -1px 0 0 rgba(0, 0, 0, 0.3);
}

.glass-filter-btn-active::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 50%;
    background: linear-gradient(to bottom, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
    z-index: 1;
}

.glass-filter-btn-inactive {
    position: relative;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.65);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    color: #3B2314;
    box-shadow: 
        inset 0 0 6px 0 rgba(255, 255, 255, 0.4);
}

.category-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    white-space: nowrap;
    transition: transform 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.category-btn:active {
    transform: scale(0.92);
    transition: transform 0.05s ease-out;
}

/* Add Item translucent button and expand animation */
.add-btn {
    background: rgba(212, 163, 115, 0.85);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: none;
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.add-btn:active {
    transform: scale(0.8);
}

.qty-selector {
    background: #3B2314;
    border: none;
}

/* Expand Bounce Transition */
.expand-bounce-enter-active {
    animation: expand-bounce-in 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.expand-bounce-leave-active {
    animation: expand-bounce-out 0.25s ease-in;
}

@keyframes expand-bounce-in {
    0% {
        transform: scale(0.5);
        opacity: 0;
    }
    70% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes expand-bounce-out {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(0.8);
        opacity: 0;
    }
}
</style>
