<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    patient: Object,
    anamneses: Array,
    evolutions: Array,
    prescriptions: Array,
    files: Array,
});

const activeSubTab = ref('all');
const filterDate = ref('');

const matchDate = (createdAtString) => {
    if (!filterDate.value) return true;
    const recordDate = new Date(createdAtString);
    const localYear = recordDate.getFullYear();
    const localMonth = String(recordDate.getMonth() + 1).padStart(2, '0');
    const localDay = String(recordDate.getDate()).padStart(2, '0');
    return `${localYear}-${localMonth}-${localDay}` === filterDate.value;
};

const filteredAnamneses = computed(() => (props.anamneses || []).filter(item => matchDate(item.created_at)));
const filteredEvolutions = computed(() => (props.evolutions || []).filter(item => matchDate(item.created_at)));
const filteredPrescriptions = computed(() => (props.prescriptions || []).filter(item => matchDate(item.created_at)));
const filteredFiles = computed(() => (props.files || []).filter(item => matchDate(item.created_at)));

const totalFilteredItems = computed(() => {
    let count = 0;
    if (activeSubTab.value === 'all' || activeSubTab.value === 'anamnese') count += filteredAnamneses.value.length;
    if (activeSubTab.value === 'all' || activeSubTab.value === 'evolution') count += filteredEvolutions.value.length;
    if (activeSubTab.value === 'all' || activeSubTab.value === 'prescription') count += filteredPrescriptions.value.length;
    if (activeSubTab.value === 'all' || activeSubTab.value === 'file') count += filteredFiles.value.length;
    return count;
});

const clearDateFilter = () => filterDate.value = '';

const age = computed(() => {
    if (!props.patient.date_of_birth) return 'Idade não informada';
    const today = new Date();
    const birthDate = new Date(props.patient.date_of_birth);
    let currentAge = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) currentAge--;
    return `${currentAge} anos`;
});

// ==========================================
// 📋 LÓGICA DE COPIAR SINTOMAS DA ANAMNESE
// ==========================================
const copiedAnamnesisId = ref(null);

const copySymptoms = (anamnese) => {
    if (!anamnese.symptoms_checklist || anamnese.symptoms_checklist.length === 0) {
        alert('Nenhum sintoma foi assinalado nesta anamnese para copiar.');
        return;
    }
    const textToCopy = anamnese.symptoms_checklist.join(', ');

    navigator.clipboard.writeText(textToCopy).then(() => {
        copiedAnamnesisId.value = anamnese.id;
        setTimeout(() => copiedAnamnesisId.value = null, 2000);
    }).catch(err => {
        console.error('Falha ao copiar texto: ', err);
        alert('O seu navegador bloqueou a cópia automática.');
    });
};

// ==========================================
// 🤖 ASSISTENTE DE IA GERAL
// ==========================================
const showingAiModal = ref(false);
const aiPrompt = ref('');
const aiResponse = ref('');
const isAnalyzing = ref(false);

const askAI = async () => {
    if (!aiPrompt.value.trim()) return;
    isAnalyzing.value = true;
    aiResponse.value = '';

    const context = `
[DADOS DO PACIENTE ATUAL]
Nome: ${props.patient.name}
Idade: ${age.value}
Gênero: ${props.patient.gender || 'Não informado'}

[INSTRUÇÃO DO MÉDICO]
${aiPrompt.value}
    `;

    try {
        const response = await axios.post(route('ai.analyze'), { prompt: context });
        aiResponse.value = response.data.response;
    } catch (error) {
        aiResponse.value = '⚠️ Ocorreu um erro ao comunicar com a IA.';
    } finally {
        isAnalyzing.value = false;
    }
};

const closeAiModal = () => { showingAiModal.value = false; setTimeout(() => { aiPrompt.value = ''; aiResponse.value = ''; }, 300); };

