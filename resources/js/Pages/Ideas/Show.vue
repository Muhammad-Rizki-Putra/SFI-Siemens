<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ArrowLeft, User, Calendar, Tag, Activity, FileText, Calculator, CheckCircle, Lightbulb, Paperclip } from 'lucide-vue-next';
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
const intangibleAwardLookup = (points) => {
    if (points < 10)  return 0;
    if (points >= 60) return 40_000_000;
    const anchors = [
        [10, 300_000],   [15, 1_000_000],  [20, 2_500_000],
        [25, 5_000_000], [30, 8_000_000],  [35, 12_500_000],
        [40, 17_500_000],[45, 23_000_000], [50, 28_500_000],
        [55, 34_000_000],[60, 40_000_000],
    ];
    for (let i = 0; i < anchors.length - 1; i++) {
        const [p1, r1] = anchors[i];
        const [p2, r2] = anchors[i + 1];
        if (points >= p1 && points < p2) {
            const t = (points - p1) / (p2 - p1);
            return Math.round(r1 + t * (r2 - r1));
        }
    }
    return 40_000_000;
};
const displayReward = computed(() => {
    if (!score.value.category) return 0;
    if (score.value.category === 'intangible') return intangibleAwardLookup(sfTotal.value);
    return Number(score.value.cost_savings || 0) * (Number(score.value.reward_percent || 0) / 100);
});

// ─── SPS Phase: controls which admin panel is shown ───────────────────────
// Phase 'workflow'   → SPS has not yet approved (no SPS Review log)
// Phase 'evaluation' → SPS approved workflow, evaluation matrix not yet saved
// Phase 'done'       → scoring saved, idea forwarded to Technical Review
const spsWorkflowApproved = computed(() =>
    Array.isArray(props.idea.review_logs) &&
    props.idea.review_logs.some((l) => l.action === 'SPS Review')
);
const spsPhase = computed(() => {
    if (!spsWorkflowApproved.value) return 'workflow';
    if (score.value.category) return 'done';       // score saved → forwarded
    return 'evaluation';                            // score not yet filled
});
const step1Tab = ref('details');
const step2Tab = ref('matrix');
const doneTab  = ref('summary');

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

// ─── Attachment helpers ────────────────────────────────────────────────────
const attachmentExt = (path) => (path ? path.split('.').pop()?.toLowerCase() : '');
const isImage = (a) => {
    const mime = a?.mime_type || '';
    const ext = attachmentExt(a?.storage_path);
    return mime.startsWith('image/') || ['jpg', 'jpeg', 'png', 'webp'].includes(ext);
};
const isVideo = (a) => {
    const mime = a?.mime_type || '';
    const ext = attachmentExt(a?.storage_path);
    return mime.startsWith('video/') || ['mp4', 'mov'].includes(ext);
};
const isPdf = (a) => {
    const mime = a?.mime_type || '';
    const ext = attachmentExt(a?.storage_path);
    return mime === 'application/pdf' || ext === 'pdf';
};

const previewUrls = reactive({});
const selectedAttachmentId = ref(null);
const selectedAttachment = computed(() =>
    props.idea?.attachments?.find((a) => a.id === selectedAttachmentId.value) || null
);

const loadPreview = async (attachment) => {
    if (!attachment?.id || previewUrls[attachment.id]) return;
    if (isPdf(attachment)) return;
    try {
        const response = await fetch(route('attachments.show', attachment.id));
        if (!response.ok) return;
        const blob = await response.blob();
        const reader = new FileReader();
        reader.onload = () => { previewUrls[attachment.id] = reader.result; };
        reader.readAsDataURL(blob);
    } catch (_) {}
};

const previewUrl = (a) => previewUrls[a.id] || '';
const attachmentPreviewUrl = (a) => route('attachments.preview', a.id);

