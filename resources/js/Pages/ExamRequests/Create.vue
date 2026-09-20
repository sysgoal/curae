<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    patient: Object
});

const form = useForm({
    patient_id: props.patient.id,
    exams: [''], // Inicia com um campo vazio
    clinical_indication: '',
    notes: ''
});

// Funções para adicionar ou remover linhas de exames dinamicamente
const addExamField = () => form.exams.push('');
const removeExamField = (index) => {
    if (form.exams.length > 1) form.exams.splice(index, 1);
};

const submit = () => {
    // Filtra campos vazios antes de submeter
    form.exams = form.exams.filter(exam => exam.trim() !== '');
    
    if (form.exams.length === 0) {
        alert('Por favor, adicione pelo menos um exame.');
        form.exams = [''];
        return;
    }

    form.post(route('exam-requests.store'));
};
</script>

<template>
    <Head title="Solicitar Exames" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('patients.show', patient.id)" class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-xl text-sm font-bold transition-colors flex items-center gap-2 shadow-sm border border-gray-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        Voltar ao Prontuário
                    </Link>
                    
                    <h2 class="font-bold text-xl text-gray-800 leading-tight border-l-2 border-gray-300 pl-4 ml-1">
                        {{ patient.name }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-12 max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <form @submit.prevent="submit" class="space-y-8">
                    
                    <div>
                        <h3 class="text-lg font-black text-gray-900 border-b pb-3 mb-5">Lista de Exames Solicitados</h3>
                        <div class="space-y-3">
                            <div v-for="(exam, index) in form.exams" :key="index" class="flex items-center gap-3">
                                <span class="text-gray-400 font-bold text-sm w-6">{{ index + 1 }}.</span>
                                <TextInput v-model="form.exams[index]" type="text" placeholder="Ex: Hemograma Completo, TSH, Glicemia em jejum..." class="w-full text-sm rounded-xl" required />
                                <button type="button" @click="removeExamField(index)" v-if="form.exams.length > 1" class="text-red-400 hover:text-red-600 font-bold p-2 transition-colors" title="Remover Exame">✕</button>
                            </div>
                        </div>
                        <button type="button" @click="addExamField" class="mt-4 text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-4 py-2 rounded-xl transition-colors flex items-center gap-1">
                            + Adicionar outro exame
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-6 pt-6 border-t border-gray-100">
                        <div>
                            <InputLabel value="Indicação Clínica (Justificativa médica / CID)" class="font-bold text-gray-700" />
                            <textarea v-model="form.clinical_indication" rows="2" class="mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl text-sm shadow-sm resize-none"></textarea>
                        </div>
                        
                        <div>
                            <InputLabel value="Observações Adicionais (Recomendações ao laboratório ou paciente)" class="font-bold text-gray-700" />
                            <textarea v-model="form.notes" rows="2" class="mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl text-sm shadow-sm resize-none"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-6">
                        <Link :href="route('patients.show', patient.id)" class="px-6 py-3 font-bold rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors text-sm">Cancelar</Link>
                        <PrimaryButton class="bg-gray-900 hover:bg-black rounded-xl px-6 py-3 text-sm flex items-center gap-2" :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                            <span class="text-lg">🖨️</span> Gerar e Imprimir Pedido
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>