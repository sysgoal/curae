<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({ patient: Object });

// Inicia com um medicamento vazio
const form = useForm({
    patient_id: props.patient.id,
    medications: [{ name: '', dosage: '', instructions: '' }],
    notes: ''
});

// Adicionar mais um medicamento à lista
const addMedication = () => {
    form.medications.push({ name: '', dosage: '', instructions: '' });
};

// Remover um medicamento
const removeMedication = (index) => {
    if (form.medications.length > 1) {
        form.medications.splice(index, 1);
    }
};

const submit = () => {
    form.post(route('prescriptions.store'));
};
</script>

<template>
    <Head title="Nova Prescrição" />

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

        <div class="py-12 max-w-5xl mx-auto sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                
                <div class="mb-6 flex justify-between items-center border-b pb-4">
                    <div>
                        <h3 class="text-lg font-black text-gray-900">Medicamentos e Fórmulas</h3>
                        <p class="text-sm text-gray-500">Adicione os itens que constarão no receituário digital.</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div v-for="(med, index) in form.medications" :key="index" class="p-5 bg-gray-50 rounded-xl border border-gray-200 relative">
                        <button v-if="form.medications.length > 1" type="button" @click="removeMedication(index)" class="absolute top-3 right-3 text-red-500 hover:text-red-700 font-bold text-xs bg-white px-2 py-1 rounded shadow-sm border">Remover</button>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <InputLabel value="Nome do Medicamento / Fórmula *" />
                                <TextInput type="text" class="mt-1 block w-full bg-white" v-model="med.name" placeholder="Ex: Amoxicilina / Fórmula Magistral" required />
                            </div>
                            <div>
                                <InputLabel value="Dose / Quantidade *" />
                                <TextInput type="text" class="mt-1 block w-full bg-white" v-model="med.dosage" placeholder="Ex: 500mg, 1 caixa..." required />
                            </div>
                            <div class="md:col-span-3">
                                <InputLabel value="Posologia e Instruções de Uso *" />
                                <TextInput type="text" class="mt-1 block w-full bg-white" v-model="med.instructions" placeholder="Ex: Tomar 1 comprimido a cada 8 horas durante 7 dias." required />
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" @click="addMedication" class="mt-4 text-emerald-600 font-bold text-sm flex items-center gap-1 hover:text-emerald-800 transition-colors">
                    + Adicionar outro medicamento
                </button>

                <div class="mt-10 pt-6 border-t border-gray-100">
                    <InputLabel value="Orientações Gerais (Opcional)" />
                    <textarea v-model="form.notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-lg shadow-sm" placeholder="Instruções adicionais, dieta, repouso..."></textarea>
                </div>

                <div class="flex items-center justify-end mt-8">
                    <PrimaryButton :disabled="form.processing" class="bg-emerald-600 hover:bg-emerald-700 py-3 px-8 rounded-xl shadow-lg">
                        Emitir Prescrição Digital
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>