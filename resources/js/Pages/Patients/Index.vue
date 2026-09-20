<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ patients: Array });
const page = usePage();
const search = ref('');

// Verificação de permissões ultra-segura
const hasRole = (rolesAllowed) => {
    const userRoles = page.props.auth?.roles || [];
    if (userRoles.includes('admin')) return true;
    return rolesAllowed.some(role => userRoles.includes(role));
};

const filteredPatients = computed(() => {
    if (!props.patients) return [];
    return props.patients.filter(patient => {
        const term = search.value.toLowerCase();
        return (patient.name && patient.name.toLowerCase().includes(term)) || 
               (patient.cpf && patient.cpf.includes(term));
    });
});

const calculateAge = (dob) => {
    if (!dob) return '-';
    const today = new Date();
    const birth = new Date(dob);
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
    return `${age} anos`;
};

const deletePatient = (id) => {
    if (confirm('Tem a certeza que deseja remover permanentemente o registo deste paciente?')) {
        router.delete(route('patients.destroy', id));
    }
};
</script>

<template>
    <Head title="Diretório de Pacientes" />
    <AuthenticatedLayout>
        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 font-bold">
                {{ $page.props.flash.success }}
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                
                <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white">
                    <div class="relative w-full sm:w-96">
                        <input type="text" v-model="search" placeholder="Buscar por nome ou CPF..." class="block w-full px-4 py-2.5 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm" />
                    </div>

                    <Link v-if="hasRole(['admin', 'secretaria'])" :href="route('patients.create')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-bold text-sm shadow-sm transition-all w-full sm:w-auto text-center flex justify-center items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Novo Paciente
                    </Link>
                </div>

                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b text-xs uppercase tracking-wider text-gray-500 font-black">
                            <th class="p-4">Paciente</th>
                            <th class="p-4">Contato</th>
                            <th class="p-4">Idade</th>
                            <th class="p-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-100">
                        <tr v-if="filteredPatients.length === 0">
                            <td colspan="4" class="p-8 text-center text-gray-400">Nenhum paciente localizado.</td>
                        </tr>
                        <tr v-for="patient in filteredPatients" :key="patient.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-4">
                                <div class="font-bold text-gray-900">{{ patient.name }}</div>
                                <div class="text-xs text-gray-500">CPF: {{ patient.cpf }}</div>
                            </td>
                            <td class="p-4">
                                <div class="text-gray-700 font-medium">{{ patient.phone || 'Sem telefone' }}</div>
                                <div class="text-xs text-gray-400">{{ patient.email || 'Sem e-mail' }}</div>
                            </td>
                            <td class="p-4">
                                <span class="px-3 py-1 text-xs font-bold rounded-full bg-indigo-50 text-indigo-700">
                                    {{ calculateAge(patient.date_of_birth) }}
                                </span>
                            </td>
                            <td class="p-4 text-right space-x-2">
                                <Link :href="route('patients.show', patient.id)" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg transition-colors">
                                    📄 Prontuário
                                </Link>
                                <Link v-if="hasRole(['admin', 'secretaria'])" :href="route('patients.edit', patient.id)" class="inline-flex items-center px-3 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-700 text-xs font-bold rounded-lg border transition-colors">
                                    Editar
                                </Link>
                                <button v-if="hasRole(['admin'])" @click="deletePatient(patient.id)" class="text-red-500 hover:text-red-700 font-bold text-xs p-1.5 rounded-lg">
                                    ✕
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>