<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, computed, nextTick, watch } from 'vue';
import ZunoiAdminLayout from '@/layouts/ZunoiAdminLayout.vue';

// Receive banners, vouchers, promotions, and products from server
const props = defineProps({
    banners: {
        type: Array,
        default: () => [],
    },
    vouchers: {
        type: Array,
        default: () => [],
    },
    promotions: {
        type: Array,
        default: () => [],
    },
    products: {
        type: Array,
        default: () => [],
    },
});

const localBanners = ref([...props.banners]);
const localVouchers = ref([...props.vouchers]);

const triggerToast = (message, type = 'success') => {
    window.dispatchEvent(
        new CustomEvent('zunoi-toast', {
            detail: { message, type },
        }),
    );
};

// Form state
const showFormModal = ref(false);
const isEditing = ref(false);
const editingBannerId = ref(null);

const form = ref({
    title: '',
    description: '',
    is_active: true,
});

const fileInputRef = ref(null);
const croppedImageSrc = ref(''); // Base64 data URL for upload

// Crop Editor state
const showCropModal = ref(false);
const rawImageSrc = ref('');
const cropBoxRef = ref(null);
const containerWidth = ref(480);
const containerHeight = computed(() => (containerWidth.value * 9) / 16);
const imageLoaded = ref(false);
const naturalWidth = ref(0);
const naturalHeight = ref(0);

// Drag & Pan state
const isDragging = ref(false);
const startX = ref(0);
const startY = ref(0);
const panX = ref(0);
const panY = ref(0);
const savedPanX = ref(0);
const savedPanY = ref(0);
const zoom = ref(1.0);

const layoutWidth = computed(() => {
    if (!imageLoaded.value) {
        return 0;
    }

    const rNat = naturalWidth.value / naturalHeight.value;
    const rCont = 16 / 9;

    if (rNat > rCont) {
        return containerHeight.value * rNat;
    } else {
        return containerWidth.value;
    }
});

const layoutHeight = computed(() => {
    if (!imageLoaded.value) {
        return 0;
    }

    const rNat = naturalWidth.value / naturalHeight.value;
    const rCont = 16 / 9;

    if (rNat > rCont) {
        return containerHeight.value;
    } else {
        return containerWidth.value / rNat;
    }
});

const applyConstraints = () => {
    const ws = layoutWidth.value * zoom.value;
    const hs = layoutHeight.value * zoom.value;
    const wc = containerWidth.value;
    const hc = containerHeight.value;

    const limitX = Math.max(0, (ws - wc) / 2);
    const limitY = Math.max(0, (hs - hc) / 2);

    if (panX.value > limitX) {
        panX.value = limitX;
    }

    if (panX.value < -limitX) {
        panX.value = -limitX;
    }

    if (panY.value > limitY) {
        panY.value = limitY;
    }

    if (panY.value < -limitY) {
        panY.value = -limitY;
    }
};

watch(zoom, () => {
    applyConstraints();
});

const startDrag = (e) => {
    e.preventDefault();
    isDragging.value = true;
    startX.value = e.clientX;
    startY.value = e.clientY;
    savedPanX.value = panX.value;
    savedPanY.value = panY.value;
};

const onDrag = (e) => {
    if (!isDragging.value) {
        return;
    }

    const dx = e.clientX - startX.value;
    const dy = e.clientY - startY.value;
    panX.value = savedPanX.value + dx;
    panY.value = savedPanY.value + dy;
    applyConstraints();
};

const startDragTouch = (e) => {
    if (e.touches.length !== 1) {
        return;
    }

    isDragging.value = true;
    startX.value = e.touches[0].clientX;
    startY.value = e.touches[0].clientY;
    savedPanX.value = panX.value;
    savedPanY.value = panY.value;
};

const onDragTouch = (e) => {
    if (!isDragging.value || e.touches.length !== 1) {
        return;
    }

    const dx = e.touches[0].clientX - startX.value;
    const dy = e.touches[0].clientY - startY.value;
    panX.value = savedPanX.value + dx;
    panY.value = savedPanY.value + dy;
    applyConstraints();
};

const endDrag = () => {
    isDragging.value = false;
};

// Handle file input selection
const onFileSelected = (e) => {
    const file = e.target.files[0];

    if (!file) {
        return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
        rawImageSrc.value = event.target.result;
        openCropModal();
    };
    reader.readAsDataURL(file);
};

const openCropModal = () => {
    showCropModal.value = true;
    imageLoaded.value = false;
    zoom.value = 1.0;
    panX.value = 0;
    panY.value = 0;

    nextTick(() => {
        if (cropBoxRef.value) {
            containerWidth.value = cropBoxRef.value.clientWidth || 480;
        }
    });
};

const onImageLoaded = (e) => {
    naturalWidth.value = e.target.naturalWidth;
    naturalHeight.value = e.target.naturalHeight;
    imageLoaded.value = true;
    applyConstraints();
};

// Canvas-based cropping to exactly 1280x720 (16:9)
const executeCrop = () => {
    if (!imageLoaded.value) {
        return;
    }

    const canvas = document.createElement('canvas');
    canvas.width = 1280;
    canvas.height = 720;
    const ctx = canvas.getContext('2d');

    const r = 1280 / containerWidth.value;
    const w = layoutWidth.value * zoom.value * r;
    const h = layoutHeight.value * zoom.value * r;
    const x = 1280 / 2 + panX.value * r;
    const y = 720 / 2 + panY.value * r;

    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, 1280, 720);

    const img = new Image();
    img.src = rawImageSrc.value;
    img.onload = () => {
        ctx.drawImage(img, x - w / 2, y - h / 2, w, h);
        croppedImageSrc.value = canvas.toDataURL('image/jpeg', 0.9);
        showCropModal.value = false;
        triggerToast('Gambar berhasil di-crop menjadi 16:9!', 'success');
    };
};

// Form submission
const isSubmitting = ref(false);
const openAddModal = () => {
    isEditing.value = false;
    editingBannerId.value = null;
    form.value = {
        title: '',
        description: '',
        is_active: true,
    };
    croppedImageSrc.value = '';

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }

    showFormModal.value = true;
};

const openEditModal = (banner) => {
    isEditing.value = true;
    editingBannerId.value = banner.id;
    form.value = {
        title: banner.title || '',
        description: banner.description || '',
        is_active: banner.is_active,
    };
    croppedImageSrc.value = banner.image_url;

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }

    showFormModal.value = true;
};

const submitForm = async () => {
    if (!croppedImageSrc.value) {
        triggerToast(
            'Silakan pilih dan crop gambar banner terlebih dahulu!',
            'error',
        );

        return;
    }

    isSubmitting.value = true;

    try {
        const payload = {
            title: form.value.title,
            description: form.value.description,
            is_active: form.value.is_active ? 1 : 0,
        };

        // If it starts with data:image, it means a new crop was generated
        if (croppedImageSrc.value.startsWith('data:image')) {
            payload.image_data = croppedImageSrc.value;
        }

        let response;

        if (isEditing.value) {
            response = await axios.post(
                `/api/promos/${editingBannerId.value}`,
                payload,
            );

            if (response.data.success) {
                const idx = localBanners.value.findIndex(
                    (b) => b.id === editingBannerId.value,
                );

                if (idx !== -1) {
                    localBanners.value[idx] = response.data.banner;
                }

                triggerToast('Promo berhasil diperbarui!', 'success');
            }
        } else {
            response = await axios.post('/api/promos', payload);

            if (response.data.success) {
                localBanners.value.unshift(response.data.banner);
                triggerToast('Promo baru berhasil ditambahkan!', 'success');
            }
        }

        showFormModal.value = false;
    } catch (error) {
        console.error(error);
        const errMsg =
            error.response?.data?.message || 'Gagal menyimpan promo.';
        triggerToast(errMsg, 'error');
    } finally {
        isSubmitting.value = false;
    }
};

