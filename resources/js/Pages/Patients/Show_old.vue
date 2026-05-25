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

// Filtro de data considerando fuso horário local do utilizador
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

const clearDateFilter = () => {
    filterDate.value = '';
};

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
// 🤖 LÓGICA DO ASSISTENTE DE IA GERAL (CHAT)
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
Você é um assistente médico avançado operando no sistema Curae.
Atue como um colega de profissão auxiliando um médico. Seja direto, clínico e profissional.
Não invente dados. Baseie-se apenas nas informações fornecidas.

[DADOS DO PACIENTE ATUAL]
Nome: ${props.patient.name}
Idade: ${age.value}
Gênero: ${props.patient.gender || 'Não informado'}
Este paciente possui ${props.anamneses.length} anamneses e ${props.evolutions.length} evoluções no sistema.

[INSTRUÇÃO DO MÉDICO]
${aiPrompt.value}
    `;

    try {
        const response = await axios.post(route('ai.analyze'), { prompt: context });
        aiResponse.value = response.data.response;
    } catch (error) {
        aiResponse.value = '⚠️ Ocorreu um erro ao comunicar com a IA. Verifique se configurou a sua GEMINI_API_KEY no ficheiro .env.';
        console.error(error);
    } finally {
        isAnalyzing.value = false;
    }
};

const closeAiModal = () => {
    showingAiModal.value = false;
    setTimeout(() => {
        aiPrompt.value = '';
        aiResponse.value = '';
    }, 300);
};

// ==========================================
// ✨ LÓGICA DA GERAÇÃO DE PROTOCOLO IA (ANAMNESE)
// ==========================================
const showingProtocolModal = ref(false);
const generatedProtocol = ref('');
const isGeneratingProtocol = ref(false);

const createProtocol = async (anamnesisId) => {
    isGeneratingProtocol.value = true;
    generatedProtocol.value = '';
    showingProtocolModal.value = true;

    try {
        const response = await axios.post(route('ai.protocol'), { anamnesis_id: anamnesisId });
        generatedProtocol.value = response.data.protocol;
    } catch (error) {
        generatedProtocol.value = "⚠️ Erro ao gerar o protocolo clínico. Verifique os logs do servidor local ou a sua chave da API.";
        console.error(error);
    } finally {
        isGeneratingProtocol.value = false;
    }
};

// ==========================================
// 📎 GESTÃO E UPLOAD DE ARQUIVOS/EXAMES
// ==========================================
const showingUploadModal = ref(false);
const uploadForm = useForm({
    patient_id: props.patient.id,
    name: '',
    file: null,
    notes: '',
});

const handleFileUpload = (e) => {
    uploadForm.file = e.target.files[0];
};

const submitUpload = () => {
    uploadForm.post(route('patient-files.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showingUploadModal.value = false;
            uploadForm.reset('name', 'file', 'notes');
        },
    });
};

const deleteFile = (id) => {
    if (confirm('Tem certeza que deseja apagar este documento permanentemente do servidor?')) {
        router.delete(route('patient-files.destroy', id), { preserveScroll: true });
    }
};

// ==========================================
// 🔍 LABELS E VISUALIZAÇÃO DE MODAIS CLÁSSICOS
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
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Prontuário Clínico: {{ patient.name }}
                </h2>
                <Link :href="route('patients.index')" class="text-gray-600 hover:underline text-sm font-medium">
                    &larr; Voltar para lista
                </Link>
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
                                <p><span class="font-semibold text-gray-600">Data de Nascimento:</span> {{ new Date(patient.date_of_birth).toLocaleDateString('pt-BR', { timeZone: 'UTC' }) }}</p>
                                <p><span class="font-semibold text-gray-600">Telefone:</span> {{ patient.phone || 'Não informado' }}</p>
                                <p><span class="font-semibold text-gray-600">E-mail:</span> {{ patient.email || 'Não informado' }}</p>
                                <p><span class="font-semibold text-gray-600">Gênero:</span> {{ patient.gender || 'Não informado' }}</p>
                            </div>
                            <div class="mt-6 pt-4 border-t">
                                <Link :href="route('patients.edit', patient.id)" class="text-indigo-600 hover:underline text-sm font-bold">Editar Ficha Cadastral</Link>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 space-y-6">
                        
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-wrap gap-3 border border-gray-100">
                            <Link :href="route('evolutions.create', { patient_id: patient.id })" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors shadow-sm">
                                + Nova Evolução
                            </Link>
                            <Link :href="route('prescriptions.create', { patient_id: patient.id })" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors shadow-sm">
                                + Nova Receita
                            </Link>
                            <Link :href="route('anamneses.create', { patient_id: patient.id })" class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-md font-medium text-sm transition-colors border border-gray-300 shadow-sm">
                                Preencher Anamnese
                            </Link>
                            <button @click="showingUploadModal = true" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors shadow-sm flex items-center gap-1.5">
                                📎 Anexar Exame
                            </button>
                            
                            <button @click="showingAiModal = true" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white px-4 py-2 rounded-md font-bold text-sm transition-all shadow-md flex items-center gap-1.5 transform hover:scale-102">
                                💡 IA: Analisar Prontuário
                            </button>
                        </div>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-100">
                            
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6 flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.603 10.602Z" /></svg>
                                    <span class="text-sm font-semibold text-gray-700">Filtrar histórico por data de atendimento:</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="date" v-model="filterDate" class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm p-2 bg-white" />
                                    <button v-if="filterDate" @click="clearDateFilter" type="button" class="text-xs text-red-600 hover:underline font-bold px-2 py-1 bg-red-50 rounded border border-red-200">Limpar</button>
                                </div>
                            </div>

                            <div class="border-b border-gray-200 mb-6 overflow-x-auto">
                                <nav class="-mb-px flex space-x-6 min-w-max" aria-label="Tabs">
                                    <button @click="activeSubTab = 'all'" :class="[activeSubTab === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']">Ver Tudo</button>
                                    <button @click="activeSubTab = 'anamnese'" :class="[activeSubTab === 'anamnese' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']">Anamneses ({{ filteredAnamneses.length }})</button>
                                    <button @click="activeSubTab = 'evolution'" :class="[activeSubTab === 'evolution' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']">Evoluções ({{ filteredEvolutions.length }})</button>
                                    <button @click="activeSubTab = 'prescription'" :class="[activeSubTab === 'prescription' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']">Receitas ({{ filteredPrescriptions.length }})</button>
                                    <button @click="activeSubTab = 'file'" :class="[activeSubTab === 'file' ? 'border-gray-800 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']">Arquivos/Exames ({{ filteredFiles.length }})</button>
                                </nav>
                            </div>

                            <div class="space-y-6">
                                
                                <div v-if="activeSubTab === 'all' || activeSubTab === 'file'">
                                    <div v-for="file in filteredFiles" :key="'file-'+file.id" class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-sm transition-all mb-4 flex items-center justify-between">
                                        <div class="flex items-center gap-4">
                                            <div class="bg-gray-800 text-white p-3 rounded-lg">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-gray-900 text-base">{{ file.name }}</h4>
                                                <p class="text-xs text-gray-400 mt-0.5 uppercase font-bold">Extensão: {{ file.file_type }} • Inserido em: {{ new Date(file.created_at).toLocaleDateString('pt-BR') }}</p>
                                                <p v-if="file.notes" class="text-sm text-gray-600 mt-1 italic">Obs: {{ file.notes }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <a :href="`/storage/${file.file_path}`" target="_blank" class="text-xs font-bold text-gray-700 bg-white border border-gray-300 hover:bg-gray-100 px-3 py-2 rounded shadow-sm">
                                                Ver / Baixar
                                            </a>
                                            <button @click="deleteFile(file.id)" class="text-gray-400 hover:text-red-600 font-bold px-1 text-sm transition-colors">✕</button>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="activeSubTab === 'all' || activeSubTab === 'anamnese'">
                                    <div v-for="anamnese in filteredAnamneses" :key="'anam-'+anamnese.id" class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm mb-4">
                                        <div class="flex justify-between items-center">
                                            <div class="flex flex-col flex-1 pr-4">
                                                <h4 class="font-bold text-indigo-700 flex items-center gap-1.5">Anamnese Integrativa</h4>
                                                <span class="text-sm text-gray-600 mt-1 line-clamp-1"><span class="font-semibold">Queixa:</span> {{ anamnese.chief_complaint || 'Não especificada' }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button @click="createProtocol(anamnese.id)" class="px-3 py-1.5 text-xs font-extrabold text-purple-700 bg-purple-50 border border-purple-200 rounded-md hover:bg-purple-600 hover:text-white transition-all shadow-sm">
                                                    ✨ Gerar Protocolo IA
                                                </button>
                                                
                                                <button @click="openAnamnesisModal(anamnese)" class="px-3 py-1.5 text-xs font-bold text-gray-700 bg-gray-100 border border-gray-200 rounded-md hover:bg-gray-200 transition-all">
                                                    Visualizar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="activeSubTab === 'all' || activeSubTab === 'prescription'">
                                    <div v-for="prescription in filteredPrescriptions" :key="'presc-'+prescription.id" class="bg-emerald-50 border border-emerald-100 rounded-lg p-5 shadow-sm mb-4">
                                        <div class="flex justify-between items-center">
                                            <div class="flex flex-col flex-1 pr-4">
                                                <h4 class="font-bold text-emerald-800 flex items-center gap-2">Prescrição Médica</h4>
                                                <span class="text-xs font-mono text-emerald-600 mt-1 font-bold">Autenticação: {{ prescription.verification_code }}</span>
                                            </div>
                                            <div class="flex flex-col items-end gap-2">
                                                <span class="text-xs font-bold text-emerald-700 bg-emerald-100 px-2 rounded-full">{{ new Date(prescription.created_at).toLocaleDateString('pt-BR') }}</span>
                                                <button @click="openPrescriptionModal(prescription)" class="text-xs font-bold text-emerald-800 bg-white border border-emerald-300 px-3 py-1.5 rounded-md hover:bg-emerald-600 hover:text-white transition-all shadow-sm">Ver Receita</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="activeSubTab === 'all' || activeSubTab === 'evolution'">
                                    <div v-for="evolution in filteredEvolutions" :key="'ev-'+evolution.id" class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm mb-4 border-l-4 border-l-blue-500">
                                        <div class="flex justify-between items-center mb-3">
                                            <h4 class="font-bold text-blue-700 flex items-center gap-2">Evolução Clínica</h4>
                                            <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full border border-gray-200">
                                                {{ new Date(evolution.created_at).toLocaleDateString('pt-BR') }} às {{ new Date(evolution.created_at).toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'}) }}
                                            </span>
                                        </div>
                                        <div class="p-3 bg-gray-50 rounded border border-gray-100 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ evolution.clinical_notes }}</div>
                                    </div>
                                </div>

                                <div v-if="totalFilteredItems === 0" class="py-16 text-center bg-gray-50 border border-dashed border-gray-300 rounded-lg">
                                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    <h3 class="text-sm font-bold text-gray-800">Nenhum registo localizado</h3>
                                    <p class="text-xs text-gray-400 mt-1">Não existem lançamentos nesta categoria para os filtros atuais.</p>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showingAiModal" @close="closeAiModal" maxWidth="2xl">
            <div class="bg-gray-900 rounded-lg overflow-hidden flex flex-col h-[70vh]">
                <div class="bg-gray-800 p-4 border-b border-gray-700 flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="bg-purple-600 p-2 rounded-lg shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09l2.846.813-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">Assistente Gemini (Curae)</h3>
                            <p class="text-xs text-gray-400">Contexto clínico ativo: <span class="text-purple-400 font-bold">{{ patient.name }}</span></p>
                        </div>
                    </div>
                    <button @click="closeAiModal" class="text-gray-400 hover:text-white transition">✕</button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 bg-gray-900 text-gray-300 text-sm leading-relaxed whitespace-pre-wrap">
                    <div v-if="!aiResponse && !isAnalyzing" class="h-full flex flex-col items-center justify-center text-gray-500">
                        <p class="text-center font-medium">Como posso ajudar a analisar este caso clínico hoje?</p>
                    </div>
                    <div v-if="isAnalyzing" class="flex items-center gap-3 text-purple-400 font-bold animate-pulse">
                        <div class="w-3 h-3 rounded-full bg-purple-500 animate-bounce"></div>
                        A processar dados e histórico...
                    </div>
                    <div v-if="aiResponse" class="prose prose-invert max-w-none text-gray-200">
                        {{ aiResponse }}
                    </div>
                </div>

                <div class="p-4 bg-gray-800 border-t border-gray-700 shrink-0">
                    <form @submit.prevent="askAI" class="relative">
                        <textarea v-model="aiPrompt" rows="2" placeholder="Digite uma pergunta médica (Ex: Correlacione os históricos)..." class="w-full bg-gray-900 border border-gray-600 text-white rounded-lg pl-4 pr-16 py-3 focus:ring-purple-500 focus:border-purple-500 resize-none shadow-inner text-sm" @keydown.enter.prevent="askAI"></textarea>
                        <button type="submit" :disabled="isAnalyzing || !aiPrompt.trim()" class="absolute right-2 bottom-2 top-2 bg-purple-600 hover:bg-purple-500 disabled:bg-gray-600 text-white px-4 rounded-md font-bold transition-colors flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                        </button>
                    </form>
                </div>
            </div>
        </Modal>

        <Modal :show="showingProtocolModal" @close="showingProtocolModal = false" maxWidth="3xl">
            <div class="p-6">
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <h3 class="text-xl font-bold text-purple-800 flex items-center gap-2">
                        ✨ Protocolo Integrativo Sugerido por IA
                    </h3>
                    <button @click="showingProtocolModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <div class="max-h-[55vh] overflow-y-auto bg-gray-50 p-6 rounded-lg border text-gray-800 leading-relaxed whitespace-pre-wrap shadow-inner font-sans">
                    <div v-if="isGeneratingProtocol" class="flex flex-col items-center py-12">
                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-purple-700 mb-3"></div>
                        <p class="text-purple-700 font-bold text-sm">O Gemini está a cruzar a queixa principal e o checklist de sintomas...</p>
                    </div>
                    <div v-else class="text-sm">
                        {{ generatedProtocol }}
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                    <SecondaryButton @click="showingProtocolModal = false">Fechar Painel</SecondaryButton>
                    <a v-if="generatedProtocol && !isGeneratingProtocol" :href="route('ai.protocol.download')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-bold text-sm shadow-md flex items-center gap-1.5 transition-all">
                        📥 Descarregar Protocolo (PDF)
                    </a>
                </div>
            </div>
        </Modal>

        <Modal :show="showingUploadModal" @close="showingUploadModal = false" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">Anexar Laudo / Exame</h3>
                <form @submit.prevent="submitUpload" class="space-y-4 text-sm">
                    <div>
                        <InputLabel value="Nome do Documento *" />
                        <TextInput type="text" class="mt-1 block w-full" v-model="uploadForm.name" placeholder="Ex: Painel Hormonal Salivar" required />
                    </div>
                    <div>
                        <InputLabel value="Ficheiro (PDF ou Imagens) *" />
                        <input type="file" @change="handleFileUpload" class="mt-2 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-100 file:text-gray-700 file:font-bold hover:file:bg-gray-200" required accept=".pdf,image/*">
                        <progress v-if="uploadForm.progress" :value="uploadForm.progress.percentage" max="100" class="w-full mt-2 h-1.5 rounded bg-indigo-200"></progress>
                    </div>
                    <div>
                        <InputLabel value="Anotações / Notas de Triagem" />
                        <textarea v-model="uploadForm.notes" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Opcional..."></textarea>
                    </div>
                    <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                        <SecondaryButton @click="showingUploadModal = false">Cancelar</SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': uploadForm.processing }" :disabled="uploadForm.processing" class="bg-gray-800 hover:bg-black">
                            Iniciar Upload
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showingAnamnesisModal" @close="closeAnamnesisModal" maxWidth="3xl">
            <div class="p-6" v-if="selectedAnamnesis">
                <h2 class="text-lg font-bold text-gray-900 border-b pb-4 mb-4 flex justify-between items-center">
                    <span>Anamnese Integrativa Salva</span>
                    <span class="text-xs bg-gray-100 px-3 py-1 rounded-full font-bold">Data: {{ new Date(selectedAnamnesis.created_at).toLocaleDateString('pt-BR') }}</span>
                </h2>
                <div class="space-y-4 overflow-y-auto max-h-[55vh] text-sm text-gray-800 pr-1">
                    <div>
                        <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t text-xs uppercase tracking-wider text-gray-600">Queixa Principal</span>
                        <div class="p-3 border rounded-b bg-white whitespace-pre-wrap leading-relaxed">{{ selectedAnamnesis.chief_complaint || 'Não informada.' }}</div>
                    </div>
                    <div v-if="selectedAnamnesis.patient_routine">
                        <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t text-xs uppercase tracking-wider text-gray-600">Rotina & Estilo de Vida</span>
                        <div class="p-3 border rounded-b bg-white whitespace-pre-wrap leading-relaxed">{{ selectedAnamnesis.patient_routine }}</div>
                    </div>
                    <div v-if="selectedAnamnesis.family_history">
                        <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t text-xs uppercase tracking-wider text-gray-600">Histórico Familiar</span>
                        <div class="p-3 border rounded-b bg-white whitespace-pre-wrap leading-relaxed">{{ selectedAnamnesis.family_history }}</div>
                    </div>
                    <div v-if="selectedAnamnesis.symptoms_checklist && selectedAnamnesis.symptoms_checklist.length > 0">
                        <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t text-xs uppercase tracking-wider text-gray-600">Sintomas e Disfunções Assinaladas</span>
                        <div class="p-3 border rounded-b bg-white flex flex-wrap gap-1.5">
                            <span v-for="(symp, idx) in selectedAnamnesis.symptoms_checklist" :key="idx" class="px-2.5 py-1 text-xs font-bold bg-red-50 text-red-700 border border-red-100 rounded">
                                {{ symp }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end border-t pt-4">
                    <SecondaryButton @click="closeAnamnesisModal">Fechar Visualização</SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showingPrescriptionModal" @close="closePrescriptionModal" maxWidth="2xl">
            <div class="p-6" v-if="selectedPrescription">
                <div class="border-b-2 border-emerald-500 pb-4 mb-4 flex justify-between items-start">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Prescrição Médica</h2>
                        <p class="text-xs text-gray-500 mt-1">Paciente associado: <span class="font-bold text-gray-700">{{ patient.name }}</span></p>
                    </div>
                    <div class="text-right">
                        <span class="text-[9px] uppercase font-bold text-emerald-600 block">Assinatura Digital</span>
                        <span class="text-xs font-mono font-bold bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded mt-1 inline-block">{{ selectedPrescription.verification_code }}</span>
                    </div>
                </div>
                
                <div class="space-y-4 mb-6">
                    <div v-if="Array.isArray(selectedPrescription.medications)">
                        <div v-for="(med, idx) in selectedPrescription.medications" :key="idx" class="pl-3 border-l-4 border-emerald-400 py-1 mb-3">
                            <div class="font-bold text-gray-900 text-base">{{ med.name }} <span class="text-xs font-normal text-gray-500">({{ med.dosage }})</span></div>
                            <p class="text-sm text-gray-600 mt-0.5">Posologia/Uso: {{ med.instructions }}</p>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">
                        {{ selectedPrescription.notes }}
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end gap-2 border-t pt-4">
                    <SecondaryButton @click="closePrescriptionModal">Fechar</SecondaryButton>
                    <a :href="route('prescriptions.pdf', selectedPrescription.id)" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors flex items-center gap-1.5 shadow-sm">
                        🖨️ Imprimir Receita
                    </a>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>