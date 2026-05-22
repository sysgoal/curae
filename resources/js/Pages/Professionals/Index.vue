<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    professionals: Array
});

const deleteProfessional = (id) => {
    if (confirm('Tem certeza que deseja remover este profissional do histórico?')) {
        router.delete(route('professionals.destroy', id));
    }
};

const formatCouncil = (prof) => {
    if (!prof.council_type && !prof.council_number) return '---';
    return `${prof.council_type || ''}-${prof.council_state || ''} ${prof.council_number || ''}`.trim();
};
</script>

<template>
    <Head title="Profissionais" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Equipe Profissional
                </h2>
                <Link :href="route('professionals.create')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors shadow-sm">
                    + Novo Profissional
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="$page.props.flash && $page.props.flash.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.success }}
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome / CPF</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Atuação</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registro (Conselho)</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Ações</span></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="prof in professionals" :key="prof.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900">{{ prof.name }}</div>
                                            <div class="text-sm text-gray-500">CPF: {{ prof.cpf }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ prof.profession }}</div>
                                            <div class="text-xs text-gray-500">{{ prof.specialty || 'Clínico Geral' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">
                                            {{ formatCouncil(prof) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="prof.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full border">
                                                {{ prof.is_active ? 'Ativo' : 'Inativo' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="deleteProfessional(prof.id)" class="text-red-400 hover:text-red-700 font-bold transition-colors">Remover</button>
                                        </td>
                                    </tr>
                                    <tr v-if="professionals.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 text-sm italic">
                                            Nenhum profissional cadastrado no sistema.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>