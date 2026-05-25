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

const age = computed(() => {
    if (!props.patient.date_of_birth) return 'Idade não informada';
    const today = new Date(); const birthDate = new Date(props.patient.date_of_birth);
    let currentAge = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) currentAge--;
    return `${currentAge} anos`;
});

const lastAnamnesis = computed(() => {
    if (!props.patient.last_anamnesis_at) return 'Não preenchida';
    const dt = new Date(props.patient.last_anamnesis_at);
    return dt.toLocaleString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
});

// ==========================================
// 🕒 LINHA DO TEMPO UNIFICADA E ORDENADA
// ==========================================
const activeTypeFilter = ref('all');
const filterDate = ref('');

// Formatação rápida de data e hora
const formatDate = (dateString) => new Date(dateString).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
const formatTime = (dateString) => new Date(dateString).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

// Junta todos os arrays num só e ordena por data (mais recente primeiro)
const allTimelineItems = computed(() => {
    const items = [];
    (props.anamneses || []).forEach(a => items.push({ ...a, item_type: 'anamnesis', sort_date: new Date(a.created_at) }));
    (props.evolutions || []).forEach(e => items.push({ ...e, item_type: 'evolution', sort_date: new Date(e.created_at) }));
    (props.prescriptions || []).forEach(p => items.push({ ...p, item_type: 'prescription', sort_date: new Date(p.created_at) }));
    (props.files || []).forEach(f => items.push({ ...f, item_type: 'file', sort_date: new Date(f.created_at) }));

    return items.sort((a, b) => b.sort_date - a.sort_date);
});

// Aplica os filtros de Data e de Tipo
const filteredTimeline = computed(() => {
    return allTimelineItems.value.filter(item => {
        // Filtro de Tipo
        if (activeTypeFilter.value !== 'all' && item.item_type !== activeTypeFilter.value) return false;
        
        // Filtro de Data
        if (filterDate.value) {
            const itemDate = new Date(item.created_at);
            const localYear = itemDate.getFullYear();
            const localMonth = String(itemDate.getMonth() + 1).padStart(2, '0');
            const localDay = String(itemDate.getDate()).padStart(2, '0');
            const formattedDate = `${localYear}-${localMonth}-${localDay}`;
            if (formattedDate !== filterDate.value) return false;
        }
        
        return true;
    });
});

const clearFilters = () => { filterDate.value = ''; activeTypeFilter.value = 'all'; };

// ==========================================
// 📋 LÓGICA DE COPIAR SINTOMAS
// ==========================================
const copiedAnamnesisId = ref(null);
const copySymptoms = (anamnese) => {
    if (!anamnese.symptoms_checklist || anamnese.symptoms_checklist.length === 0) { alert('Nenhum sintoma assinalado nesta anamnese para copiar.'); return; }
    navigator.clipboard.writeText(anamnese.symptoms_checklist.join(', ')).then(() => {
        copiedAnamnesisId.value = anamnese.id; setTimeout(() => copiedAnamnesisId.value = null, 2000);
    }).catch(err => { console.error(err); alert('O seu navegador bloqueou a cópia automática.'); });
};

// ==========================================
// 📱 LÓGICA DO LINK WHATSAPP
// ==========================================
const patientPhone = computed(() => {
    if (!props.patient.phone) return null;
    const digits = props.patient.phone.replace(/\D/g, '');
    return digits || null;
});

const isGeneratingLink = ref(false);
const sendAnamnesisLink = async () => {
    if (!patientPhone.value) {
        alert('Telefone do paciente não informado. Atualize o cadastro para usar o WhatsApp.');
        return;
    }

    isGeneratingLink.value = true;
    try {
        const response = await axios.post(route('patients.anamnesis.link', props.patient.id));
        const message = `Olá ${props.patient.name}, por favor, preencha a sua ficha de anamnese online antes da consulta.\n\nAcesse o link abaixo:\n${response.data.url}`;
        const whatsappUrl = `https://api.whatsapp.com/send?phone=${patientPhone.value}&text=${encodeURIComponent(message)}`;
        window.open(whatsappUrl, '_blank');
    } catch (error) {
        alert('Erro ao gerar o link.');
    } finally {
        isGeneratingLink.value = false;
    }
};

