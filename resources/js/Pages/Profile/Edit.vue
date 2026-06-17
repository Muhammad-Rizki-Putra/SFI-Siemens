<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    User, Mail, Building2, ShieldCheck, KeyRound, Save,
    Camera, Pencil, BarChart3, Lightbulb, CheckCircle2, Clock,
    ChevronRight, Phone, BadgeInfo, Lock
} from 'lucide-vue-next';
import { useToast } from '@/Composables/useToast';

const { show: showToast } = useToast();
const page = usePage();
const auth = computed(() => page.props.auth?.user || {});

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

// Active tab
const activeTab = ref('profile');

// ── Profile Form ─────────────────────────────────────────────────────────────
const profileForm = useForm({
    name:             auth.value.name  || '',
    email:            auth.value.email || '',
    supervisor_email: auth.value.supervisor_email || '',
});

const submitProfile = () => {
    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => showToast('Profile updated successfully.', 'success'),
    });
};

// ── Password Form ────────────────────────────────────────────────────────────
const passwordForm = useForm({
    current_password:      '',
    password:              '',
    password_confirmation: '',
});
const showCurrentPw = ref(false);
const showNewPw     = ref(false);
const showConfirmPw = ref(false);

const submitPassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            showToast('Password updated successfully.', 'success');
        },
    });
};

// ── Helpers ──────────────────────────────────────────────────────────────────
const roleLabel = computed(() => {
    const map = {
        sps: 'SPS Reviewer',
        technical_reviewer: 'Technical Reviewer',
        technical_manager:  'Technical Manager',
        financial_manager:  'Financial Manager',
        user:               'Employee',
    };
    return map[auth.value.role] || auth.value.role || 'User';
});

const roleBadgeClass = computed(() => ({
    'bg-teal-100   text-teal-800   border-teal-300':    auth.value.role === 'user',
    'bg-cyan-100   text-cyan-800   border-cyan-300':    auth.value.role === 'sps',
    'bg-indigo-100 text-indigo-800 border-indigo-300':  ['technical_reviewer','technical_manager'].includes(auth.value.role),
    'bg-amber-100  text-amber-800  border-amber-300':   auth.value.role === 'financial_manager',
}));

const avatarInitials = computed(() => {
    const parts = (auth.value.name || 'U').split(' ');
    return (parts[0]?.[0] || '') + (parts[1]?.[0] || '');
});

// Dummy stats (visual only)
const stats = [
    { label: 'Ideas Submitted', value: '—', icon: Lightbulb, color: 'text-teal-600', bg: 'bg-teal-50' },
    { label: 'Implemented',     value: '—', icon: CheckCircle2, color: 'text-emerald-600', bg: 'bg-emerald-50' },
    { label: 'In Review',       value: '—', icon: Clock,         color: 'text-indigo-600', bg: 'bg-indigo-50' },
    { label: 'Total Score',     value: '—', icon: BarChart3,     color: 'text-amber-600',  bg: 'bg-amber-50' },
];
</script>

