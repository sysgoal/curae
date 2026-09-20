<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
    patient: Object,
    anamneses: Array,
    evolutions: Array,
    prescriptions: Array,
    examRequests: Array,
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

// Iniciais do paciente para o Avatar
const patientInitials = computed(() => {
    if (!props.patient?.name) return 'PT';
    return props.patient.name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
});

// Controle do Menu Dropdown de Ações
const showingActionMenu = ref(false);
const closeActionMenu = () => showingActionMenu.value = false;

// ==========================================
// 📄 GESTÃO DE IMPRESSÃO DE PDF (RECEITAS)
// ==========================================
const verifyAndOpenFlashPdf = () => {
    if (page.props.flash?.open_prescription_pdf) {
        window.open(page.props.flash.open_prescription_pdf, '_blank');
    }
    if (page.props.flash?.open_exam_pdf) {
        window.open(page.props.flash.open_exam_pdf, '_blank'); 
    }
};

onMounted(() => { verifyAndOpenFlashPdf(); });
watch(() => page.props.flash, () => { verifyAndOpenFlashPdf(); }, { deep: true });



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
        (props.examRequests || []).forEach(er => items.push({ ...er, item_type: 'exam_request', sort_date: new Date(er.created_at) }));
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
    closeActionMenu();
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
const openUploadModal = () => { closeActionMenu(); showingUploadModal.value = true; };
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
const promptInput = ref(null);

const openAiModal = () => {
    showingAiModal.value = true;
    nextTick(() => { if (promptInput.value) promptInput.value.focus(); });
};

const askAI = async () => {
    if (!aiPrompt.value.trim() || isAnalyzing.value) return;
    
    const question = aiPrompt.value; 
    aiPrompt.value = ''; 
    nextTick(() => { if (promptInput.value) promptInput.value.focus(); });

    isAnalyzing.value = true; 
    aiResponse.value = '';
    
    // ==========================================
    // EXTRAÇÃO DO HISTÓRICO PARA A IA LER
    // ==========================================
    let historyText = '';
    
    if (allTimelineItems.value && allTimelineItems.value.length > 0) {
        // Pega no máximo os últimos 15 registros para não sobrecarregar a memória da IA
        const recentHistory = allTimelineItems.value.slice(0, 15);
        
        historyText = recentHistory.map(item => {
            let record = `Data: ${formatDate(item.created_at)} | Tipo: ${item.item_type.toUpperCase()}\n`;
            
            if (item.item_type === 'anamnesis') {
                record += `- Queixa Principal: ${item.chief_complaint || 'Não informada'}\n`;
                if (item.symptoms_checklist && item.symptoms_checklist.length > 0) {
                    record += `- Sintomas Assinalados: ${item.symptoms_checklist.join(', ')}\n`;
                }
            } else if (item.item_type === 'evolution') {
                record += `- Anotações Clínicas: ${item.clinical_notes}\n`;
            } else if (item.item_type === 'prescription') {
                record += `- Ação: Receita prescrita ao paciente.\n`;
            } else if (item.item_type === 'file') {
                record += `- Exame/Arquivo anexado: ${item.name}\n`;
            }
            return record;
        }).join('\n\n');
    } else {
        historyText = "O paciente ainda não possui histórico clínico no sistema.";
    }
    
    // ==========================================
    // MONTAGEM DO CONTEXTO RICO (PROMPT MESTRE)
    // ==========================================
    const context = `[DADOS BÁSICOS DO PACIENTE]
Nome: ${props.patient.name}
Idade: ${age.value}

[HISTÓRICO CLÍNICO CRONOLÓGICO (Mais recente primeiro)]
${historyText}

[PERGUNTA/SOLICITAÇÃO DO MÉDICO BASEADA NO HISTÓRICO ACIMA]
${question}`;
    
    // Envio para o Backend (Llama/Ollama)
    try {
        const response = await axios.post(route('ai.analyze'), { prompt: context });
        aiResponse.value = response.data.response;
    } catch (error) {
        aiResponse.value = '⚠️ Não foi possível processar a análise clínica através da IA no momento. Verifique a conexão com o Ollama.';
    } finally {
        isAnalyzing.value = false;
        nextTick(() => { if (promptInput.value) promptInput.value.focus(); });
    }
};
const closeAiModal = () => { 
    showingAiModal.value = false; 
    setTimeout(() => { aiPrompt.value = ''; aiResponse.value = ''; }, 300); 
};
</script>

