<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ patient: Object });

const form = useForm({
    patient_id: props.patient.id,
    // Alterado de 'medicines' para 'medications'
    medications: [
        { name: '', dosage: '', instructions: '' }
    ],
    notes: ''
});

// Função para adicionar nova linha de medicamento
const addMedicine = () => {
    form.medications.push({ name: '', dosage: '', instructions: '' });
};

// Função para remover uma linha específica
const removeMedicine = (index) => {
    form.medications.splice(index, 1);
};

const submit = () => {
    form.post(route('prescriptions.store'));
};
</script>

<template>
    <Head :title="`Nova Prescrição - ${patient.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Nova Receita: {{ patient.name }}
                </h2>
                <Link :href="route('patients.show', patient.id)" class="text-gray-600 hover:underline">
                    Cancelar
                </Link>
            </div>
        </template>

        <div class="py-12 max-w-5xl mx-auto sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="space-y-6">
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6 border-b pb-4">
                        <h3 class="text-lg font-bold text-gray-900">Medicamentos Prescritos</h3>
                        <button type="button" @click="addMedicine" class="text-sm font-semibold bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded border border-indigo-200 hover:bg-indigo-600 hover:text-white transition-colors duration-200 shadow-sm">
                            + Adicionar Medicamento
                        </button>
                    </div>

                    <div v-for="(medicine, index) in form.medications" :key="index" class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg relative hover:shadow-md transition-shadow">
                        
                        <button v-if="form.medications.length > 1" type="button" @click="removeMedicine(index)" class="absolute top-2 right-2 text-red-400 hover:text-red-600 font-bold px-2 py-1 bg-white border border-red-100 rounded shadow-sm transition">
                            X
                        </button>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="md:col-span-2">
                                <InputLabel value="Nome do Medicamento *" />
                                <TextInput v-model="medicine.name" type="text" class="mt-1 block w-full" placeholder="Ex: Amoxicilina" required />
                            </div>
                            <div class="md:col-span-2">
                                <InputLabel value="Dosagem / Quantidade" />
                                <TextInput v-model="medicine.dosage" type="text" class="mt-1 block w-full" placeholder="Ex: 500mg - 1 caixa" />
                            </div>
                            <div class="md:col-span-4">
                                <InputLabel value="Instruções de Uso *" />
                                <TextInput v-model="medicine.instructions" type="text" class="mt-1 block w-full" placeholder="Ex: Tomar 1 comprimido de 8/8h por 7 dias" required />
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 border-t pt-6">
                        <InputLabel value="Orientações Gerais ao Paciente (Opcional)" />
                        <textarea v-model="form.notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Ex: Retornar caso a febre persista. Repouso absoluto de 3 dias."></textarea>
                    </div>
                </div>

                <div class="flex justify-end">
                    <PrimaryButton :disabled="form.processing" class="px-8 py-3 text-base">
                        Salvar e Gerar Receita
                    </PrimaryButton>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>