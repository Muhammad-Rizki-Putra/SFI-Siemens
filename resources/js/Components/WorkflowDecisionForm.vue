<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    idea: {
        type: Object,
        required: true,
    },
    // 'sps' or 'manager'
    type: {
        type: String,
        default: 'manager',
    }
});
const { show: showToast } = useToast();

const isFinalStatus = computed(() => ['Implemented', 'Closed', 'Rejected'].includes(props.idea.status));

const workflowForm = useForm({
    action: 'Approved',
    reject_mode: 'revise',
    comments: '',
    technical_pic: props.idea.technical_pic || '',
    managerial_decision_summary: props.idea.managerial_decision_summary || '',
    reward_request_type: props.idea.reward_request_type || '',
    reward_request_amount: props.idea.reward_request_amount || '',
    reward_request_notes: props.idea.reward_request_notes || '',
    reward_processing_status: props.idea.reward_processing_status || '',
    reward_processed_at: props.idea.reward_processed_at || '',
    opw_type: props.idea.opw_type || '',
    opw_notes: props.idea.opw_notes || '',
});

const submitWorkflow = () => {
    const routeName = props.type === 'sps' ? 'admin.ideas.review' : 'admin.ideas.workflow';
    
    workflowForm.post(route(routeName, props.idea.id), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Workflow decision saved successfully.', 'success');
            router.reload({ only: ['idea'] });
        },
    });
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-5">Workflow Decision</h3>
        <form @submit.prevent="submitWorkflow" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Decision</label>
                    <select v-model="workflowForm.action" class="w-full rounded-lg border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500">
                        <option value="Approved">Approve (Proceed)</option>
                        <option value="Rejected">Reject / Revise</option>
                        <option v-if="props.type === 'manager'" value="Draft">Move to Draft</option>
                        <option v-if="props.type === 'sps'" value="Implemented">Mark as Implemented</option>
                    </select>
                </div>
                <div v-if="workflowForm.action === 'Rejected'">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Reject Mode</label>
                    <select v-model="workflowForm.reject_mode" class="w-full rounded-lg border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500">
                        <option value="revise">Return for Revision</option>
                        <option value="closed">Reject &amp; Close</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Note / Comments</label>
                <textarea v-model="workflowForm.comments" rows="3" class="w-full rounded-lg border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500" placeholder="Masukkan catatan..."></textarea>
            </div>
            
            <div v-if="props.type === 'manager'">
                <label class="block text-xs font-medium text-slate-600 mb-1">PIC Technical</label>
                <input v-model="workflowForm.technical_pic" type="text" placeholder="Assigned technical PIC" class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-700 placeholder:text-slate-400 focus:border-teal-500 focus:ring-teal-500" />
            </div>
            
            <div v-if="props.type === 'manager'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Reward Change Request</label>
                    <select v-model="workflowForm.reward_request_type" class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500">
                        <option value="">No change</option>
                        <option value="voucher_to_cash">Voucher to Cash</option>
                        <option value="cash_to_voucher">Cash to Voucher</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Requested Amount</label>
                    <input v-model.number="workflowForm.reward_request_amount" type="number" min="0" placeholder="1,000.00" class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-700 placeholder:text-slate-400 focus:border-teal-500 focus:ring-teal-500" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Reward Request Notes</label>
                    <textarea v-model="workflowForm.reward_request_notes" rows="2" placeholder="Reason for reward change" class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-700 placeholder:text-slate-400 focus:border-teal-500 focus:ring-teal-500"></textarea>
                </div>
            </div>
            
            <div class="flex justify-end pt-2">
                <button
                    type="submit"
                    :disabled="workflowForm.processing || isFinalStatus"
                    class="inline-flex items-center px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-teal-700 hover:bg-teal-600 disabled:opacity-50 transition shadow-sm"
                >
                    Save Decision
                </button>
            </div>
        </form>
    </div>
</template>
