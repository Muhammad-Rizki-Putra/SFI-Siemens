<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ToastContainer from '@/Components/ToastContainer.vue';
import {
    LayoutDashboard, Lightbulb, Trophy, ShieldCheck,
    ChevronLeft, ChevronRight, LogOut, User, Menu, X, UserCircle,
    Globe
} from 'lucide-vue-next';

const { t, locale } = useI18n();
const page = usePage();
const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => user.value?.role && user.value.role !== 'user');

const collapsed = ref(false);
const mobileOpen = ref(false);

const toggleLocale = () => {
    locale.value = locale.value === 'en' ? 'id' : 'en';
    localStorage.setItem('locale', locale.value);
};

const navItems = computed(() => {
    const items = [
        {
            label: t('nav.dashboard'),
            href: route('dashboard'),
            active: route().current('dashboard'),
            icon: LayoutDashboard,
            show: true,
        },
        {
            label: t('nav.my_ideas'),
            href: route('ideas.index'),
            active: route().current('ideas.index'),
            icon: Lightbulb,
            show: !isAdmin.value,
        },
        {
            label: 'Leaderboard', // no translation needed for proper noun
            href: route('ideas.leaderboard'),
            active: route().current('ideas.leaderboard'),
            icon: Trophy,
            show: true,
        },
        {
            label: t('nav.admin_dashboard'),
            href: route('admin.ideas.index'),
            active: route().current('admin.ideas.index'),
            icon: ShieldCheck,
            show: isAdmin.value,
        },
    ];
    return items.filter(i => i.show);
});

const roleLabel = computed(() => {
    const map = {
        sps: 'SPS Reviewer',
        technical_reviewer: 'Technical Reviewer',
        technical_manager: 'Technical Manager',
        financial_manager: 'Financial Manager',
        user: 'Employee',
    };
    return map[user.value?.role] || user.value?.role || 'User';
});
</script>

