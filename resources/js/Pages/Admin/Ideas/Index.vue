<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ClipboardList, Search, Clock, FileText, ArrowUpDown, ChevronUp, ChevronDown } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    ideas: {
        type: Array,
        required: true,
    },
});

const page = usePage();

const adminShowRoute = (ideaId) => {
    const role = page.props.auth?.user?.role;

    if (role === 'technical_manager' || role === 'technical_reviewer') {
        return route('admin.ideas.technical.show', ideaId);
    }

    if (role === 'financial_manager') {
        return route('admin.ideas.financial.show', ideaId);
    }

    return route('admin.ideas.sps.show', ideaId);
};

// Simple search functionality for the admin to filter the table
const searchQuery = ref('');

const filteredIdeas = computed(() => {
    if (!searchQuery.value) return props.ideas;
    const lowerQuery = searchQuery.value.toLowerCase();
    return props.ideas.filter(idea => 
        idea.submission_code.toLowerCase().includes(lowerQuery) ||
        idea.title.toLowerCase().includes(lowerQuery) ||
        (idea.user && idea.user.name.toLowerCase().includes(lowerQuery))
    );
});

const sortKey = ref('created_at');
const sortDir = ref('desc');
const dateFrom = ref('');
const dateTo = ref('');

const getSortValue = (idea, key) => {
    switch (key) {
        case 'submitter':
            return (idea.user?.name || '').toLowerCase();
        case 'department':
            return (idea.user?.department?.name || '').toLowerCase();
        case 'created_at':
            return idea.created_at ? new Date(idea.created_at).getTime() : 0;
        default:
            return (idea[key] || '').toString().toLowerCase();
    }
};

const filterByDateRange = (ideas) => {
    if (!dateFrom.value && !dateTo.value) return ideas;

    const from = dateFrom.value ? new Date(`${dateFrom.value}T00:00:00`).getTime() : null;
    const to = dateTo.value ? new Date(`${dateTo.value}T23:59:59`).getTime() : null;

    return ideas.filter(idea => {
        if (!idea.created_at) return false;
        const created = new Date(idea.created_at).getTime();
        if (from !== null && created < from) return false;
        if (to !== null && created > to) return false;
        return true;
    });
};

const sortedIdeas = computed(() => {
    const ideas = filterByDateRange([...filteredIdeas.value]);
    const dir = sortDir.value === 'asc' ? 1 : -1;
    return ideas.sort((a, b) => {
        const aVal = getSortValue(a, sortKey.value);
        const bVal = getSortValue(b, sortKey.value);

        if (aVal < bVal) return -1 * dir;
        if (aVal > bVal) return 1 * dir;
        return 0;
    });
});

const toggleSort = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
        return;
    }

    sortKey.value = key;
    sortDir.value = 'asc';
};

// Helper to format timestamps
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatRupiah = (v) => 'Rp\u202f' + Number(v || 0).toLocaleString('id-ID');

const canImplement = (status) => ['SPS Review', 'Technical Review'].includes(status);
</script>

