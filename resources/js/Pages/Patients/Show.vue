<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    patient: Object,
    anamneses: Array,
    evolutions: Array,
    prescriptions: Array,
});

// Aba ativa no prontuário: 'all' (Tudo), 'anamnese', 'evolution', 'prescription'
const activeSubTab = ref('all');

// Estado para o filtro de data (Formato do input type="date" é YYYY-MM-DD)
const filterDate = ref('');

// FUNÇÃO CORRIGIDA PARA COMPARAR DATAS COM PRECISÃO DE FUSO HORÁRIO
const matchDate = (createdAtString) => {
    if (!filterDate.value) return true; // Se o filtro estiver vazio, mostra tudo
    
    // Converte a string UTC do Laravel para a data/hora local do computador
    const recordDate = new Date(createdAtString);
    
    // Extrai o dia, mês e ano no fuso horário local para o formato YYYY-MM-DD
    const localYear = recordDate.getFullYear();
    const localMonth = String(recordDate.getMonth() + 1).padStart(2, '0');
    const localDay = String(recordDate.getDate()).padStart(2, '0');
    
    const localFormattedDate = `${localYear}-${localMonth}-${localDay}`;
    
    return localFormattedDate === filterDate.value;
};

// Listas Filtradas Reativas (Filtram por Data em tempo real)
const filteredAnamneses = computed(() => {
    return (props.anamneses || []).filter(item => matchDate(item.created_at));
});

const filteredEvolutions = computed(() => {
    return (props.evolutions || []).filter(item => matchDate(item.created_at));
});

const filteredPrescriptions = computed(() => {
    return (props.prescriptions || []).filter(item => matchDate(item.created_at));
});

// Calcula o total de itens filtrados para exibir estados vazios precisos
const totalFilteredItems = computed(() => {
    let count = 0;
    if (activeSubTab.value === 'all' || activeSubTab.value === 'anamnese') count += filteredAnamneses.value.length;
    if (activeSubTab.value === 'all' || activeSubTab.value === 'evolution') count += filteredEvolutions.value.length;
    if (activeSubTab.value === 'all' || activeSubTab.value === 'prescription') count += filteredPrescriptions.value.length;
    return count;
});

// Limpar o filtro de data
const clearDateFilter = () => {
    filterDate.value = '';
};

// Cálculo da idade
const age = computed(() => {
    if (!props.patient.date_of_birth) return 'Idade não informada';
    const today = new Date();
    const birthDate = new Date(props.patient.date_of_birth);
    let currentAge = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        currentAge--;
    }
    return `${currentAge} anos`;
});

// Modais
const showingAnamnesisModal = ref(false);
const selectedAnamnesis = ref(null);
const openAnamnesisModal = (anamnese) => {
    selectedAnamnesis.value = anamnese;
    showingAnamnesisModal.value = true;
};
const closeAnamnesisModal = () => {
    showingAnamnesisModal.value = false;
    setTimeout(() => selectedAnamnesis.value = null, 300);
};

const showingPrescriptionModal = ref(false);
const selectedPrescription = ref(null);
const openPrescriptionModal = (prescription) => {
    selectedPrescription.value = prescription;
    showingPrescriptionModal.value = true;
};
const closePrescriptionModal = () => {
    showingPrescriptionModal.value = false;
    setTimeout(() => selectedPrescription.value = null, 300);
};
</script>

