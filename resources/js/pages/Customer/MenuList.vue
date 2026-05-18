<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    products: Array
});

const cart = ref([]);
const showNotification = ref(false);
const notificationMessage = ref('');
let notificationTimeout = null;

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
            quantity: 1
        });
    }
    localStorage.setItem('zunoi_cart', JSON.stringify(cart.value));
    
    // Picu notifikasi melayang
    notificationMessage.value = `${product.name} berhasil ditambahkan!`;
    showNotification.value = true;
    
    if (notificationTimeout) clearTimeout(notificationTimeout);
    notificationTimeout = setTimeout(() => {
        showNotification.value = false;
    }, 2200);
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
        
        notificationMessage.value = `${product.name} dikurangi dari keranjang.`;
        showNotification.value = true;
        
        if (notificationTimeout) clearTimeout(notificationTimeout);
        notificationTimeout = setTimeout(() => {
            showNotification.value = false;
        }, 2000);
    }
};
</script>

<template>
    <Head title="Menu Zunoi Caffe" />

    <!-- Outer Desktop Background (Cozy Coffee Shop) -->
    <div class="min-h-screen bg-[#3B2314]/5 md:bg-[#1E100A] md:bg-[url('https://images.unsplash.com/photo-1554118811-1e0d58224f24?q=80&w=1600')] md:bg-cover md:bg-center md:bg-blend-multiply flex items-center justify-center p-0 md:p-6 font-sans">
        
        <!-- Smartphone Mockup Container -->
        <div class="w-full h-screen md:h-[88vh] md:max-h-[850px] md:max-w-md md:rounded-[44px] md:shadow-[0_30px_70px_rgba(0,0,0,0.8)] bg-[#FAEDCD] overflow-hidden md:border-[10px] md:border-[#3B2314] relative flex flex-col">
            
            <!-- Scrollable Content Area -->
            <div class="flex-1 overflow-y-auto pb-28 scrollbar-none">
                <!-- Sticky Header -->
                <header class="bg-[#3B2314] text-[#FAEDCD] p-6 shadow-md rounded-b-[32px] sticky top-0 z-40">
                    <h1 class="text-2xl font-black tracking-wide">Zunoi Caffe</h1>
                    <p class="text-xs text-gray-300 mt-1 flex items-center justify-between">
                        <span>Meja: {{ $page.props.active_table_name || 'Scan QR Meja' }}</span>
                        <span class="bg-[#D4A373] text-[#3B2314] text-[9px] px-2.5 py-0.5 rounded-full font-black uppercase tracking-widest">Pelanggan</span>
                    </p>
                </header>

                <!-- Menu Grid -->
                <main class="p-6">
                    <h2 class="text-lg font-black text-[#3B2314] mb-4 tracking-tight flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5 text-[#D4A373]">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.007 5.25H3.75v.008h.008V12Zm0 5.25H3.75v.008h.008v-.008Z" />
                        </svg>
                        Daftar Menu Kafe
                    </h2>
                    
                    <div class="space-y-4">
                        <div v-for="product in products" :key="product.id" 
                             class="bg-white rounded-[24px] shadow-sm overflow-hidden flex border border-[#D4A373]/10 hover:border-[#D4A373]/30 transition-all duration-300"
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
                                                class="bg-[#3B2314] hover:bg-[#25150c] text-white w-7 h-7 rounded-full flex items-center justify-center shadow-md active:scale-95 transition transform">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-3.5 h-3.5">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                        </button>
                                        
                                        <!-- If in cart, show [-] Qty [+] selector -->
                                        <div v-else class="flex items-center bg-[#3B2314] text-white rounded-full shadow-md py-0.5 px-1.5 gap-2">
                                            <button @click="removeFromCart(product)" 
                                                    class="w-6 h-6 rounded-full flex items-center justify-center hover:bg-[#25150c] active:scale-95 transition text-[#FAEDCD] font-black text-sm">
                                                -
                                            </button>
                                            <span class="text-[11px] font-black text-[#FAEDCD] px-0.5 min-w-[12px] text-center">
                                                {{ getCartItemQuantity(product.id) }}
                                            </span>
                                            <button @click="addToCart(product)" 
                                                    class="w-6 h-6 rounded-full flex items-center justify-center hover:bg-[#25150c] active:scale-95 transition text-[#FAEDCD] font-black text-sm">
                                                +
                                            </button>
                                        </div>
                                    </div>
                                    <span v-else class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-bold">Habis</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>

            <!-- Floating Cart Container with absolute locking inside the device frame -->
            <div class="absolute bottom-6 left-0 right-0 flex flex-col items-center justify-end px-6 pointer-events-none z-50">
                
                <!-- Micro-Notification Bubble (Slides up elegantly) -->
                <transition
                    enter-active-class="transform ease-out duration-300 transition-all"
                    enter-from-class="translate-y-4 opacity-0 scale-95"
                    enter-to-class="translate-y-0 opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="translate-y-0 opacity-100 scale-100"
                    leave-to-class="translate-y-2 opacity-0 scale-95"
                >
                    <div v-if="showNotification" class="pointer-events-auto mb-3 bg-[#3B2314]/95 backdrop-blur-md text-[#FAEDCD] px-5 py-2.5 rounded-full shadow-2xl border border-[#D4A373]/30 text-xs font-black flex items-center gap-2 animate-bounce">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor" class="w-4 h-4 text-green-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        {{ notificationMessage }}
                    </div>
                </transition>

                <!-- Main Floating Cart Button -->
                <Link :href="'/checkout'" 
                      class="pointer-events-auto bg-gradient-to-r from-[#3B2314] to-[#24150c] text-[#FAEDCD] px-6 py-4 rounded-full shadow-[0_10px_30px_rgba(59,35,20,0.45)] hover:shadow-[0_10px_35px_rgba(59,35,20,0.6)] font-black flex items-center justify-between hover:scale-105 active:scale-95 transition-all duration-300 w-full max-w-sm border border-[#D4A373]/40"
                      :class="{'scale-105 border-green-500 ring-2 ring-green-500/20': showNotification}">
                    
                    <span class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        Lihat Keranjang Belanja
                    </span>
                    
                    <span class="bg-[#D4A373] text-[#3B2314] text-xs font-black px-3 py-1 rounded-full shadow-inner tracking-wider">
                        {{ totalCartItems }} Item
                    </span>
                </Link>
            </div>
        </div>
    </div>
</template>
