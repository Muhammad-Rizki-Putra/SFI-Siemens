<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, User, Calendar, Tag, Activity, FileText } from 'lucide-vue-next';

// Catch the 'idea' prop sent from IdeaController@show
const props = defineProps({
    idea: {
        type: Object,
        required: true,
    },
});

// A simple helper to format the timestamp
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<template>
    <Head :title="idea.submission_code" />

    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-6">
                <Link :href="route('ideas.index')" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 transition mb-4">
                    <ArrowLeft class="w-4 h-4 mr-1" />
                    Back to Dashboard
                </Link>
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ idea.title }}</h1>
                        <p class="text-sm text-gray-500 mt-1">Submission Code: <span class="font-mono text-gray-700">{{ idea.submission_code }}</span></p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                            <Activity class="w-4 h-4 mr-1.5" />
                            {{ idea.status }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-lg shadow border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <FileText class="w-5 h-5 mr-2 text-blue-600" />
                            Detailed Description
                        </h2>
                        <div class="prose max-w-none text-gray-700 whitespace-pre-line">
                            {{ idea.description }}
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow border border-gray-100 p-6 opacity-75 border-dashed border-2">
                        <h2 class="text-lg font-semibold text-gray-500 mb-2">AI Evaluation Matrix</h2>
                        <p class="text-sm text-gray-400 italic">Scores and cost-saving calculations from the multi-agent system will appear here once processed.</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow border border-gray-100 p-6">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Submission Details</h3>
                        <ul class="space-y-4">
                            <li class="flex items-start">
                                <User class="w-5 h-5 text-gray-400 mr-3 mt-0.5" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ idea.user?.name || 'Unknown User' }}</p>
                                    <p class="text-xs text-gray-500">{{ idea.user?.department?.name || 'No Department Assigned' }}</p>
                                </div>
                            </li>
                            <li class="flex items-center">
                                <Tag class="w-5 h-5 text-gray-400 mr-3" />
                                <div>
                                    <p class="text-sm text-gray-900">{{ idea.type_of_improvement }}</p>
                                    <p class="text-xs text-gray-500">Category</p>
                                </div>
                            </li>
                            <li class="flex items-center">
                                <Calendar class="w-5 h-5 text-gray-400 mr-3" />
                                <div>
                                    <p class="text-sm text-gray-900">{{ formatDate(idea.created_at) }}</p>
                                    <p class="text-xs text-gray-500">Date Submitted</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white rounded-lg shadow border border-gray-100 p-6">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Approval History</h3>
                        
                        <div v-if="idea.review_logs && idea.review_logs.length > 0">
                            <div v-for="log in idea.review_logs" :key="log.id" class="mb-4 last:mb-0">
                                <p class="text-sm font-medium text-gray-900">{{ log.reviewer?.name }} <span class="text-xs text-gray-500 font-normal">({{ log.action }})</span></p>
                                <p class="text-xs text-gray-600 mt-1 italic">"{{ log.comments }}"</p>
                            </div>
                        </div>
                        <div v-else>
                            <p class="text-sm text-gray-500 italic">Waiting for initial SPS review...</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>