<template>
    <Head :title="`Prontuário - ${patient.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Prontuário: {{ patient.name }}
                </h2>
                <Link :href="route('patients.index')" class="text-gray-600 hover:underline">
                    Voltar para lista
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="md:col-span-1">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 sticky top-6">
                            <h3 class="text-lg font-bold text-gray-900 border-b pb-2 mb-4">Dados do Paciente</h3>
                            
                            <div class="space-y-3 text-sm">
                                <p><span class="font-semibold text-gray-600">Idade:</span> {{ age }}</p>
                                <p><span class="font-semibold text-gray-600">CPF:</span> {{ patient.cpf }}</p>
                                <p><span class="font-semibold text-gray-600">Nascimento:</span> {{ new Date(patient.date_of_birth).toLocaleDateString('pt-BR', { timeZone: 'UTC' }) }}</p>
                                <p><span class="font-semibold text-gray-600">Telefone:</span> {{ patient.phone || 'Não informado' }}</p>
                                <p><span class="font-semibold text-gray-600">E-mail:</span> {{ patient.email || 'Não informado' }}</p>
                                <p><span class="font-semibold text-gray-600">Gênero:</span> {{ patient.gender || 'Não informado' }}</p>
                            </div>

                            <div class="mt-6 pt-4 border-t">
                                <Link :href="route('patients.edit', patient.id)" class="text-blue-600 hover:underline text-sm font-medium">
                                    Editar Cadastro
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 space-y-6">
                        
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-wrap gap-4">
                            <Link :href="route('evolutions.create', { patient_id: patient.id })" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium text-sm transition-colors shadow-sm text-center">
                                + Nova Evolução
                            </Link>
                            
                            <Link :href="route('prescriptions.create', { patient_id: patient.id })" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded font-medium text-sm transition-colors shadow-sm text-center">
                                + Nova Receita
                            </Link>
                            
                            <Link :href="route('anamneses.create', { patient_id: patient.id })" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded font-medium text-sm transition-colors text-center border border-gray-200">
                                Preencher Anamnese Integrativa
                            </Link>
                        </div>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6 flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.603 10.602Z" />
                                    </svg>
                                    <span class="text-sm font-semibold text-gray-700">Recuperar informações por data:</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input 
                                        type="date" 
                                        v-model="filterDate"
                                        class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm p-2"
                                    />
                                    <button 
                                        v-if="filterDate" 
                                        @click="clearDateFilter"
                                        type="button" 
                                        class="text-xs text-red-600 hover:underline font-medium px-2 py-1 bg-red-50 rounded border border-red-200"
                                    >
                                        Limpar
                                    </button>
                                </div>
                            </div>

                            <div class="border-b border-gray-200 mb-6">
                                <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                                    <button 
                                        @click="activeSubTab = 'all'"
                                        :class="[activeSubTab === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']"
                                    >
                                        Ver Tudo
                                    </button>
                                    <button 
                                        @click="activeSubTab = 'anamnese'"
                                        :class="[activeSubTab === 'anamnese' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']"
                                    >
                                        Anamneses ({{ filteredAnamneses.length }})
                                    </button>
                                    <button 
                                        @click="activeSubTab = 'evolution'"
                                        :class="[activeSubTab === 'evolution' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']"
                                    >
                                        Evoluções ({{ filteredEvolutions.length }})
                                    </button>
                                    <button 
                                        @click="activeSubTab = 'prescription'"
                                        :class="[activeSubTab === 'prescription' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']"
                                    >
                                        Receitas ({{ filteredPrescriptions.length }})
                                    </button>
                                </nav>
                            </div>

                            <div class="space-y-6">
                                
                                <div v-if="activeSubTab === 'all' || activeSubTab === 'anamnese'">
                                    <div v-for="anamnese in filteredAnamneses" :key="'anam-'+anamnese.id" class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm hover:shadow-md transition-shadow duration-200 mb-4">
                                        <div class="flex justify-between items-center">
                                            <div class="flex flex-col flex-1 pr-4">
                                                <h4 class="font-bold text-indigo-700 flex items-center gap-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                      <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                    </svg>
                                                    Anamnese Integrativa
                                                </h4>
                                                <span class="text-sm text-gray-600 mt-1 line-clamp-1">
                                                    <span class="font-medium">Queixa:</span> {{ anamnese.chief_complaint || 'Não informada' }}
                                                </span>
                                            </div>
                                            <div class="flex flex-col items-end gap-2">
                                                <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full border border-gray-200">
                                                    {{ new Date(anamnese.created_at).toLocaleDateString('pt-BR') }}
                                                </span>
                                                <button @click="openAnamnesisModal(anamnese)" class="flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-600 hover:text-white rounded-md transition-all border border-indigo-200">
                                                    Visualizar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="activeSubTab === 'all' || activeSubTab === 'prescription'">
                                    <div v-for="prescription in filteredPrescriptions" :key="'presc-'+prescription.id" class="bg-emerald-50 border border-emerald-200 rounded-lg p-5 shadow-sm hover:shadow-md transition-shadow duration-200 mb-4">
                                        <div class="flex justify-between items-center">
                                            <div class="flex flex-col flex-1 pr-4">
                                                <h4 class="font-bold text-emerald-800 flex items-center gap-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    Prescrição Médica
                                                </h4>
                                                <span class="text-sm text-emerald-700 mt-1 line-clamp-1">
                                                    <span class="font-medium">Validação:</span> {{ prescription.verification_code }}
                                                    • {{ prescription.medications ? prescription.medications.length : 0 }} medicamento(s)
                                                </span>
                                            </div>
                                            <div class="flex flex-col items-end gap-2">
                                                <span class="text-xs font-semibold text-emerald-700 bg-emerald-100 px-2.5 py-1 rounded-full border border-emerald-200">
                                                    {{ new Date(prescription.created_at).toLocaleDateString('pt-BR') }}
                                                </span>
                                                <button @click="openPrescriptionModal(prescription)" class="flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-emerald-800 bg-white hover:bg-emerald-600 hover:text-white rounded-md transition-all border border-emerald-300 shadow-sm">
                                                    Ver Receita
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="activeSubTab === 'all' || activeSubTab === 'evolution'">
                                    <div v-for="evolution in filteredEvolutions" :key="'ev-'+evolution.id" class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm hover:shadow-md transition-shadow duration-200 mb-4">
                                        <div class="flex justify-between items-center mb-3">
                                            <h4 class="font-bold text-blue-700 flex items-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                                Evolução Clínica
                                            </h4>
                                            <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full border border-gray-200">
                                                {{ new Date(evolution.created_at).toLocaleDateString('pt-BR') }} às {{ new Date(evolution.created_at).toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'}) }}
                                            </span>
                                        </div>
                                        
                                        <div class="flex flex-wrap gap-2 mb-3" v-if="evolution.weight || evolution.systolic_bp || evolution.temperature || evolution.blood_glucose || evolution.oxygen_saturation || evolution.heart_rate">
                                            <span v-if="evolution.weight" class="text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded">Peso: {{ evolution.weight }}kg</span>
                                            <span v-if="evolution.height" class="text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded">Alt: {{ evolution.height }}m</span>
                                            <span v-if="evolution.bmi" class="text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded">IMC: {{ evolution.bmi }}</span>
                                            <span v-if="evolution.systolic_bp && evolution.diastolic_bp" class="text-xs font-medium bg-red-50 text-red-700 border border-red-200 px-2 py-0.5 rounded">PA: {{ evolution.systolic_bp }}/{{ evolution.diastolic_bp }}</span>
                                            <span v-if="evolution.heart_rate" class="text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200 px-2 py-0.5 rounded">FC: {{ evolution.heart_rate }} bpm</span>
                                            <span v-if="evolution.temperature" class="text-xs font-medium bg-orange-50 text-orange-700 border border-orange-200 px-2 py-0.5 rounded">Temp: {{ evolution.temperature }}°C</span>
                                            <span v-if="evolution.oxygen_saturation" class="text-xs font-medium bg-sky-50 text-sky-700 border border-sky-200 px-2 py-0.5 rounded">SpO2: {{ evolution.oxygen_saturation }}%</span>
                                            <span v-if="evolution.blood_glucose" class="text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 rounded">Glicemia: {{ evolution.blood_glucose }} mg/dL</span>
                                        </div>

                                        <div class="p-3 bg-gray-50 rounded border border-gray-100 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">
                                            {{ evolution.clinical_notes }}
                                        </div>
                                    </div>
                                </div>

                                <div v-if="totalFilteredItems === 0" class="py-12 text-center bg-gray-50 border border-dashed border-gray-300 rounded-lg">
                                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum registo encontrado</h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Não existem informações nesta categoria para a data selecionada.
                                    </p>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <Modal :show="showingAnamnesisModal" @close="closeAnamnesisModal" maxWidth="3xl">
            <div class="p-6" v-if="selectedAnamnesis">
                <h2 class="text-lg font-bold text-gray-900 border-b pb-4 mb-4 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-indigo-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        Anamnese Completa do Paciente
                    </span>
                    <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-3 py-1 rounded-full border">
                        {{ new Date(selectedAnamnesis.created_at).toLocaleDateString('pt-BR') }}
                    </span>
                </h2>

                <div class="space-y-5 overflow-y-auto max-h-[70vh] pr-2 text-sm text-gray-800">
                    <div>
                        <span class="font-bold block text-gray-900 bg-gray-100 p-2 rounded-t border border-b-0 border-gray-200">Queixa Principal / Motivo da Consulta</span>
                        <div class="p-3 border rounded-b border-gray-200 bg-white whitespace-pre-wrap leading-relaxed">
                            {{ selectedAnamnesis.chief_complaint || 'Nenhum registro inserido.' }}
                        </div>
                    </div>

                    <div v-if="selectedAnamnesis.patient_routine">
                        <span class="font-bold block text-gray-900 bg-gray-100 p-2 rounded-t border border-b-0 border-gray-200">Rotina (Sono, Estresse, Alimentação)</span>
                        <div class="p-3 border rounded-b border-gray-200 bg-white whitespace-pre-wrap leading-relaxed">
                            {{ selectedAnamnesis.patient_routine }}
                        </div>
                    </div>

                    <div v-if="selectedAnamnesis.family_history">
                        <span class="font-bold block text-gray-900 bg-gray-100 p-2 rounded-t border border-b-0 border-gray-200">Histórico Familiar</span>
                        <div class="p-3 border rounded-b border-gray-200 bg-white whitespace-pre-wrap leading-relaxed">
                            {{ selectedAnamnesis.family_history }}
                        </div>
                    </div>

                    <div v-if="selectedAnamnesis.medications_in_use">
                        <span class="font-bold block text-gray-900 bg-gray-100 p-2 rounded-t border border-b-0 border-gray-200">Medicamentos e Suplementos em Uso</span>
                        <div class="p-3 border rounded-b border-gray-200 bg-white whitespace-pre-wrap leading-relaxed">
                            {{ selectedAnamnesis.medications_in_use }}
                        </div>
                    </div>

                    <div>
                        <span class="font-bold block text-gray-900 bg-gray-100 p-2 rounded-t border border-b-0 border-gray-200">
                            Sinais Clínicos e Sintomas Detectados ({{ selectedAnamnesis.symptoms_checklist ? selectedAnamnesis.symptoms_checklist.length : 0 }})
                        </span>
                        <div class="p-4 border rounded-b border-gray-200 bg-white">
                            <div v-if="selectedAnamnesis.symptoms_checklist && selectedAnamnesis.symptoms_checklist.length > 0" class="flex flex-wrap gap-2">
                                <span 
                                    v-for="(symptom, idx) in selectedAnamnesis.symptoms_checklist" :key="idx"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-red-50 text-red-700 border border-red-200 rounded-md shadow-sm"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    {{ symptom }}
                                </span>
                            </div>
                            <div v-else class="text-gray-500 text-center py-4 italic">
                                Nenhum sinal ou sintoma foi assinalado nesta consulta.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end border-t pt-4">
                    <SecondaryButton @click="closeAnamnesisModal">
                        Fechar Relatório
                    </SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showingPrescriptionModal" @close="closePrescriptionModal" maxWidth="2xl">
            <div class="p-6" v-if="selectedPrescription">
                <div class="border-b-2 border-emerald-500 pb-4 mb-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Prescrição Médica</h2>
                            <p class="text-sm text-gray-500 mt-1">Paciente: <span class="font-semibold text-gray-700">{{ patient.name }}</span></p>
                            <p class="text-sm text-gray-500">Data: <span class="font-semibold text-gray-700">{{ new Date(selectedPrescription.created_at).toLocaleDateString('pt-BR') }}</span></p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs uppercase font-bold text-emerald-600 tracking-wider">Código de Validação</span>
                            <p class="text-lg font-mono font-bold text-gray-800 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded mt-1">{{ selectedPrescription.verification_code || '---' }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 mb-8">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-2">Uso Prescrito</h3>
                    <div v-for="(med, idx) in selectedPrescription.medications" :key="idx" class="pl-4 border-l-4 border-emerald-400 py-1">
                        <div class="flex justify-between items-baseline">
                            <span class="font-bold text-lg text-gray-900">{{ med.name }}</span>
                            <span class="font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded text-sm">{{ med.dosage }}</span>
                        </div>
                        <p class="text-gray-700 mt-1 flex items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 mt-0.5 text-emerald-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                            {{ med.instructions }}
                        </p>
                    </div>
                </div>

                <div v-if="selectedPrescription.notes" class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-6">
                    <h3 class="text-xs font-bold text-yellow-800 uppercase tracking-wider mb-1">Orientações Adicionais</h3>
                    <p class="text-sm text-yellow-900 whitespace-pre-wrap">{{ selectedPrescription.notes }}</p>
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4">
                    <SecondaryButton @click="closePrescriptionModal">
                        Fechar Visualização
                    </SecondaryButton>
                    <a 
    :href="route('prescriptions.pdf', selectedPrescription.id)" 
    target="_blank" 
    class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded font-medium text-sm transition-colors shadow-sm flex items-center gap-2"
>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
    </svg>
    Imprimir Receita
</a>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>