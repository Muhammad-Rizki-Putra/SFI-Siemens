<script setup>
import { useToast } from '@/Composables/useToast';
import Toast from '@/Components/Toast.vue';

const { toasts } = useToast();
</script>

<template>
    <!-- Fixed bottom-right toast stack -->
    <div class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-3 items-end pointer-events-none">
        <TransitionGroup name="toast">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto"
            >
                <Toast
                    :id="toast.id"
                    :message="toast.message"
                    :type="toast.type"
                    :duration="toast.duration"
                />
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active {
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-leave-active {
    transition: all 0.25s ease-in;
}
.toast-enter-from {
    opacity: 0;
    transform: translateX(80px) scale(0.9);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(80px) scale(0.9);
}
</style>
