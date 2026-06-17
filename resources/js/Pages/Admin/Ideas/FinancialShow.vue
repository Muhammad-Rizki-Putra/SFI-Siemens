<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EvaluationMatrixForm from '@/Components/EvaluationMatrixForm.vue';
import WorkflowDecisionForm from '@/Components/WorkflowDecisionForm.vue';
import { ArrowLeft, User, Calendar, Tag, Activity, FileText } from 'lucide-vue-next';
import { useToast } from '@/Composables/useToast';

const { show: showToast } = useToast();

const props = defineProps({
    idea: {
        type: Object,
        required: true,
    },
    backUrl: {
        type: String,
        default: '',
    },
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const attachmentExt = (path) => (path ? path.split('.').pop()?.toLowerCase() : '');
const isImage = (attachment) => {
    const mime = attachment?.mime_type || '';
    const ext = attachmentExt(attachment?.storage_path);
    return mime.startsWith('image/') || ['jpg', 'jpeg', 'png', 'webp'].includes(ext);
};
const isVideo = (attachment) => {
    const mime = attachment?.mime_type || '';
    const ext = attachmentExt(attachment?.storage_path);
    return mime.startsWith('video/') || ['mp4', 'mov'].includes(ext);
};
const isPdf = (attachment) => {
    const mime = attachment?.mime_type || '';
    const ext = attachmentExt(attachment?.storage_path);
    return mime === 'application/pdf' || ext === 'pdf';
};

const previewUrls = reactive({});
const attachmentPreviewUrl = (attachment) => route('attachments.preview', attachment.id);
const selectedAttachmentId = ref(null);
const selectedAttachment = computed(() =>
    props.idea?.attachments?.find((attachment) => attachment.id === selectedAttachmentId.value) || null
);

const loadPreview = async (attachment) => {
    if (!attachment?.id || previewUrls[attachment.id]) return;
    if (isPdf(attachment)) return;

    try {
        const response = await fetch(route('attachments.show', attachment.id));
        if (!response.ok) return;
        const blob = await response.blob();

        const reader = new FileReader();
        reader.onload = () => {
            previewUrls[attachment.id] = reader.result;
        };
        reader.readAsDataURL(blob);
    } catch (error) {
        // If preview fails, keep empty and show a fallback message.
    }
};

const previewUrl = (attachment) => previewUrls[attachment.id] || '';

onMounted(() => {
    if (!props.idea?.attachments) return;
    if (props.idea.attachments.length && !selectedAttachmentId.value) {
        selectedAttachmentId.value = props.idea.attachments[0].id;
    }
    props.idea.attachments.forEach((attachment) => loadPreview(attachment));
});

const activeTab = ref('workflow');

const score = computed(() => props.idea.score || {});
const aTotal = computed(() =>
    Number(score.value.factor_a1 || 0) + Number(score.value.factor_a2 || 0) + Number(score.value.factor_a3 || 0),
);

// Intangible award lookup — piecewise linear (KB: 10→300K, 60→40M)
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

const totalPoints = computed(() => {
    const base = aTotal.value * Number(score.value.factor_b || 0) + Number(score.value.implementation_factor || 0);
    return score.value.category === 'intangible'
        ? base + Number(score.value.factor_c || 0)
        : base;
});
const calculatedReward = computed(() => {
    if (score.value.category === 'intangible') {
        return intangibleAwardLookup(totalPoints.value);
    }
    const cost = Number(score.value.cost_savings || 0);
    const pct  = Number(score.value.reward_percent || 0) > 0
        ? Number(score.value.reward_percent || 0)
        : totalPoints.value;
    return cost * (pct / 100);
});

const formatRupiah = (v) => 'Rp ' + Number(v || 0).toLocaleString('id-ID');

const statusPalette = {
    'Submitted': { dot: 'bg-slate-500', border: 'border-slate-300', text: 'text-slate-700' },
    'SPS Review': { dot: 'bg-teal-500', border: 'border-teal-300', text: 'text-teal-700' },
    'Technical Review': { dot: 'bg-cyan-500', border: 'border-cyan-300', text: 'text-cyan-700' },
    'Managerial Review': { dot: 'bg-indigo-500', border: 'border-indigo-300', text: 'text-indigo-700' },
    'Reward Processing': { dot: 'bg-blue-500', border: 'border-blue-300', text: 'text-blue-700' },
    'Implemented': { dot: 'bg-emerald-600', border: 'border-emerald-300', text: 'text-emerald-700' },
    'Revision Requested': { dot: 'bg-amber-500', border: 'border-amber-300', text: 'text-amber-700' },
    'Rejected': { dot: 'bg-rose-600', border: 'border-rose-300', text: 'text-rose-700' },
    'Closed': { dot: 'bg-rose-700', border: 'border-rose-300', text: 'text-rose-700' },
    'Draft': { dot: 'bg-slate-400', border: 'border-slate-300', text: 'text-slate-700' },
    'Resubmitted': { dot: 'bg-teal-500', border: 'border-teal-300', text: 'text-teal-700' },
    'Revision Viewed': { dot: 'bg-amber-400', border: 'border-amber-200', text: 'text-amber-600' },
};

const timelineEntries = computed(() => {
    const logs = Array.isArray(props.idea.review_logs)
        ? [...props.idea.review_logs]
        : [];

    if (!logs.length) {
        logs.push({
            id: 'submitted',
            action: props.idea.status || 'Submitted',
            comments: 'Initial submission.',
            reviewer: { name: 'System' },
            created_at: props.idea.created_at,
        });
    }

    const status = props.idea.status || 'Submitted';
    // Treat 'Resubmitted' as equivalent to 'SPS Review' for the current status log check
    const hasStatusLog = logs.some((log) => 
        log.action === status || (status === 'SPS Review' && log.action === 'Resubmitted')
    );

    if (status && !hasStatusLog) {
        logs.push({
            id: `status-${status}`,
            action: status,
            comments: status === 'SPS Review'
                ? 'Submission updated after revision request.'
                : 'Current status updated.',
            reviewer: { name: 'System' },
            created_at: props.idea.updated_at || props.idea.created_at,
        });
    }

    // Stable sort: by date desc, then by ID desc
    logs.sort((a, b) => {
        const dateA = new Date(a.created_at).getTime();
        const dateB = new Date(b.created_at).getTime();
        if (dateB !== dateA) {
            return dateB - dateA;
        }
        // Tie-breaker for identical timestamps
        const idA = typeof a.id === 'number' ? a.id : 0;
        const idB = typeof b.id === 'number' ? b.id : 0;
        return idB - idA;
    });

    return logs;
});

const timelineMeta = (status) => statusPalette[status] || statusPalette.Submitted;
</script>

<template>
    <Head :title="idea.submission_code" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-blue-50 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <Link :href="backUrl || route('admin.ideas.index')" class="inline-flex items-center text-sm font-medium text-teal-700 hover:text-teal-900 transition mb-4">
                        <ArrowLeft class="w-4 h-4 mr-1" />
                        Back to Dashboard
                    </Link>
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold text-slate-900">{{ idea.title }}</h1>
                            <p class="text-sm text-slate-500 mt-1">Submission Code: <span class="font-mono text-slate-700">{{ idea.submission_code }}</span></p>
                        </div>
                        <div class="mt-4 md:mt-0 flex flex-col items-start gap-3 md:items-end">
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold border"
                                :class="{
                                    'bg-slate-100 text-slate-700 border-slate-200': idea.status === 'Draft',
                                    'bg-amber-100 text-amber-700 border-amber-200': idea.status === 'Revision Requested',
                                    'bg-teal-100 text-teal-800 border-teal-300': ['SPS Review', 'Technical Review', 'Managerial Review'].includes(idea.status),
                                    'bg-blue-100 text-blue-700 border-blue-200': idea.status === 'Reward Processing',
                                    'bg-teal-200 text-teal-900 border-teal-300': idea.status === 'Implemented',
                                    'bg-rose-100 text-rose-700 border-rose-200': ['Rejected', 'Closed'].includes(idea.status)
                                }"
                            >
                                <Activity class="w-4 h-4 mr-1.5" />
                                {{ idea.status }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="space-y-4">
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
                                    <WorkflowDecisionForm :idea="idea" type="manager" />
                                </div>
                                
                                <div v-else-if="activeTab === 'evaluation'" :key="'evaluation'">
                                    <EvaluationMatrixForm :idea="idea" />
                                </div>
                                
                                <div v-else-if="activeTab === 'details'" :key="'details'" class="space-y-6">
                                    <div class="bg-white rounded-2xl shadow-sm border border-teal-100 p-6">
                                        <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4">Technical Summary</h3>
                                        <div class="space-y-3 text-sm text-slate-700">
                                            <div>
                                                <div class="text-xs font-semibold text-teal-700 uppercase tracking-wider">Problem Description</div>
                                                <div class="mt-1 whitespace-pre-line">{{ idea.problem_description || 'N/A' }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs font-semibold text-teal-700 uppercase tracking-wider">Solution Description</div>
                                                <div class="mt-1 whitespace-pre-line">{{ idea.solution_description || 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-white rounded-2xl shadow-sm border border-teal-100 p-6">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wider">Attachments</h3>
                                            <div v-if="idea.attachments && idea.attachments.length" class="w-full sm:w-64">
                                                <label class="sr-only" for="attachment-select">Select attachment</label>
                                                <select
                                                    id="attachment-select"
                                                    v-model="selectedAttachmentId"
                                                    class="w-full rounded-md border-teal-100 bg-teal-50 text-sm text-slate-700 focus:border-teal-600 focus:ring-teal-600"
                                                >
                                                    <option
                                                        v-for="attachment in idea.attachments"
                                                        :key="attachment.id"
                                                        :value="attachment.id"
                                                    >
                                                        {{ attachment.original_name }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>

                                        <div v-if="selectedAttachment" class="mt-4">
                                            <div class="flex items-center justify-between gap-4">
                                                <div class="min-w-0">
                                                    <p class="text-sm text-slate-700 truncate">{{ selectedAttachment.original_name }}</p>
                                                    <p class="text-xs text-slate-500">
                                                        {{ (selectedAttachment.size_bytes / 1024 / 1024).toFixed(2) }} MB
                                                        <span v-if="selectedAttachment.is_compressed" class="ml-2 text-teal-600">Compressed</span>
                                                    </p>
                                                </div>
                                            </div>

                                            <div v-if="isImage(selectedAttachment)" class="mt-3">
                                                <img
                                                    :src="previewUrl(selectedAttachment)"
                                                    :alt="selectedAttachment.original_name"
                                                    class="w-full max-h-64 object-contain rounded-md border border-teal-100"
                                                    loading="lazy"
                                                />
                                            </div>
                                            <div v-else-if="isVideo(selectedAttachment)" class="mt-3">
                                                <video
                                                    :src="previewUrl(selectedAttachment)"
                                                    controls
                                                    class="w-full max-h-64 rounded-md border border-teal-100 bg-slate-900"
                                                />
                                            </div>
                                            <div v-else-if="isPdf(selectedAttachment)" class="mt-3">
                                                <iframe
                                                    :src="attachmentPreviewUrl(selectedAttachment)"
                                                    class="w-full h-64 rounded-md border border-teal-100"
                                                />
                                            </div>
                                            <div v-else class="mt-3 text-xs text-slate-500 italic">
                                                Preview unavailable for this file type.
                                            </div>
                                        </div>
                                        <p v-else class="mt-4 text-sm text-slate-500 italic">No attachments uploaded.</p>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white rounded-2xl shadow-sm border border-teal-100 p-6">
                            <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4">Submission Details</h3>
                            <ul class="space-y-4">
                                <li class="flex items-start">
                                    <User class="w-5 h-5 text-teal-700 mr-3 mt-0.5" />
                                    <div>
                                        <p class="text-sm font-medium text-slate-900">{{ idea.user?.name || 'Unknown User' }}</p>
                                        <p class="text-xs text-slate-500">{{ idea.user?.department?.name || 'No Department Assigned' }}</p>
                                    </div>
                                </li>
                                <li class="flex items-center">
                                    <Tag class="w-5 h-5 text-teal-700 mr-3" />
                                    <div>
                                        <p class="text-sm text-slate-900">{{ idea.type_of_improvement }}</p>
                                        <p class="text-xs text-slate-500">Category</p>
                                    </div>
                                </li>
                                <li class="flex items-start" v-if="idea.team_members && idea.team_members.length">
                                    <User class="w-5 h-5 text-teal-700 mr-3 mt-0.5" />
                                    <div>
                                        <ul class="list-disc pl-4 text-sm text-slate-900 space-y-0.5">
                                            <li v-for="member in idea.team_members" :key="member.id">{{ member.name }}</li>
                                        </ul>
                                        <p class="text-xs text-slate-500 mt-1">Team Members</p>
                                    </div>
                                </li>
                                <li class="flex items-center">
                                    <Calendar class="w-5 h-5 text-teal-700 mr-3" />
                                    <div>
                                        <p class="text-sm text-slate-900">{{ formatDate(idea.created_at) }}</p>
                                        <p class="text-xs text-slate-500">Date Submitted</p>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div class="bg-white rounded-2xl shadow-sm border border-teal-100 p-6">
                            <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4">Timeline</h3>
                            <div class="relative">
                                <div class="absolute left-16 top-2 bottom-2 w-px bg-teal-100"></div>
                                <div class="space-y-6">
                                    <div
                                        v-for="log in timelineEntries"
                                        :key="log.id"
                                        class="grid grid-cols-[4rem,1.5rem,1fr] gap-3 items-start"
                                    >
                                        <div class="text-xs font-semibold text-slate-500 text-center">
                                            <div>{{ new Date(log.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short' }) }}</div>
                                            <div class="text-lg text-slate-700">{{ new Date(log.created_at).getDate() }}</div>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <div
                                                class="h-6 w-6 rounded-full border-4 bg-white"
                                                :class="[timelineMeta(log.action).border, timelineMeta(log.action).dot]"
                                            ></div>
                                        </div>
                                        <div
                                            class="rounded-2xl border border-dashed bg-white px-4 py-3 shadow-sm"
                                            :class="timelineMeta(log.action).border"
                                        >
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="text-sm font-semibold" :class="timelineMeta(log.action).text">
                                                    {{ log.action === 'Resubmitted' ? 'submission resubmit for check' : log.action }}
                                                </p>
                                                <span class="text-[11px] text-slate-500">
                                                    {{ new Date(log.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-slate-600 mt-1">
                                                {{ log.reviewer?.name || 'System' }}
                                            </p>
                                            <p v-if="log.comments" class="text-xs text-slate-600 mt-2 italic break-words overflow-hidden">
                                                "{{ log.comments }}"
                                            </p>
                                            <p v-else class="text-xs text-slate-400 mt-2 italic">
                                                No comments.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
