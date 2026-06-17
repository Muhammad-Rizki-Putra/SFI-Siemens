<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useToast } from '@/Composables/useToast';

const { show: showToast } = useToast();

const props = defineProps({
    idea: {
        type: Object,
        required: true,
    },
    employees: {
        type: Array,
        default: () => [],
    },
    currentUserName: {
        type: String,
        default: '',
    },
});

const existingTeamMembers = (props.idea.team_members || []).map((member) => member.name);
const existingAttachments = ref([...(props.idea.attachments || [])]);
const selectedAttachmentIds = ref([]);
if (props.currentUserName && !existingTeamMembers.includes(props.currentUserName)) {
    existingTeamMembers.push(props.currentUserName);
}

const form = useForm({
    title: props.idea.title || '',
    type_of_improvement: props.idea.type_of_improvement || '',
    problem_description: props.idea.problem_description || '',
    solution_description: props.idea.solution_description || '',
    area_of_application: props.idea.area_of_application || '',
    implementation_date: props.idea.implementation_date || '',
    team_members: existingTeamMembers,
    attachments: [],
});

const selectedEmployee = ref('');
const hasEmployees = computed(() => props.employees.length > 0);

const addTeamMember = () => {
    const name = selectedEmployee.value;
    if (!name) return;
    if (form.team_members.includes(name)) {
        selectedEmployee.value = '';
        return;
    }
    form.team_members.push(name);
    selectedEmployee.value = '';
};

const removeTeamMember = (index) => {
    const name = form.team_members[index];
    if (name === props.currentUserName) return;
    form.team_members.splice(index, 1);
};

watch(selectedEmployee, (name) => {
    if (!name) return;
    addTeamMember();
});

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            implementation_date: data.implementation_date || null,
        }))
        .post(route('ideas.update.post', props.idea.id), {
            forceFormData: true,
            onSuccess: () => {
                showToast('Changes saved successfully.', 'success');
            },
        });
};

const removeFile = (index) => {
    form.attachments.splice(index, 1);
};

const handleAttachments = (event) => {
    const newFiles = Array.from(event.target.files || []);
    const uniqueNewFiles = newFiles.filter(newFile => 
        !form.attachments.some(existingFile => 
            existingFile.name === newFile.name && existingFile.size === newFile.size
        )
    );
    form.attachments.push(...uniqueNewFiles);
    event.target.value = '';
};

