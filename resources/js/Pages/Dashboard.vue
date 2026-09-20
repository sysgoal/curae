<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = page.props.auth.user;

const isAdmin = computed(() => page.props.auth.roles.includes('admin'));
const isSecretaria = computed(() => page.props.auth.roles.includes('secretaria'));
const isMedico = computed(() => page.props.auth.roles.includes('medico'));
const isEnfermeira = computed(() => page.props.auth.roles.includes('enfermeira'));
const isNutri = computed(() => page.props.auth.roles.includes('nutricionista'));
const isFisio = computed(() => page.props.auth.roles.includes('fisioterapeuta'));

const props = defineProps({ stats: Object });

const getRoleDisplay = () => {
    if (isAdmin.value) return 'Administrador Geral';
    if (isSecretaria.value) return 'Secretaria / Recepção';
    if (isMedico.value) return 'Médico Integrativo';
    if (isEnfermeira.value) return 'Equipa de Enfermagem';
    if (isNutri.value) return 'Nutricionista Funcional';
    if (isFisio.value) return 'Fisioterapeuta';
    return 'Profissional de Saúde';
};
</script>

<template>
    <Head title="Painel Inicial" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Painel de Controlo</h2>
        </template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm">
                <h3 class="text-2xl font-black text-gray-900">Bem-vindo, {{ user.name }}</h3>
                <p class="text-sm text-gray-500 mt-1 font-medium">Nível de credenciais ativo: <span class="text-indigo-600 font-bold uppercase tracking-wider text-xs">{{ getRoleDisplay() }}</span></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <div class="bg-gradient-to-br from-emerald-600 to-emerald-800 p-6 rounded-2xl text-white shadow-sm flex flex-col justify-between">
                    <div>
                        <h4 class="font-bold text-sm uppercase tracking-wider opacity-75">
                            {{ isAdmin || isSecretaria ? 'Total de Pacientes' : 'Meus Pacientes' }}
                        </h4>
                        <p class="text-4xl font-black mt-2">
                            {{ isAdmin || isSecretaria ? (stats.total_patients ?? 0) : (stats.total_linked_patients ?? 0) }}
                        </p>
                    </div>
                    <Link :href="route('patients.index')" class="mt-6 block text-center bg-white text-emerald-700 font-bold text-xs py-2 rounded-lg hover:bg-gray-50 transition-colors">
                        {{ isAdmin || isSecretaria ? 'Cadastrar / Localizar Paciente' : 'Ver Meus Pacientes' }}
                    </Link>
                </div>

                <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 p-6 rounded-2xl text-white shadow-sm flex flex-col justify-between">
                    <div>
                        <h4 class="font-bold text-sm uppercase tracking-wider opacity-75">
                            {{ isAdmin ? 'Agendamentos Pendentes' : 'Consultas de Hoje' }}
                        </h4>
                        <p class="text-4xl font-black mt-2">
                            {{ isAdmin ? (stats.pending_appointments ?? 0) : (isSecretaria ? (stats.today_appointments ?? 0) : (stats.my_appointments_today ?? 0)) }}
                        </p>
                    </div>
                    <Link :href="route('appointments.index')" class="mt-6 block text-center bg-white text-indigo-700 font-bold text-xs py-2 rounded-lg hover:bg-gray-50 transition-colors">
                        Ver Agenda
                    </Link>
                </div>

                <div v-if="isAdmin" class="bg-gradient-to-br from-purple-600 to-purple-800 p-6 rounded-2xl text-white shadow-sm flex flex-col justify-between">
                    <div>
                        <h4 class="font-bold text-sm uppercase tracking-wider opacity-75">Profissionais Ativos</h4>
                        <p class="text-4xl font-black mt-2">{{ stats.total_professionals ?? 0 }}</p>
                    </div>
                    <Link :href="route('professionals.index')" class="mt-6 block text-center bg-white text-purple-700 font-bold text-xs py-2 rounded-lg hover:bg-gray-50 transition-colors">
                        Gerir Equipa
                    </Link>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>