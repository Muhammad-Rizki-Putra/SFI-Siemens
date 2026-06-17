<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    daily: {
        type: Array,
        required: true,
    },
    monthly: {
        type: Array,
        required: true,
    },
    yearly: {
        type: Array,
        required: true,
    },
    topSubmitters: {
        type: Array,
        required: true,
    },
});


const formatDay = (value) => {
    if (!value) return 'N/A';
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
};

const formatMonth = (value) => {
    if (!value) return 'N/A';
    const [year, month] = value.split('-');
    return new Date(`${year}-${month}-01`).toLocaleDateString('en-US', {
        month: 'short',
        year: 'numeric',
    });
};

const formatYear = (value) => value ? value.toString() : 'N/A';

const dailySeries = computed(() => props.daily.slice(-14));
const monthlySeries = computed(() => props.monthly.slice(-6));
const yearlySeries = computed(() => props.yearly.slice(-5));

const totalSubmissions = computed(() =>
    props.daily.reduce((sum, row) => sum + row.total, 0),
);

const todayCount = computed(() => {
    const today = new Date().toISOString().slice(0, 10);
    const match = props.daily.find((row) => row.period === today);
    return match ? match.total : 0;
});

const currentMonthCount = computed(() => {
    const now = new Date();
    const key = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    const match = props.monthly.find((row) => row.period === key);
    return match ? match.total : 0;
});

const topSubmitter = computed(() => props.topSubmitters[0]);

const chartData = computed(() => {
    const rows = dailySeries.value;
    if (!rows.length) {
        return { points: '', area: '' };
    }

    const width = 100;
    const height = 40;
    const padding = 4;
    const max = Math.max(...rows.map((row) => row.total), 1);
    const step = rows.length > 1 ? (width - padding * 2) / (rows.length - 1) : 0;

    const points = rows
        .map((row, index) => {
            const x = padding + index * step;
            const y = height - padding - (row.total / max) * (height - padding * 2);
            return `${x},${y}`;
        })
        .join(' ');

    const area = `M ${padding} ${height - padding} L ${points} L ${width - padding} ${height - padding} Z`;

    return { points, area };
});
</script>

