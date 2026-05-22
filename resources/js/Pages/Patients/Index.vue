<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    patients: Object,
});

// Função para confirmar e disparar a exclusão
const deletePatient = (id) => {
    if (confirm('Tem certeza que deseja excluir este paciente? O histórico será mantido no banco de dados por segurança.')) {
        router.delete(route('patients.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Pacientes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pacientes</h2>
                
                <Link :href="route('patients.create')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    + Novo Paciente
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Alerta de Sucesso (Exibe a flash message que vem do Controller) -->
                <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ $page.props.flash.success }}</span>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    
                    <div class="p-6 text-gray-900 overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nome</th>
                                    <th scope="col" class="px-6 py-3">CPF</th>
                                    <th scope="col" class="px-6 py-3">Telefone</th>
                                    <th scope="col" class="px-6 py-3">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="patient in patients.data" :key="patient.id" class="border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ patient.name }}</td>
                                    <td class="px-6 py-4">{{ patient.cpf }}</td>
                                    <td class="px-6 py-4">{{ patient.phone || 'Não informado' }}</td>
                                    <td class="px-6 py-4">
                                        <Link :href="route('patients.show', patient.id)" class="text-blue-600 hover:underline mr-3">Prontuário</Link>
                                        <Link :href="route('patients.edit', patient.id)" class="text-gray-600 hover:underline mr-3">Editar</Link>
                                        
                                        <!-- Botão de Excluir chamando a função -->
                                        <button @click="deletePatient(patient.id)" class="text-red-600 hover:underline cursor-pointer">
                                            Excluir
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div class="mt-4 flex justify-between items-center" v-if="patients.links && patients.data.length > 0">
                            <Link 
                                v-for="(link, index) in patients.links" 
                                :key="index"
                                :href="link.url || '#'" 
                                v-html="link.label"
                                class="px-3 py-1 border rounded"
                                :class="{'bg-blue-50 text-blue-600 font-bold': link.active, 'text-gray-400': !link.url}"
                            />
                        </div>
                        
                        <div v-if="patients.data.length === 0" class="text-center py-8 text-gray-500">
                            Nenhum paciente cadastrado ainda.
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>