const deleteAttachment = async (attachment, event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    if (!attachment?.id) return;
    if (!confirm('Remove this attachment? This cannot be undone.')) return;

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const response = await fetch(`/attachments/${attachment.id}/delete`,
    {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token,
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        showToast('Failed to delete the attachment.', 'error');
        return;
    }

    existingAttachments.value = existingAttachments.value.filter((item) => item.id !== attachment.id);
    selectedAttachmentIds.value = selectedAttachmentIds.value.filter((item) => item !== attachment.id);
    showToast('Attachment deleted.', 'success');
};

const deleteSelectedAttachments = async () => {
    if (!selectedAttachmentIds.value.length) return;
    if (!confirm('Remove selected attachments? This cannot be undone.')) return;

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const idsToDelete = [...selectedAttachmentIds.value];
    let hadError = false;

    for (const id of idsToDelete) {
        const response = await fetch(`/attachments/${id}/delete`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            showToast('Failed to delete one or more attachments.', 'error');
            hadError = true;
            break;
        }

        existingAttachments.value = existingAttachments.value.filter((item) => item.id !== id);
    }

    selectedAttachmentIds.value = [];
    if (!hadError) {
        showToast('Selected attachments deleted.', 'success');
    }
};
</script>

<template>
    <Head title="Edit Idea" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-blue-50 py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-slate-900">Edit Shop Floor Idea</h1>
                <Link :href="route('ideas.show', idea.id)" class="text-sm text-teal-700 hover:text-teal-900 transition">
                    &larr; Back to Idea
                </Link>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6 border border-teal-100">
                <form @submit.prevent="submit">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Idea Title</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="block w-full rounded-md border-teal-100 bg-teal-50 shadow-sm focus:border-teal-600 focus:ring-teal-600 sm:text-sm"
                            required
                        >
                        <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                        <select
                            v-model="form.type_of_improvement"
                            class="block w-full rounded-md border-teal-100 bg-teal-50 shadow-sm focus:border-teal-600 focus:ring-teal-600 sm:text-sm"
                            required
                        >
                            <option value="" disabled>Select improvement type...</option>
                            <option value="Quality">Quality</option>
                            <option value="Cost">Cost</option>
                            <option value="Delivery">Delivery</option>
                            <option value="Safety">Safety</option>
                            <option value="Morale">Morale</option>
                        </select>
                        <div v-if="form.errors.type_of_improvement" class="text-red-500 text-xs mt-1">{{ form.errors.type_of_improvement }}</div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Problem Description</label>
                        <textarea
                            v-model="form.problem_description"
                            rows="5"
                            class="block w-full rounded-md border-teal-100 bg-teal-50 shadow-sm focus:border-teal-600 focus:ring-teal-600 sm:text-sm"
                            required
                        ></textarea>
                        <div v-if="form.errors.problem_description" class="text-red-500 text-xs mt-1">{{ form.errors.problem_description }}</div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Solution Description</label>
                        <textarea
                            v-model="form.solution_description"
                            rows="5"
                            class="block w-full rounded-md border-teal-100 bg-teal-50 shadow-sm focus:border-teal-600 focus:ring-teal-600 sm:text-sm"
                            required
                        ></textarea>
                        <div v-if="form.errors.solution_description" class="text-red-500 text-xs mt-1">{{ form.errors.solution_description }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Area of Implementation</label>
                            <select
                                v-model="form.area_of_application"
                                class="block w-full rounded-md border-teal-100 bg-teal-50 shadow-sm focus:border-teal-600 focus:ring-teal-600 sm:text-sm"
                                required
                            >
                                <option value="" disabled>Select area...</option>
                                <option value="Assembly">Assembly</option>
                                <option value="Packaging">Packaging</option>
                                <option value="Warehouse">Warehouse</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Quality">Quality</option>
                                <option value="Office">Office</option>
                            </select>
                            <div v-if="form.errors.area_of_application" class="text-red-500 text-xs mt-1">{{ form.errors.area_of_application }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Date of Implementation (Optional)</label>
                            <input
                                v-model="form.implementation_date"
                                type="date"
                                class="block w-full rounded-md border-teal-100 bg-teal-50 shadow-sm focus:border-teal-600 focus:ring-teal-600 sm:text-sm"
                            />
                            <div v-if="form.errors.implementation_date" class="text-red-500 text-xs mt-1">{{ form.errors.implementation_date }}</div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Team Members</label>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <select
                                v-model="selectedEmployee"
                                class="block w-full rounded-md border-teal-100 bg-teal-50 shadow-sm focus:border-teal-600 focus:ring-teal-600 sm:text-sm"
                                :disabled="!hasEmployees"
                            >
                                <option value="" disabled>
                                    {{ hasEmployees ? 'Select employee...' : 'No employees available' }}
                                </option>
                                <option v-for="employee in employees" :key="employee.id" :value="employee.name">
                                    {{ employee.name }}
                                </option>
                            </select>
                            <button
                                type="button"
                                @click="addTeamMember"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-semibold text-white bg-teal-700 hover:bg-teal-600"
                            >
                                Add
                            </button>
                        </div>
                        <div v-if="form.errors.team_members" class="text-red-500 text-xs mt-1">{{ form.errors.team_members }}</div>
                        <div class="mt-3 space-y-2" v-if="form.team_members.length">
                            <div v-for="(member, index) in form.team_members" :key="`${member}-${index}`" class="flex items-center justify-between rounded-md border border-teal-100 bg-white px-3 py-2">
                                <span class="text-sm text-slate-700">
                                    {{ member }}
                                    <span v-if="member === currentUserName" class="text-xs text-slate-500">(You)</span>
                                </span>
                                <button
                                    type="button"
                                    @click="removeTeamMember(index)"
                                    class="text-xs font-semibold text-rose-600 hover:text-rose-700 disabled:opacity-50"
                                    :disabled="member === currentUserName"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Attachments</label>

                        <div v-if="existingAttachments.length" class="mb-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <p class="text-xs text-slate-500">Existing files:</p>
                                <button
                                    v-if="selectedAttachmentIds.length"
                                    type="button"
                                    @click="deleteSelectedAttachments"
                                    class="text-xs font-semibold rounded-md px-3 py-1 bg-rose-100 text-rose-700"
                                >
                                    Delete Selected
                                </button>
                            </div>
                            <div
                                v-for="attachment in existingAttachments"
                                :key="attachment.id"
                                class="flex items-center justify-between rounded-md border border-teal-100 bg-white px-3 py-2"
                            >
                                <div class="min-w-0 flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        v-model="selectedAttachmentIds"
                                        :value="attachment.id"
                                        class="h-4 w-4 rounded border-teal-200 text-teal-700 focus:ring-teal-600"
                                    />
                                    <div>
                                        <p class="text-sm text-slate-700 truncate">{{ attachment.original_name }}</p>
                                        <p class="text-xs text-slate-500">{{ (attachment.size_bytes / 1024 / 1024).toFixed(2) }} MB</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="deleteAttachment(attachment, $event)"
                                    class="text-xs font-semibold rounded-md px-3 py-1 bg-rose-100 text-rose-700"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>

                        <label class="block text-sm font-medium text-slate-700 mb-1">Add New Attachments</label>
                        <input
                            type="file"
                            multiple
                            @change="handleAttachments"
                            class="block w-full rounded-md border-teal-100 bg-teal-50 shadow-sm focus:border-teal-600 focus:ring-teal-600 sm:text-sm"
                            accept=".jpg,.jpeg,.png,.webp,.pdf,.docx,.xlsx,.pptx,.mp4,.mov"
                        />
                        <p class="text-xs text-slate-500 mt-2">
                            New uploads will be added to existing attachments.
                        </p>
                        <div v-if="form.errors.attachments" class="text-red-500 text-xs mt-1">{{ form.errors.attachments }}</div>
                        <div v-if="form.errors['attachments.*']" class="text-red-500 text-xs mt-1">{{ form.errors['attachments.*'] }}</div>
                        <div v-if="form.attachments.length" class="mt-3 space-y-2">
                            <div v-for="(file, index) in form.attachments" :key="index" class="flex items-center justify-between rounded-md border border-teal-100 bg-white px-3 py-2">
                                <div class="min-w-0">
                                    <p class="text-sm text-slate-700 truncate mr-2">{{ file.name }}</p>
                                    <p class="text-xs text-slate-500 whitespace-nowrap">{{ (file.size / 1024 / 1024).toFixed(2) }} MB</p>
                                </div>
                                <button 
                                    type="button"
                                    @click="removeFile(index)"
                                    class="text-xs font-semibold text-rose-600 hover:text-rose-700"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-teal-100">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-md hover:bg-teal-600 transition disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving changes...' : 'Update Idea' }}
                        </button>
                    </div>

                </form>
            </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