<template>
    <Head title="Leaderboard" />

    <AuthenticatedLayout>
        <div class="leaderboard-root relative min-h-screen bg-gradient-to-br from-blue-50 via-slate-50 to-zinc-100 py-10">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-[radial-gradient(circle_at_top,_rgba(13,148,136,0.2),_transparent_55%)]"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-6 mb-8 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs tracking-[0.3em] text-teal-600 uppercase">Idea Pulse</p>
                    <h1 class="leaderboard-title text-3xl sm:text-4xl text-slate-900">Leaderboard</h1>
                    <p class="text-sm text-slate-600 max-w-xl">Track daily energy, spotlight consistent teams, and keep the improvement rhythm alive.</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <Link
                        :href="route('ideas.create')"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-full bg-teal-700 text-white text-sm font-semibold hover:bg-teal-600"
                    >
                        Submit New Idea
                    </Link>
                    <Link
                        :href="route('ideas.index')"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-full border border-teal-100 bg-white text-teal-700 text-sm font-semibold hover:border-teal-200"
                    >
                        Back to Ideas
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-3 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="rounded-2xl bg-teal-700 text-white p-5 shadow-lg shadow-teal-200/60">
                        <div class="text-xs uppercase tracking-[0.2em] text-teal-200">Total Ideas</div>
                        <div class="mt-3 text-3xl font-semibold">{{ totalSubmissions }}</div>
                        <div class="mt-2 text-xs text-teal-100">All-time submissions</div>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-lg shadow-teal-200/50 border border-teal-100">
                        <div class="text-xs uppercase tracking-[0.2em] text-teal-600">This Month</div>
                        <div class="mt-3 text-3xl font-semibold text-slate-900">{{ currentMonthCount }}</div>
                        <div class="mt-2 text-xs text-slate-500">Current month momentum</div>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-lg shadow-teal-200/50 border border-teal-100">
                        <div class="text-xs uppercase tracking-[0.2em] text-teal-600">Today</div>
                        <div class="mt-3 text-3xl font-semibold text-slate-900">{{ todayCount }}</div>
                        <div class="mt-2 text-xs text-slate-500">Ideas logged so far</div>
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-lg shadow-teal-200/50 border border-teal-100">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 class="leaderboard-subtitle text-lg text-slate-900">Idea Submissions Chart</h2>
                                <p class="text-xs text-slate-500">Daily submissions (last 14 days)</p>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <span class="px-3 py-1 rounded-full border border-teal-100 text-teal-700">Last 14 Days</span>
                            </div>
                        </div>
                        <div class="mt-6">
                            <div class="h-40 w-full rounded-xl bg-gradient-to-b from-teal-50 to-white border border-teal-100">
                                <svg viewBox="0 0 100 40" class="w-full h-full">
                                    <defs>
                                        <linearGradient id="lineFill" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#0f766e" stop-opacity="0.35" />
                                            <stop offset="100%" stop-color="#0f766e" stop-opacity="0" />
                                        </linearGradient>
                                    </defs>
                                    <path
                                        v-if="chartData.area"
                                        :d="chartData.area"
                                        fill="url(#lineFill)"
                                    />
                                    <polyline
                                        v-if="chartData.points"
                                        :points="chartData.points"
                                        fill="none"
                                        stroke="#0f766e"
                                        stroke-width="1.6"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                            <div class="mt-4 grid grid-cols-7 gap-2 text-[10px] text-slate-400">
                                <span v-for="row in dailySeries" :key="row.period" class="text-center">
                                    {{ formatDay(row.period) }}
                                </span>
                            </div>
                            <p v-if="dailySeries.length === 0" class="text-sm text-slate-400 mt-4">No submissions yet.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-2xl bg-white p-5 shadow-lg shadow-teal-200/50 border border-teal-100">
                            <h3 class="leaderboard-subtitle text-base text-slate-900">Monthly Trend</h3>
                            <div class="mt-4 space-y-3">
                                <div v-for="row in monthlySeries" :key="row.period" class="flex items-center gap-3">
                                    <div class="w-16 text-[11px] text-slate-400">{{ formatMonth(row.period) }}</div>
                                    <div class="flex-1 h-2 rounded-full bg-teal-50">
                                        <div
                                            class="h-2 rounded-full bg-teal-500"
                                            :style="{ width: `${Math.max(row.total, 1) * 8}px` }"
                                        ></div>
                                    </div>
                                    <div class="w-6 text-[11px] font-semibold text-slate-700 text-right">{{ row.total }}</div>
                                </div>
                                <p v-if="monthlySeries.length === 0" class="text-sm text-slate-400">No data yet.</p>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-white p-5 shadow-lg shadow-teal-200/50 border border-teal-100">
                            <h3 class="leaderboard-subtitle text-base text-slate-900">Yearly Snapshot</h3>
                            <div class="mt-4 space-y-3">
                                <div v-for="row in yearlySeries" :key="row.period" class="flex items-center gap-3">
                                    <div class="w-10 text-[11px] text-slate-400">{{ formatYear(row.period) }}</div>
                                    <div class="flex-1 h-2 rounded-full bg-teal-50">
                                        <div
                                            class="h-2 rounded-full bg-teal-600"
                                            :style="{ width: `${Math.max(row.total, 1) * 10}px` }"
                                        ></div>
                                    </div>
                                    <div class="w-6 text-[11px] font-semibold text-slate-700 text-right">{{ row.total }}</div>
                                </div>
                                <p v-if="yearlySeries.length === 0" class="text-sm text-slate-400">No data yet.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-lg shadow-teal-200/50 border border-teal-100">
                        <h3 class="leaderboard-subtitle text-base text-slate-900">Top Submitters</h3>
                        <div class="mt-4 space-y-4">
                            <div v-for="row in topSubmitters" :key="row.user_id" class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">{{ row.user?.name || 'Unknown' }}</div>
                                    <div class="text-xs text-slate-400">{{ row.user?.department?.name || 'No Dept' }}</div>
                                </div>
                                <div class="text-sm font-semibold text-slate-800">{{ row.total }}</div>
                            </div>
                            <p v-if="topSubmitters.length === 0" class="text-sm text-slate-400">No submissions yet.</p>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-gradient-to-br from-teal-700 to-teal-600 p-6 text-white shadow-lg shadow-teal-200/60">
                        <div class="text-xs uppercase tracking-[0.2em] text-teal-100">Spotlight</div>
                        <div class="mt-3 text-xl font-semibold">
                            {{ topSubmitter?.user?.name || 'Be the first!' }}
                        </div>
                        <p class="text-sm text-teal-100 mt-2">Share the next improvement and lead the board this week.</p>
                        <div class="mt-4 text-xs text-slate-300">
                            {{ topSubmitter?.total || 0 }} ideas submitted
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

