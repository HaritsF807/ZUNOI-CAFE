const fs = require('fs');
const content = fs.readFileSync('resources/js/pages/MenuManagement.vue', 'utf8');

let newContent = content.replace(
    'const props = defineProps({',
    `const props = defineProps({
    globalAddons: Array,`
);

newContent = newContent.replace(
    'const localProducts = ref([...props.products]);',
    `const localProducts = ref([...props.products]);\nconst localGlobalAddons = ref([...(props.globalAddons || [])]);\nconst activeTab = ref('beverages');`
);

newContent = newContent.replace(
    `only: ['products', 'categories'],`,
    `only: ['products', 'categories', 'globalAddons'],`
);

newContent = newContent.replace(
    `localCategories.value = [...page.props.categories];`,
    `localCategories.value = [...page.props.categories];\n            localGlobalAddons.value = [...(page.props.globalAddons || [])];`
);

// Remove Manage Addons logic attached to product
newContent = newContent.replace(/const showAddonsModal = ref[\s\S]*?\/\/ --- REACTIVE FILTERING ---/, `// --- MANAJEMEN ADDON GLOBAL ---
const addonForm = ref({
    id: null,
    addon_name: '',
    extra_price: '',
    category: 'topping',
});
const isEditingAddon = ref(false);

const resetAddonForm = () => {
    addonForm.value = {
        id: null,
        addon_name: '',
        extra_price: '',
        category: 'topping',
    };
    isEditingAddon.value = false;
};

const handleEditAddon = (addon) => {
    addonForm.value = {
        id: addon.id,
        addon_name: addon.name,
        extra_price: addon.price,
        category: addon.category || 'topping',
    };
    isEditingAddon.value = true;
};

const saveAddon = async () => {
    if (!addonForm.value.addon_name || addonForm.value.extra_price === '') {
        triggerToast('Mohon isi Nama Add-on dan Harga!', 'error');
        return;
    }

    try {
        if (isEditingAddon.value) {
            const response = await axios.put(
                \`/api/addons/\${addonForm.value.id}\`,
                {
                    addon_name: addonForm.value.addon_name,
                    extra_price: addonForm.value.extra_price,
                    category: addonForm.value.category,
                },
            );

            if (response.data.success) {
                triggerToast('Add-on berhasil diperbarui!', 'success');
                resetAddonForm();
                syncData();
            }
        } else {
            const response = await axios.post(
                \`/api/addons\`,
                {
                    addon_name: addonForm.value.addon_name,
                    extra_price: addonForm.value.extra_price,
                    category: addonForm.value.category,
                },
            );

            if (response.data.success) {
                triggerToast('Add-on berhasil ditambahkan!', 'success');
                resetAddonForm();
                syncData();
            }
        }
    } catch (error) {
        console.error('Gagal menyimpan addon', error);
        triggerToast('Gagal menyimpan add-on.', 'error');
    }
};

const deleteAddon = async (addonId) => {
    if (!confirm('Apakah Anda yakin ingin menghapus add-on ini?')) {
        return;
    }

    try {
        const response = await axios.delete(\`/api/addons/\${addonId}\`);

        if (response.data.success) {
            triggerToast('Add-on berhasil dihapus!', 'success');
            syncData();
        }
    } catch (error) {
        console.error('Gagal menghapus addon', error);
        triggerToast('Gagal menghapus add-on.', 'error');
    }
};

// --- REACTIVE FILTERING ---`);

// UI Changes
const styleBlock = `
<style scoped>
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.3s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateX(20px);
  opacity: 0;
}
</style>
`;

newContent += styleBlock;

// Insert Tab buttons and transition
const oldSplit = `            <!-- LAYOUT SPLIT: KATEGORI (Kiri) & PRODUK (Kanan) -->
            <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-4">`;
            
const newSplit = `            <!-- TAB SELECTOR -->
            <div class="flex items-center justify-center mb-6">
                <div class="flex rounded-xl bg-gray-100 p-1 shadow-inner">
                    <button @click="activeTab = 'beverages'" :class="activeTab === 'beverages' ? 'bg-[#3B2314] text-white shadow-md' : 'text-gray-500 hover:text-[#3B2314]'" class="px-6 py-2.5 rounded-lg text-xs font-bold transition">Beverages</button>
                    <button @click="activeTab = 'addons'" :class="activeTab === 'addons' ? 'bg-[#3B2314] text-white shadow-md' : 'text-gray-500 hover:text-[#3B2314]'" class="px-6 py-2.5 rounded-lg text-xs font-bold transition">Add-ons</button>
                </div>
            </div>

            <!-- LAYOUT SPLIT: KATEGORI (Kiri) & PRODUK (Kanan) -->
            <div class="relative overflow-hidden min-h-[500px]">
                <Transition name="slide-fade" mode="out-in">
                    <div v-if="activeTab === 'beverages'" key="beverages" class="grid grid-cols-1 items-start gap-8 lg:grid-cols-4 w-full">`;
                    
