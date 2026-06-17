<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    className: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <Link
        :href="href"
        :class="['block w-full px-4 py-2 text-start text-sm leading-5 transition duration-150 ease-in-out focus:outline-none', className || 'text-gray-700 hover:bg-gray-100 focus:bg-gray-100']"
    >
        <slot />
    </Link>
</template>
