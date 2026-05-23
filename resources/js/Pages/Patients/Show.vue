<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    patient: Object,
    anamneses: Array,
    evolutions: Array,
    prescriptions: Array,
    files: Array,
});

const activeSubTab = ref('all');
const filterDate = ref('');

// Compara a timestamp UTC convertendo-a para a data local do utilizador (YYYY-MM-DD)
const matchDate = (createdAtString) => {
    if (!filterDate.value) return true;
    const recordDate = new Date(createdAtString);
    const localYear = recordDate.getFullYear();
    const localMonth = String(recordDate.getMonth() + 1).padStart(2, '0');
    const localDay = String(recordDate.getDate()).padStart(2, '0');
    return `${localYear}-${localMonth}-${localDay}` === filterDate.value;
};

const filteredAnamneses = computed(() => {
    return (props.anamneses || []).filter(item => matchDate(item.created_at));
});

const filteredEvolutions = computed(() => {
    return (props.evolutions || []).filter(item => matchDate(item.created_at));
});

const filteredPrescriptions = computed(() => {
    return (props.prescriptions || []).filter(item => matchDate(item.created_at));
});

const filteredFiles = computed(() => {
    return (props.files || []).filter(item => matchDate(item.created_at));
});

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
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        currentAge--;
    }
    return `${currentAge} anos`;
});

// FORMULÁRIO E LOGICA DE UPLOAD
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
    if (confirm('Tem certeza que deseja apagar este documento permanentemente?')) {
        router.delete(route('patient-files.destroy', id), { preserveScroll: true });
    }
};

