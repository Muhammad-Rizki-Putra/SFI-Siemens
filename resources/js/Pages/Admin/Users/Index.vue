<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Users, ShieldCheck, Wrench, Award, ChevronDown, Search, Check } from 'lucide-vue-next';
import { useToast } from '@/Composables/useToast';

const { show: showToast } = useToast();
const page = usePage();

const props = defineProps({
    users: { type: Array, required: true },
});

// ─── Search ───────────────────────────────────────────────────────────────
const search = ref('');
const filteredUsers = computed(() => {
    const q = search.value.toLowerCase();
    if (!q) return props.users;
    return props.users.filter(u =>
        u.name.toLowerCase().includes(q) ||
        u.email.toLowerCase().includes(q) ||
        u.department.toLowerCase().includes(q)
    );
});

// ─── Role config ───────────────────────────────────────────────────────────
const roles = [
    {
        value: 'user',
        label: 'Employee',
        short: 'Employee',
        color: 'bg-slate-100 text-slate-700 border-slate-200',
        dot: 'bg-slate-400',
    },
    {
        value: 'technical_reviewer',
        label: 'Technical Reviewer',
        short: 'Tech Reviewer',
        color: 'bg-cyan-50 text-cyan-800 border-cyan-200',
        dot: 'bg-cyan-500',
    },
    {
        value: 'rewarding_reviewer',
        label: 'Rewarding Reviewer',
        short: 'Reward Reviewer',
        color: 'bg-amber-50 text-amber-800 border-amber-200',
        dot: 'bg-amber-500',
    },
];

const roleMap = Object.fromEntries(roles.map(r => [r.value, r]));

const getRoleInfo = (role) => roleMap[role] || {
    label: role,
    short: role,
    color: 'bg-slate-100 text-slate-600 border-slate-200',
    dot: 'bg-slate-400',
};

// Roles that cannot be changed through this UI
const protectedRoles = ['sps', 'technical_manager', 'financial_manager'];
const isProtected = (user) => protectedRoles.includes(user.role);

// ─── Dropdown open state ───────────────────────────────────────────────────
const openDropdown = ref(null);
const toggleDropdown = (id) => {
    openDropdown.value = openDropdown.value === id ? null : id;
};
const closeDropdown = () => { openDropdown.value = null; };

// ─── Update role ───────────────────────────────────────────────────────────
const updating = ref(null);

const changeRole = (user, newRole) => {
    if (user.role === newRole) { closeDropdown(); return; }
    updating.value = user.id;
    closeDropdown();

    router.post(route('admin.users.updateRole', user.id), { role: newRole }, {
        preserveScroll: true,
        onSuccess: () => {
            showToast(`Role "${getRoleInfo(newRole).label}" assigned to ${user.name}.`, 'success');
        },
        onError: (errors) => {
            showToast(errors.role || 'Failed to update role.', 'error');
        },
        onFinish: () => { updating.value = null; },
    });
};

// ─── Stats ─────────────────────────────────────────────────────────────────
const statCounts = computed(() => {
    const all = props.users;
    return {
        total: all.length,
        technical: all.filter(u => u.role === 'technical_reviewer').length,
        rewarding: all.filter(u => u.role === 'rewarding_reviewer').length,
        employee: all.filter(u => u.role === 'user').length,
    };
});
</script>

