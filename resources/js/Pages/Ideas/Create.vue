<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useToast } from '@/Composables/useToast';

const { show: showToast } = useToast();

const props = defineProps({
    employees: {
        type: Array,
        default: () => [],
    },
    currentUserName: {
        type: String,
        default: '',
    },
});

// Inertia's form helper maps exactly to the fields in your IdeaController store method
const form = useForm({
    title: '',
    type_of_improvement: '',
    problem_description: '',
    solution_description: '',
    area_of_application: '',
    implementation_date: '',
    team_members: props.currentUserName ? [props.currentUserName] : [],
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

watch(selectedEmployee, (name) => {
    if (!name) return;
    addTeamMember();
});

const removeTeamMember = (index) => {
    const name = form.team_members[index];
    if (name === props.currentUserName) return;
    form.team_members.splice(index, 1);
};

const removeFile = (index) => {
    form.attachments.splice(index, 1);
};

// Submit handler
const submit = () => {
    form
        .transform((data) => ({
            ...data,
            implementation_date: data.implementation_date || null,
        }))
        .post(route('ideas.store'), {
            forceFormData: true,
            onSuccess: () => {
                showToast('Submission saved successfully.', 'success');
            },
        });
};

const handleAttachments = (event) => {
    const newFiles = Array.from(event.target.files || []);
    // Filter out duplicates (same name and size)
    const uniqueNewFiles = newFiles.filter(newFile => 
        !form.attachments.some(existingFile => 
            existingFile.name === newFile.name && existingFile.size === newFile.size
        )
    );
    form.attachments.push(...uniqueNewFiles);
    // Reset the input so the same file can be selected again if removed
    event.target.value = '';
};

</script>

<template>
    <Head title="Submit New Idea" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-blue-50 py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-slate-900">Submit Shop Floor Idea</h1>
                <Link :href="route('ideas.index')" class="text-sm text-teal-700 hover:text-teal-900 transition">
                    &larr; Back to Dashboard
                </Link>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6 border border-teal-100">
                <form @submit.prevent="submit">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Idea Title <span class="text-rose-600">*</span></label>
                        <input 
                            v-model="form.title" 
                            type="text" 
                            class="block w-full rounded-md border-teal-100 bg-teal-50 shadow-sm focus:border-teal-600 focus:ring-teal-600 sm:text-sm" 
                            placeholder="e.g., Automated inventory tracking for Line A"
                            required
                        >
                        <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Category <span class="text-rose-600">*</span></label>
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
                        <label class="block text-sm font-medium text-slate-700 mb-1">Problem Description <span class="text-rose-600">*</span></label>
                        <textarea 
                            v-model="form.problem_description" 
                            rows="5" 
                            class="block w-full rounded-md border-teal-100 bg-teal-50 shadow-sm focus:border-teal-600 focus:ring-teal-600 sm:text-sm" 
                            placeholder="Explain the current problem..."
                            required
                        ></textarea>
                        <div v-if="form.errors.problem_description" class="text-red-500 text-xs mt-1">{{ form.errors.problem_description }}</div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Solution Description <span class="text-rose-600">*</span></label>
                        <textarea 
                            v-model="form.solution_description" 
                            rows="5" 
                            class="block w-full rounded-md border-teal-100 bg-teal-50 shadow-sm focus:border-teal-600 focus:ring-teal-600 sm:text-sm" 
                            placeholder="Describe your proposed solution..."
                            required
                        ></textarea>
                        <div v-if="form.errors.solution_description" class="text-red-500 text-xs mt-1">{{ form.errors.solution_description }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Area of Implementation <span class="text-rose-600">*</span></label>
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
                        <label class="block text-sm font-medium text-slate-700 mb-2">Team Members <span class="text-rose-600">*</span></label>
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
                        <p class="mt-2 text-xs text-slate-500">
                            Selecting a name adds it automatically. Use Remove to delete.
                        </p>
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
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-slate-700">Attachments</label>
                            <button 
                                v-if="form.attachments.length" 
                                type="button" 
                                @click="form.attachments = []" 
                                class="text-xs text-rose-600 hover:underline"
                            >
                                Remove All
                            </button>
                        </div>
                        <input
                            type="file"
                            multiple
                            @change="handleAttachments"
                            class="block w-full rounded-md border-teal-100 bg-teal-50 shadow-sm focus:border-teal-600 focus:ring-teal-600 sm:text-sm"
                            accept=".jpg,.jpeg,.png,.webp,.pdf,.docx,.xlsx,.pptx,.mp4,.mov"
                        />
                        <p class="text-xs text-slate-500 mt-2">
                            Images up to 5MB, documents up to 10MB, videos up to 50MB.
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
                            {{ form.processing ? 'Saving to Database...' : 'Submit Idea' }}
                        </button>
                    </div>

                </form>
            </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>