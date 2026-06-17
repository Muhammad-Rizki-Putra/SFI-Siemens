<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Inertia's form helper maps exactly to the fields in your IdeaController store method
const form = useForm({
    title: '',
    type_of_improvement: '',
    description: '',
});

// Submit handler
const submit = () => {
    form.post(route('ideas.store'));
};

</script>

<template>
    <Head title="Submit New Idea" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">Submit Shop Floor Idea</h1>
                <Link :href="route('ideas.index')" class="text-sm text-gray-500 hover:text-gray-700 transition">
                    &larr; Back to Dashboard
                </Link>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border border-gray-100">
                <form @submit.prevent="submit">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Idea Title</label>
                        <input 
                            v-model="form.title" 
                            type="text" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" 
                            placeholder="e.g., Automated inventory tracking for Line A"
                            required
                        >
                        <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select 
                            v-model="form.type_of_improvement" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" 
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Detailed Description</label>
                        <textarea 
                            v-model="form.description" 
                            rows="5" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" 
                            placeholder="Explain the current problem and your proposed solution..."
                            required
                        ></textarea>
                        <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition disabled:opacity-50"
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