<template>
    <Head :title="`Prontuário - ${patient.name}`" />

    <AuthenticatedLayout>
        <!-- Fundo de ecrã para fechar o menu ao clicar fora -->
        <div v-if="showingActionMenu" @click="closeActionMenu" class="fixed inset-0 z-40"></div>

        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('patients.index')" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </Link>
                    <h2 class="font-bold text-xl text-gray-800 leading-tight">Visão Geral do Paciente</h2>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    
                    <!-- COLUNA ESQUERDA: PERFIL DO PACIENTE -->
                    <div class="lg:col-span-4">
                        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden sticky top-8">
                            <!-- Header do Cartão -->
                            <div class="h-24 bg-gradient-to-r from-indigo-500 to-purple-600"></div>
                            
                            <div class="px-6 pb-6 relative">
                                <!-- Avatar -->
                                <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center shadow-md border-4 border-white -mt-10 mx-auto">
                                    <div class="w-full h-full bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl font-black">
                                        {{ patientInitials }}
                                    </div>
                                </div>
                                
                                <div class="text-center mt-3 mb-6">
                                    <h3 class="text-xl font-black text-gray-900">{{ patient.name }}</h3>
                                    <p class="text-sm font-medium text-gray-500">{{ age }}</p>
                                </div>

                                <div class="space-y-4 text-sm bg-gray-50/50 p-4 rounded-2xl border border-gray-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-gray-400 shadow-sm border border-gray-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">CPF</p>
                                            <p class="font-semibold text-gray-800">{{ patient.cpf }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-gray-400 shadow-sm border border-gray-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Contato</p>
                                            <p class="font-semibold text-gray-800">{{ patient.phone || 'Não informado' }}</p>
                                        </div>
                                    </div>

                                    <div class="pt-2">
                                        <div class="flex justify-between items-center text-xs py-1.5 border-b border-gray-100">
                                            <span class="text-gray-500 font-medium">Última Consulta:</span>
                                            <span class="font-bold text-gray-900">{{ formatDateLong(last_appointment_date) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-xs py-1.5">
                                            <span class="text-gray-500 font-medium">Última Anamnese:</span>
                                            <span class="font-bold text-gray-900">{{ formatDateLong(last_anamnesis_date) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 text-center" v-if="hasRole(['secretaria', 'admin'])">
                                    <Link :href="route('patients.edit', patient.id)" class="text-indigo-600 hover:text-indigo-800 text-xs font-bold transition-colors uppercase tracking-wider">
                                        Editar Cadastro do Paciente
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUNA DIREITA: AÇÕES E TIMELINE -->
                    <div class="lg:col-span-8 space-y-6">
                        
                        <!-- BARRA DE AÇÕES SUPERIOR -->
                        <div class="bg-white rounded-3xl p-4 sm:p-5 flex flex-col sm:flex-row justify-between items-center gap-4 shadow-sm border border-gray-100">
                            <h3 class="font-black text-gray-800 text-lg">Prontuário</h3>
                            
                            <div class="flex items-center gap-3 w-full sm:w-auto">
                                
                                <!-- BOTÃO MENU DE AÇÕES -->
                                <div class="relative w-full sm:w-auto z-50">
                                    <button @click="showingActionMenu = !showingActionMenu" class="w-full sm:w-auto bg-gray-900 hover:bg-black text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all shadow-sm flex items-center justify-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                        Ações Clínicas
                                    </button>

                                    <!-- DROPDOWN DE OPÇÕES -->
                                    <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                                        <div v-if="showingActionMenu" class="absolute right-0 mt-2 w-56 rounded-2xl shadow-xl bg-white border border-gray-100 overflow-hidden">
                                            <div class="p-2 space-y-1">
                                                
                                                <Link v-if="hasRole(['medico', 'enfermeira', 'nutricionista', 'fisioterapeuta'])" :href="route('evolutions.create', { patient_id: patient.id })" class="flex items-center gap-3 w-full text-left px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-indigo-600 rounded-xl transition-colors">
                                                    <span class="text-lg bg-gray-100 p-1 rounded-lg">📝</span> Evolução Clínica
                                                </Link>
                                                
                                                <Link v-if="hasRole(['medico'])" :href="route('prescriptions.create', { patient_id: patient.id })" class="flex items-center gap-3 w-full text-left px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-emerald-600 rounded-xl transition-colors">
                                                    <span class="text-lg bg-gray-100 p-1 rounded-lg">💊</span> Nova Receita
                                                </Link>

                                                <Link v-if="hasRole(['medico'])" :href="route('exam-requests.create', { patient_id: patient.id })" class="flex items-center gap-3 w-full text-left px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-amber-600 rounded-xl transition-colors">
                                                    <span class="text-lg bg-gray-100 p-1 rounded-lg">🔬</span> Solicitar Exames
                                                </Link>

                                                <Link v-if="hasRole(['medico', 'enfermeira', 'nutricionista'])" :href="route('anamneses.create', { patient_id: patient.id })" class="flex items-center gap-3 w-full text-left px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded-xl transition-colors">
                                                    <span class="text-lg bg-gray-100 p-1 rounded-lg">📋</span> Fazer Anamnese
                                                </Link>

                                                <div class="h-px bg-gray-100 my-1"></div>

                                                <button v-if="hasRole(['medico', 'enfermeira', 'nutricionista'])" @click="openUploadModal" class="flex items-center gap-3 w-full text-left px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 rounded-xl transition-colors">
                                                    <span class="text-lg bg-gray-100 p-1 rounded-lg">📎</span> Anexar Exame
                                                </button>

                                                <button v-if="hasRole(['secretaria', 'medico', 'enfermeira', 'nutricionista'])" @click="sendAnamnesisLink" :disabled="isGeneratingLink" class="flex items-center gap-3 w-full text-left px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-green-50 hover:text-green-700 rounded-xl transition-colors disabled:opacity-50">
                                                    <span class="text-lg bg-green-100 p-1 rounded-lg text-green-600"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/></svg></span>
                                                    Pedir Ficha (ZAP)
                                                </button>

                                                <Link v-if="hasRole(['medico', 'enfermeira', 'nutricionista', 'fisioterapeuta'])" :href="route('patients.history', patient.id)" class="flex items-center gap-3 w-full text-left px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 rounded-xl transition-colors">
                                                    <span class="text-lg bg-gray-100 p-1 rounded-lg">🖨️</span> Gerar Resumo PDF
                                                </Link>
                                            </div>
                                        </div>
                                    </transition>
                                </div>

                                <!-- BOTÃO IA ISOLADO E DESTACADO -->
                                <button v-if="hasRole(['medico'])" @click="openAiModal" class="w-full sm:w-auto bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 px-5 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2">
                                    <span class="text-lg">✨</span> Analisar IA
                                </button>
                            </div>
                        </div>

                        <!-- BARRA DE FILTROS MODERNOS (Pills) -->
                        <div v-if="hasRole(['medico', 'enfermeira', 'nutricionista', 'fisioterapeuta'])" class="bg-transparent flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            
                            <div class="flex overflow-x-auto hide-scrollbar gap-2 pb-2 sm:pb-0 w-full sm:w-auto">
                                <button @click="activeTypeFilter = 'all'" :class="activeTypeFilter === 'all' ? 'bg-gray-900 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'" class="px-5 py-2 rounded-full text-xs font-bold transition-all whitespace-nowrap">Todas as Atividades</button>
                                <button @click="activeTypeFilter = 'anamnesis'" :class="activeTypeFilter === 'anamnesis' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'" class="px-5 py-2 rounded-full text-xs font-bold transition-all whitespace-nowrap">Anamneses</button>
                                <button @click="activeTypeFilter = 'evolution'" :class="activeTypeFilter === 'evolution' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'" class="px-5 py-2 rounded-full text-xs font-bold transition-all whitespace-nowrap">Evoluções</button>
                                <button @click="activeTypeFilter = 'prescription'" :class="activeTypeFilter === 'prescription' ? 'bg-emerald-600 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'" class="px-5 py-2 rounded-full text-xs font-bold transition-all whitespace-nowrap">Receitas</button>
                                <button @click="activeTypeFilter = 'exam_request'" :class="activeTypeFilter === 'exam_request' ? 'bg-amber-600 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'" class="px-5 py-2 rounded-full text-xs font-bold transition-all whitespace-nowrap">Pedidos de Exame</button>

<button @click="activeTypeFilter = 'file'" :class="activeTypeFilter === 'file' ? 'bg-gray-700 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'" class="px-5 py-2 rounded-full text-xs font-bold transition-all whitespace-nowrap">Arquivos / Resultados</button>
                            </div>

                            <div class="flex items-center gap-2 flex-shrink-0 w-full sm:w-auto">
                                <input type="date" v-model="filterDate" class="border-gray-200 text-gray-600 rounded-xl shadow-sm text-xs p-2 w-full sm:w-auto focus:ring-indigo-500 focus:border-indigo-500" />
                                <button v-if="filterDate || activeTypeFilter !== 'all'" @click="clearFilters" class="text-xs bg-red-50 text-red-600 font-bold px-3 py-2.5 rounded-xl border border-red-100 hover:bg-red-100 transition-colors" title="Limpar Filtros">✕</button>
                            </div>
                        </div>

                        <!-- LISTAGEM DA TIMELINE -->
                        <div v-if="hasRole(['medico', 'enfermeira', 'nutricionista', 'fisioterapeuta'])" class="space-y-4">
                            
                            <div v-if="filteredTimeline.length === 0" class="py-16 text-center bg-white border border-gray-100 rounded-3xl shadow-sm">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                </div>
                                <h3 class="text-sm font-bold text-gray-800">Nenhum registo localizado</h3>
                                <p class="text-xs text-gray-400 mt-1">O histórico clínico encontra-se limpo para as seleções estipuladas.</p>
                            </div>

                            <div v-for="item in filteredTimeline" :key="item.item_type + '-' + item.id" class="flex flex-col md:flex-row gap-5 bg-white p-5 md:p-6 rounded-3xl border border-gray-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-shadow">
                                
                                <!-- Linha Lateral de Cor -->
                                <div class="absolute left-0 top-0 bottom-0 w-1.5" :class="{
                                    'bg-indigo-500': item.item_type === 'anamnesis',
                                    'bg-blue-500': item.item_type === 'evolution',
                                    'bg-emerald-500': item.item_type === 'prescription',
                                    'bg-gray-400': item.item_type === 'file'
                                }"></div>

                                <!-- Coluna de Data/Hora -->
                                <div class="md:w-28 flex-shrink-0 pt-1 pl-2">
                                    <div class="flex flex-row md:flex-col items-center md:items-start gap-3 md:gap-0">
                                        <div class="text-sm font-black text-gray-900">{{ formatDate(item.created_at) }}</div>
                                        <div class="text-xs font-bold text-gray-400 md:mt-1">{{ formatTime(item.created_at) }}</div>
                                        
                                        <span class="md:mt-3 text-[9px] font-black uppercase tracking-wider px-2.5 py-1 rounded-md" :class="{
                                            'bg-indigo-50 text-indigo-700': item.item_type === 'anamnesis',
                                            'bg-blue-50 text-blue-700': item.item_type === 'evolution',
                                            'bg-emerald-50 text-emerald-700': item.item_type === 'prescription',
                                            'bg-gray-100 text-gray-600': item.item_type === 'file'
                                        }">
                                            {{ item.item_type === 'anamnesis' ? 'Anamnese' : item.item_type === 'evolution' ? 'Evolução' : item.item_type === 'prescription' ? 'Receita' : 'Exame' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Coluna de Conteúdo -->
                                <div class="flex-1 min-w-0">
                                    
                                    <!-- Anamnese -->
                                    <div v-if="item.item_type === 'anamnesis'" class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                        <div>
                                            <h4 class="font-bold text-indigo-900 text-base">Ficha Clínica / Triagem <span v-if="item.type === 'child'" class="ml-2 text-[10px] bg-pink-100 text-pink-700 px-2 py-0.5 rounded-full uppercase tracking-wider">Infantil PCA</span></h4>
                                            <p class="text-sm text-gray-600 mt-2"><span class="font-bold text-gray-800">Motivo da Consulta:</span> <br>{{ item.chief_complaint || 'Não declarada' }}</p>
                                        </div>
                                        <div class="flex gap-2 flex-shrink-0 mt-2 sm:mt-0">
                                            <button @click="copySymptoms(item)" class="px-3 py-2 text-xs font-bold bg-white text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">{{ copiedAnamnesisId === item.id ? '✔️ Copiado' : 'Sintomas' }}</button>
                                            <button @click="openAnamnesisModal(item)" class="px-4 py-2 text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl transition-colors shadow-sm">Ler Ficha Completa</button>
                                        </div>
                                    </div>

                                    <!-- Evolução -->
                                    <div v-if="item.item_type === 'evolution'">
                                        <h4 class="font-bold text-blue-900 text-base mb-3">Anotação Clínica</h4>
                                        <div class="p-4 bg-gray-50/50 rounded-2xl border border-gray-100 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ item.clinical_notes }}</div>
                                    </div>

                                    <!-- Prescrição -->
                                    <div v-if="item.item_type === 'prescription'" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div>
                                            <h4 class="font-bold text-emerald-900 text-base">Prescrição Médica</h4>
                                            <p class="text-xs font-mono text-gray-500 mt-1.5 flex items-center gap-1.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg> Autenticação: {{ item.verification_code }}</p>
                                        </div>
                                        <a :href="route('prescriptions.pdf', item.id)" target="_blank" class="px-5 py-2.5 text-sm font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl transition-colors shadow-sm flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                            Baixar PDF
                                        </a>
                                    </div>

                                    <!-- Pedido de Exames -->
                                    <div v-if="item.item_type === 'exam_request'" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div>
                                            <h4 class="font-bold text-amber-900 text-base">Solicitação de Exames</h4>
                                            <p class="text-xs text-gray-600 mt-1.5"><span class="font-bold">Indicação:</span> {{ item.clinical_indication || 'Rotina' }}</p>
                                            <p class="text-xs font-mono text-gray-500 mt-1 bg-amber-50 px-2 py-0.5 rounded border border-amber-100 inline-block">{{ item.exams.length }} exame(s) listado(s)</p>
                                        </div>
                                        <a :href="route('exam-requests.pdf', item.id)" target="_blank" class="px-5 py-2.5 text-sm font-bold bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-xl transition-colors shadow-sm flex items-center gap-2 flex-shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                            Baixar PDF
                                        </a>
                                    </div>

                                    <!-- Arquivo -->
                                    <div v-if="item.item_type === 'file'" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 border border-gray-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-gray-900 text-base">{{ item.name }}</h4>
                                                <p v-if="item.notes" class="text-xs text-gray-500 mt-1 line-clamp-1">{{ item.notes }}</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 flex-shrink-0">
                                            <a :href="`/storage/${item.file_path}`" target="_blank" class="px-4 py-2.5 text-sm font-bold bg-white text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-50 shadow-sm transition-colors">Visualizar</a>
                                            <button @click="deleteFile(item.id)" class="px-3 py-2.5 text-sm font-bold text-red-500 bg-white border border-gray-200 hover:bg-red-50 hover:border-red-200 rounded-xl transition-colors">✕</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAIS MANTIDOS INALTERADOS (Upload, Anamnese, IA) -->
        <!-- ========================================== -->
        <Modal :show="showingUploadModal" @close="showingUploadModal = false" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-black text-gray-900 mb-5 border-b pb-3">Anexar Exame ou Laudo</h3>
                <form @submit.prevent="submitUpload" class="space-y-5">
                    <div>
                        <InputLabel value="Nome do Documento" />
                        <TextInput class="w-full mt-1" v-model="uploadForm.name" placeholder="Ex: Hemograma Completo" required/>
                    </div>
                    <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-center hover:bg-gray-50 transition-colors">
                        <input type="file" @change="handleFileUpload" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" required>
                    </div>
                    <div class="flex justify-end gap-3 mt-6 border-t pt-4">
                        <button type="button" @click="showingUploadModal = false" class="text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</button>
                        <PrimaryButton class="bg-gray-900 hover:bg-black rounded-xl">Concluir Upload</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showingAnamnesisModal" @close="closeAnamnesisModal" maxWidth="3xl">
            <div class="p-0 overflow-hidden bg-white" v-if="selectedAnamnesis">
                <div class="bg-indigo-600 px-6 py-5 flex justify-between items-center text-white">
                    <h2 class="text-lg font-black flex items-center gap-3">
                        Ficha Clínica Integrativa
                        <span v-if="selectedAnamnesis.type === 'child'" class="bg-pink-500 text-white text-[10px] uppercase px-2.5 py-0.5 rounded-full font-black tracking-widest">Infantil PCA</span>
                        <span v-else class="bg-indigo-400 text-white text-[10px] uppercase px-2.5 py-0.5 rounded-full font-black tracking-widest">Adulto</span>
                    </h2>
                    <button @click="closeAnamnesisModal" class="text-indigo-200 hover:text-white font-bold text-xl">✕</button>
                </div>
                
                <div class="p-6 space-y-5 overflow-y-auto max-h-[65vh] text-sm bg-gray-50/30">
                    <div v-if="selectedAnamnesis.type === 'adult' && selectedAnamnesis.adult_data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-1 md:col-span-2 bg-white p-5 rounded-2xl border border-gray-100 shadow-sm"><span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Queixa Principal</span><p class="mt-1 font-medium text-gray-900 text-base">{{ selectedAnamnesis.chief_complaint || '-' }}</p></div>
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm"><span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Alimentação</span><p class="mt-1 font-medium text-gray-900">{{ selectedAnamnesis.adult_data.diet_routine || '-' }}</p></div>
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm"><span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Sono e Descanso</span><p class="mt-1 font-medium text-gray-900">{{ selectedAnamnesis.adult_data.sleep_routine || '-' }}</p></div>
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm"><span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Estilo de Vida</span><p class="mt-1 font-medium text-gray-900">{{ selectedAnamnesis.patient_routine || '-' }}</p></div>
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm"><span class="font-black text-[10px] uppercase text-indigo-500 tracking-wider">Medicação / Suplementação</span><p class="mt-1 font-medium text-gray-900">{{ selectedAnamnesis.adult_data.medications || '-' }}</p></div>
                    </div>

                    <div v-if="selectedAnamnesis.type === 'child' && selectedAnamnesis.child_data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-1 md:col-span-2 bg-white p-5 rounded-2xl border border-gray-100 shadow-sm"><span class="font-black text-[10px] uppercase text-pink-500 tracking-wider">Queixa Pediátrica</span><p class="mt-1 font-medium text-gray-900 text-base">{{ selectedAnamnesis.chief_complaint || '-' }}</p></div>
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm"><span class="font-black text-[10px] uppercase text-pink-500 tracking-wider">Responsáveis e Peso</span><p class="mt-1 font-medium text-gray-900">Pais: {{ selectedAnamnesis.child_data.parents_names || '-' }} <br> Peso Atual: {{ selectedAnamnesis.child_data.weight || '-' }} kg</p></div>
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm"><span class="font-black text-[10px] uppercase text-pink-500 tracking-wider">Nutrição Infantil</span><p class="mt-1 font-medium text-gray-900">{{ selectedAnamnesis.child_data.diet_description || '-' }}</p></div>
                    </div>

                    <div v-if="selectedAnamnesis.symptoms_checklist && selectedAnamnesis.symptoms_checklist.length > 0" class="pt-4">
                        <span class="font-black text-[10px] text-gray-400 uppercase block mb-3 tracking-wider">Sinais e Disfunções Identificadas</span>
                        <div class="p-5 border border-gray-100 rounded-2xl bg-white flex flex-wrap gap-2 shadow-sm">
                            <span v-for="(symp, idx) in selectedAnamnesis.symptoms_checklist" :key="idx" :class="selectedAnamnesis.type === 'child' ? 'bg-pink-50 text-pink-700 border-pink-200' : 'bg-indigo-50 text-indigo-700 border-indigo-100'" class="px-3 py-1.5 text-[11px] font-bold rounded-lg border uppercase tracking-wide">{{ symp }}</span>
                        </div>
                    </div>
                </div>
                <div class="p-5 bg-white border-t border-gray-100 flex justify-end gap-3">
                    <button @click="copySymptoms(selectedAnamnesis)" class="px-5 py-2.5 font-bold rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-sm transition-colors shadow-sm">Copiar Sintomas</button>
                    <button @click="closeAnamnesisModal" class="px-5 py-2.5 font-bold rounded-xl bg-gray-900 text-white text-sm hover:bg-black transition-colors shadow-sm">Fechar Leitura</button>
                </div>
            </div>
        </Modal>

        <Modal :show="showingAiModal" @close="closeAiModal" maxWidth="2xl">
            <div class="bg-gray-900 rounded-2xl flex flex-col h-[75vh] shadow-2xl overflow-hidden border border-gray-700">
                <div class="bg-gray-800 p-5 flex justify-between items-center text-white border-b border-gray-700">
                    <h3 class="font-black flex items-center gap-2 text-lg"><span class="text-purple-400">✨</span> Assistente de IA Clínica</h3>
                    <button @click="closeAiModal" class="text-gray-400 hover:text-white font-bold text-xl">✕</button>
                </div>
                <div class="p-6 overflow-y-auto flex-1 text-gray-300 text-sm whitespace-pre-wrap leading-relaxed">
                    <div v-if="isAnalyzing" class="flex flex-col items-center justify-center h-full gap-4 opacity-75">
                        <svg class="animate-spin h-8 w-8 text-purple-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        A analisar o historial e a cruzar dados...
                    </div>
                    <div v-else-if="aiResponse" class="bg-gray-800 p-5 rounded-2xl border border-gray-700 shadow-inner text-base">{{ aiResponse }}</div>
                    <div v-else class="flex flex-col items-center justify-center h-full gap-3 text-center text-gray-500">
                        <span class="text-5xl opacity-20">🧠</span>
                        <p class="font-bold text-gray-400 text-base">O que gostaria de cruzar ou investigar neste prontuário?</p>
                    </div>
                </div>
                <div class="p-5 bg-gray-800 border-t border-gray-700">
                    <form @submit.prevent="askAI" class="relative">
                        <textarea ref="promptInput" v-model="aiPrompt" rows="2" placeholder="Ex: Existe relação entre a queixa atual e o padrão de sono do paciente?" class="w-full bg-gray-900 border-gray-600 focus:border-purple-500 focus:ring-purple-500 text-white rounded-2xl text-sm pr-14 shadow-inner resize-none transition-colors" @keydown.enter.prevent="askAI"></textarea>
                        <button type="submit" class="absolute right-3 bottom-3.5 text-purple-400 hover:text-purple-300 hover:bg-gray-700 font-bold w-9 h-9 flex items-center justify-center bg-gray-800 rounded-xl transition-colors disabled:opacity-50" :disabled="isAnalyzing">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M3.478 2.404a.75.75 0 00-.926.941l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.404z" /></svg>
                        </button>
                    </form>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>