<template>
    <Head title="User Management" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-600 shadow-sm">
                    <Users class="h-5 w-5 text-white" />
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-900 leading-tight">User Management</h1>
                    <p class="text-xs text-slate-500 leading-tight">Kelola role reviewer untuk semua pengguna</p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8 space-y-6">

            <!-- Stats cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex flex-col gap-1">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Users</span>
                    <span class="text-3xl font-extrabold text-slate-800">{{ statCounts.total }}</span>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex flex-col gap-1">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Employees</span>
                    <span class="text-3xl font-extrabold text-slate-700">{{ statCounts.employee }}</span>
                </div>
                <div class="bg-white rounded-2xl border border-cyan-100 shadow-sm p-5 flex flex-col gap-1">
                    <span class="text-xs font-semibold text-cyan-500 uppercase tracking-wider">Tech Reviewers</span>
                    <span class="text-3xl font-extrabold text-cyan-700">{{ statCounts.technical }}</span>
                </div>
                <div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-5 flex flex-col gap-1">
                    <span class="text-xs font-semibold text-amber-500 uppercase tracking-wider">Reward Reviewers</span>
                    <span class="text-3xl font-extrabold text-amber-700">{{ statCounts.rewarding }}</span>
                </div>
            </div>

            <!-- Table card -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

                <!-- Table toolbar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <ShieldCheck class="w-4 h-4 text-teal-600" />
                        Daftar Pengguna
                    </h2>
                    <div class="relative w-full sm:w-64">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari nama, email, departemen…"
                            class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50 text-slate-700 placeholder:text-slate-400 focus:border-teal-400 focus:ring-teal-400 focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="px-6 py-3 text-left">Nama</th>
                                <th class="px-6 py-3 text-left hidden sm:table-cell">Email</th>
                                <th class="px-6 py-3 text-left hidden md:table-cell">Departemen</th>
                                <th class="px-6 py-3 text-left">Role</th>
                                <th class="px-6 py-3 text-right">Ubah Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr
                                v-for="user in filteredUsers"
                                :key="user.id"
                                class="hover:bg-slate-50/60 transition-colors"
                                :class="isProtected(user) ? 'opacity-60' : ''"
                            >
                                <!-- Name + avatar -->
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full font-bold text-xs text-white"
                                            :class="isProtected(user) ? 'bg-teal-600' : 'bg-slate-400'">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <span class="font-medium text-slate-800 truncate max-w-[140px]">{{ user.name }}</span>
                                    </div>
                                </td>

                                <!-- Email -->
                                <td class="px-6 py-3.5 hidden sm:table-cell text-slate-500 truncate max-w-[200px]">{{ user.email }}</td>

                                <!-- Department -->
                                <td class="px-6 py-3.5 hidden md:table-cell text-slate-500">{{ user.department }}</td>

                                <!-- Current role badge -->
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border"
                                        :class="getRoleInfo(user.role).color">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getRoleInfo(user.role).dot"></span>
                                        {{ getRoleInfo(user.role).short }}
                                    </span>
                                </td>

                                <!-- Role dropdown -->
                                <td class="px-6 py-3.5 text-right">
                                    <!-- Protected accounts: no dropdown -->
                                    <span v-if="isProtected(user)" class="text-xs text-slate-400 italic">Admin — tidak dapat diubah</span>

                                    <!-- Loading spinner -->
                                    <div v-else-if="updating === user.id" class="flex justify-end">
                                        <svg class="animate-spin h-5 w-5 text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                    </div>

                                    <!-- Dropdown button -->
                                    <div v-else class="relative flex justify-end" @click.stop>
                                        <button
                                            @click="toggleDropdown(user.id)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-700 hover:border-teal-400 hover:text-teal-700 transition-colors shadow-sm"
                                        >
                                            Ubah
                                            <ChevronDown class="w-3.5 h-3.5" :class="openDropdown === user.id ? 'rotate-180 transition-transform' : 'transition-transform'" />
                                        </button>

                                        <!-- Dropdown menu -->
                                        <transition
                                            enter-active-class="transition duration-100 ease-out"
                                            enter-from-class="opacity-0 scale-95"
                                            enter-to-class="opacity-100 scale-100"
                                            leave-active-class="transition duration-75 ease-in"
                                            leave-from-class="opacity-100 scale-100"
                                            leave-to-class="opacity-0 scale-95"
                                        >
                                            <div
                                                v-if="openDropdown === user.id"
                                                class="absolute right-0 top-full mt-1.5 z-20 w-52 origin-top-right rounded-xl bg-white border border-slate-200 shadow-xl overflow-hidden"
                                            >
                                                <div class="px-3 py-2 border-b border-slate-100">
                                                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Pilih Role</p>
                                                </div>
                                                <ul>
                                                    <li v-for="r in roles" :key="r.value">
                                                        <button
                                                            @click="changeRole(user, r.value)"
                                                            class="w-full flex items-center justify-between gap-2 px-3 py-2.5 text-sm hover:bg-slate-50 transition-colors text-left"
                                                            :class="user.role === r.value ? 'text-teal-700 font-semibold' : 'text-slate-700'"
                                                        >
                                                            <span class="flex items-center gap-2">
                                                                <span class="w-2 h-2 rounded-full flex-shrink-0" :class="r.dot"></span>
                                                                {{ r.label }}
                                                            </span>
                                                            <Check v-if="user.role === r.value" class="w-3.5 h-3.5 text-teal-600 shrink-0" />
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </transition>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2 text-slate-400">
                                        <Users class="w-8 h-8 opacity-40" />
                                        <p class="text-sm font-medium">Tidak ada pengguna ditemukan</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer legend -->
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 flex flex-wrap gap-4">
                    <div v-for="r in roles" :key="r.value" class="flex items-center gap-1.5 text-xs text-slate-500">
                        <span class="w-2 h-2 rounded-full" :class="r.dot"></span>
                        {{ r.label }}
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-500">
                        <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                        SPS / Manager (tidak dapat diubah)
                    </div>
                </div>
            </div>

        </div>

        <!-- Close dropdown on outside click -->
        <div v-if="openDropdown !== null" class="fixed inset-0 z-10" @click="closeDropdown" />
    </AuthenticatedLayout>
</template>