// Delete dialog state
const showConfirmModal = ref(false);
const bannerToDelete = ref(null);

const confirmDelete = (banner) => {
    bannerToDelete.value = banner;
    showConfirmModal.value = true;
};

const executeDelete = async () => {
    if (!bannerToDelete.value) {
        return;
    }

    try {
        const response = await axios.delete(
            `/api/promos/${bannerToDelete.value.id}`,
        );

        if (response.data.success) {
            localBanners.value = localBanners.value.filter(
                (b) => b.id !== bannerToDelete.value.id,
            );
            triggerToast('Promo berhasil dihapus!', 'success');
        }
    } catch (error) {
        console.error(error);
        triggerToast('Gagal menghapus promo.', 'error');
    } finally {
        showConfirmModal.value = false;
        bannerToDelete.value = null;
    }
};

// Voucher form & state variables
const showVoucherModal = ref(false);
const isEditingVoucher = ref(false);
const editingVoucherId = ref(null);
const isSubmittingVoucher = ref(false);

const voucherForm = ref({
    code: '',
    name: '',
    discount_type: 'percentage',
    discount_value: 0,
    min_purchase: 0,
    is_active: true,
});

const openAddVoucherModal = () => {
    isEditingVoucher.value = false;
    editingVoucherId.value = null;
    voucherForm.value = {
        code: '',
        name: '',
        discount_type: 'percentage',
        discount_value: 0,
        min_purchase: 0,
        is_active: true,
    };
    showVoucherModal.value = true;
};

const openEditVoucherModal = (voucher) => {
    isEditingVoucher.value = true;
    editingVoucherId.value = voucher.id;
    voucherForm.value = {
        code: voucher.code,
        name: voucher.name,
        discount_type: voucher.discount_type,
        discount_value: parseFloat(voucher.discount_value),
        min_purchase: parseFloat(voucher.min_purchase),
        is_active: !!voucher.is_active,
    };
    showVoucherModal.value = true;
};

const submitVoucherForm = async () => {
    isSubmittingVoucher.value = true;

    try {
        const payload = {
            ...voucherForm.value,
            is_active: voucherForm.value.is_active ? 1 : 0,
        };

        let response;

        if (isEditingVoucher.value) {
            response = await axios.post(
                `/api/vouchers/${editingVoucherId.value}`,
                payload,
            );

            if (response.data.success) {
                const idx = localVouchers.value.findIndex(
                    (v) => v.id === editingVoucherId.value,
                );

                if (idx !== -1) {
                    localVouchers.value[idx] = response.data.voucher;
                }

                triggerToast('Voucher berhasil diperbarui!', 'success');
            }
        } else {
            response = await axios.post('/api/vouchers', payload);

            if (response.data.success) {
                localVouchers.value.unshift(response.data.voucher);
                triggerToast('Voucher baru berhasil ditambahkan!', 'success');
            }
        }

        showVoucherModal.value = false;
    } catch (error) {
        console.error(error);
        const errMsg =
            error.response?.data?.message || 'Gagal menyimpan voucher.';
        triggerToast(errMsg, 'error');
    } finally {
        isSubmittingVoucher.value = false;
    }
};

const showVoucherDeleteModal = ref(false);
const voucherToDelete = ref(null);

const confirmDeleteVoucher = (voucher) => {
    voucherToDelete.value = voucher;
    showVoucherDeleteModal.value = true;
};

const executeDeleteVoucher = async () => {
    if (!voucherToDelete.value) {
return;
}

    try {
        const response = await axios.delete(
            `/api/vouchers/${voucherToDelete.value.id}`,
        );

        if (response.data.success) {
            localVouchers.value = localVouchers.value.filter(
                (v) => v.id !== voucherToDelete.value.id,
            );
            triggerToast('Voucher berhasil dihapus!', 'success');
        }
    } catch (error) {
        console.error(error);
        triggerToast('Gagal menghapus voucher.', 'error');
    } finally {
        showVoucherDeleteModal.value = false;
        voucherToDelete.value = null;
    }
};

const toggleVoucherStatus = async (voucher) => {
    try {
        const newStatus = !voucher.is_active;
        const response = await axios.post(`/api/vouchers/${voucher.id}`, {
            code: voucher.code,
            name: voucher.name,
            discount_type: voucher.discount_type,
            discount_value: voucher.discount_value,
            min_purchase: voucher.min_purchase,
            is_active: newStatus ? 1 : 0,
        });

        if (response.data.success) {
            voucher.is_active = newStatus;
            triggerToast(
                `Voucher berhasil ${newStatus ? 'diaktifkan' : 'dinonaktifkan'}!`,
                'success',
            );
        }
    } catch (error) {
        console.error(error);
        triggerToast('Gagal mengubah status voucher.', 'error');
    }
};

// Promotions state & CRUD
const user = usePage().props.auth.user;
const isAllowedRole = computed(() => ['owner', 'barista'].includes(user.role));

const localPromotions = ref([...props.promotions]);
const showPromoDealModal = ref(false);
const isEditingPromoDeal = ref(false);
const editingPromoDealId = ref(null);
const isSubmittingPromoDeal = ref(false);

const promoDealForm = ref({
    name: '',
    type: 'bundling',
    buy_product_id: '',
    buy_quantity: 1,
    bundling_product_id: '',
    get_product_id: '',
    get_quantity: 1,
    discount_type: 'nominal',
    discount_value: 0,
    is_active: true,
});

const openAddPromoDealModal = () => {
    isEditingPromoDeal.value = false;
    editingPromoDealId.value = null;
    promoDealForm.value = {
        name: '',
        type: 'bundling',
        buy_product_id: props.products[0]?.id || '',
        buy_quantity: 1,
        bundling_product_id: props.products[1]?.id || '',
        get_product_id: props.products[0]?.id || '',
        get_quantity: 1,
        discount_type: 'nominal',
        discount_value: 0,
        is_active: true,
    };
    showPromoDealModal.value = true;
};

const openEditPromoDealModal = (promo) => {
    isEditingPromoDeal.value = true;
    editingPromoDealId.value = promo.id;
    promoDealForm.value = {
        name: promo.name,
        type: promo.type,
        buy_product_id: promo.buy_product_id,
        buy_quantity: promo.buy_quantity,
        bundling_product_id: promo.bundling_product_id || '',
        get_product_id: promo.get_product_id || '',
        get_quantity: promo.get_quantity || 1,
        discount_type: promo.discount_type,
        discount_value: parseFloat(promo.discount_value),
        is_active: !!promo.is_active,
    };
    showPromoDealModal.value = true;
};