onMounted(() => {
    if (!props.idea?.attachments?.length) return;
    selectedAttachmentId.value = props.idea.attachments[0].id;
    props.idea.attachments.forEach((a) => loadPreview(a));
});
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
                        <div class="min-w-0">
                            <h1 class="text-2xl font-bold text-slate-900 truncate">{{ idea.title }}</h1>
                            <p class="text-sm text-slate-500 mt-1">
                                Code: <span class="font-mono font-medium text-slate-700">{{ idea.submission_code }}</span>
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 shrink-0">
                            <Link
                                v-if="!adminActions && ['Revision Requested', 'Draft'].includes(idea.status)"
                                :href="route('ideas.edit', idea.id)"
                                class="inline-flex items-center px-4 py-1.5 bg-amber-500 text-white text-sm font-semibold rounded-lg hover:bg-amber-600 transition shadow-sm"
                            >
                                Edit Submission
                            </Link>
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
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- ── Left Column (2/3) ──────────────────────────── -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- ════ ADMIN SPS: Phase-based panels ════════════════════ -->
                        <div v-if="adminActions" class="space-y-4">

                            <!-- Phase badge -->
                            <div class="flex items-center gap-2">
                                <span v-if="spsPhase === 'workflow'" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                                    <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                                    Step 1 &mdash; Review &amp; Decision
                                </span>
                                <span v-else-if="spsPhase === 'evaluation'" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                                    Step 2 &mdash; Evaluation Matrix
                                </span>
                                <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Selesai &mdash; Diteruskan ke Technical Review
                                </span>
                            </div>

                            <!-- Outer phase transition -->
                            <transition mode="out-in"
                                enter-active-class="transition duration-200 ease-out"
                                enter-from-class="opacity-0 translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition duration-150 ease-in"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 translate-y-1"
                            >
                                <!-- ── STEP 1: Tabbed (Submission Details | Workflow Decision) ── -->
                                <div v-if="spsPhase === 'workflow'" key="sps-step1" class="space-y-4">
                                    <!-- Tab bar -->
                                    <div class="flex border-b border-slate-200">
                                        <button @click="step1Tab = 'details'"
                                            :class="['py-2.5 px-5 text-sm font-medium border-b-2 transition-colors', step1Tab === 'details' ? 'border-teal-500 text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300']">
                                            Submission Details
                                        </button>
                                        <button @click="step1Tab = 'workflow'"
                                            :class="['py-2.5 px-5 text-sm font-medium border-b-2 transition-colors', step1Tab === 'workflow' ? 'border-teal-500 text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300']">
                                            Workflow Decision
                                        </button>
                                    </div>

                                    <!-- Inner tab transition -->
                                    <transition mode="out-in"
                                        enter-active-class="transition duration-150 ease-out"
                                        enter-from-class="opacity-0 translate-y-1"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition duration-100 ease-in"
                                        leave-from-class="opacity-100"
                                        leave-to-class="opacity-0"
                                    >
                                        <!-- Submission Details -->
                                        <div v-if="step1Tab === 'details'" key="s1-details" class="space-y-4">
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
                                            <!-- Attachments -->
                                            <div class="bg-white rounded-2xl shadow-sm border border-teal-100 p-6">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                                                    <h2 class="text-sm font-semibold text-slate-900 uppercase tracking-wider flex items-center">
                                                        <Paperclip class="w-4 h-4 mr-2 text-teal-600" />
                                                        Attachments
                                                    </h2>
                                                    <div v-if="idea.attachments && idea.attachments.length" class="w-full sm:w-64">
                                                        <select v-model="selectedAttachmentId" class="w-full rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 px-3 py-1.5 focus:border-teal-500 focus:ring-teal-500">
                                                            <option v-for="att in idea.attachments" :key="att.id" :value="att.id">{{ att.original_name }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div v-if="selectedAttachment">
                                                    <div class="flex items-center gap-3 mb-3">
                                                        <div class="min-w-0">
                                                            <p class="text-sm font-medium text-slate-700 truncate">{{ selectedAttachment.original_name }}</p>
                                                            <p class="text-xs text-slate-500">{{ (selectedAttachment.size_bytes / 1024 / 1024).toFixed(2) }} MB
                                                                <span v-if="selectedAttachment.is_compressed" class="ml-1.5 text-teal-600 font-medium">Compressed</span>
                                                            </p>
                                                        </div>
                                                        <a :href="route('attachments.show', selectedAttachment.id)" target="_blank"
                                                           class="ml-auto shrink-0 text-xs font-medium text-teal-700 hover:text-teal-900 underline underline-offset-2">Open / Download</a>
                                                    </div>
                                                    <div class="rounded-lg overflow-hidden border border-slate-100">
                                                        <img v-if="isImage(selectedAttachment)" :src="previewUrl(selectedAttachment)" :alt="selectedAttachment.original_name" class="w-full max-h-72 object-contain bg-slate-50" loading="lazy" />
                                                        <video v-else-if="isVideo(selectedAttachment)" :src="previewUrl(selectedAttachment)" controls class="w-full max-h-72 bg-slate-900" />
                                                        <iframe v-else-if="isPdf(selectedAttachment)" :src="attachmentPreviewUrl(selectedAttachment)" class="w-full h-72" />
                                                        <div v-else class="p-6 text-center text-xs text-slate-400 italic">Preview unavailable for this file type.</div>
                                                    </div>
                                                </div>
                                                <p v-else class="text-sm text-slate-400 italic">No attachments uploaded.</p>
                                            </div>
                                        </div>

                                        <!-- Workflow Decision -->
                                        <div v-else key="s1-workflow">
                                            <WorkflowDecisionForm :idea="idea" type="sps" />
                                        </div>
                                    </transition>
                                </div>

                                <!-- ── STEP 2: Evaluation Matrix ── -->
                                <div v-else-if="spsPhase === 'evaluation'" key="sps-step2" class="space-y-4">
                                    <!-- Tab bar -->
                                    <div class="flex border-b border-slate-200">
                                        <button @click="step2Tab = 'details'"
                                            :class="['py-2.5 px-5 text-sm font-medium border-b-2 transition-colors', step2Tab === 'details' ? 'border-teal-500 text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300']">
                                            Submission Details
                                        </button>
                                        <button @click="step2Tab = 'matrix'"
                                            :class="['py-2.5 px-5 text-sm font-medium border-b-2 transition-colors', step2Tab === 'matrix' ? 'border-teal-500 text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300']">
                                            Evaluation Matrix
                                        </button>
                                    </div>
                                    <transition mode="out-in"
                                        enter-active-class="transition duration-150 ease-out"
                                        enter-from-class="opacity-0 translate-y-1"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition duration-100 ease-in"
                                        leave-from-class="opacity-100"
                                        leave-to-class="opacity-0">
                                        <div v-if="step2Tab === 'details'" key="s2-details" class="space-y-4">
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
                                            <div class="bg-white rounded-2xl shadow-sm border border-teal-100 p-6">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                                                    <h2 class="text-sm font-semibold text-slate-900 uppercase tracking-wider flex items-center">
                                                        <Paperclip class="w-4 h-4 mr-2 text-teal-600" />
                                                        Attachments
                                                    </h2>
                                                    <div v-if="idea.attachments && idea.attachments.length" class="w-full sm:w-64">
                                                        <select v-model="selectedAttachmentId" class="w-full rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 px-3 py-1.5 focus:border-teal-500 focus:ring-teal-500">
                                                            <option v-for="att in idea.attachments" :key="att.id" :value="att.id">{{ att.original_name }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div v-if="selectedAttachment">
                                                    <div class="flex items-center gap-3 mb-3">
                                                        <div class="min-w-0">
                                                            <p class="text-sm font-medium text-slate-700 truncate">{{ selectedAttachment.original_name }}</p>
                                                            <p class="text-xs text-slate-500">{{ (selectedAttachment.size_bytes / 1024 / 1024).toFixed(2) }} MB
                                                                <span v-if="selectedAttachment.is_compressed" class="ml-1.5 text-teal-600 font-medium">Compressed</span>
                                                            </p>
                                                        </div>
                                                        <a :href="route('attachments.show', selectedAttachment.id)" target="_blank"
                                                           class="ml-auto shrink-0 text-xs font-medium text-teal-700 hover:text-teal-900 underline underline-offset-2">Open / Download</a>
                                                    </div>
                                                    <div class="rounded-lg overflow-hidden border border-slate-100">
                                                        <img v-if="isImage(selectedAttachment)" :src="previewUrl(selectedAttachment)" :alt="selectedAttachment.original_name" class="w-full max-h-72 object-contain bg-slate-50" loading="lazy" />
                                                        <video v-else-if="isVideo(selectedAttachment)" :src="previewUrl(selectedAttachment)" controls class="w-full max-h-72 bg-slate-900" />
                                                        <iframe v-else-if="isPdf(selectedAttachment)" :src="attachmentPreviewUrl(selectedAttachment)" class="w-full h-72" />
                                                        <div v-else class="p-6 text-center text-xs text-slate-400 italic">Preview unavailable for this file type.</div>
                                                    </div>
                                                </div>
                                                <p v-else class="text-sm text-slate-400 italic">No attachments uploaded.</p>
                                            </div>
                                        </div>
                                        <div v-else key="s2-matrix">
                                            <EvaluationMatrixForm :idea="idea" />
                                        </div>
                                    </transition>
                                </div>

                                <!-- ── DONE: Forwarded to Technical Review ── -->
                                <div v-else key="sps-done" class="space-y-4">
                                    <!-- Tab bar -->
                                    <div class="flex border-b border-slate-200">
                                        <button @click="doneTab = 'summary'"
                                            :class="['py-2.5 px-5 text-sm font-medium border-b-2 transition-colors', doneTab === 'summary' ? 'border-teal-500 text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300']">
                                            Result Summary
                                        </button>
                                        <button @click="doneTab = 'details'"
                                            :class="['py-2.5 px-5 text-sm font-medium border-b-2 transition-colors', doneTab === 'details' ? 'border-teal-500 text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300']">
                                            Submission Details
                                        </button>
                                    </div>
                                    <transition mode="out-in"
                                        enter-active-class="transition duration-150 ease-out"
                                        enter-from-class="opacity-0 translate-y-1"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition duration-100 ease-in"
                                        leave-from-class="opacity-100"
                                        leave-to-class="opacity-0">
                                        <div v-if="doneTab === 'summary'" key="done-summary">
                                            <div class="bg-white rounded-2xl shadow-sm border border-emerald-200 p-8 text-center">
                                                <div class="mx-auto w-14 h-14 rounded-full bg-emerald-100 flex items-center justify-center mb-4">
                                                    <CheckCircle class="w-7 h-7 text-emerald-600" />
                                                </div>
                                                <h3 class="text-base font-bold text-slate-800 mb-1">Scoring Tersimpan</h3>
                                                <p class="text-sm text-slate-500 mb-5">
                                                    Ide ini telah diteruskan ke <span class="font-semibold text-teal-700">Technical Review</span>.
                                                </p>
                                                <div class="inline-flex flex-col items-start gap-2 rounded-xl bg-slate-50 border border-slate-200 px-5 py-4 text-left text-sm text-slate-700">
                                                    <div class="flex gap-3">
                                                        <span class="text-slate-400 w-28 shrink-0">Kategori</span>
                                                        <span class="font-semibold capitalize">{{ score.category }}</span>
                                                    </div>
                                                    <div class="flex gap-3">
                                                        <span class="text-slate-400 w-28 shrink-0">Suggestion Factor</span>
                                                        <span class="font-semibold text-teal-700">{{ sfTotal }}{{ score.category === 'tangible' ? '%' : ' pt' }}</span>
                                                    </div>
                                                    <div class="flex gap-3">
                                                        <span class="text-slate-400 w-28 shrink-0">Est. Reward</span>
                                                        <span class="font-semibold text-emerald-700">{{ formatRupiah(displayReward) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else key="done-details" class="space-y-4">
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
                                            <div class="bg-white rounded-2xl shadow-sm border border-teal-100 p-6">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                                                    <h2 class="text-sm font-semibold text-slate-900 uppercase tracking-wider flex items-center">
                                                        <Paperclip class="w-4 h-4 mr-2 text-teal-600" />
                                                        Attachments
                                                    </h2>
                                                    <div v-if="idea.attachments && idea.attachments.length" class="w-full sm:w-64">
                                                        <select v-model="selectedAttachmentId" class="w-full rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 px-3 py-1.5 focus:border-teal-500 focus:ring-teal-500">
                                                            <option v-for="att in idea.attachments" :key="att.id" :value="att.id">{{ att.original_name }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div v-if="selectedAttachment">
                                                    <div class="flex items-center gap-3 mb-3">
                                                        <div class="min-w-0">
                                                            <p class="text-sm font-medium text-slate-700 truncate">{{ selectedAttachment.original_name }}</p>
                                                            <p class="text-xs text-slate-500">{{ (selectedAttachment.size_bytes / 1024 / 1024).toFixed(2) }} MB
                                                                <span v-if="selectedAttachment.is_compressed" class="ml-1.5 text-teal-600 font-medium">Compressed</span>
                                                            </p>
                                                        </div>
                                                        <a :href="route('attachments.show', selectedAttachment.id)" target="_blank"
                                                           class="ml-auto shrink-0 text-xs font-medium text-teal-700 hover:text-teal-900 underline underline-offset-2">Open / Download</a>
                                                    </div>
                                                    <div class="rounded-lg overflow-hidden border border-slate-100">
                                                        <img v-if="isImage(selectedAttachment)" :src="previewUrl(selectedAttachment)" :alt="selectedAttachment.original_name" class="w-full max-h-72 object-contain bg-slate-50" loading="lazy" />
                                                        <video v-else-if="isVideo(selectedAttachment)" :src="previewUrl(selectedAttachment)" controls class="w-full max-h-72 bg-slate-900" />
                                                        <iframe v-else-if="isPdf(selectedAttachment)" :src="attachmentPreviewUrl(selectedAttachment)" class="w-full h-72" />
                                                        <div v-else class="p-6 text-center text-xs text-slate-400 italic">Preview unavailable for this file type.</div>
                                                    </div>
                                                </div>
                                                <p v-else class="text-sm text-slate-400 italic">No attachments uploaded.</p>
                                            </div>
                                        </div>
                                    </transition>
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

                        <!-- ════ USER: Attachments ════════════════════════ -->
                        <div v-if="!adminActions" class="bg-white rounded-2xl shadow-sm border border-teal-100 p-6">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                                <h2 class="text-sm font-semibold text-slate-900 uppercase tracking-wider flex items-center">
                                    <Paperclip class="w-4 h-4 mr-2 text-teal-600" />
                                    Attachments
                                </h2>
                                <div v-if="idea.attachments && idea.attachments.length" class="w-full sm:w-64">
                                    <label class="sr-only" for="attachment-select-user">Select attachment</label>
                                    <select
                                        id="attachment-select-user"
                                        v-model="selectedAttachmentId"
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 px-3 py-1.5 focus:border-teal-500 focus:ring-teal-500"
                                    >
                                        <option v-for="att in idea.attachments" :key="att.id" :value="att.id">
                                            {{ att.original_name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div v-if="selectedAttachment">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-700 truncate">{{ selectedAttachment.original_name }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ (selectedAttachment.size_bytes / 1024 / 1024).toFixed(2) }} MB
                                            <span v-if="selectedAttachment.is_compressed" class="ml-1.5 text-teal-600 font-medium">Compressed</span>
                                        </p>
                                    </div>
                                    <a :href="route('attachments.show', selectedAttachment.id)" target="_blank"
                                       class="ml-auto shrink-0 text-xs font-medium text-teal-700 hover:text-teal-900 underline underline-offset-2">
                                        Open / Download
                                    </a>
                                </div>
                                <div class="rounded-lg overflow-hidden border border-slate-100">
                                    <img
                                        v-if="isImage(selectedAttachment)"
                                        :src="previewUrl(selectedAttachment)"
                                        :alt="selectedAttachment.original_name"
                                        class="w-full max-h-72 object-contain bg-slate-50"
                                        loading="lazy"
                                    />
                                    <video
                                        v-else-if="isVideo(selectedAttachment)"
                                        :src="previewUrl(selectedAttachment)"
                                        controls
                                        class="w-full max-h-72 bg-slate-900"
                                    />
                                    <iframe
                                        v-else-if="isPdf(selectedAttachment)"
                                        :src="attachmentPreviewUrl(selectedAttachment)"
                                        class="w-full h-72"
                                    />
                                    <div v-else class="p-6 text-center text-xs text-slate-400 italic">
                                        Preview unavailable for this file type.
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-sm text-slate-400 italic">No attachments uploaded.</p>
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
                                <div class="flex items-start gap-3" v-if="idea.team_members && idea.team_members.length">
                                    <User class="w-4 h-4 text-teal-600 mt-0.5 shrink-0" />
                                    <div>
                                        <dt class="text-xs text-slate-500 mb-1">Team Members</dt>
                                        <dd>
                                            <ul class="list-disc pl-4 text-sm text-slate-900 space-y-0.5">
                                                <li v-for="member in idea.team_members" :key="member.id">{{ member.name }}</li>
                                            </ul>
                                        </dd>
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
                            <div class="relative">
                                <div class="flex flex-col">
                                    <div v-for="(log, index) in idea.review_logs" :key="log.id" class="relative pb-5 last:pb-0 flex items-stretch gap-3">
                                        <div class="relative flex flex-col items-center pt-1 w-3.5">
                                            <div class="h-3.5 w-3.5 rounded-full border-2 border-white ring-2 ring-slate-100 shrink-0 z-10"
                                                :class="timelineMeta(log.action).dot"></div>
                                            <div v-if="index !== idea.review_logs.length - 1" class="w-px bg-slate-200 flex-1 mt-0.5 -mb-5"></div>
                                        </div>
                                        <div class="min-w-0 flex-1">
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
