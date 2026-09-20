<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch, nextTick } from 'vue'; // Adicionado nextTick
import axios from 'axios';

const props = defineProps({
    patient: Object,
    anamneses: Array,
    evolutions: Array,
    prescriptions: Array,
    files: Array,
    last_anamnesis_date: String,
    last_appointment_date: String,
});

const page = usePage();

// ==========================================
// 🔐 CONTROLO DE ACESSO BASEADO EM PERFIS (RBAC)
// ==========================================
const hasRole = (rolesAllowed) => {
    const userRoles = page.props.auth?.roles || [];
    if (userRoles.includes('admin')) return true;
    return rolesAllowed.some(role => userRoles.includes(role));
};

const age = computed(() => {
    if (!props.patient.date_of_birth) return 'Idade não informada';
    const today = new Date(); const birthDate = new Date(props.patient.date_of_birth);
    let currentAge = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) currentAge--;
    return `${currentAge} anos`;
});

// ==========================================
// 📄 GESTÃO DE IMPRESSÃO DE PDF (RECEITAS)
// ==========================================
const verifyAndOpenFlashPdf = () => {
    if (page.props.flash?.open_prescription_pdf) {
        window.open(page.props.flash.open_prescription_pdf, '_blank');
    }
};

onMounted(() => {
    verifyAndOpenFlashPdf();
});

watch(() => page.props.flash, () => {
    verifyAndOpenFlashPdf();
}, { deep: true });

// ==========================================
// 🕒 LINHA DO TEMPO CRONOLÓGICA UNIFICADA
// ==========================================
const activeTypeFilter = ref('all');
const filterDate = ref('');

const formatDate = (dateString) => new Date(dateString).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
const formatTime = (dateString) => new Date(dateString).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

