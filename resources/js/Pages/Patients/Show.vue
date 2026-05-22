<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    patient: Object,
    anamneses: Array,
});

const age = computed(() => {
    if (!props.patient.date_of_birth) return 'Idade não informada';
    const today = new Date();
    const birthDate = new Date(props.patient.date_of_birth);
    let currentAge = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        currentAge--;
    }
    return `${currentAge} anos`;
});

const showingAnamnesisModal = ref(false);
const selectedAnamnesis = ref(null);

const openAnamnesisModal = (anamnese) => {
    selectedAnamnesis.value = anamnese;
    showingAnamnesisModal.value = true;
};

const closeAnamnesisModal = () => {
    showingAnamnesisModal.value = false;
    setTimeout(() => selectedAnamnesis.value = null, 300);
};
</script>

<template>
    <Head :title="`Prontuário - ${patient.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Prontuário: {{ patient.name }}
                </h2>
                <Link :href="route('patients.index')" class="text-gray-600 hover:underline">
                    Voltar para lista
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Resumo do Paciente -->
                    <div class="md:col-span-1">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-bold text-gray-900 border-b pb-2 mb-4">Dados do Paciente</h3>
                            
                            <div class="space-y-3 text-sm">
                                <p><span class="font-semibold text-gray-600">Idade:</span> {{ age }}</p>
                                <p><span class="font-semibold text-gray-600">CPF:</span> {{ patient.cpf }}</p>
                                <p><span class="font-semibold text-gray-600">Nascimento:</span> {{ new Date(patient.date_of_birth).toLocaleDateString('pt-BR', { timeZone: 'UTC' }) }}</p>
                                <p><span class="font-semibold text-gray-600">Telefone:</span> {{ patient.phone || 'Não informado' }}</p>
                                <p><span class="font-semibold text-gray-600">E-mail:</span> {{ patient.email || 'Não informado' }}</p>
                                <p><span class="font-semibold text-gray-600">Gênero:</span> {{ patient.gender || 'Não informado' }}</p>
                            </div>

                            <div class="mt-6 pt-4 border-t">
                                <Link :href="route('patients.edit', patient.id)" class="text-blue-600 hover:underline text-sm font-medium">
                                    Editar Cadastro
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Área Médica (Prontuário) -->
                    <div class="md:col-span-2 space-y-6">
                        
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-wrap gap-4">
                            <Link :href="route('evolutions.create', { patient_id: patient.id })" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium text-sm transition-colors text-center inline-block shadow-sm">
                                + Nova Evolução
                            </Link>
                            <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded font-medium text-sm transition-colors shadow-sm">
                                + Nova Receita
                            </button>
                            
                            <Link :href="route('anamneses.create', { patient_id: patient.id })" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded font-medium text-sm transition-colors text-center inline-block border border-gray-200">
                                Preencher Anamnese
                            </Link>
                        </div>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-bold text-gray-900 border-b pb-2 mb-4">Histórico Clínico</h3>
                            
                            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                                <span class="block sm:inline">{{ $page.props.flash.success }}</span>
                            </div>

                            <!-- Lista de Anamneses -->
                            <div v-if="anamneses && anamneses.length > 0" class="space-y-6">
                                <div v-for="anamnese in anamneses" :key="anamnese.id" class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm hover:shadow-md transition-shadow duration-200">
                                    <div class="flex justify-between items-center">
                                        <div class="flex flex-col">
                                            <h4 class="font-bold text-indigo-700 flex items-center gap-2">
                                                <!-- Ícone Documento Moderno -->
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                </svg>
                                                Anamnese Registrada
                                            </h4>
                                            <span class="text-sm text-gray-600 mt-1">
                                                <span class="font-medium">Queixa:</span> {{ anamnese.chief_complaint }}
                                            </span>
                                        </div>
                                        
                                        <div class="flex flex-col items-end gap-3">
                                            <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full border border-gray-200">
                                                {{ new Date(anamnese.created_at).toLocaleDateString('pt-BR') }}
                                            </span>
                                            
                                            <!-- NOVO BOTÃO DE VISUALIZAR MODERNO (OLHO) -->
                                            <button @click="openAnamnesisModal(anamnese)" title="Visualizar Completa" class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-600 hover:text-white rounded-md transition-all duration-200 border border-indigo-200 hover:border-indigo-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                                Visualizar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum registro</h3>
                                <p class="mt-1 text-sm text-gray-500">Comece adicionando uma evolução ou preenchendo a anamnese.</p>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL DA ANAMNESE -->
        <Modal :show="showingAnamnesisModal" @close="closeAnamnesisModal" maxWidth="2xl">
            <div class="p-6" v-if="selectedAnamnesis">
                <h2 class="text-lg font-bold text-gray-900 border-b pb-4 mb-4 flex items-center gap-2">
                    <!-- Ícone Documento no Modal -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-indigo-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    Detalhes da Anamnese
                </h2>

                <div class="space-y-4 text-sm text-gray-800">
                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded border">
                        <span class="font-semibold text-gray-600">Data do Registro:</span>
                        <span class="font-bold">{{ new Date(selectedAnamnesis.created_at).toLocaleDateString('pt-BR') }} as {{ new Date(selectedAnamnesis.created_at).toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'}) }}</span>
                    </div>

                    <div>
                        <span class="font-bold block text-gray-900 bg-gray-100 p-2 rounded-t border-b-0 border">Queixa Principal</span>
                        <div class="p-3 border rounded-b bg-white whitespace-pre-wrap">{{ selectedAnamnesis.chief_complaint }}</div>
                    </div>

                    <div v-if="selectedAnamnesis.history_of_present_illness">
                        <span class="font-bold block text-gray-900 bg-gray-100 p-2 rounded-t border-b-0 border">História da Moléstia Atual (HMA)</span>
                        <div class="p-3 border rounded-b bg-white whitespace-pre-wrap">{{ selectedAnamnesis.history_of_present_illness }}</div>
                    </div>

                    <div v-if="selectedAnamnesis.past_medical_history">
                        <span class="font-bold block text-gray-900 bg-gray-100 p-2 rounded-t border-b-0 border">Histórico Médico e Familiar</span>
                        <div class="p-3 border rounded-b bg-white whitespace-pre-wrap">{{ selectedAnamnesis.past_medical_history }}</div>
                    </div>

                    <div v-if="selectedAnamnesis.allergies || selectedAnamnesis.current_medications">
                        <span class="font-bold block text-gray-900 bg-gray-100 p-2 rounded-t border-b-0 border">Medicações e Alergias</span>
                        <div class="p-3 border rounded-b bg-white whitespace-pre-wrap">
                            <p v-if="selectedAnamnesis.current_medications"><strong>Em uso:</strong> {{ selectedAnamnesis.current_medications }}</p>
                            <p v-if="selectedAnamnesis.allergies" class="mt-2"><strong>Alergias:</strong> {{ selectedAnamnesis.allergies }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeAnamnesisModal">
                        Fechar
                    </SecondaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>