<template>
    <Head :title="t('admin.dashboard_title')" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-blue-50 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            

            <div class="mb-6 rounded-2xl border border-teal-100 bg-white p-4 md:p-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="relative w-full md:w-1/3">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search class="h-5 w-5 text-slate-400" />
                        </div>
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="block w-full pl-10 pr-3 py-2 border border-teal-100 rounded-md leading-5 bg-teal-50 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-600 focus:border-teal-600 sm:text-sm"
                            :placeholder="t('admin.search_placeholder')"
                        >
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center text-xs font-semibold text-teal-700 uppercase tracking-wider">
                            <ArrowUpDown class="h-4 w-4 mr-1" />
                            {{ t('ideas.date_range') }}
                        </div>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="block w-full md:w-40 border border-teal-100 rounded-md bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-1 focus:ring-teal-600 focus:border-teal-600"
                            aria-label="Filter from date"
                        >
                        <span class="text-sm text-slate-400">{{ t('ideas.to') }}</span>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="block w-full md:w-40 border border-teal-100 rounded-md bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-1 focus:ring-teal-600 focus:border-teal-600"
                            aria-label="Filter to date"
                        >
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-teal-100">
                <template v-if="sortedIdeas.length > 0">
                    <div class="md:hidden">
                        <div class="divide-y divide-gray-200">
                            <div v-for="idea in sortedIdeas" :key="idea.id" class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <Link :href="adminShowRoute(idea.id)" class="text-sm font-semibold text-slate-900 hover:underline">
                                            {{ idea.submission_code }}
                                        </Link>
                                        <div class="mt-1 text-sm font-medium text-gray-900">
                                            {{ idea.title }}
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ idea.type_of_improvement }}
                                        </div>
                                    </div>
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full border"
                                        :class="{
                                            'bg-slate-100 text-slate-700 border-slate-200': idea.status === 'Draft',
                                            'bg-amber-100 text-amber-700 border-amber-200': idea.status === 'Revision Requested',
                                            'bg-teal-50 text-teal-700 border-teal-100': ['SPS Review', 'Technical Review', 'Managerial Review'].includes(idea.status),
                                            'bg-blue-100 text-blue-700 border-blue-200': idea.status === 'Reward Processing',
                                            'bg-emerald-50 text-emerald-700 border-emerald-100': idea.status === 'Implemented',
                                            'bg-rose-50 text-rose-700 border-rose-100': ['Rejected', 'Closed'].includes(idea.status)
                                        }"
                                    >
                                        {{ idea.status }}
                                    </span>
                                </div>
                                <div class="mt-2 text-xs text-gray-500">
                                    {{ idea.user?.name || 'Unknown' }}
                                    <span class="text-gray-300">|</span>
                                    {{ idea.user?.department?.name || 'No Dept' }}
                                </div>
                                <div class="mt-2 text-xs text-gray-500 flex items-center">
                                    <Clock class="w-3 h-3 mr-1" />
                                    {{ formatDate(idea.created_at) }}
                                </div>
                                <div class="mt-2 text-xs text-teal-700 font-semibold flex items-center gap-2">
                                    <span>Score: {{ idea.score?.total_points ?? '-' }}{{ idea.score?.category === 'tangible' ? '%' : (idea.score ? ' pt' : '') }}</span>
                                    <span v-if="idea.score?.calculated_reward" class="text-emerald-600 border-l border-slate-300 pl-2">
                                        {{ formatRupiah(idea.score.calculated_reward) }}
                                    </span>
                                </div>
                                <div class="mt-3 text-xs text-gray-500">
                                    {{ t('admin.open_details') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-[980px] w-full divide-y divide-gray-200">
                            <thead class="bg-teal-700">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-teal-50 uppercase tracking-wider">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 hover:text-slate-900"
                                            @click="toggleSort('submission_code')"
                                        >
                                            {{ t('ideas.idea_details') }}
                                            <ChevronUp v-if="sortKey === 'submission_code' && sortDir === 'asc'" class="h-3 w-3" />
                                            <ChevronDown v-else-if="sortKey === 'submission_code'" class="h-3 w-3" />
                                        </button>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-teal-50 uppercase tracking-wider">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 hover:text-slate-900"
                                            @click="toggleSort('submitter')"
                                        >
                                            {{ t('admin.submitter_info') }}
                                            <ChevronUp v-if="sortKey === 'submitter' && sortDir === 'asc'" class="h-3 w-3" />
                                            <ChevronDown v-else-if="sortKey === 'submitter'" class="h-3 w-3" />
                                        </button>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-teal-50 uppercase tracking-wider">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 hover:text-slate-900"
                                            @click="toggleSort('created_at')"
                                        >
                                            {{ t('ideas.status_date') }}
                                            <ChevronUp v-if="sortKey === 'created_at' && sortDir === 'asc'" class="h-3 w-3" />
                                            <ChevronDown v-else-if="sortKey === 'created_at'" class="h-3 w-3" />
                                        </button>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-teal-50 uppercase tracking-wider">{{ t('ideas.score') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                <tr v-for="idea in sortedIdeas" :key="idea.id" :class="{'bg-slate-50': idea.status === 'SPS Review'}" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <Link :href="adminShowRoute(idea.id)" class="text-sm font-semibold text-slate-900 hover:underline mb-1">
                                                {{ idea.submission_code }}
                                            </Link>
                                            <span class="text-sm text-gray-900 font-medium">{{ idea.title }}</span>
                                            <span class="text-xs text-gray-500 mt-1 flex items-center">
                                                <FileText class="w-3 h-3 mr-1" />
                                                {{ idea.type_of_improvement }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ idea.user?.name || 'Unknown' }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ idea.user?.department?.name || 'No Dept' }}</div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full border"
                                            :class="{
                                                'bg-slate-100 text-slate-700 border-slate-200': idea.status === 'Draft',
                                                'bg-amber-100 text-amber-700 border-amber-200': idea.status === 'Revision Requested',
                                                'bg-teal-100 text-teal-800 border-teal-300': ['SPS Review', 'Technical Review', 'Managerial Review'].includes(idea.status),
                                                'bg-blue-100 text-blue-700 border-blue-200': idea.status === 'Reward Processing',
                                                'bg-teal-200 text-teal-900 border-teal-300': idea.status === 'Implemented',
                                                'bg-rose-100 text-rose-700 border-rose-200': ['Rejected', 'Closed'].includes(idea.status)
                                            }"
                                        >
                                            {{ idea.status }}
                                        </span>
                                        <div class="text-xs text-gray-500 mt-2 flex items-center">
                                            <Clock class="w-3 h-3 mr-1" />
                                            {{ formatDate(idea.created_at) }}
                                        </div>
                                    </td>

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

                <div v-else class="text-center py-16">
                    <ClipboardList class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ searchQuery || dateFrom || dateTo ? t('ideas.no_results') : t('admin.no_submissions') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ searchQuery || dateFrom || dateTo ? t('ideas.no_results_desc') : t('admin.no_submissions_desc') }}</p>
                </div>
            </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>