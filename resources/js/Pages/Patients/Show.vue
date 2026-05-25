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

const props = defineProps({ patient: Object, anamneses: Array, evolutions: Array, prescriptions: Array, files: Array });

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
    const today = new Date(); const birthDate = new Date(props.patient.date_of_birth);
    let currentAge = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) currentAge--;
    return `${currentAge} anos`;
});

// COPIAR SINTOMAS
const copiedAnamnesisId = ref(null);
const copySymptoms = (anamnese) => {
    if (!anamnese.symptoms_checklist || anamnese.symptoms_checklist.length === 0) { alert('Nenhum sintoma assinalado.'); return; }
    navigator.clipboard.writeText(anamnese.symptoms_checklist.join(', ')).then(() => {
        copiedAnamnesisId.value = anamnese.id;
        setTimeout(() => copiedAnamnesisId.value = null, 2000);
    }).catch(err => { alert('O navegador bloqueou a cópia.'); });
};

// LINK WHATSAPP
const isGeneratingLink = ref(false);
const sendAnamnesisLink = async () => {
    isGeneratingLink.value = true;
    try {
        const response = await axios.post(route('patients.anamnesis.link', props.patient.id));
        const message = `Olá, aqui é do consultório! \nPor favor, preencha a sua Ficha de Triagem Clínica online antes da nossa consulta. \n\nAcesse: ${response.data.url}`;
        window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`, '_blank');
    } catch (error) { alert('Erro ao gerar link.'); } finally { isGeneratingLink.value = false; }
};

// UPLOADS
const showingUploadModal = ref(false);
const uploadForm = useForm({ patient_id: props.patient.id, name: '', file: null, notes: '' });
const handleFileUpload = (e) => uploadForm.file = e.target.files[0];
const submitUpload = () => { uploadForm.post(route('patient-files.store'), { preserveScroll: true, onSuccess: () => { showingUploadModal.value = false; uploadForm.reset(); } }); };
const deleteFile = (id) => { if (confirm('Apagar arquivo?')) router.delete(route('patient-files.destroy', id), { preserveScroll: true }); };

// MODAIS
const showingAnamnesisModal = ref(false); const selectedAnamnesis = ref(null);
const openAnamnesisModal = (anamnese) => { selectedAnamnesis.value = anamnese; showingAnamnesisModal.value = true; };
const closeAnamnesisModal = () => { showingAnamnesisModal.value = false; setTimeout(() => selectedAnamnesis.value = null, 300); };

const showingPrescriptionModal = ref(false); const selectedPrescription = ref(null);
const openPrescriptionModal = (prescription) => { selectedPrescription.value = prescription; showingPrescriptionModal.value = true; };
const closePrescriptionModal = () => { showingPrescriptionModal.value = false; setTimeout(() => selectedPrescription.value = null, 300); };
</script>

<template>
    <Head :title="`Prontuário - ${patient.name}`" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-semibold text-xl leading-tight">Prontuário: {{ patient.name }}</h2></template>
        <div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div class="md:col-span-1">
                <div class="bg-white p-6 sticky top-6 border border-gray-100 rounded-lg">
                    <h3 class="text-lg font-bold border-b pb-2 mb-4">Dados Fundamentais</h3>
                    <div class="space-y-3 text-sm"><p><span class="font-semibold text-gray-600">Idade:</span> {{ age }}</p><p><span class="font-semibold text-gray-600">CPF:</span> {{ patient.cpf }}</p><p><span class="font-semibold text-gray-600">Telefone:</span> {{ patient.phone }}</p></div>
                    <div class="mt-6 pt-4 border-t"><Link :href="route('patients.edit', patient.id)" class="text-indigo-600 font-bold">Editar Ficha</Link></div>
                </div>
            </div>

            <div class="md:col-span-2 space-y-6">
                <div class="bg-white p-6 flex flex-wrap gap-3 border border-gray-100 rounded-lg">
                    <Link :href="route('evolutions.create', { patient_id: patient.id })" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">+ Nova Evolução</Link>
                    <Link :href="route('prescriptions.create', { patient_id: patient.id })" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md text-sm">+ Nova Receita</Link>
                    <Link :href="route('anamneses.create', { patient_id: patient.id })" class="bg-white text-gray-700 px-4 py-2 rounded-md border border-gray-300 text-sm">Preencher Anamnese</Link>
                    <button @click="sendAnamnesisLink" :disabled="isGeneratingLink" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md font-bold text-sm flex items-center gap-1.5"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16"><path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/></svg>WhatsApp (Link)</button>
                    <button @click="showingUploadModal = true" class="bg-gray-800 text-white px-4 py-2 rounded-md text-sm">📎 Anexar Exame</button>
                </div>

                <div class="bg-white p-6 border border-gray-100 rounded-lg">
                    <div class="border-b mb-6 overflow-x-auto"><nav class="-mb-px flex space-x-6 min-w-max"><button @click="activeSubTab = 'all'" :class="[activeSubTab === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500', 'py-4 border-b-2 font-bold text-sm']">Ver Tudo</button><button @click="activeSubTab = 'anamnese'" :class="[activeSubTab === 'anamnese' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500', 'py-4 border-b-2 font-bold text-sm']">Anamneses</button><button @click="activeSubTab = 'evolution'" :class="[activeSubTab === 'evolution' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500', 'py-4 border-b-2 font-bold text-sm']">Evoluções</button><button @click="activeSubTab = 'prescription'" :class="[activeSubTab === 'prescription' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500', 'py-4 border-b-2 font-bold text-sm']">Receitas</button></nav></div>
                    
                    <div class="space-y-6">
                        <div v-if="activeSubTab === 'all' || activeSubTab === 'anamnese'">
                            <div v-for="anamnese in filteredAnamneses" :key="'anam-'+anamnese.id" class="bg-white border rounded-lg p-5 shadow-sm mb-4">
                                <div class="flex justify-between items-center">
                                    <div class="flex flex-col">
                                        <h4 class="font-bold text-indigo-700 flex gap-2">Anamnese Integrativa <span v-if="anamnese.type === 'child'" class="bg-pink-100 text-pink-700 text-[10px] px-2 py-0.5 rounded-full uppercase">Infantil</span><span v-else class="bg-indigo-100 text-indigo-700 text-[10px] px-2 py-0.5 rounded-full uppercase">Adulto</span></h4>
                                    </div>
                                    <div class="flex gap-2">
                                        <button @click="copySymptoms(anamnese)" class="px-3 py-1.5 text-xs font-extrabold border rounded-md" :class="copiedAnamnesisId === anamnese.id ? 'bg-green-50 text-green-700 border-green-200' : 'bg-indigo-50 text-indigo-700 border-indigo-200'">{{ copiedAnamnesisId === anamnese.id ? '✔️ Copiado' : 'Copiar Sintomas' }}</button>
                                        <button @click="openAnamnesisModal(anamnese)" class="px-3 py-1.5 text-xs font-bold bg-gray-100 border rounded-md">Visualizar</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        </div>
                </div>
            </div>
        </div></div></div>

        <Modal :show="showingUploadModal" @close="showingUploadModal = false" maxWidth="md">
            <div class="p-6"><h3 class="text-lg font-bold mb-4">Anexar Exame</h3><form @submit.prevent="submitUpload" class="space-y-4"><TextInput class="w-full" v-model="uploadForm.name" placeholder="Nome"/><input type="file" @change="handleFileUpload" class="w-full text-xs" required><div class="flex justify-end gap-3 mt-4"><PrimaryButton>Upload</PrimaryButton></div></form></div>
        </Modal>

        <Modal :show="showingAnamnesisModal" @close="closeAnamnesisModal" maxWidth="3xl">
            <div class="p-6" v-if="selectedAnamnesis">
                <h2 class="text-lg font-bold text-gray-900 border-b pb-4 mb-4 flex justify-between items-center">
                    <span class="flex items-center gap-2">Ficha Clínica Integrativa <span v-if="selectedAnamnesis.type === 'child'" class="bg-pink-100 text-pink-700 text-[10px] px-2 py-0.5 rounded-full">Infantil (PCA)</span><span v-else class="bg-indigo-100 text-indigo-700 text-[10px] px-2 py-0.5 rounded-full">Adulto</span></span>
                    <span class="text-xs bg-gray-100 px-3 py-1 rounded-full font-bold">Data: {{ new Date(selectedAnamnesis.created_at).toLocaleDateString('pt-BR') }}</span>
                </h2>
                
                <div class="space-y-4 overflow-y-auto max-h-[60vh] text-sm pr-2">
                    
                    <div v-if="selectedAnamnesis.type === 'adult' && selectedAnamnesis.adult_data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-1 md:col-span-2"><span class="font-bold block bg-gray-100 p-2 text-xs uppercase">Queixa Principal</span><div class="p-3 border bg-white whitespace-pre-wrap">{{ selectedAnamnesis.chief_complaint || '-' }}</div></div>
                        <div><span class="font-bold block bg-indigo-50 p-2 text-xs uppercase">Rotina Alimentar</span><div class="p-3 border bg-white whitespace-pre-wrap">{{ selectedAnamnesis.adult_data.diet_routine || '-' }}</div></div>
                        <div><span class="font-bold block bg-indigo-50 p-2 text-xs uppercase">Sono e Celular</span><div class="p-3 border bg-white whitespace-pre-wrap">{{ selectedAnamnesis.adult_data.sleep_routine || '-' }}</div></div>
                        <div><span class="font-bold block bg-indigo-50 p-2 text-xs uppercase">Trabalho / Exercício</span><div class="p-3 border bg-white whitespace-pre-wrap">{{ selectedAnamnesis.patient_routine || '-' }}</div></div>
                        <div><span class="font-bold block bg-indigo-50 p-2 text-xs uppercase">Remédios / Hormônios</span><div class="p-3 border bg-white whitespace-pre-wrap">{{ selectedAnamnesis.adult_data.medications || '-' }}</div></div>
                        <div><span class="font-bold block bg-indigo-50 p-2 text-xs uppercase">Sol e Traumas</span><div class="p-3 border bg-white whitespace-pre-wrap">Sol: {{ selectedAnamnesis.adult_data.sun_exposure || '-' }} | Trauma: {{ selectedAnamnesis.adult_data.past_trauma || '-' }}</div></div>
                        <div><span class="font-bold block bg-indigo-50 p-2 text-xs uppercase">Histórico e Parto</span><div class="p-3 border bg-white whitespace-pre-wrap">Família: {{ selectedAnamnesis.family_history || '-' }} | Parto: {{ selectedAnamnesis.adult_data.birth_type || '-' }}</div></div>
                    </div>

                    <div v-if="selectedAnamnesis.type === 'child' && selectedAnamnesis.child_data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><span class="font-bold block bg-pink-50 p-2 text-xs uppercase">Pais</span><div class="p-3 border bg-white">{{ selectedAnamnesis.child_data.parents_names || '-' }}</div></div>
                        <div><span class="font-bold block bg-pink-50 p-2 text-xs uppercase">Peso</span><div class="p-3 border bg-white">{{ selectedAnamnesis.child_data.weight || '-' }} kg</div></div>
                        <div class="col-span-1 md:col-span-2"><span class="font-bold block bg-gray-100 p-2 text-xs uppercase">Queixa Principal</span><div class="p-3 border bg-white whitespace-pre-wrap">{{ selectedAnamnesis.chief_complaint || '-' }}</div></div>
                        <div><span class="font-bold block bg-pink-50 p-2 text-xs uppercase">Diagnóstico / Medicação</span><div class="p-3 border bg-white whitespace-pre-wrap">Diag: {{ selectedAnamnesis.child_data.previous_diagnosis || '-' }}<br>Med: {{ selectedAnamnesis.child_data.supplements || '-' }}</div></div>
                        <div><span class="font-bold block bg-pink-50 p-2 text-xs uppercase">Dieta e Água</span><div class="p-3 border bg-white whitespace-pre-wrap">Dieta: {{ selectedAnamnesis.child_data.diet_description || '-' }}<br>Água: {{ selectedAnamnesis.child_data.water_intake || '-' }}</div></div>
                        <div><span class="font-bold block bg-pink-50 p-2 text-xs uppercase">Alergias</span><div class="p-3 border bg-white whitespace-pre-wrap">{{ selectedAnamnesis.child_data.allergies || '-' }}</div></div>
                        <div><span class="font-bold block bg-pink-50 p-2 text-xs uppercase">Dores Frequentes</span><div class="p-3 border bg-white whitespace-pre-wrap">{{ selectedAnamnesis.child_data.pain_complaint || '-' }}</div></div>
                    </div>

                    <div v-if="selectedAnamnesis.symptoms_checklist && selectedAnamnesis.symptoms_checklist.length > 0">
                        <Modal :show="showingAnamnesisModal" @close="closeAnamnesisModal" maxWidth="3xl">
            <div class="p-0 overflow-hidden bg-gray-50" v-if="selectedAnamnesis">
                <div class="bg-indigo-900 px-6 py-4 flex justify-between items-center text-white">
                    <div>
                        <h2 class="text-lg font-black flex items-center gap-3">
                            Ficha Clínica Integrativa
                            <span v-if="selectedAnamnesis.type === 'child'" class="bg-pink-500 text-white text-[10px] uppercase px-2.5 py-0.5 rounded-full font-black tracking-widest shadow-sm">Infantil PCA</span>
                            <span v-else class="bg-indigo-500 text-white text-[10px] uppercase px-2.5 py-0.5 rounded-full font-black tracking-widest shadow-sm">Adulto</span>
                        </h2>
                        <p class="text-indigo-200 text-xs mt-1 font-medium">Preenchida a {{ new Date(selectedAnamnesis.created_at).toLocaleDateString('pt-BR') }}</p>
                    </div>
                    <button @click="closeAnamnesisModal" class="text-indigo-200 hover:text-white transition-colors">✕</button>
                </div>
                
                <div class="p-6 space-y-6 overflow-y-auto max-h-[70vh] text-sm text-gray-800">
                    
                    <div v-if="selectedAnamnesis.type === 'adult' && selectedAnamnesis.adult_data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-1 md:col-span-2 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Queixa Principal</span>
                            <p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.chief_complaint || 'Sem registo' }}</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Rotina e Trabalho</span>
                            <p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.patient_routine || 'Sem registo' }}</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Dieta e Alimentação</span>
                            <p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.adult_data.diet_routine || 'Sem registo' }}</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Sono e Celular</span>
                            <p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.adult_data.sleep_routine || 'Sem registo' }}</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Medicações em Uso</span>
                            <p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.adult_data.medications || 'Sem registo' }}</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Histórico Familiar</span>
                            <p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.family_history || 'Sem registo' }}</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Outros Detalhes</span>
                            <p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">Sol: {{ selectedAnamnesis.adult_data.sun_exposure || '-' }} | Parto: {{ selectedAnamnesis.adult_data.birth_type || '-' }}</p>
                        </div>
                    </div>

                    <div v-if="selectedAnamnesis.type === 'child' && selectedAnamnesis.child_data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-1 md:col-span-2 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <span class="font-black text-[10px] uppercase text-pink-500 tracking-wider">Queixa Principal</span>
                            <p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.chief_complaint || 'Sem registo' }}</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <span class="font-black text-[10px] uppercase text-pink-500 tracking-wider">Pais e Peso</span>
                            <p class="mt-1 font-medium text-gray-900">Responsáveis: {{ selectedAnamnesis.child_data.parents_names || '-' }} <br>Peso: {{ selectedAnamnesis.child_data.weight || '-' }} kg</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <span class="font-black text-[10px] uppercase text-pink-500 tracking-wider">Diagnóstico e Remédios</span>
                            <p class="mt-1 font-medium text-gray-900">Diag: {{ selectedAnamnesis.child_data.previous_diagnosis || '-' }}<br>Med: {{ selectedAnamnesis.child_data.supplements || '-' }}</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <span class="font-black text-[10px] uppercase text-pink-500 tracking-wider">Alimentação e Água</span>
                            <p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.child_data.diet_description || 'Sem registo' }} (Água: {{ selectedAnamnesis.child_data.water_intake || '-' }})</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <span class="font-black text-[10px] uppercase text-pink-500 tracking-wider">Dores e Alergias</span>
                            <p class="mt-1 font-medium text-gray-900">Alergias: {{ selectedAnamnesis.child_data.allergies || '-' }}<br>Dores: {{ selectedAnamnesis.child_data.pain_complaint || '-' }}</p>
                        </div>
                    </div>

                    <div v-if="selectedAnamnesis.symptoms_checklist && selectedAnamnesis.symptoms_checklist.length > 0" class="pt-4 border-t border-gray-200">
                        <h3 class="font-black text-gray-900 mb-4">Sinais e Sintomas Assinalados ({{ selectedAnamnesis.symptoms_checklist.length }})</h3>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="(symp, idx) in selectedAnamnesis.symptoms_checklist" :key="idx" 
                                  :class="selectedAnamnesis.type === 'child' ? 'bg-pink-100 text-pink-800 border border-pink-200' : 'bg-indigo-100 text-indigo-800 border border-indigo-200'" 
                                  class="px-3 py-1.5 text-xs font-bold rounded-lg shadow-sm">
                                {{ symp }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 bg-white border-t border-gray-200 flex justify-end gap-3 rounded-b-lg">
                    <button @click="copySymptoms(selectedAnamnesis)" class="px-5 py-2.5 font-bold rounded-xl transition-all shadow-sm flex items-center gap-2 text-sm" :class="copiedAnamnesisId === selectedAnamnesis.id ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'">
                        <span v-if="copiedAnamnesisId === selectedAnamnesis.id">✔️ Sintomas Copiados!</span>
                        <span v-else>📋 Copiar Sintomas</span>
                    </button>
                    <button @click="closeAnamnesisModal" class="px-5 py-2.5 font-bold rounded-xl bg-gray-800 text-white hover:bg-black transition-all text-sm">Fechar Ficha</button>
                </div>
            </div>
        </Modal><span class="font-bold block bg-gray-100 p-2 border-b-0 rounded-t text-xs uppercase">Mapeamento Clínico (Sinais Marcados)</span>
                        <div class="p-4 border rounded-b bg-white flex flex-wrap gap-2.5">
                            <span v-for="(symp, idx) in selectedAnamnesis.symptoms_checklist" :key="idx" :class="selectedAnamnesis.type === 'child' ? 'bg-pink-50 text-pink-700' : 'bg-indigo-50 text-indigo-700'" class="px-3 py-1.5 text-xs font-extrabold border rounded-md shadow-sm">{{ symp }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                    <button @click="copySymptoms(selectedAnamnesis)" class="px-4 py-2 font-bold border rounded-md" :class="copiedAnamnesisId === selectedAnamnesis.id ? 'bg-green-50 text-green-700' : 'bg-indigo-50 text-indigo-700'">Copiar Sintomas</button>
                    <SecondaryButton @click="closeAnamnesisModal">Fechar</SecondaryButton>
                </div>
            </div>
        </Modal>

        </AuthenticatedLayout>
</template>