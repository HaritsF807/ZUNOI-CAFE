<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    products: Array,
    categories: Array
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
    const existing = cart.value.find(item => item.id === product.id);
    if (existing) {
        existing.quantity++;
    } else {
        cart.value.push({
            id: product.id,
            name: product.name,
            price: product.price,
            image: product.image,
            quantity: 1
        });
    }
    localStorage.setItem('zunoi_cart', JSON.stringify(cart.value));
};

const totalCartItems = computed(() => {
    return cart.value.reduce((total, item) => total + item.quantity, 0);
});

const getCartItemQuantity = (productId) => {
    const item = cart.value.find(i => i.id === productId);
    return item ? item.quantity : 0;
};

const removeFromCart = (product) => {
    const existing = cart.value.find(item => item.id === product.id);
    if (existing) {
        existing.quantity--;
        if (existing.quantity === 0) {
            cart.value = cart.value.filter(item => item.id !== product.id);
        }
        localStorage.setItem('zunoi_cart', JSON.stringify(cart.value));
    }
};

const filteredProducts = computed(() => {
    if (selectedCategoryId.value === 'all') {
        return props.products;
    }
    return props.products.filter(product => product.category_id === selectedCategoryId.value);
});
</script>

<template>
    <Head title="Menu Zunoi Caffe" />

    <!-- Main Customer Area -->
    <div class="min-h-screen bg-gradient-to-b from-[#FAEDCD] via-white to-white font-sans flex flex-col relative">
        
        <!-- Sticky Header -->
        <header class="bg-[#3B2314] text-[#FAEDCD] py-3.5 shadow-md sticky top-[-1px] z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center gap-4">
                <div>
                    <h1 class="text-lg md:text-xl font-black tracking-wide">Zunoi Caffe</h1>
                </div>
                <span class="bg-[#D4A373] text-[#3B2314] text-xs px-3.5 py-1.5 rounded-lg font-black uppercase tracking-wider shrink-0 shadow-sm">
                    {{ $page.props.active_table_name || 'Meja -' }}
                </span>
            </div>
        </header>

        <!-- Menu Content -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-32">
            <h2 class="text-lg md:text-xl font-black text-[#3B2314] mb-4 tracking-tight">
                Daftar Menu Kafe
            </h2>

            <!-- Category Filter Bar -->
            <div class="flex items-center gap-2 overflow-x-auto pb-4 mb-6 scrollbar-none -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <!-- All Category Pill -->
                <button 
                    @click="selectedCategoryId = 'all'" 
                    :class="{
                        'bg-[#3B2314] text-white border-[#3B2314] shadow-md': selectedCategoryId === 'all',
                        'bg-white text-[#3B2314] border-gray-300 hover:bg-[#FAEDCD]/50': selectedCategoryId !== 'all'
                    }"
                    class="px-4 py-2 rounded-xl text-xs font-black tracking-wide whitespace-nowrap transition duration-200 border"
                >
                    Semua Menu
                </button>
                
                <!-- Dynamic Category Pills -->
                <button 
                    v-for="cat in categories" 
                    :key="cat.id"
                    @click="selectedCategoryId = cat.id" 
                    :class="{
                        'bg-[#3B2314] text-white border-[#3B2314] shadow-md': selectedCategoryId === cat.id,
                        'bg-white text-[#3B2314] border-gray-300 hover:bg-[#FAEDCD]/50': selectedCategoryId !== cat.id
                    }"
                    class="px-4 py-2 rounded-xl text-xs font-black tracking-wide whitespace-nowrap transition duration-200 border"
                >
                    {{ cat.name }}
                </button>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
                <div v-for="product in filteredProducts" :key="product.id" 
                     class="bg-white rounded-2xl shadow-sm overflow-hidden flex border border-[#D4A373]/10 hover:border-[#D4A373]/30 transition-all duration-300"
                     :class="{'opacity-50 grayscale': !product.is_available}">
                    
                    <img :src="product.image || 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=400'" 
                         :alt="product.name" class="w-28 h-28 object-cover">
                    
                    <div class="p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="font-extrabold text-sm text-[#3B2314]">{{ product.name }}</h3>
                            <p class="text-[10px] text-gray-500 line-clamp-2 mt-1 leading-normal">{{ product.description }}</p>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="font-black text-xs text-[#D4A373]">Rp {{ parseInt(product.price).toLocaleString('id-ID') }}</span>
                            <div v-if="product.is_available" class="flex items-center">
                                <!-- If not in cart, show single (+) Add button -->
                                <button v-if="getCartItemQuantity(product.id) === 0" 
                                        @click="addToCart(product)" 
                                        class="bg-[#3B2314] hover:bg-[#25150c] text-white w-7 h-7 rounded-lg flex items-center justify-center shadow-md active:scale-95 transition transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-3.5 h-3.5">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                </button>
                                
                                <!-- If in cart, show [-] Qty [+] selector -->
                                <div v-else class="flex items-center bg-[#3B2314] text-white rounded-lg shadow-md py-0.5 px-1.5 gap-2">
                                    <button @click="removeFromCart(product)" 
                                            class="w-6 h-6 rounded-md flex items-center justify-center hover:bg-[#25150c] active:scale-95 transition text-[#FAEDCD] font-black text-sm">
                                        -
                                    </button>
                                    <span class="text-[11px] font-black text-[#FAEDCD] px-0.5 min-w-[12px] text-center">
                                        {{ getCartItemQuantity(product.id) }}
                                    </span>
                                    <button @click="addToCart(product)" 
                                            class="w-6 h-6 rounded-md flex items-center justify-center hover:bg-[#25150c] active:scale-95 transition text-[#FAEDCD] font-black text-sm">
                                        +
                                    </button>
                                </div>
                            </div>
                            <span v-else class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded-lg font-bold">Habis</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Floating Cart Container (Fixed at viewport bottom) -->
        <div class="fixed bottom-6 left-0 right-0 flex flex-col items-center justify-end px-6 pointer-events-none z-50">
            
            <!-- Main Floating Cart Button -->
            <Link :href="'/checkout'" 
                  class="pointer-events-auto bg-gradient-to-br from-[#5C3E26] via-[#3B2314] to-[#24150c] text-[#FAEDCD] px-6 py-4 rounded-2xl shadow-[0_10px_30px_rgba(59,35,20,0.45)] hover:shadow-[0_10px_35px_rgba(59,35,20,0.6)] font-black flex items-center justify-between hover:scale-105 active:scale-95 transition-all duration-300 w-full max-w-sm md:max-w-md lg:max-w-lg border border-[#D4A373]/40">
                
                <span class="flex items-center gap-2 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    Keranjang Belanja
                </span>
                
                <span class="bg-[#D4A373] text-[#3B2314] text-sm font-black px-4 py-2 rounded-xl shadow-inner tracking-wider shrink-0 flex items-center justify-center">
                    {{ totalCartItems }} Item
                </span>
            </Link>
        </div>
    </div>
</template>
