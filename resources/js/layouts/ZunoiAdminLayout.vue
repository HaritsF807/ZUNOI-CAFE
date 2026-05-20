<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, provide, onMounted, onUnmounted, watch } from 'vue';
import { logout } from '@/routes';

const user = usePage().props.auth.user;
const isSidebarOpen = ref(true);

const openMenus = ref<Record<string, boolean>>({
    analitik: true,
    transaksi: true,
    operasional: true,
    sistem: true,
});

const toggleMenu = (menuKey: string) => {
    openMenus.value[menuKey] = !openMenus.value[menuKey];
};

const handleLogout = () => {
    router.post(logout().url);
};

// Global Toast State
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('success');

const triggerToast = (message: string, type: string = 'success') => {
    toastMessage.value = message;
    toastType.value = type;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 4500);
};

const handleToastEvent = (event: any) => {
    if (event.detail) {
        triggerToast(event.detail.message, event.detail.type || 'success');
    }
};

// Watch for Inertia Flash Messages
const page = usePage();
watch(
    page.props,
    (props: any) => {
        const flash = props.flash;

        if (flash && flash.success) {
            triggerToast(flash.success, 'success');
            flash.success = null;
        }

        if (flash && flash.error) {
            triggerToast(flash.error, 'error');
            flash.error = null;
        }
    },
    { deep: true, immediate: true },
);

// Watchers for persistent state
watch(isSidebarOpen, (newVal) => {
    localStorage.setItem('zunoi_sidebar_open', String(newVal));
});

watch(
    openMenus,
    (newVal) => {
        localStorage.setItem('zunoi_open_menus', JSON.stringify(newVal));
    },
    { deep: true },
);

onMounted(() => {
    const storedSidebar = localStorage.getItem('zunoi_sidebar_open');

    if (storedSidebar !== null) {
        isSidebarOpen.value = storedSidebar !== 'false';
    }

    const storedMenus = localStorage.getItem('zunoi_open_menus');

    if (storedMenus !== null) {
        try {
            openMenus.value = JSON.parse(storedMenus);
        } catch (e) {
            // ignore
        }
    }

    window.addEventListener('zunoi-toast', handleToastEvent);
});

onUnmounted(() => {
    window.removeEventListener('zunoi-toast', handleToastEvent);
});

provide('triggerToast', triggerToast);
</script>