// ==========================================
// 📎 UPLOADS
// ==========================================
const showingUploadModal = ref(false);
const uploadForm = useForm({ patient_id: props.patient.id, name: '', file: null, notes: '' });
const handleFileUpload = (e) => uploadForm.file = e.target.files[0];
const submitUpload = () => {
    uploadForm.post(route('patient-files.store'), {
        preserveScroll: true,
        onSuccess: () => { showingUploadModal.value = false; uploadForm.reset('name', 'file', 'notes'); },
    });
};
const deleteFile = (id) => { if (confirm('Apagar arquivo?')) router.delete(route('patient-files.destroy', id), { preserveScroll: true }); };

// ==========================================
// 🔍 VISUALIZAÇÃO DE MODAIS
// ==========================================
const showingAnamnesisModal = ref(false);
const selectedAnamnesis = ref(null);
const openAnamnesisModal = (anamnese) => { selectedAnamnesis.value = anamnese; showingAnamnesisModal.value = true; };
const closeAnamnesisModal = () => { showingAnamnesisModal.value = false; setTimeout(() => selectedAnamnesis.value = null, 300); };

const showingPrescriptionModal = ref(false);
const selectedPrescription = ref(null);
const openPrescriptionModal = (prescription) => { selectedPrescription.value = prescription; showingPrescriptionModal.value = true; };
const closePrescriptionModal = () => { showingPrescriptionModal.value = false; setTimeout(() => selectedPrescription.value = null, 300); };
</script>