newContent = newContent.replace(oldSplit, newSplit);

// Close beverages tab and insert addons tab
const endOfBeverages = `                    </div>
                </div>
            </div>
        </div>
    </ZunoiAdminLayout>`;

const addonsView = `                    </div>
                    </div>
                    
                    <div v-else key="addons" class="grid grid-cols-1 items-start gap-8 lg:grid-cols-4 w-full">
                        <div class="space-y-4 rounded-3xl border border-[#D4A373]/20 bg-white p-5 shadow-sm lg:col-span-1">
                            <h4 class="text-xs font-black tracking-wider text-[#3B2314] uppercase">{{ isEditingAddon ? 'Edit Add-on' : 'Tambah Add-on Baru' }}</h4>
                            <div class="space-y-4 mt-4">
                                <div>
                                    <label class="mb-1 block text-xs font-extrabold text-[#3B2314]/80">Nama Add-on *</label>
                                    <input v-model="addonForm.addon_name" type="text" placeholder="Contoh: Caramel Sauce" class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-bold text-[#3B2314] placeholder-gray-400 shadow-inner focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-extrabold text-[#3B2314]/80">Harga Tambahan (Rp) *</label>
                                    <input v-model="addonForm.extra_price" type="number" placeholder="Contoh: 4000" class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-bold text-[#3B2314] placeholder-gray-400 shadow-inner focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]" />
                                </div>
                                <div class="flex justify-end gap-2 border-t border-[#D4A373]/10 pt-4">
                                    <button v-if="isEditingAddon" @click="resetAddonForm" type="button" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-bold text-gray-600 shadow-sm transition hover:bg-gray-50 active:scale-95">Batal</button>
                                    <button @click="saveAddon" type="button" class="rounded-xl bg-[#3B2314] px-5 py-2 text-xs font-bold text-[#FAEDCD] shadow-sm transition hover:bg-[#2A180E] active:scale-95">{{ isEditingAddon ? 'Simpan' : 'Tambah' }}</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-6 rounded-3xl border border-[#D4A373]/20 bg-white p-6 shadow-sm lg:col-span-3">
                            <h4 class="border-b pb-4 text-sm font-black tracking-wider text-[#3B2314] uppercase">Daftar Add-on Aktif ({{ localGlobalAddons.length }})</h4>
                            <div v-if="localGlobalAddons.length === 0" class="py-8 text-center text-xs text-gray-400">Belum ada add-on yang ditambahkan.</div>
                            <div v-else class="divide-y divide-gray-100">
                                <div v-for="addon in localGlobalAddons" :key="addon.id" class="flex items-center justify-between py-4 hover:bg-gray-50/50 px-4 rounded-xl transition">
                                    <div>
                                        <span class="text-sm font-bold text-[#3B2314]">{{ addon.name }}</span>
                                        <div class="mt-1 flex items-center gap-1.5">
                                            <span class="font-mono text-xs font-extrabold text-gray-500">+ Rp {{ parseInt(addon.price).toLocaleString('id-ID') }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button @click="handleEditAddon(addon)" class="rounded-lg p-2 text-blue-500 transition hover:bg-blue-100" title="Edit Addon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.83 20.82a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                        </button>
                                        <button @click="deleteAddon(addon.id)" class="rounded-lg p-2 text-red-500 transition hover:bg-red-100" title="Hapus Addon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </ZunoiAdminLayout>`;
    
newContent = newContent.replace(endOfBeverages, addonsView);

// Remove the addons button in the product card
const addonsBtnRegex = /<!-- Kelola Addons -->[\s\S]*?<\/button>/;
newContent = newContent.replace(addonsBtnRegex, '');

// Remove MODAL MANAGE ADDONS
const modalAddonsRegex = /<!-- MODAL MANAGE ADDONS -->[\s\S]*?<!-- MODAL CATEGORY \(ADD \/ EDIT\) -->/;
newContent = newContent.replace(modalAddonsRegex, '<!-- MODAL CATEGORY (ADD / EDIT) -->');

fs.writeFileSync('resources/js/pages/MenuManagement.vue', newContent);