const submitPromoDealForm = async () => {
    isSubmittingPromoDeal.value = true;

    try {
        const payload = {
            ...promoDealForm.value,
            is_active: promoDealForm.value.is_active ? 1 : 0,
        };

        let response;

        if (isEditingPromoDeal.value) {
            response = await axios.post(
                `/api/promotions/${editingPromoDealId.value}`,
                payload,
            );

            if (response.data.success) {
                const idx = localPromotions.value.findIndex(
                    (p) => p.id === editingPromoDealId.value,
                );

                if (idx !== -1) {
                    localPromotions.value[idx] = response.data.promotion;
                }

                triggerToast('Promo berhasil diperbarui!', 'success');
            }
        } else {
            response = await axios.post('/api/promotions', payload);

            if (response.data.success) {
                localPromotions.value.unshift(response.data.promotion);
                triggerToast('Promo baru berhasil ditambahkan!', 'success');
            }
        }

        showPromoDealModal.value = false;
    } catch (error) {
        console.error(error);
        const errMsg =
            error.response?.data?.message || 'Gagal menyimpan promo.';
        triggerToast(errMsg, 'error');
    } finally {
        isSubmittingPromoDeal.value = false;
    }
};

const showPromoDealDeleteModal = ref(false);
const promoDealToDelete = ref(null);

const confirmDeletePromoDeal = (promo) => {
    promoDealToDelete.value = promo;
    showPromoDealDeleteModal.value = true;
};

const executeDeletePromoDeal = async () => {
    if (!promoDealToDelete.value) {
return;
}

    try {
        const response = await axios.delete(
            `/api/promotions/${promoDealToDelete.value.id}`,
        );

        if (response.data.success) {
            localPromotions.value = localPromotions.value.filter(
                (p) => p.id !== promoDealToDelete.value.id,
            );
            triggerToast('Promo berhasil dihapus!', 'success');
        }
    } catch (error) {
        console.error(error);
        triggerToast('Gagal menghapus promo.', 'error');
    } finally {
        showPromoDealDeleteModal.value = false;
        promoDealToDelete.value = null;
    }
};

const togglePromoDealStatus = async (promo) => {
    try {
        const newStatus = !promo.is_active;
        const response = await axios.post(`/api/promotions/${promo.id}`, {
            name: promo.name,
            type: promo.type,
            buy_product_id: promo.buy_product_id,
            buy_quantity: promo.buy_quantity,
            bundling_product_id: promo.bundling_product_id,
            get_product_id: promo.get_product_id,
            get_quantity: promo.get_quantity,
            discount_type: promo.discount_type,
            discount_value: promo.discount_value,
            is_active: newStatus ? 1 : 0,
        });

        if (response.data.success) {
            promo.is_active = newStatus;
            triggerToast(
                `Promo berhasil ${newStatus ? 'diaktifkan' : 'dinonaktifkan'}!`,
                'success',
            );
        }
    } catch (error) {
        console.error(error);
        triggerToast('Gagal mengubah status promo.', 'error');
    }
};
</script>