<template>
    <div class="flex min-h-screen bg-slate-100">

        <!-- ═══ MOBILE OVERLAY ══════════════════════════════════════════ -->
        <transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mobileOpen"
                class="fixed inset-0 z-20 bg-black/50 lg:hidden"
                @click="mobileOpen = false"
            />
        </transition>

        <!-- ═══ SIDEBAR ══════════════════════════════════════════════════ -->
        <aside
            class="fixed inset-y-0 left-0 z-30 flex flex-col bg-gradient-to-b from-teal-900 via-teal-800 to-teal-900 shadow-2xl transition-all duration-300 ease-in-out"
            :class="[
                collapsed ? 'w-[72px]' : 'w-64',
                mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            ]"
        >
            <!-- Logo Area -->
            <div class="flex items-center border-b border-teal-700/60 px-4 py-5" :class="collapsed ? 'justify-center' : 'justify-between gap-3'">
                <Link :href="route('dashboard')" class="flex items-center gap-3 min-w-0">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                        <img src="/storage/idea-attachments/Siemens-Logo.png" alt="SFI Logo" class="h-6 w-6 object-contain" />
                    </div>
                    <transition
                        enter-active-class="transition-all duration-200"
                        enter-from-class="opacity-0 -translate-x-2"
                        enter-to-class="opacity-100 translate-x-0"
                        leave-active-class="transition-all duration-150"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <span v-if="!collapsed" class="text-sm font-bold tracking-wide text-white truncate">SFI Portal</span>
                    </transition>
                </Link>

                <!-- Collapse toggle (desktop only) -->
                <button
                    v-if="!collapsed"
                    @click="collapsed = true"
                    class="hidden lg:flex items-center justify-center h-7 w-7 shrink-0 rounded-lg text-teal-300 hover:bg-white/10 hover:text-white transition-colors"
                    title="Collapse sidebar"
                >
                    <ChevronLeft class="h-4 w-4" />
                </button>
            </div>

            <!-- Expand button (collapsed state) -->
            <div v-if="collapsed" class="flex justify-center py-2 border-b border-teal-700/60">
                <button
                    @click="collapsed = false"
                    class="hidden lg:flex items-center justify-center h-7 w-7 rounded-lg text-teal-300 hover:bg-white/10 hover:text-white transition-colors"
                    title="Expand sidebar"
                >
                    <ChevronRight class="h-4 w-4" />
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto px-3 py-4">
                <ul class="space-y-1">
                    <li v-for="item in navItems" :key="item.label">
                        <Link
                            :href="item.href"
                            class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-150"
                            :class="[
                                item.active
                                    ? 'bg-white/15 text-white shadow-inner ring-1 ring-white/10'
                                    : 'text-teal-200 hover:bg-white/10 hover:text-white',
                                collapsed ? 'justify-center' : '',
                            ]"
                            @click="mobileOpen = false"
                        >
                            <!-- Active indicator bar -->
                            <span
                                v-if="item.active"
                                class="absolute left-0 top-1/2 -translate-y-1/2 h-5 w-1 rounded-r-full bg-teal-300"
                            />

                            <component
                                :is="item.icon"
                                class="h-5 w-5 shrink-0 transition-colors"
                                :class="item.active ? 'text-teal-300' : 'text-teal-400 group-hover:text-teal-200'"
                            />

                            <transition
                                enter-active-class="transition-all duration-200"
                                enter-from-class="opacity-0"
                                enter-to-class="opacity-100"
                                leave-active-class="transition-all duration-100"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                            >
                                <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                            </transition>

                            <!-- Tooltip on collapsed -->
                            <div
                                v-if="collapsed"
                                class="pointer-events-none absolute left-full ml-3 hidden whitespace-nowrap rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-lg lg:group-hover:block z-50"
                            >
                                {{ item.label }}
                                <div class="absolute -left-1 top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900" />
                            </div>
                        </Link>
                    </li>
                </ul>
            </nav>

            <!-- User Profile Footer -->
            <div class="border-t border-teal-700/60 p-3">
                <div
                    class="flex items-center gap-3 rounded-xl px-2 py-2"
                    :class="collapsed ? 'justify-center' : ''"
                >
                    <!-- Avatar -->
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-600 ring-2 ring-teal-400/40">
                        <User class="h-4 w-4 text-teal-100" />
                    </div>

                    <transition
                        enter-active-class="transition-all duration-200"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition-all duration-100"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <div v-if="!collapsed" class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-white leading-tight">{{ user?.name || 'User' }}</p>
                            <p class="truncate text-[10px] text-teal-300 leading-tight mt-0.5">{{ roleLabel }}</p>
                        </div>
                    </transition>
                </div>

                <!-- Profile Link -->
                <Link
                    :href="route('profile.edit')"
                    class="group mt-1 flex w-full items-center gap-3 rounded-xl px-3 py-2 text-xs font-medium text-teal-300 hover:bg-white/10 hover:text-white transition-colors"
                    :class="collapsed ? 'justify-center' : ''"
                >
                    <UserCircle class="h-4 w-4 shrink-0 transition-colors" />
                    <transition
                        enter-active-class="transition-all duration-200"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition-all duration-100"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <span v-if="!collapsed">{{ t('nav.profile') }}</span>
                    </transition>

                    <!-- Tooltip on collapsed -->
                    <div
                        v-if="collapsed"
                        class="pointer-events-none absolute left-full ml-3 hidden whitespace-nowrap rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-lg lg:group-hover:block z-50"
                    >
                        {{ t('nav.profile') }}
                        <div class="absolute -left-1 top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900" />
                    </div>
                </Link>

                <!-- Language Toggle -->
                <button
                    @click="toggleLocale"
                    class="group mt-1 flex w-full items-center gap-3 rounded-xl px-3 py-2 text-xs font-medium text-teal-300 hover:bg-white/10 hover:text-white transition-colors focus:outline-none"
                    :class="collapsed ? 'justify-center' : ''"
                >
                    <Globe class="h-4 w-4 shrink-0 transition-colors" />
                    <transition
                        enter-active-class="transition-all duration-200"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition-all duration-100"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <span v-if="!collapsed" class="flex items-center gap-2">
                            <span :class="locale === 'en' ? 'text-white font-bold' : 'text-teal-400'">EN</span>
                            <span class="text-teal-500">/</span>
                            <span :class="locale === 'id' ? 'text-white font-bold' : 'text-teal-400'">ID</span>
                        </span>
                    </transition>

                    <!-- Tooltip on collapsed -->
                    <div
                        v-if="collapsed"
                        class="pointer-events-none absolute left-full ml-3 hidden whitespace-nowrap rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-lg lg:group-hover:block z-50"
                    >
                        {{ locale === 'en' ? 'Indonesian' : 'English' }}
                        <div class="absolute -left-1 top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900" />
                    </div>
                </button>

                <!-- Logout -->
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="group mt-1 flex w-full items-center gap-3 rounded-xl px-3 py-2 text-xs font-medium text-teal-300 hover:bg-white/10 hover:text-rose-300 transition-colors"
                    :class="collapsed ? 'justify-center' : ''"
                >
                    <LogOut class="h-4 w-4 shrink-0 group-hover:text-rose-400 transition-colors" />
                    <transition
                        enter-active-class="transition-all duration-200"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition-all duration-100"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <span v-if="!collapsed">{{ t('nav.log_out') }}</span>
                    </transition>

                    <!-- Tooltip on collapsed -->
                    <div
                        v-if="collapsed"
                        class="pointer-events-none absolute left-full ml-3 hidden whitespace-nowrap rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-lg lg:group-hover:block z-50"
                    >
                        {{ t('nav.log_out') }}
                        <div class="absolute -left-1 top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900" />
                    </div>
                </Link>
            </div>
        </aside>

        <!-- ═══ MAIN CONTENT AREA ═══════════════════════════════════════ -->
        <div
            class="flex min-h-screen flex-1 flex-col transition-all duration-300"
            :class="collapsed ? 'lg:pl-[72px]' : 'lg:pl-64'"
        >
            <!-- Mobile top bar -->
            <header class="sticky top-0 z-10 flex h-14 items-center gap-3 border-b border-teal-100 bg-white px-4 shadow-sm lg:hidden">
                <button
                    @click="mobileOpen = !mobileOpen"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors"
                    aria-label="Toggle menu"
                >
                    <X v-if="mobileOpen" class="h-5 w-5" />
                    <Menu v-else class="h-5 w-5" />
                </button>
                <img src="/storage/idea-attachments/Siemens-Logo.png" alt="SFI" class="h-7 w-auto object-contain" />
                <span class="text-sm font-bold text-teal-800">SFI Portal</span>
            </header>

            <!-- Page Header (slot) -->
            <header v-if="$slots.header" class="bg-white shadow-sm border-b border-slate-100">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1">
                <slot />
            </main>
        </div>

        <!-- Global Toast Notifications -->
        <ToastContainer />
    </div>
</template>
