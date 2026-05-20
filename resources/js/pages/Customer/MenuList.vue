<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import BannerSlider from '@/components/BannerSlider.vue';

const props = defineProps({
    products: Array,
    categories: Array,
    banners: {
        type: Array,
        default: () => [],
    },
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

const isModalOpen = ref(false);
const showAddAnimation = ref(false);
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

const openSelectionModal = (product) => {
    selectedProduct.value = product;
    selectedQuantity.value = 1;

    const sourceAdditions = product.additions || [];

    additions.value = sourceAdditions.map((a) => ({ ...a, selected: false }));
    isModalOpen.value = true;
};

const closeSelectionModal = () => {
    isModalOpen.value = false;
    selectedProduct.value = null;
};

const increaseQuantity = () => {
    selectedQuantity.value++;
};

const decreaseQuantity = () => {
    if (selectedQuantity.value > 1) {
        selectedQuantity.value--;
    }
};

const addSelectionToCart = () => {
    if (!selectedProduct.value) {
return;
}

    const product = selectedProduct.value;

    const selectedAddons = additions.value.filter((a) => a.selected);

    let addonsTotal = 0;
    selectedAddons.forEach((add) => {
        addonsTotal += parseInt(add.price);
    });

    const unitPrice = parseInt(product.price) + addonsTotal;

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

    const existing = cart.value.find(
        (item) => item.id === product.id && item.notes === notesStr,
    );

    if (existing) {
        existing.quantity += selectedQuantity.value;
    } else {
        cart.value.push({
            id: product.id,
            name: product.name,
            basePrice: parseInt(product.price),
            addonPrice: addonsTotal,
            price: unitPrice,
            image: product.image,
            quantity: selectedQuantity.value,
            notes: notesStr,
            additions: JSON.parse(JSON.stringify(additions.value)),
        });
    }

    localStorage.setItem('zunoi_cart', JSON.stringify(cart.value));

    // Tampilkan animasi +1 tanpa menutup modal
    showAddAnimation.value = true;
    setTimeout(() => {
        showAddAnimation.value = false;
    }, 600);
};
</script>

<template>
    <Head title="Menu Zunoi Caffe" />

    <!-- Main Customer Area -->
    <div class="relative z-0 flex min-h-screen flex-col font-sans">
        <!-- Fixed Background Gradient (Seals tablet scroll behavior) -->
        <div
            class="pointer-events-none fixed inset-x-0 -top-24 bottom-0 -z-10 bg-gradient-to-b from-[#FAEDCD] via-white to-white"
        ></div>
        <!-- Sticky Header -->
        <header class="glass-header sticky z-40 pt-[22px] pb-3 text-[#FAEDCD]">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"
            >
                <div>
                    <h1 class="text-lg font-black tracking-wide md:text-xl">
                        Zunoi Caffe
                    </h1>
                </div>
                <span
                    class="shrink-0 rounded-lg border border-white/40 bg-white/95 px-3 py-1 text-xs font-black tracking-wider text-[#3B2314] uppercase shadow-sm backdrop-blur-sm"
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
            <BannerSlider :banners="banners" class="mt-4 mb-8" />

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
                        'glass-filter-btn-inactive':
                            selectedCategoryId !== 'all',
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
                        'glass-filter-btn-active':
                            selectedCategoryId === cat.id,
                        'glass-filter-btn-inactive':
                            selectedCategoryId !== cat.id,
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
                    class="relative flex overflow-hidden rounded-2xl bg-white transition-all duration-300"
                    :class="[
                        !product.is_available ? 'opacity-50 grayscale' : '',
                        getCartItemQuantity(product.id) > 0
                            ? 'z-10 scale-[1.02] border-none shadow-[0_8px_25px_rgba(59,35,20,0.15)]'
                            : 'border border-[#D4A373]/10 shadow-sm hover:border-[#D4A373]/30 hover:shadow-md',
                    ]"
                >
                    <!-- Animated Gradient Border for Selected Items -->
                    <div
                        v-if="getCartItemQuantity(product.id) > 0"
                        class="animated-gradient-border pointer-events-none absolute inset-0 z-20 rounded-2xl p-[2px]"
                    ></div>
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
                                <!-- 'Pilih' / 'Pilih Lagi' Button -->
                                <button
                                    @click.stop="openSelectionModal(product)"
                                    :disabled="!product.is_available"
                                    class="rounded-xl px-4 py-1.5 text-xs font-black transition-all duration-300"
                                    :class="
                                        getCartItemQuantity(product.id) > 0
                                            ? 'bg-[#3B2314] text-white shadow-md hover:bg-[#2A180E] active:scale-95'
                                            : 'border border-[#D4A373]/30 bg-white text-[#3B2314] shadow-sm hover:border-[#D4A373]/50 hover:bg-[#D4A373]/10 active:scale-95'
                                    "
                                >
                                    {{
                                        getCartItemQuantity(product.id) > 0
                                            ? 'Pilih Lagi'
                                            : 'Pilih'
                                    }}
                                </button>
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
            class="pointer-events-none fixed right-0 bottom-4 left-0 z-50 flex flex-col items-center justify-end px-6 md:bottom-16"
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

        <!-- Selection Modal with smooth slide animations -->
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
                                    <p
                                        class="mt-1.5 line-clamp-3 text-sm leading-snug text-[#3B2314]/70"
                                    >
                                        {{ selectedProduct?.description }}
                                    </p>
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
                        <div class="relative flex items-center gap-4">
                            <!-- Animasi +1 -->
                            <Transition name="fade-up-plus">
                                <span
                                    v-if="showAddAnimation"
                                    class="absolute top-1/2 -left-10 z-50 -translate-y-1/2 text-xl font-extrabold text-[#3B2314] drop-shadow-sm"
                                    >+1</span
                                >
                            </Transition>
                            <button
                                type="button"
                                @click="addSelectionToCart"
                                class="rounded-xl bg-[#3B2314] px-5 py-2.5 text-center text-[13px] leading-tight font-bold text-[#FAEDCD] shadow-md transition hover:bg-[#2A180E] active:scale-95"
                            >
                                Masukkan Ke<br />Keranjang
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.glass-glossy-btn {
    position: relative;
    overflow: hidden;
    background: linear-gradient(
        135deg,
        rgba(92, 62, 38, 0.75) 0%,
        rgba(59, 35, 20, 0.8) 50%,
        rgba(36, 21, 12, 0.85) 100%
    );
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
    background: linear-gradient(
        to bottom,
        rgba(255, 255, 255, 0.12) 0%,
        rgba(255, 255, 255, 0) 100%
    );
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
    background: linear-gradient(
        to right,
        rgba(255, 255, 255, 0) 0%,
        rgba(255, 255, 255, 0.22) 50%,
        rgba(255, 255, 255, 0) 100%
    );
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
    background: linear-gradient(
        135deg,
        rgba(92, 62, 38, 0.85) 0%,
        rgba(59, 35, 20, 0.9) 50%,
        rgba(36, 21, 12, 0.95) 100%
    );
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    color: #faedcd;
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
    background: linear-gradient(
        to bottom,
        rgba(255, 255, 255, 0.12) 0%,
        rgba(255, 255, 255, 0) 100%
    );
    pointer-events: none;
    z-index: 1;
}

.glass-filter-btn-inactive {
    position: relative;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.65);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    color: #3b2314;
    box-shadow: inset 0 0 6px 0 rgba(255, 255, 255, 0.4);
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
    background: #3b2314;
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

/* Animated Glassmorphism Gradient Border */
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
