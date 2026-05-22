<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    patient: Object,
});

const form = useForm({
    patient_id: props.patient.id,
    chief_complaint: '', 
    history_of_present_illness: '', 
    past_medical_history: '', 
    allergies: '',
    current_medications: ''
});

const submit = () => {
    form.post(route('anamneses.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Nova Anamnese - ${patient.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Nova Anamnese: {{ patient.name }}
                </h2>
                <Link :href="route('patients.show', patient.id)" class="text-gray-600 hover:underline">
                    Voltar para o Prontuário
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div>
                            <InputLabel for="chief_complaint" value="Queixa Principal *" />
                            <TextInput 
                                id="chief_complaint" 
                                type="text" 
                                class="mt-1 block w-full" 
                                v-model="form.chief_complaint" 
                                required 
                                autofocus 
                                placeholder="Ex: Dor de cabeça constante há 3 dias" 
                            />
                            <InputError class="mt-2" :message="form.errors.chief_complaint" />
                        </div>

                        <div>
                            <InputLabel for="history_of_present_illness" value="História da Moléstia Atual (HMA)" />
                            <textarea 
                                id="history_of_present_illness" 
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" 
                                rows="4" 
                                v-model="form.history_of_present_illness"
                                placeholder="Detalhe os sintomas, evolução, fatores de melhora/piora..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.history_of_present_illness" />
                        </div>

                        <div>
                            <InputLabel for="past_medical_history" value="Histórico Médico e Familiar" />
                            <textarea 
                                id="past_medical_history" 
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" 
                                rows="3" 
                                v-model="form.past_medical_history"
                                placeholder="Doenças prévias, cirurgias, histórico de doenças na família..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.past_medical_history" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="current_medications" value="Medicações em uso" />
                                <textarea 
                                    id="current_medications" 
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" 
                                    rows="2" 
                                    v-model="form.current_medications"
                                    placeholder="Liste os remédios de uso contínuo..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.current_medications" />
                            </div>

                            <div>
                                <InputLabel for="allergies" value="Alergias" />
                                <textarea 
                                    id="allergies" 
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" 
                                    rows="2" 
                                    v-model="form.allergies"
                                    placeholder="Qualquer alergia conhecida..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.allergies" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4 border-t pt-4">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Salvar Anamnese
                            </PrimaryButton>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>