<template>
    <Head title="Kelola Promo" />

    <ZunoiAdminLayout>
        <div class="mx-auto max-w-7xl space-y-8 text-gray-800">
            <!-- Header Halaman -->
            <div
                class="relative overflow-hidden rounded-xl border border-[#D4A373]/20 bg-white p-6 shadow-sm md:p-8"
            >
                <div
                    class="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-[#D4A373]/10 blur-2xl"
                ></div>
                <div
                    class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h1 class="text-2xl font-black text-[#3B2314]">
                            Kelola Promo & Banner
                        </h1>
                        <p class="text-xs text-gray-500">
                            Atur banner promosi berasio 16:9 yang tampil di
                            halaman pemesanan menu kafe.
                        </p>
                    </div>
                    <button
                        @click="openAddModal"
                        class="flex shrink-0 items-center justify-center gap-2 rounded-lg bg-[#3B2314] px-5 py-3 text-xs font-black text-[#FAEDCD] shadow-md transition hover:bg-[#2A180E] active:scale-95"
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
                                d="M12 4.5v15m7.5-7.5h-15"
                            />
                        </svg>
                        Tambah Promo
                    </button>
                </div>
            </div>

            <!-- List Banner Promo -->
            <div
                v-if="localBanners.length > 0"
                class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="banner in localBanners"
                    :key="banner.id"
                    class="group overflow-hidden rounded-lg border border-[#D4A373]/20 bg-white shadow-sm transition hover:shadow-md"
                >
                    <!-- Tampilan Banner 16:9 -->
                    <div
                        class="relative aspect-[16/9] w-full overflow-hidden bg-gray-100"
                    >
                        <img
                            :src="banner.image_url"
                            :alt="banner.title || 'Promo'"
                            class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                        />
                        <div
                            class="absolute top-3 right-3 rounded-full px-2.5 py-1 text-[10px] font-bold shadow-sm"
                            :class="
                                banner.is_active
                                    ? 'bg-emerald-100 text-emerald-800'
                                    : 'bg-gray-100 text-gray-500'
                            "
                        >
                            {{ banner.is_active ? 'Aktif' : 'Nonaktif' }}
                        </div>
                    </div>

                    <!-- Informasi Promo & Aksi -->
                    <div class="space-y-3 p-5">
                        <div>
                            <h3
                                class="line-clamp-1 font-extrabold text-[#3B2314]"
                            >
                                {{ banner.title || 'Tanpa Judul' }}
                            </h3>
                            <p
                                class="mt-1 line-clamp-2 min-h-[2rem] text-xs text-gray-500"
                            >
                                {{
                                    banner.description || 'Tidak ada deskripsi.'
                                }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2 border-t pt-3">
                            <button
                                @click="openEditModal(banner)"
                                class="flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-[#D4A373]/40 py-2 text-xs font-black text-[#3B2314] transition hover:bg-[#FAEDCD]/30 active:scale-98"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-3.5 w-3.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.83 20.82a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                    />
                                </svg>
                                Edit
                            </button>
                            <button
                                @click="confirmDelete(banner)"
                                class="rounded-lg border border-red-200 bg-red-50 p-2 text-red-600 transition hover:border-red-300 hover:bg-red-100 active:scale-98"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-4 w-4"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Pembatas & Heading Kelola Voucher -->
            <div
                class="relative overflow-hidden rounded-xl border border-[#D4A373]/20 bg-white p-6 shadow-sm md:p-8"
            >
                <div
                    class="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-[#D4A373]/10 blur-2xl"
                ></div>
                <div
                    class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2 class="text-2xl font-black text-[#3B2314]">
                            Kelola Voucher Promo
                        </h2>
                        <p class="text-xs text-gray-500">
                            Buat, edit, dan atur kupon diskon nominal atau
                            persentase untuk pemesanan pelanggan.
                        </p>
                    </div>
                    <button
                        @click="openAddVoucherModal"
                        class="flex shrink-0 items-center justify-center gap-2 rounded-lg bg-[#3B2314] px-5 py-3 text-xs font-black text-[#FAEDCD] shadow-md transition hover:bg-[#2A180E] active:scale-95"
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
                                d="M12 4.5v15m7.5-7.5h-15"
                            />
                        </svg>
                        Tambah Voucher
                    </button>
                </div>
            </div>

            <div
                v-if="localVouchers.length > 0"
                class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="voucher in localVouchers"
                    :key="voucher.id"
                    class="group flex flex-col justify-between overflow-hidden rounded-lg border border-[#D4A373]/20 bg-white shadow-sm transition hover:shadow-md"
                >
                    <!-- Header Voucher Card -->
                    <div
                        class="relative flex flex-col gap-2 bg-gradient-to-r from-[#3B2314] to-[#4e301d] p-5 text-white"
                    >
                        <!-- Switch Toggle Active Status -->
                        <div
                            class="absolute top-4 right-4 flex items-center gap-1.5 rounded-full bg-black/25 px-2.5 py-1 backdrop-blur-sm"
                        >
                            <span
                                class="text-[9px] font-black text-[#FAEDCD] uppercase"
                            >
                                {{ voucher.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            <button
                                @click="toggleVoucherStatus(voucher)"
                                class="relative inline-flex h-4 w-7 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="
                                    voucher.is_active
                                        ? 'bg-emerald-500'
                                        : 'bg-gray-400'
                                "
                            >
                                <span
                                    class="pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="
                                        voucher.is_active
                                            ? 'translate-x-3'
                                            : 'translate-x-0'
                                    "
                                ></span>
                            </button>
                        </div>

                        <span
                            class="inline-block self-start rounded-lg bg-[#FAEDCD] px-3 py-1 text-xs font-black tracking-wider text-[#3B2314] uppercase shadow-sm"
                        >
                            {{ voucher.code }}
                        </span>

                        <h3
                            class="mt-1 line-clamp-1 text-sm font-extrabold text-[#FAEDCD]"
                        >
                            {{ voucher.name }}
                        </h3>
                    </div>

                    <!-- Detail & Action Voucher -->
                    <div class="flex flex-1 flex-col justify-between gap-4 p-5">
                        <div class="space-y-2 text-xs">
                            <div
                                class="flex items-center justify-between text-gray-500"
                            >
                                <span>Tipe Potongan</span>
                                <span
                                    class="font-bold text-[#3B2314] uppercase"
                                >
                                    {{
                                        voucher.discount_type === 'percentage'
                                            ? 'Persentase (%)'
                                            : 'Nominal (Rupiah)'
                                    }}
                                </span>
                            </div>
                            <div
                                class="flex items-center justify-between text-gray-500"
                            >
                                <span>Nilai Potongan</span>
                                <span class="font-extrabold text-emerald-600">
                                    {{
                                        voucher.discount_type === 'percentage'
                                            ? `${parseFloat(voucher.discount_value)}%`
                                            : `Rp ${parseFloat(voucher.discount_value).toLocaleString('id-ID')}`
                                    }}
                                </span>
                            </div>
                            <div
                                class="flex items-center justify-between text-gray-500"
                            >
                                <span>Minimal Belanja</span>
                                <span class="font-bold text-[#3B2314]">
                                    Rp
                                    {{
                                        parseFloat(
                                            voucher.min_purchase,
                                        ).toLocaleString('id-ID')
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Aksi Button -->
                        <div class="flex items-center gap-2 border-t pt-3">
                            <button
                                @click="openEditVoucherModal(voucher)"
                                class="flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-[#D4A373]/40 py-2 text-xs font-black text-[#3B2314] transition hover:bg-[#FAEDCD]/30 active:scale-98"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-3.5 w-3.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.83 20.82a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                    />
                                </svg>
                                Edit
                            </button>
                            <button
                                @click="confirmDeleteVoucher(voucher)"
                                class="rounded-lg border border-red-200 bg-red-50 p-2 text-red-600 transition hover:border-red-300 hover:bg-red-100 active:scale-98"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-4 w-4"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION KELOLA POTONGAN & BUY 1 GET 1 (Hanya Owner & Barista) -->
            <div v-if="isAllowedRole" class="space-y-8">
                <!-- Section Pembatas & Heading -->
                <div
                    class="relative overflow-hidden rounded-xl border border-[#D4A373]/20 bg-white p-6 shadow-sm md:p-8"
                >
                    <div
                        class="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-[#D4A373]/10 blur-2xl"
                    ></div>
                    <div
                        class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <h2 class="text-2xl font-black text-[#3B2314]">
                                Kelola Potongan & Promo Deal
                            </h2>
                            <p class="text-xs text-gray-500">
                                Buat dan kelola paket promo bundling item
                                (contoh: Kopi + Makanan) atau Buy 1 Get 1 (Beli
                                X Gratis Y).
                            </p>
                        </div>
                        <button
                            @click="openAddPromoDealModal"
                            class="flex shrink-0 items-center justify-center gap-2 rounded-lg bg-[#3B2314] px-5 py-3 text-xs font-black text-[#FAEDCD] shadow-md transition hover:bg-[#2A180E] active:scale-95"
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
                                    d="M12 4.5v15m7.5-7.5h-15"
                                />
                            </svg>
                            Tambah Promo Deal
                        </button>
                    </div>
                </div>

                <!-- Grid List Promo Deal -->
                <div
                    v-if="localPromotions.length > 0"
                    class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="promo in localPromotions"
                        :key="promo.id"
                        class="group flex flex-col justify-between overflow-hidden rounded-lg border border-[#D4A373]/20 bg-white shadow-sm transition hover:shadow-md"
                    >
                        <!-- Header Card -->
                        <div
                            class="relative flex flex-col gap-2 bg-gradient-to-r from-[#5C3E21] to-[#3B2314] p-5 text-white"
                        >
                            <!-- Toggle switch status aktif -->
                            <div
                                class="absolute top-4 right-4 flex items-center gap-1.5 rounded-full bg-black/25 px-2.5 py-1 backdrop-blur-sm"
                            >
                                <span
                                    class="text-[9px] font-black text-[#FAEDCD] uppercase"
                                >
                                    {{ promo.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                <button
                                    @click="togglePromoDealStatus(promo)"
                                    class="relative inline-flex h-4 w-7 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="
                                        promo.is_active
                                            ? 'bg-emerald-500'
                                            : 'bg-gray-400'
                                    "
                                >
                                    <span
                                        class="pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                        :class="
                                            promo.is_active
                                                ? 'translate-x-3'
                                                : 'translate-x-0'
                                        "
                                    ></span>
                                </button>
                            </div>

                            <span
                                class="inline-block self-start rounded-lg bg-[#FAEDCD] px-3 py-1 text-[10px] font-black tracking-wider text-[#3B2314] uppercase shadow-sm"
                            >
                                {{
                                    promo.type === 'bundling'
                                        ? 'Paket Bundling'
                                        : 'Buy X Get Y'
                                }}
                            </span>

                            <h3
                                class="mt-1 line-clamp-1 text-sm font-extrabold text-[#FAEDCD]"
                            >
                                {{ promo.name }}
                            </h3>
                        </div>

                        <!-- Details & Actions -->
                        <div
                            class="flex flex-1 flex-col justify-between gap-4 p-5"
                        >
                            <!-- Detail Kondisi Promo -->
                            <div class="space-y-3 text-xs">
                                <!-- Bundling Info -->
                                <div
                                    v-if="promo.type === 'bundling'"
                                    class="space-y-2"
                                >
                                    <div
                                        class="flex flex-col gap-1 rounded border bg-gray-50 p-2"
                                    >
                                        <span
                                            class="text-[10px] font-bold text-gray-400 uppercase"
                                            >Item Wajib Beli:</span
                                        >
                                        <div
                                            class="flex items-center justify-between text-[#3B2314]"
                                        >
                                            <span class="font-black"
                                                >{{ promo.buy_quantity }}x
                                                {{
                                                    promo.buy_product?.name ||
                                                    'Produk Utama'
                                                }}</span
                                            >
                                        </div>
                                        <div
                                            class="mt-1 flex items-center justify-between border-t pt-1 text-[#3B2314]"
                                        >
                                            <span class="font-black"
                                                >1x
                                                {{
                                                    promo.bundling_product
                                                        ?.name ||
                                                    'Produk Bundling'
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                    <div
                                        class="flex items-center justify-between text-gray-500"
                                    >
                                        <span>Reward Diskon</span>
                                        <span
                                            class="text-sm font-extrabold text-emerald-600"
                                        >
                                            {{
                                                promo.discount_type ===
                                                'percentage'
                                                    ? `Potongan ${parseFloat(promo.discount_value)}%`
                                                    : `Potongan Rp ${parseFloat(promo.discount_value).toLocaleString('id-ID')}`
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Buy Get Info -->
                                <div v-else class="space-y-2">
                                    <div
                                        class="flex flex-col gap-1 rounded border bg-gray-50 p-2"
                                    >
                                        <span
                                            class="text-[10px] font-bold text-gray-400 uppercase"
                                            >Beli Produk:</span
                                        >
                                        <span class="font-black text-[#3B2314]"
                                            >{{ promo.buy_quantity }}x
                                            {{
                                                promo.buy_product?.name ||
                                                'Produk Utama'
                                            }}</span
                                        >
                                        <span
                                            class="mt-1 border-t pt-1 text-[10px] font-bold text-gray-400 uppercase"
                                            >Mendapatkan Bonus:</span
                                        >
                                        <span
                                            class="font-black text-emerald-600"
                                            >{{ promo.get_quantity }}x
                                            {{
                                                promo.get_product?.name ||
                                                'Produk Bonus'
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center justify-between text-gray-500"
                                    >
                                        <span>Reward Bonus</span>
                                        <span
                                            class="text-sm font-extrabold text-emerald-600"
                                        >
                                            {{
                                                promo.discount_type === 'free'
                                                    ? 'GRATIS'
                                                    : `Potongan Rp ${parseFloat(promo.discount_value).toLocaleString('id-ID')}`
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex items-center gap-2 border-t pt-3">
                                <button
                                    @click="openEditPromoDealModal(promo)"
                                    class="flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-[#D4A373]/40 py-2 text-xs font-black text-[#3B2314] transition hover:bg-[#FAEDCD]/30 active:scale-98"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                        class="h-3.5 w-3.5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.83 20.82a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                        />
                                    </svg>
                                    Edit
                                </button>
                                <button
                                    @click="confirmDeletePromoDeal(promo)"
                                    class="rounded-lg border border-red-200 bg-red-50 p-2 text-red-600 transition hover:border-red-300 hover:bg-red-100 active:scale-98"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                        class="h-4 w-4"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL FORM PROMO (TAMBAH/EDIT) -->
        <div
            v-if="showFormModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm transition-opacity duration-300"
        >
            <div
                class="flex max-h-[90vh] w-full max-w-lg scale-100 transform flex-col overflow-hidden rounded-xl border border-[#D4A373]/30 bg-white shadow-2xl transition-all duration-300"
            >
                <!-- Header Modal -->
                <div
                    class="flex items-center justify-between bg-[#3B2314] px-6 py-4 text-white"
                >
                    <h3 class="truncate text-sm font-extrabold text-[#FAEDCD]">
                        {{
                            isEditing
                                ? 'Edit Banner Promo'
                                : 'Tambah Banner Promo'
                        }}
                    </h3>
                    <button
                        @click="showFormModal = false"
                        class="rounded-full p-1 text-[#FAEDCD]/80 transition hover:bg-[#FAEDCD]/10 hover:text-white"
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
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Form Body (Scrollable) -->
                <form
                    @submit.prevent="submitForm"
                    class="flex-1 space-y-5 overflow-y-auto p-6"
                >
                    <!-- Input File Gambar & Pratinjau Crop -->
                    <div class="space-y-2">
                        <label
                            class="block text-xs font-extrabold text-[#3B2314]/80"
                        >
                            Gambar Banner (Rasio 16:9) *
                        </label>

                        <!-- Box Pratinjau Ter-crop -->
                        <div
                            v-if="croppedImageSrc"
                            class="group relative aspect-[16/9] w-full overflow-hidden rounded-lg border border-dashed border-[#D4A373]/40 bg-gray-50"
                        >
                            <img
                                :src="croppedImageSrc"
                                class="h-full w-full object-cover"
                            />
                            <div
                                class="absolute inset-0 flex items-center justify-center gap-2 bg-black/40 opacity-0 transition duration-200 group-hover:opacity-100"
                            >
                                <button
                                    type="button"
                                    @click="() => fileInputRef.click()"
                                    class="rounded-lg bg-[#FAEDCD] px-3.5 py-1.5 text-[11px] font-bold text-[#3B2314] transition hover:bg-white active:scale-95"
                                >
                                    Ubah Gambar
                                </button>
                            </div>
                        </div>

                        <!-- Box Upload Kosong -->
                        <div
                            v-else
                            @click="() => fileInputRef.click()"
                            class="flex aspect-[16/9] w-full cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-[#D4A373]/30 bg-gray-50/50 py-6 text-center transition hover:bg-gray-50"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="mb-2 h-8 w-8 text-[#D4A373]"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z"
                                />
                            </svg>
                            <span
                                class="text-[11px] font-bold text-[#3B2314]/80"
                                >Unggah & Crop Gambar</span
                            >
                            <span class="mt-1 text-[10px] text-gray-400"
                                >Format JPG, PNG, atau WEBP</span
                            >
                        </div>

                        <input
                            type="file"
                            ref="fileInputRef"
                            class="hidden"
                            accept="image/*"
                            @change="onFileSelected"
                        />
                    </div>

                    <!-- Input Judul -->
                    <div class="space-y-1">
                        <label
                            class="block text-xs font-extrabold text-[#3B2314]/80"
                        >
                            Judul Promo (Opsional)
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            placeholder="Controw: Paket Coffee & Donut Hemat"
                            class="w-full rounded-lg border border-gray-200 px-4 py-2.5 text-xs text-[#3B2314] placeholder-gray-400 outline-none focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                        />
                    </div>

                    <!-- Input Deskripsi -->
                    <div class="space-y-1">
                        <label
                            class="block text-xs font-extrabold text-[#3B2314]/80"
                        >
                            Deskripsi Promo (Opsional)
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            placeholder="Jelaskan detail promo atau diskon menarik di sini..."
                            class="w-full resize-none rounded-lg border border-gray-200 px-4 py-2.5 text-xs text-[#3B2314] placeholder-gray-400 outline-none focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                        ></textarea>
                    </div>

                    <!-- Switch Aktif/Nonaktif -->
                    <div
                        class="flex items-center justify-between border-t pt-4"
                    >
                        <div>
                            <span
                                class="block text-xs font-extrabold text-[#3B2314]"
                                >Status Banner</span
                            >
                            <span class="block text-[10px] text-gray-400"
                                >Tampilkan promo langsung di halaman
                                pemesanan.</span
                            >
                        </div>
                        <button
                            type="button"
                            @click="form.is_active = !form.is_active"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                            :class="
                                form.is_active
                                    ? 'bg-emerald-500'
                                    : 'bg-gray-200'
                            "
                        >
                            <span
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                :class="
                                    form.is_active
                                        ? 'translate-x-5'
                                        : 'translate-x-0'
                                "
                            />
                        </button>
                    </div>
                </form>

                <!-- Footer Modal -->
                <div
                    class="flex items-center justify-end gap-3 border-t bg-gray-50 px-6 py-4"
                >
                    <button
                        type="button"
                        @click="showFormModal = false"
                        class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-500 transition hover:bg-gray-50 active:scale-95"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitForm"
                        :disabled="isSubmitting"
                        class="flex items-center gap-1.5 rounded-lg bg-[#3B2314] px-5 py-2.5 text-xs font-bold text-[#FAEDCD] shadow-sm transition hover:bg-[#2A180E] active:scale-95 disabled:opacity-55"
                    >
                        <span v-if="isSubmitting">Menyimpan...</span>
                        <span v-else>Simpan Promo</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL CROP GAMBAR 16:9 KUSTOM -->
        <div
            v-if="showCropModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 p-4 backdrop-blur-md transition-opacity duration-300"
        >
            <div
                class="flex max-h-[95vh] w-full max-w-xl scale-100 transform flex-col overflow-hidden rounded-xl border border-[#D4A373]/30 bg-white shadow-2xl transition-all duration-300"
            >
                <!-- Header Crop Modal -->
                <div
                    class="flex items-center justify-between bg-[#3B2314] px-6 py-4 text-white"
                >
                    <h3 class="text-sm font-extrabold text-[#FAEDCD]">
                        Pemotong Gambar Banner (16:9)
                    </h3>
                    <button
                        @click="showCropModal = false"
                        class="rounded-full p-1 text-[#FAEDCD]/80 transition hover:bg-[#FAEDCD]/10 hover:text-white"
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
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Crop Body -->
                <div class="flex-1 space-y-6 overflow-y-auto p-6">
                    <p class="text-center text-[11px] text-gray-500">
                        Geser gambar di dalam bingkai dan gunakan slider untuk
                        memperbesar agar posisi crop pas 16:9.
                    </p>

                    <!-- Workspace Pemotong Gambar -->
                    <div
                        ref="cropBoxRef"
                        class="relative aspect-[16/9] w-full cursor-move overflow-hidden rounded-lg border border-dashed border-[#D4A373]/60 bg-black"
                        @mousedown="startDrag"
                        @mousemove="onDrag"
                        @mouseup="endDrag"
                        @mouseleave="endDrag"
                        @touchstart="startDragTouch"
                        @touchmove="onDragTouch"
                        @touchend="endDrag"
                    >
                        <img
                            :src="rawImageSrc"
                            class="pointer-events-none absolute max-w-none origin-center select-none"
                            :style="{
                                width: layoutWidth + 'px',
                                height: layoutHeight + 'px',
                                transform: `translate(-50%, -50%) translate(${panX}px, ${panY}px) scale(${zoom})`,
                                left: '50%',
                                top: '50%',
                            }"
                            @load="onImageLoaded"
                        />

                        <!-- Grid Mask Visual overlay 16:9 -->
                        <div
                            class="pointer-events-none absolute inset-0 flex flex-col justify-between border border-white/40"
                        >
                            <div class="flex w-full justify-between">
                                <div
                                    class="m-3 h-6 w-6 border-t-2 border-l-2 border-white"
                                ></div>
                                <div
                                    class="m-3 h-6 w-6 border-t-2 border-r-2 border-white"
                                ></div>
                            </div>
                            <div class="flex w-full justify-between">
                                <div
                                    class="m-3 h-6 w-6 border-b-2 border-l-2 border-white"
                                ></div>
                                <div
                                    class="m-3 h-6 w-6 border-r-2 border-b-2 border-white"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Slider Zoom -->
                    <div class="space-y-2">
                        <div
                            class="flex items-center justify-between text-xs font-bold text-[#3B2314]"
                        >
                            <span>Perbesar Gambar (Zoom)</span>
                            <span class="font-mono text-[#D4A373]"
                                >{{ Math.round(zoom * 100) }}%</span
                            >
                        </div>
                        <div class="flex items-center gap-3">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="h-4 w-4 shrink-0 text-gray-400"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.637 10.637Z"
                                />
                            </svg>
                            <input
                                v-model.number="zoom"
                                type="range"
                                min="1"
                                max="3"
                                step="0.01"
                                class="w-full cursor-pointer accent-[#3B2314]"
                            />
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="h-4 w-4 shrink-0 text-[#3B2314]"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.637 10.637ZM10.5 7.5v6m3-3h-6"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Footer Crop Modal -->
                <div
                    class="flex items-center justify-between border-t bg-gray-50 px-6 py-4"
                >
                    <button
                        type="button"
                        @click="showCropModal = false"
                        class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-500 transition hover:bg-gray-50 active:scale-95"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeCrop"
                        class="rounded-lg bg-[#3B2314] px-5 py-2.5 text-xs font-bold text-[#FAEDCD] shadow-md transition hover:bg-[#2A180E] active:scale-95"
                    >
                        Potong & Terapkan (16:9)
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL KONFIRMASI HAPUS -->
        <div
            v-if="showConfirmModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm transition-opacity duration-300"
        >
            <div
                class="flex w-full max-w-sm scale-100 transform flex-col overflow-hidden rounded-xl border border-red-100 bg-white p-6 text-center shadow-2xl transition-all duration-300"
            >
                <div
                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        class="h-6 w-6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                        />
                    </svg>
                </div>
                <h3 class="text-sm font-black text-gray-800">
                    Hapus Banner Promo?
                </h3>
                <p class="mt-2 text-xs text-gray-500">
                    Tindakan ini permanen. Gambar banner akan dihapus dari
                    server dan tidak dapat dikembalikan.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <button
                        @click="showConfirmModal = false"
                        class="flex-1 rounded-lg border border-gray-200 bg-white py-2.5 text-xs font-bold text-gray-500 transition hover:bg-gray-50 active:scale-95"
                    >
                        Batal
                    </button>
                    <button
                        @click="executeDelete"
                        class="flex-1 rounded-lg bg-red-600 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-red-700 active:scale-95"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
        <!-- MODAL FORM VOUCHER (TAMBAH/EDIT) -->
        <div
            v-if="showVoucherModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm transition-opacity duration-300"
        >
            <div
                class="flex max-h-[90vh] w-full max-w-lg scale-100 transform flex-col overflow-hidden rounded-xl border border-[#D4A373]/30 bg-white shadow-2xl transition-all duration-300"
            >
                <!-- Header Modal -->
                <div
                    class="flex items-center justify-between bg-[#3B2314] px-6 py-4 text-white"
                >
                    <h3 class="truncate text-sm font-extrabold text-[#FAEDCD]">
                        {{
                            isEditingVoucher
                                ? 'Edit Voucher Promo'
                                : 'Tambah Voucher Baru'
                        }}
                    </h3>
                    <button
                        @click="showVoucherModal = false"
                        class="rounded-full p-1 text-[#FAEDCD]/80 transition hover:bg-[#FAEDCD]/10 hover:text-white"
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
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Form Body -->
                <form
                    @submit.prevent="submitVoucherForm"
                    class="flex-1 space-y-4 overflow-y-auto p-6"
                >
                    <!-- Kode Voucher -->
                    <div>
                        <label
                            class="mb-1 block text-xs font-bold text-gray-700"
                            >Kode Voucher</label
                        >
                        <input
                            v-model="voucherForm.code"
                            type="text"
                            placeholder="Contoh: COFFEEHEBAT"
                            required
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-xs font-bold text-[#3B2314] uppercase placeholder-[#3B2314]/30 shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                        />
                        <p class="mt-1 text-[10px] text-gray-400">
                            Kode unik yang diinput oleh pelanggan saat checkout.
                        </p>
                    </div>

                    <!-- Nama Voucher -->
                    <div>
                        <label
                            class="mb-1 block text-xs font-bold text-gray-700"
                            >Nama Voucher</label
                        >
                        <input
                            v-model="voucherForm.name"
                            type="text"
                            placeholder="Contoh: Diskon Kopi 10%"
                            required
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-xs text-[#3B2314] placeholder-gray-400 shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                        />
                    </div>

                    <!-- Grid: Tipe Potongan & Nilai Potongan -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold text-gray-700"
                                >Tipe Potongan</label
                            >
                            <select
                                v-model="voucherForm.discount_type"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-xs font-bold text-[#3B2314] shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                            >
                                <option value="percentage">
                                    Persentase (%)
                                </option>
                                <option value="nominal">
                                    Nominal (Rupiah)
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold text-gray-700"
                            >
                                {{
                                    voucherForm.discount_type === 'percentage'
                                        ? 'Nilai Potongan (%)'
                                        : 'Nilai Potongan (Rp)'
                                }}
                            </label>
                            <input
                                v-model.number="voucherForm.discount_value"
                                type="number"
                                min="0"
                                required
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 font-mono text-xs font-bold text-[#3B2314] shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                            />
                        </div>
                    </div>

                    <!-- Minimal Pembelian -->
                    <div>
                        <label
                            class="mb-1 block text-xs font-bold text-gray-700"
                            >Minimal Pembelian (Rp)</label
                        >
                        <input
                            v-model.number="voucherForm.min_purchase"
                            type="number"
                            min="0"
                            required
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 font-mono text-xs font-bold text-[#3B2314] shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                        />
                    </div>

                    <!-- Status Aktif Switch -->
                    <div
                        class="flex items-center justify-between rounded-lg border bg-gray-50 p-3.5"
                    >
                        <div>
                            <span
                                class="block text-xs font-extrabold text-[#3B2314]"
                                >Aktifkan Voucher</span
                            >
                            <span class="text-[10px] text-gray-500"
                                >Tentukan apakah voucher ini bisa langsung
                                digunakan oleh pelanggan.</span
                            >
                        </div>
                        <button
                            type="button"
                            @click="
                                voucherForm.is_active = !voucherForm.is_active
                            "
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                            :class="
                                voucherForm.is_active
                                    ? 'bg-emerald-500'
                                    : 'bg-gray-300'
                            "
                        >
                            <span
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                :class="
                                    voucherForm.is_active
                                        ? 'translate-x-5'
                                        : 'translate-x-0'
                                "
                            ></span>
                        </button>
                    </div>

                    <!-- Footer Modal Buttons -->
                    <div
                        class="mt-6 flex items-center justify-end gap-3 border-t pt-4"
                    >
                        <button
                            type="button"
                            @click="showVoucherModal = false"
                            class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-500 transition hover:bg-gray-50 active:scale-95"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmittingVoucher"
                            class="rounded-lg bg-[#3B2314] px-5 py-2.5 text-xs font-black text-[#FAEDCD] shadow-md transition hover:bg-[#2A180E] active:scale-95 disabled:opacity-50"
                        >
                            {{
                                isSubmittingVoucher
                                    ? 'Menyimpan...'
                                    : 'Simpan Voucher'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL KONFIRMASI HAPUS VOUCHER -->
        <div
            v-if="showVoucherDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm transition-opacity duration-300"
        >
            <div
                class="flex w-full max-w-sm scale-100 transform flex-col overflow-hidden rounded-xl border border-red-100 bg-white p-6 text-center shadow-2xl transition-all duration-300"
            >
                <div
                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        class="h-6 w-6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                        />
                    </svg>
                </div>
                <h3 class="text-sm font-black text-gray-800">
                    Hapus Voucher Promo?
                </h3>
                <p class="mt-2 text-xs text-gray-500">
                    Tindakan ini permanen. Voucher
                    <b>{{ voucherToDelete?.code }}</b> akan dihapus dari sistem
                    dan tidak dapat digunakan lagi.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <button
                        @click="showVoucherDeleteModal = false"
                        class="flex-1 rounded-lg border border-gray-200 bg-white py-2.5 text-xs font-bold text-gray-500 transition hover:bg-gray-50 active:scale-95"
                    >
                        Batal
                    </button>
                    <button
                        @click="executeDeleteVoucher"
                        class="flex-1 rounded-lg bg-red-600 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-red-700 active:scale-95"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
        <!-- MODAL FORM PROMO DEAL (TAMBAH/EDIT) -->
        <div
            v-if="showPromoDealModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm transition-opacity duration-300"
        >
            <div
                class="flex max-h-[90vh] w-full max-w-lg scale-100 transform flex-col overflow-hidden rounded-xl border border-[#D4A373]/30 bg-white shadow-2xl transition-all duration-300"
            >
                <!-- Header Modal -->
                <div
                    class="flex items-center justify-between bg-[#3B2314] px-6 py-4 text-white"
                >
                    <h3 class="truncate text-sm font-extrabold text-[#FAEDCD]">
                        {{
                            isEditingPromoDeal
                                ? 'Edit Promo Deal'
                                : 'Tambah Promo Deal Baru'
                        }}
                    </h3>
                    <button
                        @click="showPromoDealModal = false"
                        class="rounded-full p-1 text-[#FAEDCD]/80 transition hover:bg-[#FAEDCD]/10 hover:text-white"
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
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Form Body -->
                <form
                    @submit.prevent="submitPromoDealForm"
                    class="flex-1 space-y-4 overflow-y-auto p-6"
                >
                    <!-- Nama Promo -->
                    <div>
                        <label
                            class="mb-1 block text-xs font-bold text-gray-700"
                            >Nama Promo Deal</label
                        >
                        <input
                            v-model="promoDealForm.name"
                            type="text"
                            placeholder="Contoh: Promo Bundling Cappuccino + Donut"
                            required
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-xs text-[#3B2314] placeholder-gray-400 shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                        />
                    </div>

                    <!-- Tipe Promo -->
                    <div>
                        <label
                            class="mb-1 block text-xs font-bold text-gray-700"
                            >Tipe Promo</label
                        >
                        <select
                            v-model="promoDealForm.type"
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-xs font-bold text-[#3B2314] shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                        >
                            <option value="bundling">
                                Paket Bundling Potongan (Beli A + B dapet
                                Potongan)
                            </option>
                            <option value="buy_get">
                                Buy X Get Y (Beli X dapet Bonus Y)
                            </option>
                        </select>
                    </div>

                    <!-- Blok Pilihan Produk Pembelian -->
                    <div class="grid grid-cols-3 gap-2">
                        <div class="col-span-2">
                            <label
                                class="mb-1 block text-xs font-bold text-gray-700"
                                >Produk Utama</label
                            >
                            <select
                                v-model="promoDealForm.buy_product_id"
                                required
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-xs font-bold text-[#3B2314] shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                            >
                                <option
                                    v-for="product in products"
                                    :key="product.id"
                                    :value="product.id"
                                >
                                    {{ product.name }} (Rp
                                    {{
                                        parseFloat(
                                            product.price,
                                        ).toLocaleString('id-ID')
                                    }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold text-gray-700"
                                >Kuantitas</label
                            >
                            <input
                                v-model.number="promoDealForm.buy_quantity"
                                type="number"
                                min="1"
                                required
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 font-mono text-xs font-bold text-[#3B2314] shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                            />
                        </div>
                    </div>

                    <!-- Jika Tipe = Bundling (Pilih Item Kedua) -->
                    <div v-if="promoDealForm.type === 'bundling'">
                        <label
                            class="mb-1 block text-xs font-bold text-gray-700"
                            >Kombinasi Produk Bundling</label
                        >
                        <select
                            v-model="promoDealForm.bundling_product_id"
                            required
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-xs font-bold text-[#3B2314] shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                        >
                            <option
                                v-for="product in products"
                                :key="product.id"
                                :value="product.id"
                            >
                                {{ product.name }} (Rp
                                {{
                                    parseFloat(product.price).toLocaleString(
                                        'id-ID',
                                    )
                                }})
                            </option>
                        </select>
                    </div>

                    <!-- Jika Tipe = Buy Get (Pilih Bonus Item & Kuantitas) -->
                    <div
                        v-if="promoDealForm.type === 'buy_get'"
                        class="grid grid-cols-3 gap-2"
                    >
                        <div class="col-span-2">
                            <label
                                class="mb-1 block text-xs font-bold text-gray-700"
                                >Bonus Produk (Free/Discounted)</label
                            >
                            <select
                                v-model="promoDealForm.get_product_id"
                                required
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-xs font-bold text-[#3B2314] shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                            >
                                <option
                                    v-for="product in products"
                                    :key="product.id"
                                    :value="product.id"
                                >
                                    {{ product.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold text-gray-700"
                                >Kuantitas</label
                            >
                            <input
                                v-model.number="promoDealForm.get_quantity"
                                type="number"
                                min="1"
                                required
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 font-mono text-xs font-bold text-[#3B2314] shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                            />
                        </div>
                    </div>

                    <!-- Grid: Tipe Potongan & Nilai Potongan -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold text-gray-700"
                                >Tipe Potongan Reward</label
                            >
                            <select
                                v-model="promoDealForm.discount_type"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-xs font-bold text-[#3B2314] shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373]"
                            >
                                <option value="nominal">
                                    Nominal (Rupiah)
                                </option>
                                <option value="percentage">
                                    Persentase (%)
                                </option>
                                <option
                                    value="free"
                                    v-if="promoDealForm.type === 'buy_get'"
                                >
                                    Gratis Item Bonus
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold text-gray-700"
                                >Nilai Potongan / Reward</label
                            >
                            <input
                                v-model.number="promoDealForm.discount_value"
                                type="number"
                                min="0"
                                :disabled="
                                    promoDealForm.discount_type === 'free'
                                "
                                required
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 font-mono text-xs font-bold text-[#3B2314] shadow-sm focus:border-[#D4A373] focus:ring-1 focus:ring-[#D4A373] disabled:bg-gray-100"
                            />
                        </div>
                    </div>

                    <!-- Status Aktif Switch -->
                    <div
                        class="flex items-center justify-between rounded-lg border bg-gray-50 p-3.5"
                    >
                        <div>
                            <span
                                class="block text-xs font-extrabold text-[#3B2314]"
                                >Aktifkan Promo Deal</span
                            >
                            <span class="text-[10px] text-gray-500"
                                >Tentukan apakah promo bundling ini langsung
                                aktif dan bisa digunakan.</span
                            >
                        </div>
                        <button
                            type="button"
                            @click="
                                promoDealForm.is_active =
                                    !promoDealForm.is_active
                            "
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                            :class="
                                promoDealForm.is_active
                                    ? 'bg-emerald-500'
                                    : 'bg-gray-300'
                            "
                        >
                            <span
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                :class="
                                    promoDealForm.is_active
                                        ? 'translate-x-5'
                                        : 'translate-x-0'
                                "
                            ></span>
                        </button>
                    </div>

                    <!-- Footer Modal Buttons -->
                    <div
                        class="mt-6 flex items-center justify-end gap-3 border-t pt-4"
                    >
                        <button
                            type="button"
                            @click="showPromoDealModal = false"
                            class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-500 transition hover:bg-gray-50 active:scale-95"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmittingPromoDeal"
                            class="rounded-lg bg-[#3B2314] px-5 py-2.5 text-xs font-black text-[#FAEDCD] shadow-md transition hover:bg-[#2A180E] active:scale-95 disabled:opacity-50"
                        >
                            {{
                                isSubmittingPromoDeal
                                    ? 'Menyimpan...'
                                    : 'Simpan Promo Deal'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL KONFIRMASI HAPUS PROMO DEAL -->
        <div
            v-if="showPromoDealDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm transition-opacity duration-300"
        >
            <div
                class="flex w-full max-w-sm scale-100 transform flex-col overflow-hidden rounded-xl border border-red-100 bg-white p-6 text-center shadow-2xl transition-all duration-300"
            >
                <div
                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        class="h-6 w-6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                        />
                    </svg>
                </div>
                <h3 class="text-sm font-black text-gray-800">
                    Hapus Promo Deal?
                </h3>
                <p class="mt-2 text-xs text-gray-500">
                    Tindakan ini permanen. Paket promo
                    <b>{{ promoDealToDelete?.name }}</b> akan dihapus dari
                    sistem.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <button
                        @click="showPromoDealDeleteModal = false"
                        class="flex-1 rounded-lg border border-gray-200 bg-white py-2.5 text-xs font-bold text-gray-500 transition hover:bg-gray-50 active:scale-95"
                    >
                        Batal
                    </button>
                    <button
                        @click="executeDeletePromoDeal"
                        class="flex-1 rounded-lg bg-red-600 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-red-700 active:scale-95"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </ZunoiAdminLayout>
</template>
