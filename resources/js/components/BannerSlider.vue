<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    banners: {
        type: Array,
        default: () => []
    }
});

const activeIndex = ref(0);
const timer = ref(null);

const startTimer = () => {
    if (props.banners.length <= 1) return;
    stopTimer();
    timer.value = setInterval(() => {
        nextSlide();
    }, 4500); // 4.5 seconds auto-play interval
};

const stopTimer = () => {
    if (timer.value) {
        clearInterval(timer.value);
        timer.value = null;
    }
};

const prevSlide = () => {
    if (props.banners.length === 0) return;
    activeIndex.value = (activeIndex.value - 1 + props.banners.length) % props.banners.length;
    startTimer(); // Reset timer on interaction
};

const nextSlide = () => {
    if (props.banners.length === 0) return;
    activeIndex.value = (activeIndex.value + 1) % props.banners.length;
    startTimer();
};

const setSlide = (idx) => {
    activeIndex.value = idx;
    startTimer();
};

// Unified Drag-to-Slide (Mouse & Touch) States
const sliderRef = ref(null);
const isDragging = ref(false);
const startX = ref(0);
const dragOffset = ref(0);
const startPressTime = ref(0);

const dragStart = (e) => {
    if (props.banners.length <= 1) return;
    isDragging.value = true;
    startPressTime.value = Date.now();
    // Support touch or mouse event coordinate
    startX.value = e.clientX || (e.touches && e.touches[0].clientX) || 0;
    dragOffset.value = 0;
    stopTimer();
};

const dragMove = (e) => {
    if (!isDragging.value) return;
    
    // Prevent default scrolling on mobile while sliding
    if (e.cancelable) {
        e.preventDefault();
    }
    
    const currentX = e.clientX || (e.touches && e.touches[0].clientX) || 0;
    dragOffset.value = currentX - startX.value;
};

const dragEnd = (e) => {
    if (!isDragging.value) return;
    isDragging.value = false;
    
    const elapsed = Date.now() - startPressTime.value;
    const absOffset = Math.abs(dragOffset.value);
    
    // If it was a quick click/tap with tiny movement, treat it as a click on left/right 30% zones
    if (elapsed < 250 && absOffset < 10) {
        handleTap(e);
    } else {
        const width = sliderRef.value?.clientWidth || 300;
        const threshold = width * 0.15; // 15% swipe threshold to change slide
        
        if (dragOffset.value > threshold) {
            prevSlide();
        } else if (dragOffset.value < -threshold) {
            nextSlide();
        } else {
            // Snap back to original
            startTimer();
        }
    }
    dragOffset.value = 0;
};

const handleTap = (e) => {
    if (!sliderRef.value) return;
    const rect = sliderRef.value.getBoundingClientRect();
    
    let clientX = 0;
    if (e.changedTouches && e.changedTouches.length > 0) {
        clientX = e.changedTouches[0].clientX;
    } else {
        clientX = e.clientX;
    }
    
    const clickX = clientX - rect.left;
    const percent = clickX / rect.width;
    
    if (percent <= 0.3) {
        prevSlide();
    } else if (percent >= 0.7) {
        nextSlide();
    } else {
        // Tap in center zone (do nothing or resume timer)
        startTimer();
    }
};

onMounted(() => {
    startTimer();
});

onUnmounted(() => {
    stopTimer();
});

// Watch for changes in banners to reset index
watch(() => props.banners, () => {
    activeIndex.value = 0;
    startTimer();
}, { deep: true });
</script>

<template>
    <div v-if="banners && banners.length > 0" class="relative w-full overflow-hidden select-none">
        <!-- Main 16:9 Slider Container -->
        <div
            ref="sliderRef"
            class="aspect-[16/9] w-full rounded-2xl md:rounded-[24px] bg-[#3B2314]/5 relative overflow-hidden shadow-sm border border-[#D4A373]/20 cursor-grab active:cursor-grabbing group"
            @mousedown="dragStart"
            @mousemove="dragMove"
            @mouseup="dragEnd"
            @mouseleave="dragEnd"
            @touchstart="dragStart"
            @touchmove="dragMove"
            @touchend="dragEnd"
        >
            <!-- Slides wrapper -->
            <div
                class="flex h-full w-full"
                :class="{ 'transition-transform duration-300 ease-out': !isDragging }"
                :style="{ transform: `translateX(calc(-${activeIndex * 100}% + ${dragOffset}px))` }"
            >
                <div
                    v-for="banner in banners"
                    :key="banner.id"
                    class="h-full w-full shrink-0 relative overflow-hidden"
                >
                    <img
                        :src="banner.image_url"
                        :alt="banner.title || 'Promo Banner'"
                        class="h-full w-full object-cover pointer-events-none select-none"
                        draggable="false"
                    />
                    
                    <!-- Optional overlay text if title or description is present -->
                    <div
                        v-if="banner.title || banner.description"
                        class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent flex flex-col justify-end p-4 md:p-6 text-white pointer-events-none select-none"
                    >
                        <h4 class="text-sm md:text-base font-black text-[#FAEDCD]">
                            {{ banner.title }}
                        </h4>
                        <p class="text-[10px] md:text-xs text-gray-200 mt-0.5 line-clamp-2 max-w-md">
                            {{ banner.description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Left & Right Hover Nav Arrows (visual feedback) -->
            <div
                v-if="banners.length > 1"
                class="absolute left-3 top-1/2 -translate-y-1/2 h-8 w-8 rounded-full bg-white/20 hover:bg-white/40 text-white flex items-center justify-center pointer-events-none backdrop-blur-sm transition opacity-0 group-hover:opacity-100"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="3"
                    stroke="currentColor"
                    class="h-4 w-4"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </div>
            <div
                v-if="banners.length > 1"
                class="absolute right-3 top-1/2 -translate-y-1/2 h-8 w-8 rounded-full bg-white/20 hover:bg-white/40 text-white flex items-center justify-center pointer-events-none backdrop-blur-sm transition opacity-0 group-hover:opacity-100"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="3"
                    stroke="currentColor"
                    class="h-4 w-4"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </div>
        </div>

        <!-- Dot Indicators (below banner) -->
        <div v-if="banners.length > 1" class="flex items-center justify-center gap-1.5 mt-2.5">
            <button
                v-for="(_, idx) in banners"
                :key="idx"
                @click="setSlide(idx)"
                class="h-1.5 rounded-full transition-all duration-300"
                :class="idx === activeIndex ? 'w-5 bg-[#3B2314]' : 'w-1.5 bg-[#3B2314]/20 hover:bg-[#3B2314]/40'"
                :aria-label="`Slide ${idx + 1}`"
            ></button>
        </div>
    </div>
</template>