// ==========================================
// 🤖 ASSISTENTE DE IA GERAL
// ==========================================
const showingAiModal = ref(false); const aiPrompt = ref(''); const aiResponse = ref(''); const isAnalyzing = ref(false);
const askAI = async () => {
    if (!aiPrompt.value.trim()) return;
    isAnalyzing.value = true; aiResponse.value = '';
    const context = `[DADOS DO PACIENTE]\nNome: ${props.patient.name}\nIdade: ${age.value}\n\n[INSTRUÇÃO DO MÉDICO]\n${aiPrompt.value}`;
    try { const response = await axios.post(route('ai.analyze'), { prompt: context }); aiResponse.value = response.data.response;
    } catch (error) { aiResponse.value = '⚠️ Erro ao comunicar com a IA.'; } finally { isAnalyzing.value = false; }
};
const closeAiModal = () => { showingAiModal.value = false; setTimeout(() => { aiPrompt.value = ''; aiResponse.value = ''; }, 300); };

// ==========================================
// 📎 UPLOADS
// ==========================================
const showingUploadModal = ref(false); const uploadForm = useForm({ patient_id: props.patient.id, name: '', file: null, notes: '' });
const handleFileUpload = (e) => uploadForm.file = e.target.files[0];
const submitUpload = () => { uploadForm.post(route('patient-files.store'), { preserveScroll: true, onSuccess: () => { showingUploadModal.value = false; uploadForm.reset(); } }); };
const deleteFile = (id) => { if (confirm('Apagar arquivo permanentemente?')) router.delete(route('patient-files.destroy', id), { preserveScroll: true }); };

