<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { PlusCircle, ClipboardList, Search, Clock, FileText, ArrowUpDown, ChevronUp, ChevronDown } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    ideas: {
        type: Array,
        required: true,
    },
});

// ─── Search ────────────────────────────────────────────────
const searchQuery = ref('');
const filteredIdeas = computed(() => {
    if (!searchQuery.value) return props.ideas;
    const q = searchQuery.value.toLowerCase();
    return props.ideas.filter(idea =>
        idea.submission_code?.toLowerCase().includes(q) ||
        idea.title?.toLowerCase().includes(q) ||
        idea.type_of_improvement?.toLowerCase().includes(q) ||
        idea.status?.toLowerCase().includes(q)
    );
});

// ─── Date Range Filter ──────────────────────────────────────
const dateFrom = ref('');
const dateTo   = ref('');

const filterByDateRange = (ideas) => {
    if (!dateFrom.value && !dateTo.value) return ideas;
    const from = dateFrom.value ? new Date(`${dateFrom.value}T00:00:00`).getTime() : null;
    const to   = dateTo.value   ? new Date(`${dateTo.value}T23:59:59`).getTime()   : null;
    return ideas.filter(idea => {
        if (!idea.created_at) return false;
        const ts = new Date(idea.created_at).getTime();
        if (from !== null && ts < from) return false;
        if (to   !== null && ts > to)   return false;
        return true;
    });
};

// ─── Sort ──────────────────────────────────────────────────
const sortKey = ref('created_at');
const sortDir = ref('desc');

const getSortValue = (idea, key) => {
    switch (key) {
        case 'created_at': return idea.created_at ? new Date(idea.created_at).getTime() : 0;
        default:           return (idea[key] || '').toString().toLowerCase();
    }
};

const sortedIdeas = computed(() => {
    const ideas = filterByDateRange([...filteredIdeas.value]);
    const dir = sortDir.value === 'asc' ? 1 : -1;
    return ideas.sort((a, b) => {
        const aVal = getSortValue(a, sortKey.value);
        const bVal = getSortValue(b, sortKey.value);
        if (aVal < bVal) return -1 * dir;
        if (aVal > bVal) return  1 * dir;
        return 0;
    });
});

const toggleSort = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'asc';
    }
};

