<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    patient: Object,
});

const form = useForm({
    patient_id: props.patient.id,
    clinical_notes: '',
    weight: '',
    height: '',
    bmi: '',
    systolic_bp: '',
    diastolic_bp: '',
    heart_rate: '',
    respiratory_rate: '',
    temperature: '',
    oxygen_saturation: '',
    blood_glucose: ''
});

// Calcula automaticamente o IMC quando peso e altura são inseridos
watch([() => form.weight, () => form.height], ([newWeight, newHeight]) => {
    if (newWeight && newHeight && newHeight > 0) {
        const weight = parseFloat(newWeight);
        const height = parseFloat(newHeight);
        const bmi = weight / (height * height);
        form.bmi = bmi.toFixed(2);
    } else {
        form.bmi = '';
    }
});

const submit = () => {
    form.post(route('evolutions.store'));
};
</script>

<template>
    <Head :title="`Nova Evolução - ${patient.name}`" />

    <AuthenticatedLayout>
        
        <!-- CABEÇALHO COM BOTÃO DE RASTREABILIDADE -->
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('patients.show', patient.id)" class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-xl text-sm font-bold transition-colors flex items-center gap-2 shadow-sm border border-gray-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        Voltar ao Prontuário
                    </Link>
                    <h2 class="font-bold text-xl text-gray-800 leading-tight border-l-2 border-gray-300 pl-4 ml-1">
                        Nova Evolução Clínica
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 p-8">
                    
                    <div class="mb-8 border-b border-gray-100 pb-4">
                        <h3 class="text-lg font-black text-gray-900">Paciente: {{ patient.name }}</h3>
                        <p class="text-sm text-gray-500">Registo de evolução, sinais vitais e conduta clínica.</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <!-- LINHA 1: Biometria e Temperatura -->
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div>
                                <InputLabel value="Peso (kg)" />
                                <TextInput type="number" step="0.1" class="mt-1 block w-full text-sm bg-blue-50/30" v-model="form.weight" placeholder="Ex: 75.5" />
                                <InputError class="mt-2" :message="form.errors.weight" />
                            </div>
                            <div>
                                <InputLabel value="Altura (m)" />
                                <TextInput type="number" step="0.01" class="mt-1 block w-full text-sm bg-blue-50/30" v-model="form.height" placeholder="Ex: 1.75" />
                                <InputError class="mt-2" :message="form.errors.height" />
                            </div>
                            <div>
                                <InputLabel value="IMC (Automático)" />
                                <TextInput type="text" class="mt-1 block w-full text-sm bg-gray-100 text-gray-500 font-bold" v-model="form.bmi" readonly tabindex="-1" />
                            </div>
                            <div>
                                <InputLabel value="Temp. Corporal (°C)" />
                                <TextInput type="number" step="0.1" class="mt-1 block w-full text-sm bg-blue-50/30" v-model="form.temperature" placeholder="Ex: 36.5" />
                                <InputError class="mt-2" :message="form.errors.temperature" />
                            </div>
                            <div>
                                <InputLabel value="Glicemia (mg/dL)" />
                                <TextInput type="number" step="1" class="mt-1 block w-full text-sm bg-blue-50/30" v-model="form.blood_glucose" placeholder="Ex: 95" />
                                <InputError class="mt-2" :message="form.errors.blood_glucose" />
                            </div>
                        </div>

                        <!-- LINHA 2: Pressão Arterial e Sinais Cardiológicos/Respiratórios -->
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div>
                                <InputLabel value="PA Sistólica (mmHg)" />
                                <TextInput type="number" step="1" class="mt-1 block w-full text-sm bg-red-50/30 border-red-100" v-model="form.systolic_bp" placeholder="Ex: 120" />
                                <InputError class="mt-2" :message="form.errors.systolic_bp" />
                            </div>
                            <div>
                                <InputLabel value="PA Diastólica (mmHg)" />
                                <TextInput type="number" step="1" class="mt-1 block w-full text-sm bg-red-50/30 border-red-100" v-model="form.diastolic_bp" placeholder="Ex: 80" />
                                <InputError class="mt-2" :message="form.errors.diastolic_bp" />
                            </div>
                            <div>
                                <InputLabel value="Freq. Cardíaca (bpm)" />
                                <TextInput type="number" step="1" class="mt-1 block w-full text-sm bg-blue-50/30" v-model="form.heart_rate" placeholder="Ex: 80" />
                                <InputError class="mt-2" :message="form.errors.heart_rate" />
                            </div>
                            <div>
                                <InputLabel value="Freq. Respiratória (irpm)" />
                                <TextInput type="number" step="1" class="mt-1 block w-full text-sm bg-blue-50/30" v-model="form.respiratory_rate" placeholder="Ex: 16" />
                                <InputError class="mt-2" :message="form.errors.respiratory_rate" />
                            </div>
                            <div>
                                <InputLabel value="Saturação O2 (%)" />
                                <TextInput type="number" step="1" class="mt-1 block w-full text-sm bg-blue-50/30" v-model="form.oxygen_saturation" placeholder="Ex: 98" />
                                <InputError class="mt-2" :message="form.errors.oxygen_saturation" />
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100">
                            <InputLabel value="Observações Clínicas e Conduta *" />
                            <textarea v-model="form.clinical_notes" rows="6" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 rounded-xl shadow-sm text-sm p-4" required placeholder="Registe aqui a evolução do paciente e a conduta adotada..."></textarea>
                            <InputError class="mt-2" :message="form.errors.clinical_notes" />
                        </div>

                        <div class="flex items-center justify-end pt-4 gap-4">
                            <Link :href="route('patients.show', patient.id)" class="text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</Link>
                            <PrimaryButton :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 px-8 py-3 rounded-xl">
                                Guardar Evolução
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>