<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ArrowLeft, User, Calendar, Tag, Activity, FileText, Calculator, CheckCircle, Lightbulb } from 'lucide-vue-next';
import { useToast } from '@/Composables/useToast';

const { show: showToast } = useToast();

const props = defineProps({
    idea:         { type: Object,  required: true },
    backUrl:      { type: String,  default: '' },
    adminActions: { type: Boolean, default: false },
});

const formatDate = (d) => {
    if (!d) return 'N/A';
    return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

const formatRupiah = (v) => 'Rp\u202f' + Number(v || 0).toLocaleString('id-ID');

import EvaluationMatrixForm from '@/Components/EvaluationMatrixForm.vue';
import WorkflowDecisionForm from '@/Components/WorkflowDecisionForm.vue';

const activeTab = ref('workflow');

// ─── Read-only score display ───────────────────────────────────────────────
const score    = computed(() => props.idea.score || {});
const aTotal   = computed(() =>
    Number(score.value.factor_a1 || 0) +
    Number(score.value.factor_a2 || 0) +
    Number(score.value.factor_a3 || 0)
);
const sfTotal  = computed(() => {
    const base = aTotal.value * Number(score.value.factor_b || 0) + Number(score.value.implementation_factor || 0);
    return score.value.category === 'intangible' ? base + Number(score.value.factor_c || 0) : base;
});
const displayReward = computed(() => {
    if (!score.value.category) return 0;
    if (score.value.category === 'intangible') return intangibleAwardLookup(sfTotal.value);
    return Number(score.value.cost_savings || 0) * (Number(score.value.reward_percent || 0) / 100);
});

// ─── Timeline ─────────────────────────────────────────────────────────────
const statusPalette = {
    'Submitted':         { dot: 'bg-slate-500',   text: 'text-slate-700' },
    'SPS Review':        { dot: 'bg-teal-500',    text: 'text-teal-700' },
    'Technical Review':  { dot: 'bg-cyan-500',    text: 'text-cyan-700' },
    'Managerial Review': { dot: 'bg-indigo-500',  text: 'text-indigo-700' },
    'Reward Processing': { dot: 'bg-blue-500',    text: 'text-blue-700' },
    'Implemented':       { dot: 'bg-emerald-600', text: 'text-emerald-700' },
    'Revision Requested':{ dot: 'bg-amber-500',   text: 'text-amber-700' },
    'Rejected':          { dot: 'bg-rose-600',    text: 'text-rose-700' },
    'Closed':            { dot: 'bg-rose-700',    text: 'text-rose-700' },
    'Draft':             { dot: 'bg-slate-400',   text: 'text-slate-700' },
    'Resubmitted':       { dot: 'bg-teal-500',    text: 'text-teal-700' },
};
const timelineMeta = (s) => statusPalette[s] || statusPalette.Submitted;
</script>

<template>
    <Head :title="idea.submission_code" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-blue-50 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- ── Header ────────────────────────────────────────────── -->
                <div class="mb-6">
                    <Link :href="backUrl || route('ideas.index')"
                        class="inline-flex items-center text-sm font-medium text-teal-700 hover:text-teal-900 transition mb-4">
                        <ArrowLeft class="w-4 h-4 mr-1" />
                        Back
                    </Link>
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <h1 class="text-2xl font-semibold text-slate-900">{{ idea.title }}</h1>
                            <p class="text-sm text-slate-500 mt-1">
                                Kode: <span class="font-mono font-medium text-slate-700">{{ idea.submission_code }}</span>
                            </p>
                        </div>
                        <span class="self-start md:self-auto inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold border" :class="{
                            'bg-slate-100 text-slate-700 border-slate-200':   idea.status === 'Draft',
                            'bg-amber-100 text-amber-700 border-amber-200':   idea.status === 'Revision Requested',
                            'bg-teal-100  text-teal-800  border-teal-300':    ['SPS Review','Technical Review','Managerial Review'].includes(idea.status),
                            'bg-blue-100  text-blue-700  border-blue-200':    idea.status === 'Reward Processing',
                            'bg-emerald-100 text-emerald-800 border-emerald-300': idea.status === 'Implemented',
                            'bg-rose-100  text-rose-700  border-rose-200':    ['Rejected','Closed'].includes(idea.status),
                        }">
                            <Activity class="w-4 h-4 mr-1.5" />
                            {{ idea.status }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- ── Left Column (2/3) ──────────────────────────── -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- ════ ADMIN: Tabs untuk Workflow / Evaluation ════════════ -->
                        <div v-if="adminActions" class="space-y-4">
                            <!-- Tabs Navigation -->
                            <div class="flex border-b border-slate-200">
                                <button
                                    @click="activeTab = 'workflow'"
                                    :class="[
                                        'py-2.5 px-5 text-sm font-medium border-b-2 transition-colors',
                                        activeTab === 'workflow'
                                            ? 'border-teal-500 text-teal-700'
                                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                    ]"
                                >
                                    Workflow Decision
                                </button>
                                <button
                                    @click="activeTab = 'evaluation'"
                                    :class="[
                                        'py-2.5 px-5 text-sm font-medium border-b-2 transition-colors',
                                        activeTab === 'evaluation'
                                            ? 'border-teal-500 text-teal-700'
                                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                    ]"
                                >
                                    Evaluation Matrix
                                </button>
                                <button
                                    @click="activeTab = 'details'"
                                    :class="[
                                        'py-2.5 px-5 text-sm font-medium border-b-2 transition-colors',
                                        activeTab === 'details'
                                            ? 'border-teal-500 text-teal-700'
                                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                    ]"
                                >
                                    Submission Details
                                </button>
                            </div>

                            <!-- Tabs Content -->
                            <transition mode="out-in" enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
                                <div v-if="activeTab === 'workflow'" :key="'workflow'">
                                    <WorkflowDecisionForm :idea="idea" type="sps" />
                                </div>
                                <div v-else-if="activeTab === 'evaluation'" :key="'evaluation'">
                                    <EvaluationMatrixForm :idea="idea" />
                                </div>
                                <div v-else-if="activeTab === 'details'" :key="'details'">
                                    <div class="bg-white rounded-2xl shadow-sm border border-teal-100 p-6">
                                        <h2 class="text-sm font-semibold text-slate-900 uppercase tracking-wider flex items-center mb-4">
                                            <FileText class="w-4 h-4 mr-2 text-teal-600" />
                                            Deskripsi Ide
                                        </h2>
                                        <div class="space-y-4 text-sm text-slate-700">
                                            <div>
                                                <div class="text-xs font-semibold text-teal-700 uppercase tracking-wider mb-1">Deskripsi Masalah</div>
                                                <div class="whitespace-pre-line leading-relaxed">{{ idea.problem_description || 'N/A' }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs font-semibold text-teal-700 uppercase tracking-wider mb-1">Deskripsi Solusi</div>
                                                <div class="whitespace-pre-line leading-relaxed">{{ idea.solution_description || 'N/A' }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs font-semibold text-teal-700 uppercase tracking-wider mb-1">Area Penerapan</div>
                                                <div>{{ idea.area_of_application || 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </transition>
                        </div>

                        <!-- ════ USER: Deskripsi Ide (read-only) ════════ -->
                        <div v-if="!adminActions" class="bg-white rounded-2xl shadow-sm border border-teal-100 p-6">
                            <h2 class="text-sm font-semibold text-slate-900 uppercase tracking-wider flex items-center mb-4">
                                <FileText class="w-4 h-4 mr-2 text-teal-600" />
                                Deskripsi Ide
                            </h2>
                            <div class="space-y-4 text-sm text-slate-700">
                                <div>
                                    <div class="text-xs font-semibold text-teal-700 uppercase tracking-wider mb-1">Deskripsi Masalah</div>
                                    <div class="whitespace-pre-line leading-relaxed">{{ idea.problem_description || 'N/A' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-teal-700 uppercase tracking-wider mb-1">Deskripsi Solusi</div>
                                    <div class="whitespace-pre-line leading-relaxed">{{ idea.solution_description || 'N/A' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-teal-700 uppercase tracking-wider mb-1">Area Penerapan</div>
                                    <div>{{ idea.area_of_application || 'N/A' }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- ════ USER: Hasil Evaluasi (read-only) ════════ -->
                        <div v-if="!adminActions && score.category" class="bg-white rounded-2xl shadow-sm border border-teal-100 p-6">
                            <h2 class="text-sm font-semibold text-slate-900 uppercase tracking-wider flex items-center mb-4">
                                <Calculator class="w-4 h-4 mr-2 text-teal-600" />
                                Hasil Evaluasi
                            </h2>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                                <div class="rounded-xl border border-teal-100 bg-teal-50 px-4 py-3">
                                    <div class="text-xs font-semibold text-teal-700 uppercase tracking-wider">Kategori</div>
                                    <div class="mt-1 font-semibold text-slate-900 capitalize">{{ score.category }}</div>
                                </div>
                                <div class="rounded-xl border border-teal-100 bg-teal-50 px-4 py-3">
                                    <div class="text-xs font-semibold text-teal-700 uppercase tracking-wider">Suggestion Factor</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ sfTotal }}{{ score.category === 'tangible' ? '%' : ' pt' }}</div>
                                </div>
                                <div class="rounded-xl border border-teal-100 bg-teal-50 px-4 py-3 col-span-2 md:col-span-1">
                                    <div class="text-xs font-semibold text-teal-700 uppercase tracking-wider">Estimated Reward</div>
                                    <div class="mt-1 font-semibold text-teal-700">{{ formatRupiah(displayReward) }}</div>
                                </div>
                            </div>
                            <div v-if="score.remark" class="mt-3 text-xs text-slate-500 italic">Catatan: {{ score.remark }}</div>
                        </div>

                    </div>

                    <!-- ── Right Sidebar (1/3) ────────────────────────── -->
                    <div class="space-y-6">

                        <!-- Submission Details -->
                        <div class="bg-white rounded-2xl shadow-sm border border-teal-100 p-5">
                            <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4">Submission Details</h3>
                            <dl class="space-y-4">
                                <div class="flex items-start gap-3">
                                    <User class="w-4 h-4 text-teal-600 mt-0.5 shrink-0" />
                                    <div class="min-w-0">
                                        <dt class="text-xs text-slate-500">Submitter</dt>
                                        <dd class="text-sm font-medium text-slate-900 truncate">{{ idea.user?.name || 'Unknown' }}</dd>
                                        <dd class="text-xs text-slate-500">{{ idea.user?.department?.name || 'No Department' }}</dd>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <Tag class="w-4 h-4 text-teal-600 mt-0.5 shrink-0" />
                                    <div>
                                        <dt class="text-xs text-slate-500">Type of Improvement</dt>
                                        <dd class="text-sm text-slate-900">{{ idea.type_of_improvement }}</dd>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <Calendar class="w-4 h-4 text-teal-600 mt-0.5 shrink-0" />
                                    <div>
                                        <dt class="text-xs text-slate-500">Date Submitted</dt>
                                        <dd class="text-sm text-slate-900">{{ formatDate(idea.created_at) }}</dd>
                                    </div>
                                </div>
                            </dl>
                        </div>

                        <!-- Scoring Snapshot (if scored) -->
                        <div v-if="score.category" class="bg-white rounded-2xl shadow-sm border border-teal-100 p-5">
                            <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4">Scoring Snapshot</h3>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">Kategori</dt>
                                    <dd class="font-semibold text-slate-900 capitalize">{{ score.category }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-400 text-xs">A1 (Area)</dt>
                                    <dd class="text-slate-700">{{ score.factor_a1 }} pt</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-400 text-xs">A2 (Feasibility)</dt>
                                    <dd class="text-slate-700">{{ score.factor_a2 }} pt</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-400 text-xs">A3 (Adaptability)</dt>
                                    <dd class="text-slate-700">{{ score.factor_a3 }} pt</dd>
                                </div>
                                <div class="flex justify-between font-medium border-t border-slate-100 pt-1">
                                    <dt class="text-slate-500">A Total</dt>
                                    <dd class="text-slate-900">{{ aTotal }} pt</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">× Faktor B</dt>
                                    <dd class="text-slate-900">×{{ score.factor_b }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">+ Impl. Factor</dt>
                                    <dd class="text-slate-900">+{{ score.implementation_factor }}</dd>
                                </div>
                                <div v-if="score.category === 'intangible'" class="flex justify-between">
                                    <dt class="text-slate-500">+ Faktor C</dt>
                                    <dd class="text-slate-900">+{{ score.factor_c }}</dd>
                                </div>
                                <div class="flex justify-between font-bold border-t border-slate-100 pt-2 text-teal-700">
                                    <dt>Suggestion Factor</dt>
                                    <dd>{{ sfTotal }}{{ score.category === 'tangible' ? '%' : ' pt' }}</dd>
                                </div>
                                <div v-if="score.category === 'tangible'" class="flex justify-between text-xs">
                                    <dt class="text-slate-400">Net Annual Savings</dt>
                                    <dd class="text-slate-600">{{ formatRupiah(score.cost_savings) }}</dd>
                                </div>
                                <div class="flex justify-between font-bold border-t border-slate-100 pt-2 text-emerald-700">
                                    <dt>Calculated Reward</dt>
                                    <dd>{{ formatRupiah(displayReward) }}</dd>
                                </div>
                                <div v-if="score.final_adjusted_reward" class="flex justify-between text-xs">
                                    <dt class="text-slate-500">Final Adjusted</dt>
                                    <dd class="font-semibold text-emerald-700">{{ formatRupiah(score.final_adjusted_reward) }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Timeline -->
                        <div class="bg-white rounded-2xl shadow-sm border border-teal-100 p-5">
                            <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-5">Timeline</h3>
                            <div class="relative pl-6">
                                <div class="absolute left-[7px] top-1 bottom-1 w-px bg-slate-200"></div>
                                <div class="space-y-5">
                                    <div v-for="log in idea.review_logs" :key="log.id" class="relative">
                                        <div class="absolute -left-6 top-1 h-3.5 w-3.5 rounded-full border-2 border-white ring-2 ring-slate-100 shrink-0"
                                            :class="timelineMeta(log.action).dot"></div>
                                        <div>
                                            <p class="text-sm font-semibold" :class="timelineMeta(log.action).text">
                                                {{ log.action === 'Resubmitted' ? 'Submission resubmit for check' : log.action }}
                                            </p>
                                            <div class="flex items-center gap-1 text-xs text-slate-500 mt-0.5">
                                                <span>{{ new Date(log.created_at).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' }) }}</span>
                                                <span>·</span>
                                                <span>{{ log.reviewer?.name || 'System' }}</span>
                                            </div>
                                            <p v-if="log.comments" class="text-xs text-slate-600 mt-1 italic break-words">"{{ log.comments }}"</p>
                                        </div>
                                    </div>
                                    <p v-if="!idea.review_logs?.length" class="text-sm text-slate-400 italic">
                                        Menunggu review SPS awal...
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
