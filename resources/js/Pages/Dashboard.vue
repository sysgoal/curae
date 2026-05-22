<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    nextAppointments: Array
});

const formatTime = (timeStr) => {
    if (!timeStr) return '';
    return timeStr.substring(0, 5);
};

const statusMeta = (status) => {
    if (!status) return { label: 'Indefinido', class: 'bg-gray-100 text-gray-800' };
    const safeStatus = String(status).toLowerCase().trim();
    const map = {
        agendado: { label: 'Agendado', class: 'bg-blue-100 text-blue-800' },
        confirmado: { label: 'Confirmado', class: 'bg-indigo-100 text-indigo-800' },
        espera: { label: 'Em Espera', class: 'bg-amber-100 text-amber-800' },
        atendimento: { label: 'Em Atendimento', class: 'bg-purple-100 text-purple-800' },
    };
    return map[safeStatus] || { label: status, class: 'bg-gray-100 text-gray-800' };
};
</script>

<template>
    <Head title="Painel Administrativo" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Painel Administrativo e Operacional
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 flex items-center gap-4">
                        <div class="bg-indigo-600 p-3 rounded-full text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 tracking-tight">Centro de Comando: Curae</h3>
                            <p class="text-sm text-gray-500 font-medium">Gestão de Pacientes, Profissionais e Agendamentos.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
                        <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pacientes Registrados</dt>
                        <dd class="mt-2 text-3xl font-black text-gray-900">{{ stats.total_patients }}</dd>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-purple-500">
                        <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider">Equipe Profissional</dt>
                        <dd class="mt-2 text-3xl font-black text-gray-900">{{ stats.total_professionals }}</dd>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-blue-500">
                        <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider">Consultas Hoje</dt>
                        <dd class="mt-2 text-3xl font-black text-gray-900">{{ stats.appointments_today }}</dd>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-emerald-500">
                        <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider">Concluídos Hoje</dt>
                        <dd class="mt-2 text-3xl font-black text-gray-900">{{ stats.completed_today }}</dd>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between border-b pb-3 mb-4">
                            <h3 class="text-lg font-bold text-gray-900">Fila de Atendimento (Próximos)</h3>
                            <Link :href="route('appointments.index')" class="text-sm font-bold text-indigo-600 hover:underline">Ver Agenda Completa</Link>
                        </div>

                        <div v-if="nextAppointments.length > 0" class="space-y-3">
                            <div v-for="app in nextAppointments" :key="app.id" class="flex items-center justify-between p-4 bg-gray-50 border border-gray-100 rounded-xl hover:bg-white hover:shadow-md transition-all duration-200">
                                <div class="flex items-center gap-4">
                                    <div class="text-lg font-mono font-bold text-indigo-700 bg-white border border-indigo-200 px-3 py-1 rounded shadow-sm">
                                        {{ formatTime(app.start_time) }}
                                    </div>
                                    <div>
                                        <h4 class="font-black text-gray-800">{{ app.patient?.name }}</h4>
                                        <div class="flex items-center gap-2 text-xs font-semibold text-gray-400">
                                            <span class="text-indigo-500">{{ app.type }}</span>
                                            <span>•</span>
                                            <span>Prof: {{ app.professional?.name }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span :class="[statusMeta(app.status).class, 'text-[10px] font-black uppercase tracking-tighter px-2.5 py-1 rounded-full']">
                                        {{ statusMeta(app.status).label }}
                                    </span>
                                    <Link :href="route('patients.show', app.patient_id)" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2 rounded-lg transition-colors" title="Abrir Prontuário">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                        </svg>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-12 text-center text-gray-400 italic text-sm">
                            <p>Não há consultas pendentes para este momento.</p>
                        </div>
                    </div>

                    <div class="lg:col-span-1 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 border-b pb-3 mb-6">Ações do Administrador</h3>
                        
                        <div class="flex flex-col gap-4">
                            <Link :href="route('professionals.create')" class="flex items-center gap-3 bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 px-4 rounded-xl shadow-lg shadow-purple-200 transition-all active:scale-95 group">
                                <div class="bg-purple-500 p-2 rounded-lg group-hover:bg-purple-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                                    </svg>
                                </div>
                                <span class="text-sm">Cadastrar Profissional</span>
                            </Link>

                            <Link :href="route('patients.create')" class="flex items-center gap-3 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold py-4 px-4 rounded-xl transition-all">
                                <div class="bg-gray-100 p-2 rounded-lg text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                </div>
                                <span class="text-sm">Cadastrar Paciente</span>
                            </Link>

                            <Link :href="route('appointments.index')" class="flex items-center gap-3 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold py-4 px-4 rounded-xl transition-all">
                                <div class="bg-gray-100 p-2 rounded-lg text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                    </svg>
                                </div>
                                <span class="text-sm">Gerenciar Agenda</span>
                            </Link>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>