<script setup>
import { computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Calculator, CheckCircle, Lightbulb } from 'lucide-vue-next';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    idea: {
        type: Object,
        required: true,
    },
});

const { show: showToast } = useToast();

const formatRupiah = (v) => 'Rp\u202f' + Number(v || 0).toLocaleString('id-ID');

// ─── Intangible Award Lookup Table ───────────────────────────────────────────
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

const existing = props.idea.score || {};

const scoreForm = useForm({
    category:              existing.category             || 'tangible',
    factor_a1:             existing.factor_a1            ?? 1,
    factor_a2:             existing.factor_a2            ?? 2,
    factor_a3:             existing.factor_a3            ?? 1,
    factor_b:              existing.factor_b             ?? 1,
    implementation_factor: existing.implementation_factor ?? 0,
    factor_c:              existing.factor_c             ?? 0,
    cost_savings:          existing.cost_savings         ?? 0,
    reward_percent:        existing.reward_percent       ?? 0,
    voucher_reward:        existing.voucher_reward       ?? 0,
    appraisal:             existing.appraisal            || '',
    suggestion_factor:     existing.suggestion_factor    ?? 0,
    final_adjusted_reward: existing.final_adjusted_reward || '',
    remark:                existing.remark               || '',
});

const a1Options = [
    { value: 1, label: 'Own area — Area sendiri' },
    { value: 4, label: 'Allied area — Area terkait/berdekatan' },
    { value: 5, label: 'Unconnected area — Area tidak terhubung' },
];
const a2Options = [
    { value: 2, label: 'Hanya ide, detail dikerjakan pihak lain' },
    { value: 4, label: 'Bisa diimplementasi, butuh technical clearance' },
    { value: 5, label: 'Semua detail jelas, bisa langsung diimplementasi' },
];
const a3Options = [
    { value: 1, label: 'Hanya untuk tempat kerja sendiri' },
    { value: 4, label: 'Bisa diterapkan di tempat kerja berbeda' },
    { value: 5, label: 'Bisa diterapkan di berbagai unit/pabrik/kantor' },
];
const bOptions = [
    { value: 1,   label: 'Level 3–8  (Pengali ×1)' },
    { value: 1.5, label: 'Staff / SG (Pengali ×1.5)' },
    { value: 2,   label: 'Employee / EG (Pengali ×2)' },
];

const liveATotal = computed(() =>
    Number(scoreForm.factor_a1) + Number(scoreForm.factor_a2) + Number(scoreForm.factor_a3)
);
const liveSuggestionFactor = computed(() => {
    const base = liveATotal.value * Number(scoreForm.factor_b) + Number(scoreForm.implementation_factor);
    return scoreForm.category === 'intangible' ? base + Number(scoreForm.factor_c) : base;
});
const liveReward = computed(() => {
    if (scoreForm.category === 'intangible') return intangibleAwardLookup(liveSuggestionFactor.value);
    return Number(scoreForm.cost_savings) * (liveSuggestionFactor.value / 100);
});

