<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    users: Array,
    roles: Array
});

const showingModal = ref(false);
const isEditing = ref(false);
const editingUserId = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: ''
});

const openCreateModal = () => {
    isEditing.value = false;
    editingUserId.value = null;
    form.reset();
    form.clearErrors();
    showingModal.value = true;
};

const openEditModal = (user) => {
    isEditing.value = true;
    editingUserId.value = user.id;
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.password = ''; // A senha vai em branco, se não for preenchida, não é alterada
    form.role = user.role !== 'Sem Cargo' ? user.role : '';
    showingModal.value = true;
};

const submitUser = () => {
    if (isEditing.value) {
        form.put(route('users.update', editingUserId.value), {
            preserveScroll: true,
            onSuccess: () => showingModal.value = false
        });
    } else {
        form.post(route('users.store'), {
            preserveScroll: true,
            onSuccess: () => showingModal.value = false
        });
    }
};

const deleteUser = (id) => {
    if (confirm('Atenção: Tem a certeza que deseja remover este utilizador permanentemente?')) {
        router.delete(route('users.destroy', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Gestão de Utilizadores" />
    <AuthenticatedLayout>
        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Mensagens de Sucesso Globais -->
            <div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 font-bold shadow-sm flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                {{ $page.props.flash.success }}
            </div>

            <!-- Mensagens de Erro Globais -->
            <div v-if="$page.props.flash?.error" class="mb-6 bg-red-50 text-red-700 p-4 rounded-xl border border-red-200 font-bold shadow-sm flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                {{ $page.props.flash.error }}
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white">
                    <div>
                        <h3 class="text-lg font-black text-gray-900">Equipa e Acessos</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Crie e edite acessos para médicos, secretárias e administradores.</p>
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
                                <th class="p-4">Membro Desde</th>
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
                                    <span class="px-3 py-1 text-[10px] font-black uppercase tracking-wider rounded-md border" 
                                          :class="user.role === 'admin' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-indigo-50 text-indigo-700 border-indigo-200'">
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="p-4 text-gray-600 font-medium">{{ user.created_at }}</td>
                                <td class="p-4 text-right space-x-2 flex items-center justify-end">
                                    <button @click="openEditModal(user)" class="text-indigo-600 hover:text-indigo-800 font-bold text-xs bg-indigo-50 hover:bg-indigo-100 px-3 py-2 rounded-lg transition-colors">
                                        Editar
                                    </button>
                                    <button @click="deleteUser(user.id)" class="text-red-500 hover:text-red-700 font-bold text-xs bg-red-50 hover:bg-red-100 px-3 py-2 rounded-lg transition-colors">
                                        Remover
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td colspan="4" class="p-8 text-center text-gray-400">Nenhum utilizador encontrado.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL DE CRIAÇÃO / EDIÇÃO -->
        <Modal :show="showingModal" @close="showingModal = false" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-black text-gray-900 border-b pb-3 mb-5">
                    {{ isEditing ? 'Editar Utilizador' : 'Criar Novo Acesso' }}
                </h3>
                
                <form @submit.prevent="submitUser" class="space-y-4">
                    <div>
                        <InputLabel value="Nome Completo *" />
                        <TextInput type="text" class="mt-1 block w-full text-sm" v-model="form.name" required autofocus />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel value="Email de Login *" />
                        <TextInput type="email" class="mt-1 block w-full text-sm" v-model="form.email" required />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel :value="isEditing ? 'Nova Senha (deixe em branco para manter a atual)' : 'Senha de Acesso *'" />
                        <TextInput type="password" class="mt-1 block w-full text-sm" v-model="form.password" :required="!isEditing" placeholder="Mínimo de 8 caracteres" />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel value="Perfil de Acesso (Cargo) *" />
                        <select v-model="form.role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 rounded-lg shadow-sm text-sm" required>
                            <option value="" disabled>Selecione o cargo...</option>
                            <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t gap-3 mt-6">
                        <button type="button" @click="showingModal = false" class="text-sm font-bold text-gray-600 hover:text-gray-900">Cancelar</button>
                        <PrimaryButton :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700">
                            {{ isEditing ? 'Guardar Alterações' : 'Registar Utilizador' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>