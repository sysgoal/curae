<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Painel de Controlo" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Painel de Controlo</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Mensagem de Boas-vindas -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100">
                    <div class="p-6 text-gray-900 font-medium">
                        👋 Bem-vindo(a) ao sistema, <span class="font-bold text-indigo-600">{{ $page.props.auth.user.name }}</span>!
                    </div>
                </div>

                <!-- Grelha de Acesso Rápido -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    
                    <!-- Card: Agenda Clínica -->
                    <Link :href="route('appointments.index')" class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border border-gray-100 hover:border-indigo-300 hover:shadow-md transition-all group cursor-pointer">
                        <div class="flex items-center gap-4">
                            <div class="bg-indigo-50 p-4 rounded-lg text-2xl group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                📅
                            </div>
                            <div>
                                <h3 class="font-black text-gray-900 group-hover:text-indigo-700 transition-colors">Agenda Clínica</h3>
                                <p class="text-xs text-gray-500 mt-1">Gerir marcações e horários</p>
                            </div>
                        </div>
                    </Link>

                    <!-- Card: Gestão de Utilizadores (Visível apenas para Admin/Secretaria) -->
                    <Link v-if="$page.props.auth?.roles?.includes('admin') || $page.props.auth?.roles?.includes('secretaria')" 
                          :href="route('users.index')" 
                          class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border border-gray-100 hover:border-indigo-300 hover:shadow-md transition-all group cursor-pointer">
                        <div class="flex items-center gap-4">
                            <div class="bg-indigo-50 p-4 rounded-lg text-2xl group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                👥
                            </div>
                            <div>
                                <h3 class="font-black text-gray-900 group-hover:text-indigo-700 transition-colors">Equipa e Acessos</h3>
                                <p class="text-xs text-gray-500 mt-1">Criar utilizadores e cargos</p>
                            </div>
                        </div>
                    </Link>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>