const submitScore = () => {
    scoreForm.suggestion_factor = liveSuggestionFactor.value;
    scoreForm.reward_percent    = scoreForm.category === 'tangible' ? liveSuggestionFactor.value : 0;
    scoreForm.post(route('admin.ideas.score', props.idea.id), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Scoring berhasil disimpan.', 'success');
            router.reload({ only: ['idea'] });
        },
    });
};
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border-2 border-teal-400 p-6">
        <h2 class="text-sm font-semibold text-teal-800 uppercase tracking-wider flex items-center mb-5">
            <Calculator class="w-4 h-4 mr-2 text-teal-600" />
            SFI Evaluation Matrix
        </h2>

        <form @submit.prevent="submitScore" class="space-y-6">

            <!-- Kategori -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                    Kategori Savings
                </label>
                <div class="flex gap-6">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="radio" v-model="scoreForm.category" value="tangible"
                            class="text-teal-600 focus:ring-teal-500" />
                        <span class="text-sm font-medium text-slate-700 group-hover:text-teal-700">
                            📊 Tangible Savings
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="radio" v-model="scoreForm.category" value="intangible"
                            class="text-teal-600 focus:ring-teal-500" />
                        <span class="text-sm font-medium text-slate-700 group-hover:text-teal-700">
                            💡 Intangible Savings
                        </span>
                    </label>
                </div>
            </div>

            <!-- Faktor A -->
            <div class="border-t border-slate-100 pt-5">
                <div class="flex items-center gap-2 mb-4">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-teal-100 text-teal-700 text-xs font-bold">A</span>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-widest">Kualitas &amp; Cakupan Ide</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-teal-700 mb-1.5">
                            A1 — Area of Application
                        </label>
                        <select v-model.number="scoreForm.factor_a1"
                            class="w-full rounded-lg border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500">
                            <option v-for="o in a1Options" :key="o.value" :value="o.value">
                                {{ o.label }} — {{ o.value }} pt
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-teal-700 mb-1.5">
                            A2 — Feasibility
                        </label>
                        <select v-model.number="scoreForm.factor_a2"
                            class="w-full rounded-lg border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500">
                            <option v-for="o in a2Options" :key="o.value" :value="o.value">
                                {{ o.label }} — {{ o.value }} pt
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-teal-700 mb-1.5">
                            A3 — Adaptability
                        </label>
                        <select v-model.number="scoreForm.factor_a3"
                            class="w-full rounded-lg border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500">
                            <option v-for="o in a3Options" :key="o.value" :value="o.value">
                                {{ o.label }} — {{ o.value }} pt
                            </option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 inline-flex items-center gap-2 rounded-lg bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-700 border border-teal-200">
                    A Total = {{ liveATotal }} poin
                </div>
            </div>

            <!-- Faktor B & Implementation -->
            <div class="border-t border-slate-100 pt-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold">B</span>
                            <label class="text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                Sphere of Employment
                            </label>
                        </div>
                        <select v-model.number="scoreForm.factor_b"
                            class="w-full rounded-lg border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500">
                            <option v-for="o in bOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                            Implementation Factor (0–5)
                        </label>
                        <input v-model.number="scoreForm.implementation_factor"
                            type="number" min="0" max="5" step="1"
                            class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500"
                            placeholder="0" />
                        <p class="mt-1 text-xs text-slate-400">
                            Poin tambahan jika submitter terlibat langsung &amp; menunjukkan inisiatif tinggi dalam implementasi
                        </p>
                    </div>
                </div>
            </div>

            <!-- Faktor C (intangible only) -->
            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                <div v-if="scoreForm.category === 'intangible'" class="border-t border-slate-100 pt-5">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">C</span>
                        <label class="text-xs font-semibold text-slate-600 uppercase tracking-wider">
                            Kualitas Umum Usulan — Khusus Intangible
                        </label>
                    </div>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <span class="text-xs bg-slate-100 text-slate-600 rounded-full px-3 py-1 border border-slate-200">Fair: 1–7 pt</span>
                        <span class="text-xs bg-slate-100 text-slate-600 rounded-full px-3 py-1 border border-slate-200">Good: 8–15 pt</span>
                        <span class="text-xs bg-slate-100 text-slate-600 rounded-full px-3 py-1 border border-slate-200">Excellent: 16–25 pt</span>
                    </div>
                    <input v-model.number="scoreForm.factor_c"
                        type="number" min="0" max="25" step="1"
                        class="w-full md:w-48 rounded-lg border-slate-200 bg-white text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500"
                        placeholder="1–25" />
                </div>
            </transition>

            <!-- Tangible-specific: Net Annual Savings -->
            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                <div v-if="scoreForm.category === 'tangible'" class="border-t border-slate-100 pt-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                                Net Annual Savings (Rp)
                            </label>
                            <input v-model.number="scoreForm.cost_savings"
                                type="number" min="0" step="1000"
                                class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500"
                                placeholder="0" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                                Suggestion Factor (%) — Auto
                            </label>
                            <div class="flex items-center gap-2">
                                <input :value="liveSuggestionFactor" type="number" readonly
                                    class="w-full rounded-lg border-slate-200 bg-slate-100 text-sm text-slate-500 cursor-not-allowed" />
                                <span class="text-sm text-slate-500 shrink-0">%</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-400">
                                = (A1+A2+A3) × B + Impl. Factor
                            </p>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Live Preview Box -->
            <div class="rounded-2xl bg-gradient-to-r from-teal-50 to-cyan-50 border border-teal-200 p-5">
                <div class="text-xs font-bold text-teal-700 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <Lightbulb class="w-3.5 h-3.5" />
                    Preview Kalkulasi Real-Time
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center mb-4">
                    <div class="bg-white/60 rounded-xl px-3 py-3 border border-teal-100">
                        <div class="text-xs text-slate-500 mb-1">A Total</div>
                        <div class="text-2xl font-bold text-slate-800">{{ liveATotal }}</div>
                        <div class="text-xs text-slate-400">poin</div>
                    </div>
                    <div class="bg-white/60 rounded-xl px-3 py-3 border border-teal-100">
                        <div class="text-xs text-slate-500 mb-1">Faktor B</div>
                        <div class="text-2xl font-bold text-slate-800">×{{ scoreForm.factor_b }}</div>
                    </div>
                    <div class="bg-white/60 rounded-xl px-3 py-3 border border-teal-100">
                        <div class="text-xs text-slate-500 mb-1">
                            {{ scoreForm.category === 'tangible' ? 'Suggestion Factor' : 'Total Poin' }}
                        </div>
                        <div class="text-2xl font-bold text-teal-700">{{ liveSuggestionFactor }}</div>
                        <div class="text-xs text-slate-400">{{ scoreForm.category === 'tangible' ? '%' : 'pt' }}</div>
                    </div>
                    <div class="bg-white/60 rounded-xl px-3 py-3 border border-teal-100 col-span-2 md:col-span-1">
                        <div class="text-xs text-slate-500 mb-1">Estimated Reward</div>
                        <div class="text-lg font-bold text-emerald-700">{{ formatRupiah(liveReward) }}</div>
                    </div>
                </div>
                <div class="text-xs font-mono text-slate-500 bg-white/60 rounded-lg px-3 py-2 border border-teal-100">
                    <span v-if="scoreForm.category === 'tangible'">
                        SF% = ({{ +scoreForm.factor_a1 }}+{{ +scoreForm.factor_a2 }}+{{ +scoreForm.factor_a3 }}) × {{ +scoreForm.factor_b }} + {{ +scoreForm.implementation_factor }} = <strong>{{ liveSuggestionFactor }}%</strong>
                        &nbsp;|&nbsp; Reward = {{ formatRupiah(scoreForm.cost_savings) }} × {{ liveSuggestionFactor }}% = <strong>{{ formatRupiah(liveReward) }}</strong>
                    </span>
                    <span v-else>
                        SF = ({{ +scoreForm.factor_a1 }}+{{ +scoreForm.factor_a2 }}+{{ +scoreForm.factor_a3 }}) × {{ +scoreForm.factor_b }} + {{ +scoreForm.implementation_factor }} + {{ +scoreForm.factor_c }} = <strong>{{ liveSuggestionFactor }} pt</strong>
                        &nbsp;→&nbsp; Reward Tabel = <strong>{{ formatRupiah(liveReward) }}</strong>
                    </span>
                </div>
            </div>

            <!-- Additional Fields -->
            <div class="border-t border-slate-100 pt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                        Final Adjusted Reward (Rp)
                    </label>
                    <input v-model.number="scoreForm.final_adjusted_reward"
                        type="number" min="0"
                        class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500"
                        placeholder="Jika ada penyesuaian manual" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                        Appraisal
                    </label>
                    <input v-model="scoreForm.appraisal" type="text"
                        class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500"
                        placeholder="Catatan appraisal" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                        Remark
                    </label>
                    <textarea v-model="scoreForm.remark" rows="2"
                        class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500"
                        placeholder="Catatan tambahan" />
                </div>
            </div>

            <div class="flex justify-end pt-1">
                <button type="submit" :disabled="scoreForm.processing"
                    class="inline-flex items-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-teal-700 hover:bg-teal-600 disabled:opacity-50 transition shadow-sm">
                    <CheckCircle class="w-4 h-4 mr-2" />
                    Simpan Scoring
                </button>
            </div>
        </form>
    </div>
</template>