<template>
    <Head title="My Profile" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-blue-50 pb-16">

            <!-- ── Hero Banner ─────────────────────────────────────── -->
            <div class="relative bg-gradient-to-r from-teal-800 via-teal-700 to-cyan-700 h-40 overflow-hidden">
                <!-- Decorative blobs -->
                <div class="absolute -top-10 -right-10 h-52 w-52 rounded-full bg-white/5" />
                <div class="absolute top-4 right-32 h-32 w-32 rounded-full bg-white/5" />
                <div class="absolute -bottom-6 left-1/3 h-24 w-24 rounded-full bg-white/10" />
            </div>

            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- ── Profile Card (Avatar + Name) ───────────────── -->
                <div class="relative -mt-16 mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                        <!-- Avatar -->
                        <div class="relative group shrink-0">
                            <div class="h-28 w-28 rounded-2xl bg-gradient-to-br from-teal-500 to-cyan-600 flex items-center justify-center shadow-xl ring-4 ring-white text-white text-3xl font-bold uppercase select-none">
                                {{ avatarInitials || '?' }}
                            </div>
                            <!-- Camera overlay (dummy) -->
                            <div class="absolute inset-0 rounded-2xl bg-black/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center cursor-pointer">
                                <Camera class="h-6 w-6 text-white" />
                            </div>
                        </div>

                        <!-- Name & Role -->
                        <div class="pb-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-xl font-bold text-slate-900">{{ auth.name || 'Unknown User' }}</h1>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border" :class="roleBadgeClass">
                                    <ShieldCheck class="h-3 w-3 mr-1" />
                                    {{ roleLabel }}
                                </span>
                            </div>
                            <p class="text-sm text-slate-500 mt-0.5 flex items-center gap-1.5">
                                <Mail class="h-3.5 w-3.5 text-slate-400" />
                                {{ auth.email || 'No email' }}
                            </p>
                            <p v-if="auth.department" class="text-sm text-slate-500 mt-0.5 flex items-center gap-1.5">
                                <Building2 class="h-3.5 w-3.5 text-slate-400" />
                                {{ auth.department?.name || 'No Department' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ── Stats Strip ─────────────────────────────────── -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                    <div
                        v-for="stat in stats"
                        :key="stat.label"
                        class="flex items-center gap-3 rounded-2xl border border-teal-100 bg-white px-4 py-3 shadow-sm"
                    >
                        <div :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-xl', stat.bg]">
                            <component :is="stat.icon" :class="['h-5 w-5', stat.color]" />
                        </div>
                        <div>
                            <div class="text-lg font-bold text-slate-900">{{ stat.value }}</div>
                            <div class="text-xs text-slate-400">{{ stat.label }}</div>
                        </div>
                    </div>
                </div>

                <!-- ── Tabs ────────────────────────────────────────── -->
                <div class="flex gap-1 bg-white rounded-xl border border-teal-100 p-1 shadow-sm mb-6 w-fit">
                    <button
                        id="tab-profile"
                        @click="activeTab = 'profile'"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all"
                        :class="activeTab === 'profile'
                            ? 'bg-teal-700 text-white shadow-sm'
                            : 'text-slate-600 hover:bg-slate-50'"
                    >
                        <User class="h-4 w-4" />
                        Profile Info
                    </button>
                    <button
                        id="tab-security"
                        @click="activeTab = 'security'"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all"
                        :class="activeTab === 'security'
                            ? 'bg-teal-700 text-white shadow-sm'
                            : 'text-slate-600 hover:bg-slate-50'"
                    >
                        <Lock class="h-4 w-4" />
                        Security
                    </button>
                </div>

                <!-- ══ TAB: Profile Info ════════════════════════════ -->
                <div v-show="activeTab === 'profile'" class="space-y-6">

                    <!-- Personal Information form -->
                    <div class="bg-white rounded-2xl border border-teal-100 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-4">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50">
                                <Pencil class="h-4 w-4 text-teal-600" />
                            </div>
                            <h2 class="text-sm font-semibold text-slate-800">Personal Information</h2>
                        </div>

                        <form @submit.prevent="submitProfile" class="p-6 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <!-- Full Name -->
                                <div>
                                    <label for="name" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                                        Full Name
                                    </label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <User class="h-4 w-4 text-slate-400" />
                                        </div>
                                        <input
                                            id="name"
                                            v-model="profileForm.name"
                                            type="text"
                                            autocomplete="name"
                                            required
                                            class="block w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                            placeholder="Your full name"
                                        />
                                    </div>
                                    <p v-if="profileForm.errors.name" class="mt-1 text-xs text-rose-500">{{ profileForm.errors.name }}</p>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                                        Email Address
                                    </label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <Mail class="h-4 w-4 text-slate-400" />
                                        </div>
                                        <input
                                            id="email"
                                            v-model="profileForm.email"
                                            type="email"
                                            autocomplete="username"
                                            required
                                            class="block w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                            placeholder="your@email.com"
                                        />
                                    </div>
                                    <p v-if="profileForm.errors.email" class="mt-1 text-xs text-rose-500">{{ profileForm.errors.email }}</p>
                                </div>

                                <!-- Supervisor Email -->
                                <div>
                                    <label for="supervisor_email" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                                        Supervisor Email
                                    </label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <Phone class="h-4 w-4 text-slate-400" />
                                        </div>
                                        <input
                                            id="supervisor_email"
                                            v-model="profileForm.supervisor_email"
                                            type="email"
                                            class="block w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                            placeholder="supervisor@company.com"
                                        />
                                    </div>
                                    <p v-if="profileForm.errors.supervisor_email" class="mt-1 text-xs text-rose-500">{{ profileForm.errors.supervisor_email }}</p>
                                </div>

                                <!-- Department (read-only) -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                                        Department
                                    </label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <Building2 class="h-4 w-4 text-slate-400" />
                                        </div>
                                        <input
                                            :value="auth.department?.name || 'Not assigned'"
                                            type="text"
                                            readonly
                                            class="block w-full rounded-xl border border-slate-100 bg-slate-50 pl-9 pr-4 py-2.5 text-sm text-slate-500 cursor-not-allowed"
                                        />
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400">Contact an administrator to change your department.</p>
                                </div>
                            </div>

                            <!-- Read-only info -->
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 flex flex-col sm:flex-row gap-4">
                                <div class="flex items-start gap-2 flex-1">
                                    <BadgeInfo class="h-4 w-4 text-slate-400 mt-0.5 shrink-0" />
                                    <div>
                                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Role</div>
                                        <div class="text-sm font-medium text-slate-700 mt-0.5">{{ roleLabel }}</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2 flex-1">
                                    <ShieldCheck class="h-4 w-4 text-slate-400 mt-0.5 shrink-0" />
                                    <div>
                                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Employee ID (Entra)</div>
                                        <div class="text-sm font-medium text-slate-700 mt-0.5 font-mono">{{ auth.entra_id || '—' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="profileForm.processing"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-teal-700 text-sm font-semibold text-white hover:bg-teal-600 disabled:opacity-60 transition shadow-sm"
                                >
                                    <Save class="h-4 w-4" />
                                    {{ profileForm.processing ? 'Saving…' : 'Save Changes' }}
                                </button>
                            </div>
                        </form>
                    </div>

                </div>

                <!-- ══ TAB: Security ════════════════════════════════ -->
                <div v-show="activeTab === 'security'" class="space-y-6">

                    <!-- Change Password -->
                    <div class="bg-white rounded-2xl border border-teal-100 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-4">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50">
                                <KeyRound class="h-4 w-4 text-teal-600" />
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Change Password</h2>
                                <p class="text-xs text-slate-400 mt-0.5">Use a strong password of at least 8 characters.</p>
                            </div>
                        </div>

                        <form @submit.prevent="submitPassword" class="p-6 space-y-5">
                            <!-- Current Password -->
                            <div>
                                <label for="current_password" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                                    Current Password
                                </label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <Lock class="h-4 w-4 text-slate-400" />
                                    </div>
                                    <input
                                        id="current_password"
                                        v-model="passwordForm.current_password"
                                        :type="showCurrentPw ? 'text' : 'password'"
                                        autocomplete="current-password"
                                        class="block w-full rounded-xl border border-slate-200 bg-white pl-9 pr-10 py-2.5 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                        placeholder="Current password"
                                    />
                                    <button type="button" @click="showCurrentPw = !showCurrentPw"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                        <span class="text-xs">{{ showCurrentPw ? 'Hide' : 'Show' }}</span>
                                    </button>
                                </div>
                                <p v-if="passwordForm.errors.current_password" class="mt-1 text-xs text-rose-500">{{ passwordForm.errors.current_password }}</p>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-5">
                                <!-- New Password -->
                                <div>
                                    <label for="password" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                                        New Password
                                    </label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <KeyRound class="h-4 w-4 text-slate-400" />
                                        </div>
                                        <input
                                            id="password"
                                            v-model="passwordForm.password"
                                            :type="showNewPw ? 'text' : 'password'"
                                            autocomplete="new-password"
                                            class="block w-full rounded-xl border border-slate-200 bg-white pl-9 pr-10 py-2.5 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                            placeholder="New password"
                                        />
                                        <button type="button" @click="showNewPw = !showNewPw"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                            <span class="text-xs">{{ showNewPw ? 'Hide' : 'Show' }}</span>
                                        </button>
                                    </div>
                                    <p v-if="passwordForm.errors.password" class="mt-1 text-xs text-rose-500">{{ passwordForm.errors.password }}</p>
                                </div>

                                <!-- Confirm Password -->
                                <div>
                                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                                        Confirm New Password
                                    </label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <KeyRound class="h-4 w-4 text-slate-400" />
                                        </div>
                                        <input
                                            id="password_confirmation"
                                            v-model="passwordForm.password_confirmation"
                                            :type="showConfirmPw ? 'text' : 'password'"
                                            autocomplete="new-password"
                                            class="block w-full rounded-xl border border-slate-200 bg-white pl-9 pr-10 py-2.5 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                            placeholder="Confirm new password"
                                        />
                                        <button type="button" @click="showConfirmPw = !showConfirmPw"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                            <span class="text-xs">{{ showConfirmPw ? 'Hide' : 'Show' }}</span>
                                        </button>
                                    </div>
                                    <p v-if="passwordForm.errors.password_confirmation" class="mt-1 text-xs text-rose-500">{{ passwordForm.errors.password_confirmation }}</p>
                                </div>
                            </div>

                            <!-- Password strength hint -->
                            <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3 flex items-start gap-2">
                                <ChevronRight class="h-4 w-4 text-slate-400 mt-0.5 shrink-0" />
                                <p class="text-xs text-slate-500">
                                    Password must be at least <strong>8 characters</strong> long. Use a mix of letters, numbers, and symbols for a stronger password.
                                </p>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="passwordForm.processing"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-teal-700 text-sm font-semibold text-white hover:bg-teal-600 disabled:opacity-60 transition shadow-sm"
                                >
                                    <KeyRound class="h-4 w-4" />
                                    {{ passwordForm.processing ? 'Updating…' : 'Update Password' }}
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
