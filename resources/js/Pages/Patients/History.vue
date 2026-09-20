<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const formatDate = (dateString) => new Date(dateString).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
const formatTime = (dateString) => new Date(dateString).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

// Lógica de unificação cronológica (Ordem Decrescente)
const unifiedHistory = computed(() => {
    const items = [];
    
    (props.anamneses || []).forEach(a => items.push({ ...a, log_type: 'Anamnese / Triagem', sort_date: new Date(a.created_at) }));
    (props.evolutions || []).forEach(e => items.push({ ...e, log_type: 'Evolução Clínica', sort_date: new Date(e.created_at) }));
    (props.prescriptions || []).forEach(p => items.push({ ...p, log_type: 'Prescrição', sort_date: new Date(p.created_at) }));
    (props.files || []).forEach(f => items.push({ ...f, log_type: 'Exame Anexado', sort_date: new Date(f.created_at) }));
    
    return items.sort((a, b) => b.sort_date - a.sort_date);
});

const printDocument = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Resumo Clínico - ${patient.name}`" />

    <div class="min-h-screen bg-gray-100 print:bg-white text-gray-900 font-sans">
        
        <div class="bg-gray-900 text-white p-4 flex justify-between items-center print:hidden shadow-md sticky top-0 z-10">
            <div class="flex items-center gap-4">
                <Link :href="route('patients.show', patient.id)" class="text-gray-300 hover:text-white font-bold text-sm flex items-center gap-2">
                    &larr; Voltar ao Prontuário
                </Link>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-400 font-medium">Modo de Leitura Otimizado</span>
                <button @click="printDocument" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-bold shadow transition-colors">
                    🖨️ Imprimir / Gerar PDF
                </button>
            </div>
        </div>

        <div class="max-w-4xl mx-auto bg-white p-10 print:p-0 print:shadow-none shadow-xl my-8 print:my-0 min-h-[297mm]">
            
            <div class="border-b-2 border-gray-900 pb-6 mb-8 flex justify-between items-end">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tight">{{ patient.name }}</h1>
                    <p class="text-sm text-gray-600 mt-1 font-bold">Relatório Cronológico Unificado</p>
                </div>
                <div class="text-right text-sm text-gray-700">
                    <p><strong>CPF:</strong> {{ patient.cpf }}</p>
                    <p><strong>Idade:</strong> {{ age }}</p>
                    <p><strong>Emissão:</strong> {{ new Date().toLocaleDateString('pt-BR') }}</p>
                </div>
            </div>

            <div class="space-y-8">
                <div v-if="unifiedHistory.length === 0" class="text-center text-gray-500 py-10 italic">
                    Nenhum registo clínico encontrado para este paciente.
                </div>

                <div v-for="(item, index) in unifiedHistory" :key="index" class="relative pl-6 border-l-2 border-gray-200 print:border-gray-400 break-inside-avoid">
                    
                    <div class="absolute w-3 h-3 bg-gray-400 rounded-full -left-[7px] top-1.5 print:bg-black"></div>
                    
                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-2 mb-2">
                        <span class="font-black text-indigo-700 print:text-black text-base">{{ formatDate(item.created_at) }}</span>
                        <span class="text-xs font-bold text-gray-400 print:text-gray-600">{{ formatTime(item.created_at) }}</span>
                        <span class="ml-0 sm:ml-2 text-xs font-black uppercase tracking-wider bg-gray-100 px-2 py-0.5 rounded text-gray-700 print:border print:border-gray-300">
                            {{ item.log_type }}
                        </span>
                        <span v-if="item.professional" class="text-xs text-gray-500 italic sm:ml-auto">
                            Por: {{ item.professional.name }}
                        </span>
                    </div>

                    <div class="text-sm text-gray-800 bg-gray-50 print:bg-transparent print:border-none print:p-0 p-4 rounded-lg border border-gray-100">
                        
                        <div v-if="item.log_type === 'Anamnese / Triagem'">
                            <p><strong>Queixa Principal:</strong> {{ item.chief_complaint || 'Não especificada' }}</p>
                            <p v-if="item.symptoms_checklist?.length" class="mt-2"><strong>Sintomas:</strong> {{ item.symptoms_checklist.join(', ') }}</p>
                        </div>

                        <div v-if="item.log_type === 'Evolução Clínica'" class="whitespace-pre-wrap leading-relaxed">
                            {{ item.clinical_notes }}
                        </div>

                        <div v-if="item.log_type === 'Prescrição'">
                            <p class="font-bold text-xs text-emerald-700 mb-2">Cód: {{ item.verification_code }}</p>
                            <ul class="list-disc pl-5 space-y-1">
                                <li v-for="(med, idx) in item.medications" :key="idx">
                                    <strong>{{ med.name }}</strong> ({{ med.dosage }}) - <em>{{ med.instructions }}</em>
                                </li>
                            </ul>
                            <p v-if="item.notes" class="mt-3 italic text-gray-600">"{{ item.notes }}"</p>
                        </div>

                        <div v-if="item.log_type === 'Exame Anexado'">
                            <p><strong>Documento:</strong> {{ item.name }}</p>
                            <p v-if="item.notes" class="mt-1 text-gray-600 italic">{{ item.notes }}</p>
                        </div>

                    </div>
                </div>
            </div>

            <div class="mt-16 pt-6 border-t border-gray-300 text-center text-xs text-gray-500 font-medium">
                Documento gerado através do sistema Curae. As informações contidas são confidenciais e de uso exclusivo clínico.
            </div>

        </div>
    </div>
</template>