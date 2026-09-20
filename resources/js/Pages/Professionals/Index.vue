<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    professionals: {
        type: [Object, Array],
        default: () => ({ data: [] })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', role: '' })
    }
});

const page = usePage();

// ==========================================
// FILTROS E PESQUISA COM DEBOUNCE NATIVO
// ==========================================
const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');

let searchTimeout = null;

watch([search, roleFilter], ([newSearch, newRole]) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('professionals.index'), {
            search: newSearch,
            role: newRole
        }, {
            preserveState: true, 
            replace: true        
        });
    }, 300);
});

// ==========================================
// SEGURANÇA E FORMATAÇÃO DE DADOS
// ==========================================
// Protege a tela contra falhas: aceita tanto dados paginados (.data) quanto arrays simples
const professionalList = computed(() => {
    return Array.isArray(props.professionals) ? props.professionals : (props.professionals.data || []);
});

const deleteProfessional = (id) => {
    if (confirm('Tem a certeza que deseja remover este profissional? O acesso ao sistema será revogado imediatamente.')) {
        router.delete(route('professionals.destroy', id), { preserveScroll: true });
    }
};

// Formatação visual do perfil (Role)
const formatRole = (roles) => {
    if (!roles || roles.length === 0) return 'Sem Perfil';
    const roleMap = {
        'admin': 'Administrador',
        'secretaria': 'Secretaria',
        'medico': 'Médico(a)',
        'enfermeira': 'Enfermeiro(a)',
        'nutricionista': 'Nutricionista',
        'fisioterapeuta': 'Fisioterapeuta'
    };
    return roles.map(r => roleMap[r.name] || r.name).join(', ');
};
</script>

<template>
    <Head title="Gestão de Profissionais" />

    <AuthenticatedLayout>
        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 font-bold shadow-sm">
                {{ $page.props.flash.success }}
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                
                <!-- CABEÇALHO -->
                <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white">
                    <div>
                        <h3 class="text-lg font-black text-gray-900">Equipa Clínica</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Gestão de profissionais, conselhos e acessos.</p>
                    </div>

                    <Link :href="route('professionals.create')" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-bold text-sm shadow-sm transition-all flex justify-center items-center gap-2">
                        + Novo Profissional
                    </Link>
                </div>

                <!-- BARRA DE FILTROS E PESQUISA -->
                <div class="p-4 bg-gray-50 border-b border-gray-100 flex flex-col md:flex-row gap-4 items-center justify-between">
                    <div class="w-full md:w-1/2 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg>
                        </div>
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Pesquisar por nome, CRM/Conselho ou especialidade..." 
                            class="pl-9 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl text-sm shadow-sm"
                        >
                    </div>

                    <div class="w-full md:w-auto flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo:</span>
                        <select v-model="roleFilter" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl text-sm shadow-sm min-w-[180px]">
                            <option value="">Todos os Profissionais</option>
                            <option value="medico">Médico(a)</option>
                            <option value="enfermeira">Enfermeiro(a)</option>
                            <option value="nutricionista">Nutricionista</option>
                            <option value="fisioterapeuta">Fisioterapeuta</option>
                            <option value="secretaria">Secretaria</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>

                <!-- LISTAGEM DE PROFISSIONAIS -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white border-b text-xs uppercase tracking-wider text-gray-500 font-black">
                                <th class="p-4">Profissional</th>
                                <th class="p-4">Especialidade / Conselho</th>
                                <th class="p-4">Perfil de Acesso</th>
                                <th class="p-4 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100">
                            <tr v-if="professionalList.length === 0">
                                <td colspan="4" class="p-8 text-center text-gray-400">Nenhum profissional encontrado com os filtros atuais.</td>
                            </tr>
                            <tr v-for="prof in professionalList" :key="prof.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-gray-900">{{ prof.name }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ prof.user?.email }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-indigo-700">{{ prof.specialty || 'Clínico Geral' }}</div>
                                    <div class="text-[10px] font-black uppercase text-gray-400 mt-0.5 tracking-wider">{{ prof.council_number || 'S/ Registo' }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider">
                                        {{ formatRole(prof.user?.roles) }}
                                    </span>
                                </td>
                                <td class="p-4 text-right space-x-2">
                                    <Link :href="route('professionals.edit', prof.id)" class="inline-block text-indigo-600 hover:text-indigo-800 font-bold text-xs bg-indigo-50 hover:bg-indigo-100 px-3 py-2 rounded-lg transition-colors">
                                        Editar
                                    </Link>
                                    <button @click="deleteProfessional(prof.id)" class="text-red-500 hover:text-red-700 font-bold text-xs bg-red-50 hover:bg-red-100 px-3 py-2 rounded-lg transition-colors">
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINAÇÃO (Se fornecida pelo Backend) -->
                <div v-if="professionals.links && professionals.links.length > 3" class="p-4 border-t border-gray-100 bg-gray-50 flex justify-center">
                    <div class="flex flex-wrap gap-1">
                        <template v-for="(link, key) in professionals.links" :key="key">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm leading-4 text-gray-400 border rounded-lg bg-white" v-html="link.label" />
                            <Link v-else :href="link.url" class="mr-1 mb-1 px-4 py-2 text-sm leading-4 border rounded-lg transition-colors" :class="{ 'bg-indigo-600 text-white border-indigo-600 font-bold': link.active, 'bg-white hover:bg-gray-100 text-gray-700': !link.active }" v-html="link.label" />
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>