// ==========================================
// 🔍 MODAIS DE VISUALIZAÇÃO
// ==========================================
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
                                <p><span class="font-semibold text-gray-600">Última Anamnese:</span> {{ lastAnamnesis }}</p>
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
                            <button @click="sendAnamnesisLink" :disabled="isGeneratingLink" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md font-bold text-sm transition-colors shadow-sm flex items-center gap-1.5 disabled:opacity-50">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16"><path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/></svg>
                                Solicitar Anamnese
                            </button>
                            <button @click="showingUploadModal = true" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors shadow-sm flex items-center gap-1.5">📎 Anexar Exame</button>
                            <button @click="showingAiModal = true" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white px-4 py-2 rounded-md font-bold text-sm transition-all shadow-md flex items-center gap-1.5 transform hover:scale-102">💡 Analisar Prontuário</button>
                        </div>

                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 shadow-sm">
                            <h3 class="text-xs font-black text-gray-500 uppercase tracking-wider mb-3">Pesquisar Histórico no Prontuário</h3>
                            <div class="flex flex-col md:flex-row md:items-center gap-4">
                                <div class="flex-1">
                                    <div class="flex bg-white rounded-md shadow-sm border border-gray-200 overflow-hidden w-full">
                                        <button @click="activeTypeFilter = 'all'" :class="activeTypeFilter === 'all' ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'" class="flex-1 py-2 text-xs font-bold transition-colors">Tudo</button>
                                        <button @click="activeTypeFilter = 'anamnesis'" :class="activeTypeFilter === 'anamnesis' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'" class="flex-1 py-2 text-xs font-bold border-l transition-colors">Anamneses</button>
                                        <button @click="activeTypeFilter = 'evolution'" :class="activeTypeFilter === 'evolution' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'" class="flex-1 py-2 text-xs font-bold border-l transition-colors">Evoluções</button>
                                        <button @click="activeTypeFilter = 'prescription'" :class="activeTypeFilter === 'prescription' ? 'bg-emerald-600 text-white' : 'text-gray-600 hover:bg-gray-100'" class="flex-1 py-2 text-xs font-bold border-l transition-colors">Receitas</button>
                                        <button @click="activeTypeFilter = 'file'" :class="activeTypeFilter === 'file' ? 'bg-gray-700 text-white' : 'text-gray-600 hover:bg-gray-100'" class="flex-1 py-2 text-xs font-bold border-l transition-colors">Exames</button>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="date" v-model="filterDate" class="border-gray-200 rounded-md shadow-sm text-sm p-2 w-full md:w-auto" />
                                    <button v-if="filterDate || activeTypeFilter !== 'all'" @click="clearFilters" class="text-xs bg-red-50 text-red-600 font-bold px-3 py-2 rounded-md border border-red-200 hover:bg-red-100 transition-colors">Limpar</button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div v-if="filteredTimeline.length === 0" class="py-12 text-center bg-white border border-gray-200 rounded-lg">
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <h3 class="text-sm font-bold text-gray-800">Nenhum registo encontrado</h3>
                                <p class="text-xs text-gray-400 mt-1">Altere os filtros ou adicione um novo procedimento.</p>
                            </div>

                            <div v-for="item in filteredTimeline" :key="item.item_type + '-' + item.id" class="flex flex-col md:flex-row gap-4 bg-white p-5 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden">
                                
                                <div class="absolute left-0 top-0 bottom-0 w-1.5" :class="{
                                    'bg-indigo-500': item.item_type === 'anamnesis',
                                    'bg-blue-500': item.item_type === 'evolution',
                                    'bg-emerald-500': item.item_type === 'prescription',
                                    'bg-gray-800': item.item_type === 'file'
                                }"></div>

                                <div class="md:w-32 flex-shrink-0 pt-1 pl-2">
                                    <div class="flex flex-row md:flex-col items-center md:items-start gap-2 md:gap-0">
                                        <div class="text-sm font-black text-gray-900">{{ formatDate(item.created_at) }}</div>
                                        <div class="text-xs font-semibold text-gray-400">{{ formatTime(item.created_at) }}</div>
                                        
                                        <span class="md:mt-2 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md" :class="{
                                            'bg-indigo-50 text-indigo-700 border border-indigo-200': item.item_type === 'anamnesis',
                                            'bg-blue-50 text-blue-700 border border-blue-200': item.item_type === 'evolution',
                                            'bg-emerald-50 text-emerald-700 border border-emerald-200': item.item_type === 'prescription',
                                            'bg-gray-100 text-gray-800 border border-gray-300': item.item_type === 'file'
                                        }">
                                            <span v-if="item.item_type === 'anamnesis'">Anamnese</span>
                                            <span v-if="item.item_type === 'evolution'">Evolução</span>
                                            <span v-if="item.item_type === 'prescription'">Receita</span>
                                            <span v-if="item.item_type === 'file'">Exame Anexo</span>
                                        </span>
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div v-if="item.item_type === 'anamnesis'" class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                        <div>
                                            <h4 class="font-bold text-indigo-800">Ficha Clínica Integrativa <span v-if="item.type === 'child'" class="ml-1 text-[9px] bg-pink-100 text-pink-700 px-1.5 py-0.5 rounded">Infantil</span><span v-else class="ml-1 text-[9px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded">Adulto</span></h4>
                                            <p class="text-sm text-gray-600 mt-1 line-clamp-2"><span class="font-semibold text-gray-800">Queixa:</span> {{ item.chief_complaint || 'Não especificada' }}</p>
                                        </div>
                                        <div class="flex gap-2 flex-shrink-0">
                                            <button @click="copySymptoms(item)" class="px-3 py-1.5 text-xs font-bold border rounded-lg transition-colors" :class="copiedAnamnesisId === item.id ? 'bg-green-50 text-green-700 border-green-200' : 'bg-white hover:bg-gray-50 border-gray-200'">{{ copiedAnamnesisId === item.id ? '✔️ Copiado' : 'Copiar Sintomas' }}</button>
                                            <button @click="openAnamnesisModal(item)" class="px-3 py-1.5 text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 rounded-lg transition-colors">Visualizar</button>
                                        </div>
                                    </div>

                                    <div v-if="item.item_type === 'evolution'">
                                        <h4 class="font-bold text-blue-800 mb-2">Anotações Clínicas</h4>
                                        <div class="p-3 bg-blue-50/50 rounded-lg border border-blue-100 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ item.clinical_notes }}</div>
                                    </div>

                                    <div v-if="item.item_type === 'prescription'" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div>
                                            <h4 class="font-bold text-emerald-800">Prescrição / Receituário</h4>
                                            <p class="text-xs font-mono text-emerald-600 mt-1 bg-emerald-50 px-2 py-0.5 rounded inline-block border border-emerald-100">Autenticação: {{ item.verification_code }}</p>
                                        </div>
                                        <button @click="openPrescriptionModal(item)" class="px-3 py-1.5 text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 rounded-lg transition-colors flex-shrink-0">Ver Receita</button>
                                    </div>

                                    <div v-if="item.item_type === 'file'" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-gray-800 text-white p-2.5 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg></div>
                                            <div>
                                                <h4 class="font-bold text-gray-900">{{ item.name }}</h4>
                                                <p v-if="item.notes" class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ item.notes }}</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 flex-shrink-0">
                                            <a :href="`/storage/${item.file_path}`" target="_blank" class="px-3 py-1.5 text-xs font-bold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 rounded-lg transition-colors">Visualizar</a>
                                            <button @click="deleteFile(item.id)" class="px-2 py-1.5 text-xs font-bold text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">✕</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showingUploadModal" @close="showingUploadModal = false" maxWidth="md">
            <div class="p-6"><h3 class="text-lg font-bold mb-4">Anexar Exame</h3><form @submit.prevent="submitUpload" class="space-y-4"><TextInput class="w-full" v-model="uploadForm.name" placeholder="Nome do documento"/><input type="file" @change="handleFileUpload" class="w-full text-xs" required><div class="flex justify-end gap-3 mt-4"><PrimaryButton>Fazer Upload</PrimaryButton></div></form></div>
        </Modal>

        <Modal :show="showingAnamnesisModal" @close="closeAnamnesisModal" maxWidth="3xl">
            <div class="p-0 overflow-hidden bg-gray-50" v-if="selectedAnamnesis">
                <div class="bg-indigo-900 px-6 py-4 flex justify-between items-center text-white">
                    <div>
                        <h2 class="text-lg font-black flex items-center gap-3">
                            Ficha Clínica Integrativa
                            <span v-if="selectedAnamnesis.type === 'child'" class="bg-pink-500 text-white text-[10px] uppercase px-2.5 py-0.5 rounded-full font-black tracking-widest shadow-sm">Infantil PCA</span>
                            <span v-else class="bg-indigo-500 text-white text-[10px] uppercase px-2.5 py-0.5 rounded-full font-black tracking-widest shadow-sm">Adulto</span>
                        </h2>
                        <p class="text-indigo-200 text-xs mt-1 font-medium">Registada a {{ new Date(selectedAnamnesis.created_at).toLocaleDateString('pt-BR') }} às {{ new Date(selectedAnamnesis.created_at).toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'}) }}</p>
                    </div>
                    <button @click="closeAnamnesisModal" class="text-indigo-200 hover:text-white transition-colors">✕</button>
                </div>
                
                <div class="p-6 space-y-6 overflow-y-auto max-h-[70vh] text-sm text-gray-800">
                    
                    <div v-if="selectedAnamnesis.type === 'adult' && selectedAnamnesis.adult_data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-1 md:col-span-2 bg-white p-4 rounded-xl shadow-sm border border-gray-100"><span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Queixa Principal</span><p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.chief_complaint || 'Sem registo' }}</p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100"><span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Rotina e Trabalho</span><p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.patient_routine || 'Sem registo' }}</p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100"><span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Dieta e Alimentação</span><p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.adult_data.diet_routine || 'Sem registo' }}</p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100"><span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Sono e Celular</span><p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.adult_data.sleep_routine || 'Sem registo' }}</p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100"><span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Medicações em Uso</span><p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.adult_data.medications || 'Sem registo' }}</p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100"><span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Histórico Familiar</span><p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.family_history || 'Sem registo' }}</p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100"><span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Outros Detalhes</span><p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">Sol: {{ selectedAnamnesis.adult_data.sun_exposure || '-' }} | Parto/Traumas: {{ selectedAnamnesis.adult_data.birth_type || '-' }} {{ selectedAnamnesis.adult_data.past_trauma || '' }}</p></div>
                    </div>

                    <div v-if="selectedAnamnesis.type === 'child' && selectedAnamnesis.child_data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-1 md:col-span-2 bg-white p-4 rounded-xl shadow-sm border border-gray-100"><span class="font-black text-[10px] uppercase text-pink-500 tracking-wider">Queixa Principal</span><p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.chief_complaint || 'Sem registo' }}</p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100"><span class="font-black text-[10px] uppercase text-pink-500 tracking-wider">Pais e Peso</span><p class="mt-1 font-medium text-gray-900">Responsáveis: {{ selectedAnamnesis.child_data.parents_names || '-' }} <br>Peso: {{ selectedAnamnesis.child_data.weight || '-' }} kg</p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100"><span class="font-black text-[10px] uppercase text-pink-500 tracking-wider">Diagnóstico e Remédios</span><p class="mt-1 font-medium text-gray-900">Diag: {{ selectedAnamnesis.child_data.previous_diagnosis || '-' }}<br>Med: {{ selectedAnamnesis.child_data.supplements || '-' }}</p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100"><span class="font-black text-[10px] uppercase text-pink-500 tracking-wider">Alimentação e Água</span><p class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ selectedAnamnesis.child_data.diet_description || 'Sem registo' }} (Água: {{ selectedAnamnesis.child_data.water_intake || '-' }})</p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100"><span class="font-black text-[10px] uppercase text-pink-500 tracking-wider">Dores e Alergias</span><p class="mt-1 font-medium text-gray-900">Alergias: {{ selectedAnamnesis.child_data.allergies || '-' }}<br>Dores: {{ selectedAnamnesis.child_data.pain_complaint || '-' }}</p></div>
                    </div>

                    <div v-if="selectedAnamnesis.symptoms_checklist && selectedAnamnesis.symptoms_checklist.length > 0" class="pt-4 border-t border-gray-200">
                        <h3 class="font-black text-gray-900 mb-4">Sinais e Sintomas Assinalados ({{ selectedAnamnesis.symptoms_checklist.length }})</h3>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="(symp, idx) in selectedAnamnesis.symptoms_checklist" :key="idx" :class="selectedAnamnesis.type === 'child' ? 'bg-pink-100 text-pink-800 border border-pink-200' : 'bg-indigo-100 text-indigo-800 border border-indigo-200'" class="px-3 py-1.5 text-xs font-bold rounded-lg shadow-sm">{{ symp }}</span>
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
        </Modal>

        <Modal :show="showingPrescriptionModal" @close="closePrescriptionModal" maxWidth="2xl">
            <div class="p-6" v-if="selectedPrescription">
                <div class="border-b-2 border-emerald-500 pb-4 mb-4 flex justify-between items-start">
                    <div><h2 class="text-xl font-bold text-gray-900">Prescrição Médica</h2><p class="text-xs text-gray-500 mt-1">Data: {{ new Date(selectedPrescription.created_at).toLocaleDateString('pt-BR') }}</p></div>
                    <div class="text-right"><span class="text-[9px] uppercase font-bold text-emerald-600 block">Autenticação</span><span class="text-xs font-mono font-bold bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded mt-1 inline-block">{{ selectedPrescription.verification_code }}</span></div>
                </div>
                <div class="space-y-4 mb-6">
                    <div v-if="Array.isArray(selectedPrescription.medications)">
                        <div v-for="(med, idx) in selectedPrescription.medications" :key="idx" class="pl-3 border-l-4 border-emerald-400 py-1 mb-3"><div class="font-bold text-gray-900 text-base">{{ med.name }} <span class="text-xs font-normal text-gray-500">({{ med.dosage }})</span></div><p class="text-sm text-gray-600 mt-0.5">Uso: {{ med.instructions }}</p></div>
                    </div>
                    <div v-else class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ selectedPrescription.notes }}</div>
                </div>
                <div class="mt-6 flex justify-end gap-2 border-t pt-4">
                    <SecondaryButton @click="closePrescriptionModal">Fechar</SecondaryButton>
                    <a :href="route('prescriptions.pdf', selectedPrescription.id)" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md font-medium text-sm flex items-center gap-1.5 shadow-sm">🖨️ Imprimir Receita</a>
                </div>
            </div>
        </Modal>

        <Modal :show="showingAiModal" @close="closeAiModal" maxWidth="2xl">
            <div class="bg-gray-900 rounded-lg flex flex-col h-[70vh]">
                <div class="bg-gray-800 p-4 border-b border-gray-700 flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-3"><div class="bg-purple-600 p-2 rounded-lg shadow-md">💡</div><div><h3 class="text-base font-bold text-white">Assistente IA Curae</h3><p class="text-xs text-gray-400">Paciente ativo: <span class="text-purple-400 font-bold">{{ patient.name }}</span></p></div></div>
                    <button @click="closeAiModal" class="text-gray-400 hover:text-white transition">✕</button>
                </div>
                <div class="p-6 overflow-y-auto flex-1 text-gray-300 text-sm whitespace-pre-wrap">
                    <div v-if="!aiResponse && !isAnalyzing" class="h-full flex items-center justify-center text-gray-500"><p>Como posso ajudar a analisar este prontuário hoje?</p></div>
                    <div v-if="isAnalyzing" class="flex items-center gap-3 text-purple-400 font-bold animate-pulse">A analisar...</div>
                    <div v-if="aiResponse" class="text-gray-200">{{ aiResponse }}</div>
                </div>
                <div class="p-4 bg-gray-800 border-t border-gray-700 shrink-0">
                    <form @submit.prevent="askAI" class="relative"><textarea v-model="aiPrompt" rows="2" placeholder="Digite a sua pergunta..." class="w-full bg-gray-900 border border-gray-600 text-white rounded-lg pl-4 pr-16 py-3 text-sm focus:ring-purple-500" @keydown.enter.prevent="askAI"></textarea><button type="submit" :disabled="isAnalyzing || !aiPrompt.trim()" class="absolute right-2 bottom-2 top-2 bg-purple-600 text-white px-4 rounded-md font-bold flex items-center justify-center">▶</button></form>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>