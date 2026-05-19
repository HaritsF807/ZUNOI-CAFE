<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { logout } from '@/routes';

const user = usePage().props.auth.user;
const isSidebarOpen = ref(true);

const handleLogout = () => {
    router.post(logout().url);
};
</script>

<template>
    <div class="min-h-screen bg-[#FAEDCD] flex font-sans text-gray-800">
        <!-- Sidebar -->
        <aside 
            class="bg-[#3B2314] text-[#FAEDCD] w-64 h-screen sticky top-0 flex flex-col justify-between transition-all duration-300 z-30"
            :class="{'w-64': isSidebarOpen, 'w-20 overflow-hidden': !isSidebarOpen}"
        >
            <div class="flex flex-col flex-1 overflow-y-auto">
                <!-- Sidebar Header -->
                <div class="p-6 flex items-center justify-between border-b border-[#D4A373]/30">
                    <div class="flex items-center gap-3" v-if="isSidebarOpen">
                        <div class="w-10 h-10 rounded-full bg-[#D4A373] flex items-center justify-center font-bold text-[#3B2314] text-lg shadow">
                            Z
                        </div>
                        <div>
                            <h1 class="font-extrabold text-lg tracking-wider">ZUNOI</h1>
                            <p class="text-[10px] text-[#D4A373] uppercase tracking-widest font-bold">Caffe System</p>
                        </div>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-[#D4A373] flex items-center justify-center font-bold text-[#3B2314] text-lg shadow mx-auto" v-else>
                        Z
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="p-4 space-y-2 mt-6">
                    <Link 
                        :href="'/dashboard'" 
                        class="flex items-center gap-3 px-4 py-3 rounded-xl transition duration-200"
                        :class="usePage().url === '/dashboard' ? 'bg-[#D4A373] text-[#3B2314] font-bold shadow-md' : 'hover:bg-[#FAEDCD]/10 text-gray-300 hover:text-white'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        <span v-if="isSidebarOpen">Dashboard</span>
                    </Link>

                    <Link 
                        :href="'/dashboard/menu'" 
                        class="flex items-center gap-3 px-4 py-3 rounded-xl transition duration-200"
                        :class="usePage().url === '/dashboard/menu' ? 'bg-[#D4A373] text-[#3B2314] font-bold shadow-md' : 'hover:bg-[#FAEDCD]/10 text-gray-300 hover:text-white'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-3.75 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <span v-if="isSidebarOpen">Kelola Menu</span>
                    </Link>

                    <Link 
                        :href="'/dashboard/tables'" 
                        class="flex items-center gap-3 px-4 py-3 rounded-xl transition duration-200"
                        :class="usePage().url === '/dashboard/tables' ? 'bg-[#D4A373] text-[#3B2314] font-bold shadow-md' : 'hover:bg-[#FAEDCD]/10 text-gray-300 hover:text-white'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 8.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                        </svg>
                        <span v-if="isSidebarOpen">Kelola Meja</span>
                    </Link>

                    <Link 
                        v-if="user.role === 'owner'"
                        :href="'/dashboard/integration'" 
                        class="flex items-center gap-3 px-4 py-3 rounded-xl transition duration-200"
                        :class="usePage().url === '/dashboard/integration' ? 'bg-[#D4A373] text-[#3B2314] font-bold shadow-md' : 'hover:bg-[#FAEDCD]/10 text-gray-300 hover:text-white'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <span v-if="isSidebarOpen">Integrasi API</span>
                    </Link>

                    <a 
                        href="/dashboard/menu-preview" 
                        target="_blank"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl transition duration-200 hover:bg-[#FAEDCD]/10 text-gray-300 hover:text-white"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                        <span v-if="isSidebarOpen">Lihat Menu QR</span>
                    </a>
                </nav>
            </div>

            <!-- Sidebar Footer / Profile -->
            <div class="p-4 border-t border-[#D4A373]/30">
                <div class="flex items-center justify-between" v-if="isSidebarOpen">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[#FAEDCD]/10 border border-[#D4A373]/30 flex items-center justify-center font-bold text-white uppercase">
                            {{ user.name.charAt(0) }}
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-white truncate max-w-[120px]">{{ user.name }}</h4>
                            <p class="text-xs text-[#D4A373] capitalize font-medium">{{ user.role }}</p>
                        </div>
                    </div>
                    <button @click="handleLogout" class="text-red-400 hover:text-red-300 p-2 rounded-lg hover:bg-[#FAEDCD]/10" title="Logout">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
                        </svg>
                    </button>
                </div>
                <button @click="handleLogout" class="flex items-center justify-center w-full py-2 text-red-400 hover:text-red-300 hover:bg-[#FAEDCD]/10 rounded-xl" v-else title="Logout">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-h-screen">
            <!-- Navbar -->
            <header class="bg-white border-b border-[#D4A373]/20 h-16 px-6 flex items-center justify-between shadow-sm sticky top-0 z-20">
                <div class="flex items-center gap-4">
                    <button @click="isSidebarOpen = !isSidebarOpen" class="text-[#3B2314] hover:bg-gray-100 p-2 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
                        </svg>
                    </button>
                    <span class="text-xs font-semibold bg-[#FAEDCD] text-[#3B2314] px-3 py-1 rounded-full border border-[#D4A373]/30">
                        Sistem QR Kafe Aktif
                    </span>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-xs font-bold text-[#3B2314]">{{ user.name }}</p>
                        <p class="text-[10px] text-gray-500 uppercase tracking-widest font-bold font-mono">{{ user.role }}</p>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6 md:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