<template>
    <Head :title="`Prontuário - ${patient.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Prontuário Clínico: {{ patient.name }}</h2>
                <Link :href="route('patients.index')" class="text-gray-600 hover:underline text-sm font-medium">&larr; Voltar para lista</Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-1">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 sticky top-6 border border-gray-100">
                            <h3 class="text-lg font-bold text-gray-900 border-b pb-2 mb-4">Dados Fundamentais</h3>
                            <div class="space-y-3 text-sm">
                                <p><span class="font-semibold text-gray-600">Idade Calculada:</span> {{ age }}</p>
                                <p><span class="font-semibold text-gray-600">CPF:</span> {{ patient.cpf }}</p>
                                <p><span class="font-semibold text-gray-600">Telefone:</span> {{ patient.phone || 'Não informado' }}</p>
                            </div>
                            <div class="mt-6 pt-4 border-t">
                                <Link :href="route('patients.edit', patient.id)" class="text-indigo-600 hover:underline text-sm font-bold">Editar Ficha Cadastral</Link>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 space-y-6">
                        
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-wrap gap-3 border border-gray-100">
                            <Link :href="route('evolutions.create', { patient_id: patient.id })" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors shadow-sm">+ Nova Evolução</Link>
                            <Link :href="route('prescriptions.create', { patient_id: patient.id })" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors shadow-sm">+ Nova Receita</Link>
                            <Link :href="route('anamneses.create', { patient_id: patient.id })" class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-md font-medium text-sm transition-colors border border-gray-300 shadow-sm">Preencher Anamnese</Link>
                            <button @click="showingUploadModal = true" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors shadow-sm flex items-center gap-1.5">📎 Anexar Exame</button>
                            <button @click="showingAiModal = true" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white px-4 py-2 rounded-md font-bold text-sm transition-all shadow-md flex items-center gap-1.5 transform hover:scale-102">💡 IA: Analisar Prontuário</button>
                        </div>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-100">
                            <div class="border-b border-gray-200 mb-6 overflow-x-auto">
                                <nav class="-mb-px flex space-x-6 min-w-max" aria-label="Tabs">
                                    <button @click="activeSubTab = 'all'" :class="[activeSubTab === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm']">Ver Tudo</button>
                                    <button @click="activeSubTab = 'anamnese'" :class="[activeSubTab === 'anamnese' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm']">Anamneses ({{ filteredAnamneses.length }})</button>
                                    </nav>
                            </div>

                            <div class="space-y-6">
                                <div v-if="activeSubTab === 'all' || activeSubTab === 'anamnese'">
                                    <div v-for="anamnese in filteredAnamneses" :key="'anam-'+anamnese.id" class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm mb-4">
                                        <div class="flex justify-between items-center">
                                            <div class="flex flex-col flex-1 pr-4">
                                                <h4 class="font-bold text-indigo-700 flex items-center gap-2">
                                                    Anamnese Integrativa 
                                                    <span v-if="anamnese.type === 'child'" class="bg-pink-100 text-pink-700 text-[10px] uppercase px-2 py-0.5 rounded-full font-black tracking-wide">Infantil</span>
                                                </h4>
                                                <span class="text-sm text-gray-600 mt-1 line-clamp-1"><span class="font-semibold">Queixa:</span> {{ anamnese.chief_complaint || 'Não especificada' }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button @click="copySymptoms(anamnese)" class="px-3 py-1.5 text-xs font-extrabold transition-all shadow-sm rounded-md border flex items-center gap-1.5" :class="copiedAnamnesisId === anamnese.id ? 'bg-green-50 text-green-700 border-green-200' : 'bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-600 hover:text-white'">
                                                    <span v-if="copiedAnamnesisId === anamnese.id">✔️ Copiado!</span>
                                                    <span v-else>Copiar Sintomas</span>
                                                </button>
                                                <button @click="openAnamnesisModal(anamnese)" class="px-3 py-1.5 text-xs font-bold text-gray-700 bg-gray-100 border border-gray-200 rounded-md hover:bg-gray-200 transition-all">Visualizar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showingAnamnesisModal" @close="closeAnamnesisModal" maxWidth="3xl">
            <div class="p-6" v-if="selectedAnamnesis">
                <h2 class="text-lg font-bold text-gray-900 border-b pb-4 mb-4 flex justify-between items-center">
                    <span class="flex items-center gap-2">
                        Ficha Clínica Salva
                        <span v-if="selectedAnamnesis.type === 'child'" class="bg-pink-100 text-pink-700 text-[10px] uppercase px-2 py-0.5 rounded-full font-black tracking-wide">Infantil (PCA)</span>
                    </span>
                    <span class="text-xs bg-gray-100 px-3 py-1 rounded-full font-bold">Data: {{ new Date(selectedAnamnesis.created_at).toLocaleDateString('pt-BR') }}</span>
                </h2>
                
                <div class="space-y-4 overflow-y-auto max-h-[60vh] text-sm text-gray-800 pr-2">
                    
                    <div v-if="selectedAnamnesis.type === 'child' && selectedAnamnesis.child_data" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div v-if="selectedAnamnesis.child_data.parents_names" class="bg-pink-50 p-3 rounded border border-pink-100">
                            <span class="text-xs text-pink-700 font-black uppercase block mb-1">Pais / Responsáveis</span>
                            {{ selectedAnamnesis.child_data.parents_names }}
                        </div>
                        <div v-if="selectedAnamnesis.child_data.weight" class="bg-pink-50 p-3 rounded border border-pink-100">
                            <span class="text-xs text-pink-700 font-black uppercase block mb-1">Peso Referencial</span>
                            {{ selectedAnamnesis.child_data.weight }} kg
                        </div>
                        <div v-if="selectedAnamnesis.child_data.previous_diagnosis" class="md:col-span-2 bg-pink-50 p-3 rounded border border-pink-100">
                            <span class="text-xs text-pink-700 font-black uppercase block mb-1">Diagnóstico Prévio</span>
                            {{ selectedAnamnesis.child_data.previous_diagnosis }}
                        </div>
                    </div>

                    <div>
                        <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t text-xs uppercase tracking-wider text-gray-600">Queixa Principal</span>
                        <div class="p-3 border rounded-b bg-white whitespace-pre-wrap leading-relaxed">{{ selectedAnamnesis.chief_complaint || 'Não informada.' }}</div>
                    </div>

                    <div v-if="selectedAnamnesis.type === 'adult' && selectedAnamnesis.patient_routine">
                        <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t text-xs uppercase tracking-wider text-gray-600">Rotina & Estilo de Vida</span>
                        <div class="p-3 border rounded-b bg-white whitespace-pre-wrap leading-relaxed">{{ selectedAnamnesis.patient_routine }}</div>
                    </div>

                    <div v-if="selectedAnamnesis.family_history">
                        <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t text-xs uppercase tracking-wider text-gray-600">Histórico Familiar</span>
                        <div class="p-3 border rounded-b bg-white whitespace-pre-wrap leading-relaxed">{{ selectedAnamnesis.family_history }}</div>
                    </div>

                    <div v-if="selectedAnamnesis.type === 'child' && selectedAnamnesis.child_data">
                        <div v-if="selectedAnamnesis.child_data.diet_description" class="mt-4">
                            <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t text-xs uppercase tracking-wider text-gray-600">Hábito Alimentar Relatado</span>
                            <div class="p-3 border rounded-b bg-white whitespace-pre-wrap leading-relaxed">{{ selectedAnamnesis.child_data.diet_description }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div v-if="selectedAnamnesis.child_data.water_intake">
                                <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t text-xs uppercase tracking-wider text-gray-600">Ingestão de Água</span>
                                <div class="p-3 border rounded-b bg-white">{{ selectedAnamnesis.child_data.water_intake }} ml</div>
                            </div>
                            <div v-if="selectedAnamnesis.child_data.allergies">
                                <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t text-xs uppercase tracking-wider text-gray-600">Alergias / Intolerâncias</span>
                                <div class="p-3 border rounded-b bg-white">{{ selectedAnamnesis.child_data.allergies }}</div>
                            </div>
                        </div>
                        <div v-if="selectedAnamnesis.child_data.supplements" class="mt-4">
                            <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t text-xs uppercase tracking-wider text-gray-600">Suplementação Ativa</span>
                            <div class="p-3 border rounded-b bg-white whitespace-pre-wrap leading-relaxed">{{ selectedAnamnesis.child_data.supplements }}</div>
                        </div>
                        <div v-if="selectedAnamnesis.child_data.pain_complaint" class="mt-4">
                            <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t text-xs uppercase tracking-wider text-gray-600">Dores / Desconfortos</span>
                            <div class="p-3 border rounded-b bg-white whitespace-pre-wrap leading-relaxed">{{ selectedAnamnesis.child_data.pain_complaint }}</div>
                        </div>
                    </div>

                    <div v-if="selectedAnamnesis.symptoms_checklist && selectedAnamnesis.symptoms_checklist.length > 0">
                        <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t text-xs uppercase tracking-wider text-gray-600">Sintomas e Sinais Marcados</span>
                        <div class="p-3 border rounded-b bg-white flex flex-wrap gap-2">
                            <span v-for="(symp, idx) in selectedAnamnesis.symptoms_checklist" :key="idx" 
                                  :class="selectedAnamnesis.type === 'child' ? 'bg-pink-50 text-pink-700 border-pink-200' : 'bg-red-50 text-red-700 border-red-100'" 
                                  class="px-2.5 py-1 text-xs font-bold border rounded shadow-sm">
                                {{ symp }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                    <button @click="copySymptoms(selectedAnamnesis)" class="px-4 py-2 font-bold transition-all shadow-sm rounded-md border flex items-center gap-1.5" :class="copiedAnamnesisId === selectedAnamnesis.id ? 'bg-green-50 text-green-700 border-green-200' : 'bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-600 hover:text-white'">
                        <span v-if="copiedAnamnesisId === selectedAnamnesis.id">Copiado!</span>
                        <span v-else>Copiar Sintomas</span>
                    </button>
                    <SecondaryButton @click="closeAnamnesisModal">Fechar Ficha</SecondaryButton>
                </div>
            </div>
        </Modal>

        </AuthenticatedLayout>
</template>