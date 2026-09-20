<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

const showingNavigationDropdown = ref(false);
const page = usePage();

const hasRole = (rolesAllowed) => {
    const userRoles = page.props.auth.roles || [];
    return userRoles.includes('admin') || rolesAllowed.some(role => userRoles.includes(role));
};
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav class="bg-white border-b border-gray-100 shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')" class="font-black text-xl text-indigo-600 tracking-tight">
                                    CURAE
                                </Link>
                            </div>

                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Painel Inicial
                                </NavLink>

                                <NavLink v-if="hasRole(['secretaria', 'medico', 'enfermeira', 'nutricionista', 'fisioterapeuta'])" 
                                         :href="route('patients.index')" :active="route().current('patients.*')">
                                    Pacientes
                                </NavLink>

                                <NavLink v-if="hasRole(['secretaria', 'medico', 'enfermeira', 'nutricionista', 'fisioterapeuta'])" 
                                         :href="route('appointments.index')" :active="route().current('appointments.*')">
                                    Agendamentos
                                </NavLink>

                                <NavLink v-if="hasRole([])" :href="route('professionals.index')" :active="route().current('professionals.*')">
                                    Equipa Técnica
                                </NavLink>
                                <NavLink v-if="$page.props.auth?.roles?.includes('admin')" :href="route('users.index')" :active="route().current('users.*')">
        Equipe e Usuários
    </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <div class="ms-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-bold rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                                {{ $page.props.auth.user.name }}
                                                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                            </button>
                                        </span>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')"> O meu Perfil </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button"> Terminar Sessão </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>