// ─── Helpers ───────────────────────────────────────────────
const formatDate = (d) => {
    if (!d) return 'N/A';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatRupiah = (v) => 'Rp\u202f' + Number(v || 0).toLocaleString('id-ID');

const statusClass = (status) => ({
    'bg-slate-100 text-slate-700 border-slate-200':   status === 'Draft',
    'bg-amber-100 text-amber-700 border-amber-200':   status === 'Revision Requested',
    'bg-teal-100  text-teal-800  border-teal-300':    ['SPS Review', 'Technical Review', 'Managerial Review'].includes(status),
    'bg-blue-100  text-blue-700  border-blue-200':    status === 'Reward Processing',
    'bg-emerald-100 text-emerald-800 border-emerald-300': status === 'Implemented',
    'bg-rose-100  text-rose-700  border-rose-200':    ['Rejected', 'Closed'].includes(status),
});

// Progress bar width per status
const progressMap = {
    'Draft':             5,
    'SPS Review':        20,
    'Revision Requested':20,
    'Technical Review':  40,
    'Managerial Review': 60,
    'Reward Processing': 80,
    'Implemented':       100,
    'Rejected':          0,
    'Closed':            0,
};
const progressColor = (status) => {
    if (status === 'Implemented') return 'bg-emerald-500';
    if (['Rejected','Closed'].includes(status)) return 'bg-rose-400';
    return 'bg-teal-500';
};
</script>

<template>
    <Head :title="t('ideas.my_ideas_title')" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-blue-50 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- ── Header ─────────────────────────────────────── -->
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-slate-900">{{ t('ideas.my_ideas_title') }}</h1>
                        <p class="text-sm text-slate-500 mt-0.5">
                            {{ sortedIdeas.length }} {{ sortedIdeas.length !== 1 ? t('ideas.submissions') : t('ideas.submission') }} {{ t('ideas.found') }}
                        </p>
                    </div>
                    <Link
                        :href="route('ideas.create')"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-teal-700 text-white text-sm font-semibold rounded-xl hover:bg-teal-600 transition shadow-sm"
                    >
                        <PlusCircle class="w-4 h-4" />
                        {{ t('ideas.submit_new') }}
                    </Link>
                </div>

                <!-- ── Search & Filter Bar ────────────────────────── -->
                <div class="mb-4 rounded-2xl border border-teal-100 bg-white p-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <!-- Search -->
                        <div class="relative w-full md:w-1/3">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Search class="h-4 w-4 text-slate-400" />
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                class="block w-full pl-9 pr-3 py-2 border border-teal-100 rounded-lg bg-teal-50 text-sm placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-600 focus:border-teal-600"
                                :placeholder="t('ideas.search_placeholder')"
                            />
                        </div>

                        <!-- Date Range -->
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="flex items-center text-xs font-semibold text-teal-700 uppercase tracking-wider">
                                <ArrowUpDown class="h-3.5 w-3.5 mr-1" />
                                {{ t('ideas.date_range') }}
                            </div>
                            <input
                                v-model="dateFrom"
                                type="date"
                                class="block border border-teal-100 rounded-lg bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-1 focus:ring-teal-600 focus:border-teal-600"
                                aria-label="Filter from date"
                            />
                            <span class="text-sm text-slate-400">{{ t('ideas.to') }}</span>
                            <input
                                v-model="dateTo"
                                type="date"
                                class="block border border-teal-100 rounded-lg bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-1 focus:ring-teal-600 focus:border-teal-600"
                                aria-label="Filter to date"
                            />
                        </div>
                    </div>
                </div>

                <!-- ── Table ──────────────────────────────────────── -->
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-teal-100">
                    <template v-if="sortedIdeas.length > 0">

                        <!-- Mobile Cards -->
                        <div class="md:hidden divide-y divide-slate-100">
                            <div v-for="idea in sortedIdeas" :key="idea.id" class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <Link
                                            :href="route('ideas.show', idea.id)"
                                            class="text-sm font-semibold text-teal-700 hover:text-teal-900 hover:underline"
                                        >
                                            {{ idea.submission_code }}
                                        </Link>
                                        <div class="mt-1 text-sm font-medium text-slate-900 truncate">{{ idea.title }}</div>
                                        <div class="mt-0.5 text-xs text-slate-500 flex items-center gap-1">
                                            <FileText class="w-3 h-3" />
                                            {{ idea.type_of_improvement }}
                                        </div>
                                    </div>
                                    <span
                                        class="shrink-0 px-2 py-1 text-xs font-semibold rounded-full border"
                                        :class="statusClass(idea.status)"
                                    >
                                        {{ idea.status }}
                                    </span>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mt-3">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs text-slate-400">{{ t('ideas.progress') }}</span>
                                        <span class="text-xs font-semibold text-slate-600">{{ progressMap[idea.status] ?? idea.completion_percentage ?? 0 }}%</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                                        <div
                                            class="h-1.5 rounded-full transition-all"
                                            :class="progressColor(idea.status)"
                                            :style="{ width: (progressMap[idea.status] ?? idea.completion_percentage ?? 0) + '%' }"
                                        />
                                    </div>
                                </div>

                                <div class="mt-2 flex flex-col gap-1 text-xs text-slate-400">
                                    <div class="flex items-center gap-2">
                                        <Clock class="w-3 h-3" />
                                        {{ formatDate(idea.created_at) }}
                                    </div>
                                    <div v-if="idea.score?.total_points" class="font-semibold text-teal-700 flex items-center gap-2 mt-1 border-t border-slate-100 pt-1.5">
                                        <span>{{ t('ideas.score') }}: {{ idea.score.total_points }}{{ idea.score.category === 'tangible' ? '%' : ' pt' }}</span>
                                        <span v-if="idea.score.calculated_reward" class="text-emerald-600 border-l border-slate-300 pl-2">
                                            {{ formatRupiah(idea.score.calculated_reward) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Table -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-[860px] w-full divide-y divide-slate-200">
                                <thead class="bg-teal-700">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-teal-50 uppercase tracking-wider">
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-1.5 hover:text-white"
                                                @click="toggleSort('submission_code')"
                                            >
                                                {{ t('ideas.idea_details') }}
                                                <ChevronUp   v-if="sortKey === 'submission_code' && sortDir === 'asc'"  class="h-3 w-3" />
                                                <ChevronDown v-else-if="sortKey === 'submission_code'"                  class="h-3 w-3" />
                                            </button>
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-teal-50 uppercase tracking-wider">
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-1.5 hover:text-white"
                                                @click="toggleSort('status')"
                                            >
                                                {{ t('ideas.status_date') }}
                                                <ChevronUp   v-if="sortKey === 'status' && sortDir === 'asc'"  class="h-3 w-3" />
                                                <ChevronDown v-else-if="sortKey === 'status'"                  class="h-3 w-3" />
                                            </button>
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-teal-50 uppercase tracking-wider">{{ t('ideas.progress') }}</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-teal-50 uppercase tracking-wider">
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-1.5 hover:text-white"
                                                @click="toggleSort('created_at')"
                                            >
                                                {{ t('ideas.date') }}
                                                <ChevronUp   v-if="sortKey === 'created_at' && sortDir === 'asc'"  class="h-3 w-3" />
                                                <ChevronDown v-else-if="sortKey === 'created_at'"                  class="h-3 w-3" />
                                            </button>
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-teal-50 uppercase tracking-wider">{{ t('ideas.score') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100">
                                    <tr
                                        v-for="idea in sortedIdeas"
                                        :key="idea.id"
                                        class="hover:bg-slate-50 transition-colors"
                                        :class="{ 'bg-amber-50/40': idea.status === 'Revision Requested' }"
                                    >
                                        <!-- Idea Details -->
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col gap-0.5">
                                                <Link
                                                    :href="route('ideas.show', idea.id)"
                                                    class="text-sm font-semibold text-teal-700 hover:text-teal-900 hover:underline"
                                                >
                                                    {{ idea.submission_code }}
                                                </Link>
                                                <span class="text-sm font-medium text-slate-900">{{ idea.title }}</span>
                                                <span class="text-xs text-slate-400 flex items-center gap-1">
                                                    <FileText class="w-3 h-3" />
                                                    {{ idea.type_of_improvement }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Status -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border"
                                                :class="statusClass(idea.status)"
                                            >
                                                {{ idea.status }}
                                            </span>
                                            <!-- Special badge for revision -->
                                            <div v-if="idea.status === 'Revision Requested'" class="mt-1 text-xs text-amber-600 font-medium flex items-center gap-1">
                                                ⚠ {{ t('ideas.action_required') }}
                                            </div>
                                        </td>

                                        <!-- Progress -->
                                        <td class="px-6 py-4 w-40">
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 h-1.5 rounded-full bg-slate-100 min-w-[80px]">
                                                    <div
                                                        class="h-1.5 rounded-full transition-all duration-500"
                                                        :class="progressColor(idea.status)"
                                                        :style="{ width: (progressMap[idea.status] ?? idea.completion_percentage ?? 0) + '%' }"
                                                    />
                                                </div>
                                                <span class="text-xs text-slate-500 font-medium shrink-0 w-8 text-right">
                                                    {{ progressMap[idea.status] ?? idea.completion_percentage ?? 0 }}%
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Date -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-1.5 text-sm text-slate-500">
                                                <Clock class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                                {{ formatDate(idea.created_at) }}
                                            </div>
                                        </td>

                                        <!-- Score -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div v-if="idea.score?.total_points" class="flex flex-col gap-0.5">
                                                <span class="text-sm font-semibold text-teal-700">
                                                    {{ idea.score.total_points }}{{ idea.score.category === 'tangible' ? '%' : ' pt' }}
                                                </span>
                                                <span v-if="idea.score?.calculated_reward" class="text-xs font-medium text-emerald-600">
                                                    {{ formatRupiah(idea.score.calculated_reward) }}
                                                </span>
                                            </div>
                                            <span v-else class="text-sm text-slate-300">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </template>

                    <!-- Empty State -->
                    <div v-else class="text-center py-16">
                        <ClipboardList class="mx-auto h-12 w-12 text-teal-200" />
                        <h3 class="mt-3 text-sm font-semibold text-slate-900">
                            {{ searchQuery || dateFrom || dateTo ? t('ideas.no_results') : t('ideas.no_ideas') }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ searchQuery || dateFrom || dateTo
                                ? t('ideas.no_results_desc')
                                : t('ideas.no_ideas_desc') }}
                        </p>
                        <Link
                            v-if="!searchQuery && !dateFrom && !dateTo"
                            :href="route('ideas.create')"
                            class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-teal-700 text-white text-sm font-semibold rounded-xl hover:bg-teal-600 transition"
                        >
                            <PlusCircle class="w-4 h-4" />
                            {{ t('ideas.submit_new') }}
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>