// Modais de Histórico Clínico
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
                                <Link :href="route('patients.edit', patient.id)" class="text-blue-600 hover:underline text-sm font-medium">Editar Cadastro</Link>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 space-y-6">
                        
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-wrap gap-4">
                            <Link :href="route('evolutions.create', { patient_id: patient.id })" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium text-sm transition-colors shadow-sm">
                                + Nova Evolução
                            </Link>
                            <Link :href="route('prescriptions.create', { patient_id: patient.id })" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded font-medium text-sm transition-colors shadow-sm">
                                + Nova Receita
                            </Link>
                            <Link :href="route('anamneses.create', { patient_id: patient.id })" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded font-medium text-sm transition-colors border border-gray-200">
                                Preencher Anamnese
                            </Link>
                            <button @click="showingUploadModal = true" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded font-medium text-sm transition-colors shadow-sm flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                                Anexar Arquivo
                            </button>
                        </div>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6 flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.603 10.602Z" /></svg>
                                    <span class="text-sm font-semibold text-gray-700">Recuperar informações por data:</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="date" v-model="filterDate" class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm p-2" />
                                    <button v-if="filterDate" @click="clearDateFilter" type="button" class="text-xs text-red-600 hover:underline font-medium px-2 py-1 bg-red-50 rounded border border-red-200">Limpar</button>
                                </div>
                            </div>

                            <div class="border-b border-gray-200 mb-6 overflow-x-auto">
                                <nav class="-mb-px flex space-x-6 min-w-max" aria-label="Tabs">
                                    <button @click="activeSubTab = 'all'" :class="[activeSubTab === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']">Ver Tudo</button>
                                    <button @click="activeSubTab = 'anamnese'" :class="[activeSubTab === 'anamnese' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']">Anamneses ({{ filteredAnamneses.length }})</button>
                                    <button @click="activeSubTab = 'evolution'" :class="[activeSubTab === 'evolution' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']">Evoluções ({{ filteredEvolutions.length }})</button>
                                    <button @click="activeSubTab = 'prescription'" :class="[activeSubTab === 'prescription' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']">Receitas ({{ filteredPrescriptions.length }})</button>
                                    <button @click="activeSubTab = 'file'" :class="[activeSubTab === 'file' ? 'border-gray-800 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all']">Arquivos/Exames ({{ filteredFiles.length }})</button>
                                </nav>
                            </div>

                            <div class="space-y-6">
                                
                                <div v-if="activeSubTab === 'all' || activeSubTab === 'file'">
                                    <div v-for="file in filteredFiles" :key="'file-'+file.id" class="bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow duration-200 mb-4 flex items-center justify-between">
                                        <div class="flex items-center gap-4">
                                            <div class="bg-gray-800 text-white p-3 rounded-lg flex-shrink-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-gray-900 text-base">{{ file.name }}</h4>
                                                <p class="text-xs text-gray-500 mt-1 uppercase font-semibold tracking-wider">Extensão: {{ file.file_type }} • Inserido: {{ new Date(file.created_at).toLocaleDateString('pt-BR') }}</p>
                                                <p v-if="file.notes" class="text-sm text-gray-600 mt-1">{{ file.notes }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <a :href="`/storage/${file.file_path}`" target="_blank" class="text-sm font-semibold text-gray-700 bg-white border border-gray-300 hover:bg-gray-100 px-4 py-2 rounded shadow-sm transition-colors">
                                                Ver Documento
                                            </a>
                                            <button @click="deleteFile(file.id)" class="text-gray-400 hover:text-red-600 font-bold transition-colors">✕</button>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="activeSubTab === 'all' || activeSubTab === 'anamnese'">
                                    <div v-for="anamnese in filteredAnamneses" :key="'anam-'+anamnese.id" class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm mb-4">
                                        <div class="flex justify-between items-center">
                                            <div class="flex flex-col flex-1 pr-4">
                                                <h4 class="font-bold text-indigo-700 flex items-center gap-2">Anamnese Integrativa</h4>
                                                <span class="text-sm text-gray-600 mt-1 line-clamp-1"><span class="font-medium">Queixa:</span> {{ anamnese.chief_complaint || 'Não informada' }}</span>
                                            </div>
                                            <div class="flex flex-col items-end gap-2">
                                                <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full border border-gray-200">{{ new Date(anamnese.created_at).toLocaleDateString('pt-BR') }}</span>
                                                <button @click="openAnamnesisModal(anamnese)" class="text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 px-3 py-1 rounded-md hover:bg-indigo-600 hover:text-white transition-all">Visualizar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="activeSubTab === 'all' || activeSubTab === 'prescription'">
                                    <div v-for="prescription in filteredPrescriptions" :key="'presc-'+prescription.id" class="bg-emerald-50 border border-emerald-200 rounded-lg p-5 shadow-sm mb-4">
                                        <div class="flex justify-between items-center">
                                            <div class="flex flex-col flex-1 pr-4">
                                                <h4 class="font-bold text-emerald-800 fill-none flex items-center gap-2">Prescrição Médica</h4>
                                                <span class="text-sm text-emerald-700 mt-1 line-clamp-1"><span class="font-medium">Validação:</span> {{ prescription.verification_code }}</span>
                                            </div>
                                            <div class="flex flex-col items-end gap-2">
                                                <span class="text-xs font-semibold text-emerald-700 bg-emerald-100 px-2.5 py-1 rounded-full border border-emerald-200">{{ new Date(prescription.created_at).toLocaleDateString('pt-BR') }}</span>
                                                <button @click="openPrescriptionModal(prescription)" class="text-xs font-semibold text-emerald-800 bg-white border border-emerald-300 px-3 py-1 rounded-md hover:bg-emerald-600 hover:text-white transition-all">Ver Receita</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="activeSubTab === 'all' || activeSubTab === 'evolution'">
                                    <div v-for="evolution in filteredEvolutions" :key="'ev-'+evolution.id" class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm mb-4">
                                        <div class="flex justify-between items-center mb-3">
                                            <h4 class="font-bold text-blue-700 flex items-center gap-2">Evolução Clínica</h4>
                                            <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full border border-gray-200">{{ new Date(evolution.created_at).toLocaleDateString('pt-BR') }} às {{ new Date(evolution.created_at).toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'}) }}</span>
                                        </div>
                                        <div class="p-3 bg-gray-50 rounded border border-gray-100 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ evolution.clinical_notes }}</div>
                                    </div>
                                </div>

                                <div v-if="totalFilteredItems === 0" class="py-16 text-center bg-gray-50 border border-dashed border-gray-300 rounded-xl">
                                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum registo localizado</h3>
                                    <p class="mt-1 text-xs text-gray-400">Não existem lançamentos nesta subcategoria para a data selecionada.</p>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showingUploadModal" @close="showingUploadModal = false" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">Anexar Documento / Exame</h3>
                <form @submit.prevent="submitUpload" class="space-y-4 text-sm">
                    <div>
                        <InputLabel value="Identificação do Documento *" />
                        <TextInput type="text" class="mt-1 block w-full" v-model="uploadForm.name" placeholder="Ex: Ressonância Magnética Lombar" required />
                    </div>
                    <div>
                        <InputLabel value="Ficheiro (PDF ou Imagens) *" />
                        <input type="file" @change="handleFileUpload" class="mt-2 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-100 file:text-gray-700 file:font-bold hover:file:bg-gray-200" required accept=".pdf,image/*">
                        <progress v-if="uploadForm.progress" :value="uploadForm.progress.percentage" max="100" class="w-full mt-2 h-1.5 rounded bg-gray-200"></progress>
                    </div>
                    <div>
                        <InputLabel value="Anotações / Notas de Triagem" />
                        <textarea v-model="uploadForm.notes" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Ex: Apresenta alteração L4-L5..."></textarea>
                    </div>
                    <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                        <SecondaryButton @click="showingUploadModal = false">Cancelar</SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': uploadForm.processing }" :disabled="uploadForm.processing" class="bg-gray-900 hover:bg-black">
                            Enviar Arquivo
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showingAnamnesisModal" @close="closeAnamnesisModal" maxWidth="3xl">
            <div class="p-6" v-if="selectedAnamnesis">
                <h2 class="text-lg font-bold text-gray-900 border-b pb-4 mb-4 flex items-center justify-between">
                    <span>Anamnese Completa</span>
                    <span class="text-xs bg-gray-100 px-3 py-1 rounded-full border">{{ new Date(selectedAnamnesis.created_at).toLocaleDateString('pt-BR') }}</span>
                </h2>
                <div class="space-y-4 overflow-y-auto max-h-[60vh] text-sm text-gray-800 pr-1">
                    <div>
                        <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t">Queixa Principal</span>
                        <div class="p-3 border rounded-b bg-white whitespace-pre-wrap leading-relaxed">{{ selectedAnamnesis.chief_complaint }}</div>
                    </div>
                    <div v-if="selectedAnamnesis.patient_routine">
                        <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t">Rotina Estilo de Vida</span>
                        <div class="p-3 border rounded-b bg-white whitespace-pre-wrap leading-relaxed">{{ selectedAnamnesis.patient_routine }}</div>
                    </div>
                    <div>
                        <span class="font-bold block bg-gray-100 p-2 border border-b-0 rounded-t">Sintomas Assinalados</span>
                        <div class="p-3 border rounded-b bg-white flex flex-wrap gap-1.5">
                            <span v-for="(symp, idx) in selectedAnamnesis.symptoms_checklist" :key="idx" class="px-2 py-1 text-xs font-semibold bg-red-50 text-red-700 border border-red-200 rounded">{{ symp }}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end border-t pt-4"><SecondaryButton @click="closeAnamnesisModal">Fechar</SecondaryButton></div>
            </div>
        </Modal>

        <Modal :show="showingPrescriptionModal" @close="closePrescriptionModal" maxWidth="2xl">
            <div class="p-6" v-if="selectedPrescription">
                <div class="border-b-2 border-emerald-500 pb-4 mb-4 flex justify-between items-start">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Prescrição Médica</h2>
                        <p class="text-xs text-gray-500 mt-1">Paciente: <span class="font-bold text-gray-700">{{ patient.name }}</span></p>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] uppercase font-bold text-emerald-600 block">Código Verificador</span>
                        <span class="text-sm font-mono font-bold bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded mt-1 inline-block">{{ selectedPrescription.verification_code }}</span>
                    </div>
                </div>
                <div class="space-y-4 mb-6">
                    <div v-for="(med, idx) in selectedPrescription.medications" :key="idx" class="pl-3 border-l-4 border-emerald-400 py-0.5">
                        <div class="font-bold text-gray-900 text-base">{{ med.name }} <span class="text-xs font-normal text-gray-500">({{ med.dosage }})</span></div>
                        <p class="text-sm text-gray-700 mt-0.5">Uso: {{ med.instructions }}</p>
                    </div>
                </div>
                <div v-if="selectedPrescription.notes" class="bg-amber-50 border border-amber-200 rounded p-3 text-sm text-amber-900 whitespace-pre-wrap mb-4">{{ selectedPrescription.notes }}</div>
                <div class="mt-6 flex justify-end gap-2 border-t pt-4">
                    <SecondaryButton @click="closePrescriptionModal">Fechar</SecondaryButton>
                    <a :href="route('prescriptions.pdf', selectedPrescription.id)" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded font-medium text-sm transition-colors flex items-center gap-1.5 shadow-sm">Imprimir Receita</a>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>