<template>
    <div class="flex min-h-screen bg-white font-sans text-gray-800">
        <aside
            class="sticky top-0 z-30 flex h-screen flex-col justify-between bg-[#3B2314] text-[#FAEDCD] transition-all duration-300"
            :class="{
                'w-64': isSidebarOpen,
                'w-20 overflow-hidden': !isSidebarOpen,
            }"
        >
            <div class="custom-scrollbar flex flex-1 flex-col overflow-y-auto">
                <!-- Sidebar Header -->
                <div
                    class="flex items-center justify-between border-b border-[#D4A373]/30 p-6"
                >
                    <div class="flex items-center gap-3" v-if="isSidebarOpen">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-[#D4A373] text-lg font-bold text-[#3B2314] shadow"
                        >
                            Z
                        </div>
                        <div>
                            <h1 class="text-lg font-extrabold tracking-wider">
                                ZUNOI
                            </h1>
                            <p
                                class="text-[10px] font-bold tracking-widest text-[#D4A373] uppercase"
                            >
                                Caffe System
                            </p>
                        </div>
                    </div>
                    <div
                        class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-[#D4A373] text-lg font-bold text-[#3B2314] shadow"
                        v-else
                    >
                        Z
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="mt-6 space-y-4 p-4">
                    <!-- Group: Analitik -->
                    <div>
                        <button
                            @click="toggleMenu('analitik')"
                            class="flex w-full items-center justify-between rounded-xl px-4 py-2 text-xs font-bold tracking-wider text-[#D4A373] uppercase transition hover:bg-[#FAEDCD]/5"
                        >
                            <span v-if="isSidebarOpen">Analitik</span>
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="mx-auto h-5 w-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"
                                />
                            </svg>
                            <svg
                                v-if="isSidebarOpen"
                                :class="{ 'rotate-180': openMenus.analitik }"
                                class="h-4 w-4 transition-transform duration-200"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <div v-show="openMenus.analitik" class="mt-1 space-y-1">
                            <Link
                                :href="'/dashboard'"
                                class="flex items-center gap-3 rounded-xl px-4 py-3 transition duration-200"
                                :class="
                                    usePage().url === '/dashboard'
                                        ? 'bg-[#D4A373] font-bold text-[#3B2314] shadow-md'
                                        : 'text-gray-300 hover:bg-[#FAEDCD]/10 hover:text-white'
                                "
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"
                                    />
                                </svg>
                                <span v-if="isSidebarOpen">Dashboard</span>
                            </Link>

                            <Link
                                v-if="user.role === 'owner'"
                                :href="'/dashboard/statistics'"
                                class="flex items-center gap-3 rounded-xl px-4 py-3 transition duration-200"
                                :class="
                                    usePage().url === '/dashboard/statistics'
                                        ? 'bg-[#D4A373] font-bold text-[#3B2314] shadow-md'
                                        : 'text-gray-300 hover:bg-[#FAEDCD]/10 hover:text-white'
                                "
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"
                                    />
                                </svg>
                                <span v-if="isSidebarOpen">Statistik</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Group: Transaksi & Penjualan -->
                    <div>
                        <button
                            @click="toggleMenu('transaksi')"
                            class="flex w-full items-center justify-between rounded-xl px-4 py-2 text-xs font-bold tracking-wider text-[#D4A373] uppercase transition hover:bg-[#FAEDCD]/5"
                        >
                            <span v-if="isSidebarOpen"
                                >Transaksi & Penjualan</span
                            >
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="mx-auto h-5 w-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V4.22c0-.756-.728-1.296-1.453-1.096a59.769 59.769 0 0 1-15.797 2.101c-.727-.198-1.453.342-1.453 1.096v11.334c0 .756.728 1.296 1.453 1.096ZM12 9.75a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z"
                                />
                            </svg>
                            <svg
                                v-if="isSidebarOpen"
                                :class="{ 'rotate-180': openMenus.transaksi }"
                                class="h-4 w-4 transition-transform duration-200"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <div
                            v-show="openMenus.transaksi"
                            class="mt-1 space-y-1"
                        >
                            <Link
                                :href="'/dashboard/cashier'"
                                class="flex items-center gap-3 rounded-xl px-4 py-3 transition duration-200"
                                :class="
                                    usePage().url === '/dashboard/cashier'
                                        ? 'bg-[#D4A373] font-bold text-[#3B2314] shadow-md'
                                        : 'text-gray-300 hover:bg-[#FAEDCD]/10 hover:text-white'
                                "
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"
                                    />
                                </svg>
                                <span v-if="isSidebarOpen">Kasir / POS</span>
                            </Link>

                            <Link
                                :href="'/dashboard/promos'"
                                class="flex items-center gap-3 rounded-xl px-4 py-3 transition duration-200"
                                :class="
                                    usePage().url.startsWith(
                                        '/dashboard/promos',
                                    )
                                        ? 'bg-[#D4A373] font-bold text-[#3B2314] shadow-md'
                                        : 'text-gray-300 hover:bg-[#FAEDCD]/10 hover:text-white'
                                "
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581a2.25 2.25 0 0 0 3.181 0l5.103-5.103a2.25 2.25 0 0 0 0-3.181l-9.581-9.581A2.25 2.25 0 0 0 10.432 3h-.864Z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M7.5 7.5h.008v.008H7.5V7.5Z"
                                    />
                                </svg>
                                <span v-if="isSidebarOpen">Kelola Promo</span>
                            </Link>

                            <Link
                                :href="'/dashboard/history'"
                                class="flex items-center gap-3 rounded-xl px-4 py-3 transition duration-200"
                                :class="
                                    usePage().url.startsWith('/dashboard/history')
                                        ? 'bg-[#D4A373] font-bold text-[#3B2314] shadow-md'
                                        : 'text-gray-300 hover:bg-[#FAEDCD]/10 hover:text-white'
                                "
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                    />
                                </svg>
                                <span v-if="isSidebarOpen">Riwayat</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Group: Operasional -->
                    <div>
                        <button
                            @click="toggleMenu('operasional')"
                            class="flex w-full items-center justify-between rounded-xl px-4 py-2 text-xs font-bold tracking-wider text-[#D4A373] uppercase transition hover:bg-[#FAEDCD]/5"
                        >
                            <span v-if="isSidebarOpen">Operasional</span>
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="mx-auto h-5 w-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z"
                                />
                            </svg>
                            <svg
                                v-if="isSidebarOpen"
                                :class="{ 'rotate-180': openMenus.operasional }"
                                class="h-4 w-4 transition-transform duration-200"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <div
                            v-show="openMenus.operasional"
                            class="mt-1 space-y-1"
                        >
                            <Link
                                :href="'/dashboard/menu'"
                                class="flex items-center gap-3 rounded-xl px-4 py-3 transition duration-200"
                                :class="
                                    usePage().url === '/dashboard/menu'
                                        ? 'bg-[#D4A373] font-bold text-[#3B2314] shadow-md'
                                        : 'text-gray-300 hover:bg-[#FAEDCD]/10 hover:text-white'
                                "
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-3.75 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"
                                    />
                                </svg>
                                <span v-if="isSidebarOpen">Kelola Menu</span>
                            </Link>

                            <Link
                                :href="'/dashboard/tables'"
                                class="flex items-center gap-3 rounded-xl px-4 py-3 transition duration-200"
                                :class="
                                    usePage().url === '/dashboard/tables'
                                        ? 'bg-[#D4A373] font-bold text-[#3B2314] shadow-md'
                                        : 'text-gray-300 hover:bg-[#FAEDCD]/10 hover:text-white'
                                "
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 8.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"
                                    />
                                </svg>
                                <span v-if="isSidebarOpen">Kelola Meja</span>
                            </Link>

                            <Link
                                v-if="user.role === 'owner'"
                                :href="'/dashboard/staff'"
                                class="flex items-center gap-3 rounded-xl px-4 py-3 transition duration-200"
                                :class="
                                    usePage().url === '/dashboard/staff'
                                        ? 'bg-[#D4A373] font-bold text-[#3B2314] shadow-md'
                                        : 'text-gray-300 hover:bg-[#FAEDCD]/10 hover:text-white'
                                "
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"
                                    />
                                </svg>
                                <span v-if="isSidebarOpen">Kelola Barista</span>
                            </Link>

                            <Link
                                :href="'/dashboard/reservasi'"
                                class="flex items-center gap-3 rounded-xl px-4 py-3 transition duration-200"
                                :class="
                                    usePage().url === '/dashboard/reservasi'
                                        ? 'bg-[#D4A373] font-bold text-[#3B2314] shadow-md'
                                        : 'text-gray-300 hover:bg-[#FAEDCD]/10 hover:text-white'
                                "
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z"
                                    />
                                </svg>
                                <span v-if="isSidebarOpen">Reservasi</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Group: Sistem & Integrasi -->
                    <div>
                        <button
                            @click="toggleMenu('sistem')"
                            class="flex w-full items-center justify-between rounded-xl px-4 py-2 text-xs font-bold tracking-wider text-[#D4A373] uppercase transition hover:bg-[#FAEDCD]/5"
                        >
                            <span v-if="isSidebarOpen">Sistem & Integrasi</span>
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="mx-auto h-5 w-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                />
                            </svg>
                            <svg
                                v-if="isSidebarOpen"
                                :class="{ 'rotate-180': openMenus.sistem }"
                                class="h-4 w-4 transition-transform duration-200"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <div v-show="openMenus.sistem" class="mt-1 space-y-1">
                            <Link
                                v-if="user.role === 'owner'"
                                :href="'/dashboard/integration'"
                                class="flex items-center gap-3 rounded-xl px-4 py-3 transition duration-200"
                                :class="
                                    usePage().url === '/dashboard/integration'
                                        ? 'bg-[#D4A373] font-bold text-[#3B2314] shadow-md'
                                        : 'text-gray-300 hover:bg-[#FAEDCD]/10 hover:text-white'
                                "
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                    />
                                </svg>
                                <span v-if="isSidebarOpen">Integrasi API</span>
                            </Link>

                            <a
                                href="/dashboard/menu-preview"
                                target="_blank"
                                class="flex items-center gap-3 rounded-xl px-4 py-3 text-gray-300 transition duration-200 hover:bg-[#FAEDCD]/10 hover:text-white"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"
                                    />
                                </svg>
                                <span v-if="isSidebarOpen">Lihat Menu QR</span>
                            </a>
                        </div>
                    </div>
                </nav>
            </div>

            <!-- Sidebar Footer / Profile -->
            <div class="border-t border-[#D4A373]/30 p-4">
                <div
                    class="flex items-center justify-between"
                    v-if="isSidebarOpen"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-[#D4A373]/30 bg-[#FAEDCD]/10 font-bold text-white uppercase"
                        >
                            {{ user.name.charAt(0) }}
                        </div>
                        <div>
                            <h4
                                class="max-w-[120px] truncate text-sm font-bold text-white"
                            >
                                {{ user.name }}
                            </h4>
                            <p
                                class="text-xs font-medium text-[#D4A373] capitalize"
                            >
                                {{ user.role }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="handleLogout"
                        class="rounded-lg p-2 text-red-400 hover:bg-[#FAEDCD]/10 hover:text-red-300"
                        title="Logout"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            class="h-5 w-5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15"
                            />
                        </svg>
                    </button>
                </div>
                <button
                    @click="handleLogout"
                    class="flex w-full items-center justify-center rounded-xl py-2 text-red-400 hover:bg-[#FAEDCD]/10 hover:text-red-300"
                    v-else
                    title="Logout"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        class="h-5 w-5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15"
                        />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div
            class="flex min-h-screen min-w-0 flex-1 flex-col overflow-x-hidden"
        >
            <!-- Navbar -->
            <header
                class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-[#D4A373]/20 bg-white px-6 shadow-sm"
            >
                <div class="flex items-center gap-4">
                    <button
                        @click="isSidebarOpen = !isSidebarOpen"
                        class="rounded-lg p-2 text-[#3B2314] hover:bg-gray-100"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-6 w-6"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
                            />
                        </svg>
                    </button>
                    <span
                        class="rounded-full border border-[#D4A373]/30 bg-[#FAEDCD] px-3 py-1 text-xs font-semibold text-[#3B2314]"
                    >
                        Sistem QR Kafe Aktif
                    </span>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p
                            class="font-mono text-[10px] font-bold tracking-widest text-gray-500 uppercase"
                        >
                            {{ user.role }}
                        </p>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6 md:p-8">
                <slot />
            </main>
        </div>

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
    </div>
</template>