const formatDateLong = (dateString) => {
    if (!dateString) return 'Nenhum registro encontrado';
    return new Date(dateString).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const allTimelineItems = computed(() => {
    const items = [];
    if (hasRole(['medico', 'enfermeira', 'nutricionista', 'fisioterapeuta'])) {
        (props.anamneses || []).forEach(a => items.push({ ...a, item_type: 'anamnesis', sort_date: new Date(a.created_at) }));
        (props.evolutions || []).forEach(e => items.push({ ...e, item_type: 'evolution', sort_date: new Date(e.created_at) }));
        (props.prescriptions || []).forEach(p => items.push({ ...p, item_type: 'prescription', sort_date: new Date(p.created_at) }));
        (props.files || []).forEach(f => items.push({ ...f, item_type: 'file', sort_date: new Date(f.created_at) }));
    }
    return items.sort((a, b) => b.sort_date - a.sort_date);
});

const filteredTimeline = computed(() => {
    return allTimelineItems.value.filter(item => {
        if (activeTypeFilter.value !== 'all' && item.item_type !== activeTypeFilter.value) return false;
        
        if (filterDate.value) {
            const itemDate = new Date(item.created_at);
            const localYear = itemDate.getFullYear();
            const localMonth = String(itemDate.getMonth() + 1).padStart(2, '0');
            const localDay = String(itemDate.getDate()).padStart(2, '0');
            if (`${localYear}-${localMonth}-${localDay}` !== filterDate.value) return false;
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
    if (!anamnese.symptoms_checklist || anamnese.symptoms_checklist.length === 0) {
        alert('Nenhum sintoma assinalado nesta anamnese para copiar.');
        return;
    }
    navigator.clipboard.writeText(anamnese.symptoms_checklist.join(', ')).then(() => {
        copiedAnamnesisId.value = anamnese.id;
        setTimeout(() => copiedAnamnesisId.value = null, 2000);
    });
};

// ==========================================
// 📱 SOLICITAÇÃO DE ANAMNESE VIA WHATSAPP
// ==========================================
const isGeneratingLink = ref(false);
const sendAnamnesisLink = async () => {
    isGeneratingLink.value = true;
    try {
        const response = await axios.post(route('patients.anamnesis.link', props.patient.id));
        const url = response.data.url;
        
        const phone = props.patient.phone ? props.patient.phone.replace(/\D/g, '') : '';
        const message = `Olá! Por favor, preencha a sua Ficha de Triagem Clínica online antes da nossa consulta. É rápido e seguro.\n\nAcesse o link abaixo:\n${url}`;
        
        const whatsappUrl = phone 
            ? `https://wa.me/55${phone}?text=${encodeURIComponent(message)}` 
            : `https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`;
            
        window.open(whatsappUrl, '_blank');
    } catch (error) {
        alert('Ocorreu um erro ao gerar o link de preenchimento.');
    } finally {
        isGeneratingLink.value = false;
    }
};

// ==========================================
// 📎 GESTÃO DE ARQUIVOS / ANEXOS
// ==========================================
const showingUploadModal = ref(false);
const uploadForm = useForm({ patient_id: props.patient.id, name: '', file: null, notes: '' });
const handleFileUpload = (e) => uploadForm.file = e.target.files[0];
const submitUpload = () => {
    uploadForm.post(route('patient-files.store'), {
        preserveScroll: true,
        onSuccess: () => { showingUploadModal.value = false; uploadForm.reset(); }
    });
};
const deleteFile = (id) => {
    if (confirm('Tem a certeza que deseja remover este documento permanentemente?')) {
        router.delete(route('patient-files.destroy', id), { preserveScroll: true });
    }
};

// ==========================================
// 🔍 VISUALIZAÇÃO DE MODAIS
// ==========================================
const showingAnamnesisModal = ref(false); const selectedAnamnesis = ref(null);
const openAnamnesisModal = (anamnese) => { selectedAnamnesis.value = anamnese; showingAnamnesisModal.value = true; };
const closeAnamnesisModal = () => { showingAnamnesisModal.value = false; setTimeout(() => selectedAnamnesis.value = null, 300); };

// ==========================================
// 🤖 ASSISTENTE DE IA DE APOIO CLINICO
// ==========================================
const showingAiModal = ref(false); 
const aiPrompt = ref(''); 
const aiResponse = ref(''); 
const isAnalyzing = ref(false);

// Referência para o campo de texto
const promptInput = ref(null);

const openAiModal = () => {
    showingAiModal.value = true;
    // Foca no input assim que abrir o modal
    nextTick(() => {
        if (promptInput.value) promptInput.value.focus();
    });
};

const askAI = async () => {
    if (!aiPrompt.value.trim() || isAnalyzing.value) return;
    
    // Guarda a pergunta original
    const question = aiPrompt.value; 
    
    // Limpa o input e devolve o foco
    aiPrompt.value = '';
    nextTick(() => {
        if (promptInput.value) promptInput.value.focus();
    });

    isAnalyzing.value = true; 
    aiResponse.value = '';
    
    // Usa a variável que guardou a pergunta
    const context = `[DADOS CLÍNICOS DO PACIENTE]\nNome: ${props.patient.name}\nIdade: ${age.value}\n\n[SOLICITAÇÃO MÉDICA]:\n${question}`;
    
    try {
        const response = await axios.post(route('ai.analyze'), { prompt: context });
        aiResponse.value = response.data.response;
    } catch (error) {
        aiResponse.value = '⚠️ Não foi possível processar a análise clínica através da IA no momento.';
    } finally {
        isAnalyzing.value = false;
        // Devolve o foco novamente após processar a resposta
        nextTick(() => {
            if (promptInput.value) promptInput.value.focus();
        });
    }
};

const closeAiModal = () => { 
    showingAiModal.value = false; 
    setTimeout(() => { 
        aiPrompt.value = ''; 
        aiResponse.value = ''; 
    }, 300); 
};
</script>

<template>
    <Head :title="`Prontuário - ${patient.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Prontuário Clínico: {{ patient.name }}</h2>
                <Link :href="route('patients.index')" class="text-gray-600 hover:underline text-sm font-medium">&larr; Voltar para o Diretório</Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="md:col-span-1">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl p-6 sticky top-6 border border-gray-100">
                            <h3 class="text-lg font-black text-gray-900 border-b pb-2 mb-4">Dados Fundamentais</h3>
                            <div class="space-y-4 text-sm">
                                <p><span class="font-bold text-gray-500 uppercase text-[10px]">Nome do Paciente:</span> <br> <span class="font-black text-gray-900 text-base leading-tight block mt-0.5">{{ patient.name }}</span></p>
                                <p><span class="font-bold text-gray-500 uppercase text-[10px]">Idade Calculada:</span> <br> <span class="font-semibold text-gray-900">{{ age }}</span></p>
                                <p><span class="font-bold text-gray-500 uppercase text-[10px]">CPF do Paciente:</span> <br> <span class="font-semibold text-gray-900">{{ patient.cpf }}</span></p>
                                <p><span class="font-bold text-gray-500 uppercase text-[10px]">Telemóvel / Contato:</span> <br> <span class="font-semibold text-gray-900">{{ patient.phone || 'Não informado' }}</span></p>
                                
                                <div class="pt-4 border-t border-gray-100 space-y-3 bg-gray-50/40 p-3 rounded-xl border">
                                    <p><span class="font-bold text-indigo-600 uppercase text-[10px] tracking-wider">Última Consulta:</span> <br> <span class="font-bold text-gray-800">{{ formatDateLong(last_appointment_date) }}</span></p>
                                    <p><span class="font-bold text-indigo-600 uppercase text-[10px] tracking-wider">Última Anamnese:</span> <br> <span class="font-bold text-gray-800">{{ formatDateLong(last_anamnesis_date) }}</span></p>
                                </div>
                            </div>
                            <div class="mt-6 pt-4 border-t" v-if="hasRole(['secretaria'])">
                                <Link :href="route('patients.edit', patient.id)" class="text-indigo-600 hover:text-indigo-800 text-sm font-bold transition-colors">Editar Ficha Cadastral</Link>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 space-y-6">
                        
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl p-6 flex flex-wrap gap-3 border border-gray-100">
                            <Link v-if="hasRole(['medico', 'enfermeira', 'nutricionista', 'fisioterapeuta'])" :href="route('evolutions.create', { patient_id: patient.id })" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-bold text-sm transition-colors shadow-sm">
                                + Nova Evolução
                            </Link>
                            
                            <Link v-if="hasRole(['medico'])" :href="route('prescriptions.create', { patient_id: patient.id })" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg font-bold text-sm transition-colors shadow-sm flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Nova Receita
                            </Link>
                            
                            <Link v-if="hasRole(['medico', 'enfermeira', 'nutricionista'])" :href="route('anamneses.create', { patient_id: patient.id })" class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2.5 rounded-lg font-bold text-sm border border-gray-300 shadow-sm">
                                Preencher Anamnese
                            </Link>
                            
                            <button v-if="hasRole(['secretaria', 'medico', 'enfermeira', 'nutricionista'])" @click="sendAnamnesisLink" :disabled="isGeneratingLink" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2.5 rounded-lg font-bold text-sm transition-colors shadow-sm flex items-center gap-1.5 disabled:opacity-50">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="16" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16"><path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/></svg>
                                Solicitar Ficha (WhatsApp)
                            </button>
                            <Link v-if="hasRole(['medico', 'enfermeira', 'nutricionista', 'fisioterapeuta'])" :href="route('patients.history', patient.id)" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2.5 rounded-lg font-bold text-sm transition-colors border border-gray-300 shadow-sm flex items-center gap-1.5">
                                📑 Resumo Completo
                            </Link>
                            
                            <button v-if="hasRole(['medico', 'enfermeira', 'nutricionista'])" @click="showingUploadModal = true" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2.5 rounded-lg font-bold text-sm transition-colors shadow-sm">📎 Anexar Exame</button>
                            
                            <button v-if="hasRole(['medico'])" @click="openAiModal" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-4 py-2.5 rounded-lg font-black text-sm shadow-md flex items-center gap-1.5 hover:shadow-lg transition-all">✨ Analisar Prontuário</button>
                        </div>

                        <div v-if="hasRole(['medico', 'enfermeira', 'nutricionista', 'fisioterapeuta'])" class="bg-gray-50 border border-gray-200 rounded-2xl p-5 shadow-sm">
                            <h3 class="text-xs font-black text-gray-500 uppercase tracking-wider mb-3">Pesquisar Histórico por Filtros</h3>
                            <div class="flex flex-col md:flex-row md:items-center gap-4">
                                <div class="flex-1">
                                    <div class="flex bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden w-full">
                                        <button @click="activeTypeFilter = 'all'" :class="activeTypeFilter === 'all' ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'" class="flex-1 py-2 text-xs font-bold transition-colors">Tudo</button>
                                        <button @click="activeTypeFilter = 'anamnesis'" :class="activeTypeFilter === 'anamnesis' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'" class="flex-1 py-2 text-xs font-bold border-l transition-colors">Anamneses</button>
                                        <button @click="activeTypeFilter = 'evolution'" :class="activeTypeFilter === 'evolution' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'" class="flex-1 py-2 text-xs font-bold border-l transition-colors">Evoluções</button>
                                        <button @click="activeTypeFilter = 'prescription'" :class="activeTypeFilter === 'prescription' ? 'bg-emerald-600 text-white' : 'text-gray-600 hover:bg-gray-100'" class="flex-1 py-2 text-xs font-bold border-l transition-colors">Receitas</button>
                                        <button @click="activeTypeFilter = 'file'" :class="activeTypeFilter === 'file' ? 'bg-gray-700 text-white' : 'text-gray-600 hover:bg-gray-100'" class="flex-1 py-2 text-xs font-bold border-l transition-colors">Exames</button>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="date" v-model="filterDate" class="border-gray-200 rounded-lg shadow-sm text-sm p-2 w-full md:w-auto bg-white" />
                                    <button v-if="filterDate || activeTypeFilter !== 'all'" @click="clearFilters" class="text-xs bg-red-50 text-red-600 font-bold px-3 py-2 rounded-lg border border-red-200 hover:bg-red-100 transition-colors">Limpar</button>
                                </div>
                            </div>
                        </div>

                        <div v-if="hasRole(['medico', 'enfermeira', 'nutricionista', 'fisioterapeuta'])" class="space-y-4">
                            <div v-if="filteredTimeline.length === 0" class="py-16 text-center bg-white border border-gray-100 rounded-2xl shadow-sm">
                                <h3 class="text-sm font-bold text-gray-800">Nenhum registo localizado</h3>
                                <p class="text-xs text-gray-400 mt-1">O histórico clínico encontra-se limpo para as seleções estipuladas.</p>
                            </div>

                            <div v-for="item in filteredTimeline" :key="item.item_type + '-' + item.id" class="flex flex-col md:flex-row gap-4 bg-white p-5 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden">
                                
                                <div class="absolute left-0 top-0 bottom-0 w-1.5" :class="{
                                    'bg-indigo-500': item.item_type === 'anamnesis',
                                    'bg-blue-500': item.item_type === 'evolution',
                                    'bg-emerald-500': item.item_type === 'prescription',
                                    'bg-gray-800': item.item_type === 'file'
                                }"></div>

                                <div class="md:w-32 flex-shrink-0 pt-1 pl-2">
                                    <div class="flex flex-row md:flex-col items-center md:items-start gap-2 md:gap-0">
                                        <div class="text-sm font-black text-gray-900">{{ formatDate(item.created_at) }}</div>
                                        <div class="text-xs font-bold text-gray-400">{{ formatTime(item.created_at) }}</div>
                                        
                                        <span class="md:mt-2 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md border" :class="{
                                            'bg-indigo-50 text-indigo-700 border-indigo-200': item.item_type === 'anamnesis',
                                            'bg-blue-50 text-blue-700 border-blue-200': item.item_type === 'evolution',
                                            'bg-emerald-50 text-emerald-700 border-emerald-200': item.item_type === 'prescription',
                                            'bg-gray-100 text-gray-800 border-gray-300': item.item_type === 'file'
                                        }">
                                            {{ item.item_type }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div v-if="item.item_type === 'anamnesis'" class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                        <div>
                                            <h4 class="font-bold text-indigo-800">Ficha Clínica / Triagem <span v-if="item.type === 'child'" class="ml-1 text-[9px] bg-pink-100 text-pink-700 px-1.5 py-0.5 rounded">Pediátrica</span></h4>
                                            <p class="text-sm text-gray-600 mt-1 line-clamp-2"><span class="font-semibold text-gray-800">Motivo:</span> {{ item.chief_complaint || 'Não declarada' }}</p>
                                        </div>
                                        <div class="flex gap-2 flex-shrink-0">
                                            <button @click="copySymptoms(item)" class="px-3 py-1.5 text-xs font-bold border rounded-lg bg-white shadow-sm hover:bg-gray-50">{{ copiedAnamnesisId === item.id ? '✔️ Copiado' : 'Sintomas' }}</button>
                                            <button @click="openAnamnesisModal(item)" class="px-3 py-1.5 text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg shadow-sm transition-colors">Visualizar</button>
                                        </div>
                                    </div>

                                    <div v-if="item.item_type === 'evolution'">
                                        <h4 class="font-bold text-blue-800 mb-2">Evolução de Prontuário</h4>
                                        <div class="p-3 bg-blue-50/40 rounded-lg border border-blue-100 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ item.clinical_notes }}</div>
                                    </div>

                                    <div v-if="item.item_type === 'prescription'" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div>
                                            <h4 class="font-bold text-emerald-800">Prescrição Médica e Formulações</h4>
                                            <p class="text-xs font-mono text-emerald-600 mt-1 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100 inline-block">Cód. Autenticação: {{ item.verification_code }}</p>
                                        </div>
                                        <a :href="route('prescriptions.pdf', item.id)" target="_blank" class="px-4 py-2 text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg flex-shrink-0 transition-colors shadow-sm text-center flex items-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            Visualizar PDF
                                        </a>
                                    </div>

                                    <div v-if="item.item_type === 'file'" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="text-gray-500 font-bold text-xl">📎</div>
                                            <div>
                                                <h4 class="font-bold text-gray-900">{{ item.name }}</h4>
                                                <p v-if="item.notes" class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ item.notes }}</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 flex-shrink-0">
                                            <a :href="`/storage/${item.file_path}`" target="_blank" class="px-3 py-1.5 text-xs font-bold bg-white border border-gray-300 rounded-lg hover:bg-gray-50 shadow-sm">Ver Ficheiro</a>
                                            <button @click="deleteFile(item.id)" class="px-2 text-xs font-bold text-red-500 hover:bg-red-50 rounded-lg">✕</button>
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
            <div class="p-6"><h3 class="text-lg font-bold mb-4">Vincular Anexo de Laudo</h3><form @submit.prevent="submitUpload" class="space-y-4"><TextInput class="w-full" v-model="uploadForm.name" placeholder="Descrição do Documento"/><input type="file" @change="handleFileUpload" class="w-full text-xs" required><div class="flex justify-end gap-3 mt-4"><PrimaryButton>Concluir Transmissão</PrimaryButton></div></form></div>
        </Modal>

        <Modal :show="showingAnamnesisModal" @close="closeAnamnesisModal" maxWidth="3xl">
            <div class="p-0 overflow-hidden bg-gray-50" v-if="selectedAnamnesis">
                <div class="bg-indigo-900 px-6 py-4 flex justify-between items-center text-white">
                    <h2 class="text-lg font-black flex items-center gap-3">
                        Ficha Clínica Integrativa
                        <span v-if="selectedAnamnesis.type === 'child'" class="bg-pink-500 text-white text-[10px] uppercase px-2.5 py-0.5 rounded-full font-black tracking-widest">Infantil PCA</span>
                        <span v-else class="bg-indigo-500 text-white text-[10px] uppercase px-2.5 py-0.5 rounded-full font-black tracking-widest">Adulto</span>
                    </h2>
                    <button @click="closeAnamnesisModal" class="text-indigo-200 hover:text-white">✕</button>
                </div>
                
                <div class="p-6 space-y-4 overflow-y-auto max-h-[65vh] text-sm">
                    <div v-if="selectedAnamnesis.type === 'adult' && selectedAnamnesis.adult_data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-1 md:col-span-2 bg-white p-4 rounded-xl border"><span class="font-black text-[10px] uppercase text-indigo-500">Queixa Principal</span><p class="mt-1 font-medium text-gray-900">{{ selectedAnamnesis.chief_complaint || '-' }}</p></div>
                        <div class="bg-white p-4 rounded-xl border"><span class="font-black text-[10px] uppercase text-indigo-500">Alimentação</span><p class="mt-1 font-medium text-gray-900">{{ selectedAnamnesis.adult_data.diet_routine || '-' }}</p></div>
                        <div class="bg-white p-4 rounded-xl border"><span class="font-black text-[10px] uppercase text-indigo-500">Sono e Descanso</span><p class="mt-1 font-medium text-gray-900">{{ selectedAnamnesis.adult_data.sleep_routine || '-' }}</p></div>
                        <div class="bg-white p-4 rounded-xl border"><span class="font-black text-[10px] uppercase text-indigo-500">Estilo de Vida</span><p class="mt-1 font-medium text-gray-900">{{ selectedAnamnesis.patient_routine || '-' }}</p></div>
                        <div class="bg-white p-4 rounded-xl border"><span class="font-black text-[10px] uppercase text-indigo-500">Medicação / Suplementação</span><p class="mt-1 font-medium text-gray-900">{{ selectedAnamnesis.adult_data.medications || '-' }}</p></div>
                    </div>

                    <div v-if="selectedAnamnesis.type === 'child' && selectedAnamnesis.child_data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-1 md:col-span-2 bg-white p-4 rounded-xl border"><span class="font-black text-[10px] uppercase text-pink-500">Queixa Pediátrica</span><p class="mt-1 font-medium text-gray-900">{{ selectedAnamnesis.chief_complaint || '-' }}</p></div>
                        <div class="bg-white p-4 rounded-xl border"><span class="font-black text-[10px] uppercase text-pink-500">Responsáveis e Peso</span><p class="mt-1 font-medium text-gray-900">Pais: {{ selectedAnamnesis.child_data.parents_names || '-' }} | Peso: {{ selectedAnamnesis.child_data.weight || '-' }} kg</p></div>
                        <div class="bg-white p-4 rounded-xl border"><span class="font-black text-[10px] uppercase text-pink-500">Nutrição Infantil</span><p class="mt-1 font-medium text-gray-900">{{ selectedAnamnesis.child_data.diet_description || '-' }}</p></div>
                    </div>

                    <div v-if="selectedAnamnesis.symptoms_checklist && selectedAnamnesis.symptoms_checklist.length > 0" class="pt-2">
                        <span class="font-black text-[10px] text-gray-500 uppercase block mb-2">Sinais e Disfunções Identificadas</span>
                        <div class="p-4 border rounded-xl bg-white flex flex-wrap gap-2">
                            <span v-for="(symp, idx) in selectedAnamnesis.symptoms_checklist" :key="idx" :class="selectedAnamnesis.type === 'child' ? 'bg-pink-50 text-pink-800 border-pink-200' : 'bg-indigo-50 text-indigo-800 border-indigo-200'" class="px-3 py-1.5 text-xs font-bold rounded-lg border">{{ symp }}</span>
                        </div>
                    </div>
                </div>
                <div class="p-4 bg-white border-t flex justify-end gap-2">
                    <button @click="copySymptoms(selectedAnamnesis)" class="px-4 py-2 font-bold rounded-xl border bg-gray-50 hover:bg-gray-100 text-gray-700 text-sm">Copiar Sintomas</button>
                    <button @click="closeAnamnesisModal" class="px-4 py-2 font-bold rounded-xl bg-gray-900 text-white text-sm hover:bg-black">Fechar Ficha</button>
                </div>
            </div>
        </Modal>

        <Modal :show="showingAiModal" @close="closeAiModal" maxWidth="2xl">
            <div class="bg-gray-900 rounded-2xl flex flex-col h-[75vh] shadow-2xl overflow-hidden border border-gray-700">
                <div class="bg-gray-800 p-5 flex justify-between items-center text-white border-b border-gray-700">
                    <h3 class="font-black flex items-center gap-2"><span class="text-purple-400">✨</span> Análise de Prontuário Assistida por IA</h3>
                    <button @click="closeAiModal" class="text-gray-400 hover:text-white font-bold">✕</button>
                </div>
                <div class="p-6 overflow-y-auto flex-1 text-gray-300 text-sm whitespace-pre-wrap leading-relaxed">
                    <div v-if="isAnalyzing" class="flex flex-col items-center justify-center h-full gap-4 opacity-75">
                        <svg class="animate-spin h-8 w-8 text-purple-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        A processar análises e cruzar dados clínicos...
                    </div>
                    <div v-else-if="aiResponse" class="bg-gray-800 p-5 rounded-xl border border-gray-700 shadow-inner">{{ aiResponse }}</div>
                    <div v-else class="flex flex-col items-center justify-center h-full gap-2 text-center text-gray-500">
                        <span class="text-4xl opacity-20">🧠</span>
                        <p class="font-bold text-gray-400">Submeta uma pergunta clínica estruturada.</p>
                    </div>
                </div>
                <div class="p-4 bg-gray-800 border-t border-gray-700">
                    <form @submit.prevent="askAI" class="relative">
                        <textarea 
                            ref="promptInput"
                            v-model="aiPrompt" 
                            rows="2" 
                            placeholder="Ex: Há correlação entre a fadiga relatada nas últimas anamneses e os padrões de sono?" 
                            class="w-full bg-gray-900 border-gray-700 focus:border-purple-500 focus:ring-purple-500 text-white rounded-xl text-sm pr-12 shadow-inner resize-none" 
                            @keydown.enter.prevent="askAI">
                        </textarea>
                        <button type="submit" class="absolute right-3 bottom-4 text-purple-400 hover:text-purple-300 font-bold p-2 bg-gray-800 rounded-lg transition-colors">▶</button>
                    </form>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>