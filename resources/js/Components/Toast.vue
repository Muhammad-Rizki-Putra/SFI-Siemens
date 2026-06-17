<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    id: { type: Number, required: true },
    message: { type: String, required: true },
    type: { type: String, default: 'success' },
    duration: { type: Number, default: 4000 },
});

const { dismiss } = useToast();

// Progress: 1 → 0 over `duration` ms
const progress = ref(1);
let startTime = null;
let rafId = null;

const tick = (ts) => {
    if (!startTime) startTime = ts;
    const elapsed = ts - startTime;
    progress.value = Math.max(0, 1 - elapsed / props.duration);
    if (elapsed < props.duration) {
        rafId = requestAnimationFrame(tick);
    } else {
        dismiss(props.id);
    }
};

onMounted(() => {
    rafId = requestAnimationFrame(tick);
});

onUnmounted(() => {
    if (rafId) cancelAnimationFrame(rafId);
});

// Visual config per type
const config = computed(() => {
    const map = {
        success: {
            bar: 'bg-emerald-500',
            icon: '✓',
            iconBg: 'bg-emerald-100 text-emerald-600',
            border: 'border-emerald-200',
        },
        error: {
            bar: 'bg-rose-500',
            icon: '✕',
            iconBg: 'bg-rose-100 text-rose-600',
            border: 'border-rose-200',
        },
        warning: {
            bar: 'bg-amber-400',
            icon: '!',
            iconBg: 'bg-amber-100 text-amber-600',
            border: 'border-amber-200',
        },
        info: {
            bar: 'bg-blue-500',
            icon: 'i',
            iconBg: 'bg-blue-100 text-blue-600',
            border: 'border-blue-200',
        },
    };
    return map[props.type] || map.success;
});
</script>

<template>
    <div
        class="relative flex items-start gap-3 w-80 bg-white rounded-xl shadow-xl border px-4 py-3.5 overflow-hidden"
        :class="config.border"
        role="alert"
    >
        <!-- Icon badge -->
        <div
            class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-bold"
            :class="config.iconBg"
        >
            {{ config.icon }}
        </div>

        <!-- Message -->
        <p class="flex-1 text-sm font-medium text-slate-800 leading-snug pt-0.5">
            {{ message }}
        </p>

        <!-- Close button -->
        <button
            @click="dismiss(id)"
            class="mt-0.5 text-slate-400 hover:text-slate-600 transition text-lg leading-none"
            aria-label="Dismiss"
        >
            ×
        </button>

        <!-- Progress bar -->
        <div class="absolute bottom-0 left-0 h-1 w-full bg-slate-100">
            <div
                class="h-full transition-none"
                :class="config.bar"
                :style="{ width: (progress * 100) + '%' }"
            />
        </div>
    </div>
</template>
