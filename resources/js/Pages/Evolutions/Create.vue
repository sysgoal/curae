<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({ patient: Object });

const form = useForm({
    patient_id: props.patient.id,
    weight: '',
    height: '',
    bmi: '',
    systolic_bp: '',
    diastolic_bp: '',
    heart_rate: '',
    respiratory_rate: '',
    temperature: '',
    oxygen_saturation: '',
    blood_glucose: '',
    clinical_notes: '',
});

// Calcula o IMC automaticamente
watch([() => form.weight, () => form.height], ([weight, height]) => {
    if (weight && height && height > 0) {
        const heightInMeters = height > 3 ? height / 100 : height; // Aceita ex: 175 ou 1.75
        form.bmi = (weight / (heightInMeters * heightInMeters)).toFixed(2);
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
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Evolução Clínica: {{ patient.name }}
                </h2>
                <Link :href="route('patients.show', patient.id)" class="text-gray-600 hover:underline">
                    Cancelar
                </Link>
            </div>
        </template>

        <div class="py-12 max-w-5xl mx-auto sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="space-y-6">
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 border-b pb-2 mb-4">Sinais Vitais e Biometria (Opcional)</h3>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <div>
                            <InputLabel value="Peso (kg)" />
                            <TextInput v-model="form.weight" type="number" step="0.01" class="mt-1 block w-full text-sm" placeholder="Ex: 70.5" />
                        </div>
                        <div>
                            <InputLabel value="Altura (m/cm)" />
                            <TextInput v-model="form.height" type="number" step="0.01" class="mt-1 block w-full text-sm" placeholder="Ex: 1.75" />
                        </div>
                        <div>
                            <InputLabel value="IMC" />
                            <TextInput v-model="form.bmi" type="text" class="mt-1 block w-full bg-gray-50 text-sm" readonly placeholder="Auto" />
                        </div>
                        <div>
                            <InputLabel value="Pressão Sistólica" />
                            <TextInput v-model="form.systolic_bp" type="number" class="mt-1 block w-full text-sm" placeholder="Ex: 120" />
                        </div>
                        <div>
                            <InputLabel value="Pressão Diastólica" />
                            <TextInput v-model="form.diastolic_bp" type="number" class="mt-1 block w-full text-sm" placeholder="Ex: 80" />
                        </div>
                        <div>
                            <InputLabel value="Freq. Cardíaca" />
                            <TextInput v-model="form.heart_rate" type="number" class="mt-1 block w-full text-sm" placeholder="bpm" />
                        </div>
                        <div>
                            <InputLabel value="Sat. Oxigênio (%)" />
                            <TextInput v-model="form.oxygen_saturation" type="number" class="mt-1 block w-full text-sm" placeholder="% SpO2" />
                        </div>
                        <div>
                            <InputLabel value="Temperatura (°C)" />
                            <TextInput v-model="form.temperature" type="number" step="0.1" class="mt-1 block w-full text-sm" placeholder="°C" />
                        </div>
                        <div>
                            <InputLabel value="Glicemia" />
                            <TextInput v-model="form.blood_glucose" type="number" class="mt-1 block w-full text-sm" placeholder="mg/dL" />
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 border-b pb-2 mb-4">Registo Clínico *</h3>
                    <textarea 
                        id="clinical_notes" 
                        v-model="form.clinical_notes" 
                        rows="8" 
                        class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" 
                        placeholder="Descreva aqui a evolução do estado do paciente, condutas e procedimentos..."
                        required
                    ></textarea>
                </div>

                <div class="flex items-center justify-end">
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700 px-8 py-3">
                        Guardar Evolução Completa
                    </PrimaryButton>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>