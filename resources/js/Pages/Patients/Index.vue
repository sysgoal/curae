<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    // Tipagem flexível para suportar Array simples (Patient::all()) ou Paginação (Patient::paginate())
    patients: {
        type: [Array, Object],
        default: () => []
    },
});

const searchQuery = ref('');

// Filtro reativo blindado contra dados nulos ou estruturas paginadas
const filteredPatients = computed(() => {
    let list = [];
    
    // Identifica automaticamente se o Laravel enviou uma paginação ou um array simples
    if (props.patients && props.patients.data) {
        list = props.patients.data;
    } else if (Array.isArray(props.patients)) {
        list = props.patients;
    }

    if (!searchQuery.value) return list;
    
    const lowerCaseQuery = searchQuery.value.toLowerCase();
    
    return list.filter(patient => {
        const matchName = patient.name ? patient.name.toLowerCase().includes(lowerCaseQuery) : false;
        const matchCpf = patient.cpf ? patient.cpf.includes(searchQuery.value) : false;
        return matchName || matchCpf;
    });
});

const deletePatient = (id) => {
    if (confirm('Tem certeza que deseja remover este paciente? Todo o histórico (evoluções, receitas) também será apagado permanentemente!')) {
        router.delete(route('patients.destroy', id), {
            preserveScroll: true
        });
    }
};

const calculateAge = (dob) => {
    if (!dob) return '--';
    const today = new Date();
    const birthDate = new Date(dob);
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return `${age} anos`;
};
</script>

<template>
    <Head title="Lista de Pacientes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Gestão de Pacientes
                </h2>
                <Link :href="route('patients.create')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-bold text-sm transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Cadastrar Paciente
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="$page.props.flash && $page.props.flash.success" class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg shadow-sm">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <p class="text-sm text-green-700 font-bold">{{ $page.props.flash.success }}</p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100">
                    
                    <div class="p-6 border-b border-gray-100 bg-gray-50 flex flex-wrap items-center justify-between gap-4">
                        <h3 class="text-lg font-bold text-gray-800">Diretório Clínico</h3>
                        <div class="relative w-full max-w-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input 
                                type="text" 
                                v-model="searchQuery" 
                                placeholder="Buscar por nome ou CPF..." 
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-shadow shadow-sm"
                            >
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-white">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-500 uppercase tracking-wider">Paciente</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-500 uppercase tracking-wider">Contato</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-500 uppercase tracking-wider">Idade</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-extrabold text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="patient in filteredPatients" :key="patient.id" class="hover:bg-gray-50 transition-colors duration-150">
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center font-bold text-lg">
                                                {{ patient.name ? patient.name.charAt(0).toUpperCase() : 'P' }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-bold text-gray-900">{{ patient.name }}</div>
                                                <div class="text-xs text-gray-400 mt-0.5 font-mono">CPF: {{ patient.cpf }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-medium">{{ patient.phone || 'Sem telefone' }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ patient.email || 'Sem e-mail' }}</div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                                            {{ calculateAge(patient.date_of_birth) }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            
                                            <Link :href="route('patients.show', patient.id)" class="inline-flex items-center gap-1.5 bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white px-3 py-1.5 rounded-md text-xs font-bold transition-all border border-indigo-100 hover:border-indigo-600 shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                                Prontuário
                                            </Link>

                                            <Link :href="route('patients.edit', patient.id)" class="inline-flex items-center gap-1.5 bg-white hover:bg-gray-100 text-gray-700 px-3 py-1.5 rounded-md text-xs font-bold transition-all border border-gray-300 shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                                                Editar
                                            </Link>

                                            <button @click="deletePatient(patient.id)" class="inline-flex items-center gap-1.5 bg-white hover:bg-red-50 text-gray-400 hover:text-red-600 px-2 py-1.5 rounded-md text-xs font-bold transition-all border border-transparent hover:border-red-200" title="Excluir Paciente">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                            </button>
                                            
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="filteredPatients.length === 0">
                                    <td colspan="4" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="bg-gray-50 p-4 rounded-full mb-3">
                                                <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                            </div>
                                            <h3 class="text-sm font-bold text-gray-900">Nenhum paciente localizado</h3>
                                            <p class="text-sm text-gray-500 mt-1 mb-4">A base de dados encontra-se vazia ou a pesquisa não gerou correspondências.</p>
                                            <Link :href="route('patients.create')" class="text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-4 py-2 rounded-md font-bold text-sm transition-colors border border-indigo-100">
                                                + Cadastrar Novo Paciente
                                            </Link>
                                        </div>
                                    </td>
                                        </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>