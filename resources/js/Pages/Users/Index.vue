<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    users: Array,
    roles: Array
});

const showingCreateModal = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: ''
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    showingCreateModal.value = true;
};

const submitUser = () => {
    form.post(route('users.store'), {
        onSuccess: () => showingCreateModal.value = false
    });
};

const deleteUser = (id) => {
    if (confirm('Atenção: Tem a certeza que deseja remover este utilizador e bloquear o seu acesso?')) {
        router.delete(route('users.destroy', id));
    }
};
</script>

<template>
    <Head title="Gestão de Utilizadores" />
    <AuthenticatedLayout>
        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 font-bold shadow-sm">
                {{ $page.props.flash.success }}
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white">
                    <div>
                        <h3 class="text-lg font-black text-gray-900">Equipa e Acessos</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Crie acessos para médicos, secretárias e administradores.</p>
                    </div>
                    <button @click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-bold text-sm shadow-sm transition-all flex justify-center items-center gap-2">
                        + Novo Utilizador
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b text-xs uppercase tracking-wider text-gray-500 font-black">
                                <th class="p-4">Nome e Contacto</th>
                                <th class="p-4">Perfil de Acesso</th>
                                <th class="p-4">Data de Criação</th>
                                <th class="p-4 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-gray-900">{{ user.name }}</div>
                                    <div class="text-xs text-gray-500">{{ user.email }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="px-3 py-1 text-[10px] font-black uppercase tracking-wider rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="p-4 text-gray-600 font-medium">{{ user.created_at }}</td>
                                <td class="p-4 text-right">
                                    <button @click="deleteUser(user.id)" class="text-red-500 hover:text-red-700 font-bold text-xs bg-red-50 hover:bg-red-100 px-3 py-2 rounded-lg transition-colors">
                                        Remover
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL DE CRIAÇÃO -->
        <Modal :show="showingCreateModal" @close="showingCreateModal = false" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-black text-gray-900 border-b pb-3 mb-5">Criar Novo Acesso</h3>
                
                <form @submit.prevent="submitUser" class="space-y-4">
                    <div>
                        <InputLabel value="Nome Completo *" />
                        <TextInput type="text" class="mt-1 block w-full text-sm" v-model="form.name" required autofocus />
                    </div>

                    <div>
                        <InputLabel value="Email de Login *" />
                        <TextInput type="email" class="mt-1 block w-full text-sm" v-model="form.email" required />
                    </div>

                    <div>
                        <InputLabel value="Senha de Acesso *" />
                        <TextInput type="password" class="mt-1 block w-full text-sm" v-model="form.password" required minlength="8" />
                    </div>

                    <div>
                        <InputLabel value="Perfil de Acesso (Cargo) *" />
                        <select v-model="form.role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 rounded-lg shadow-sm text-sm" required>
                            <option value="" disabled>Selecione o cargo...</option>
                            <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t gap-3 mt-6">
                        <button type="button" @click="showingCreateModal = false" class="text-sm font-bold text-gray-600 hover:text-gray-900">Cancelar</button>
                        <PrimaryButton :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700">Registar Utilizador</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>