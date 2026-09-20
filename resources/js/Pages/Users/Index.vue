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
    role: '',
    phone: '',
    specialty: '',
    council_type: '',
    council_number: ''
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
    form.password = ''; 
    form.role = user.role !== 'Sem Cargo' ? user.role : '';
    
    form.phone = user.phone || '';
    form.specialty = user.specialty || '';
    form.council_type = user.council_type || '';
    form.council_number = user.council_number || '';
    
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
            
            <div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 font-bold shadow-sm flex items-center gap-2">
                ✅ {{ $page.props.flash.success }}
            </div>

            <div v-if="$page.props.flash?.error" class="mb-6 bg-red-50 text-red-700 p-4 rounded-xl border border-red-200 font-bold shadow-sm flex items-center gap-2">
                ❌ {{ $page.props.flash.error }}
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white">
                    <div>
                        <h3 class="text-lg font-black text-gray-900">Equipa e Acessos</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Crie e edite acessos para médicos, secretárias e administradores.</p>
                    </div>
                    <button @click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-bold text-sm shadow-sm transition-all">
                        + Novo Utilizador
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b text-xs uppercase tracking-wider text-gray-500 font-black">
                                <th class="p-4">Nome e Contacto</th>
                                <th class="p-4">Perfil / Conselho</th>
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
                                    <div v-if="user.council_number" class="text-xs font-bold text-gray-500 mt-1">
                                        {{ user.council_type }}: {{ user.council_number }}
                                    </div>
                                </td>
                                <td class="p-4 text-gray-600 font-medium">{{ user.created_at }}</td>
                                <td class="p-4 text-right space-x-2">
                                    <button @click="openEditModal(user)" class="text-indigo-600 hover:text-indigo-800 font-bold text-xs bg-indigo-50 hover:bg-indigo-100 px-3 py-2 rounded-lg">Editar</button>
                                    <button @click="deleteUser(user.id)" class="text-red-500 hover:text-red-700 font-bold text-xs bg-red-50 hover:bg-red-100 px-3 py-2 rounded-lg">Remover</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="showingModal" @close="showingModal = false" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-black text-gray-900 border-b pb-3 mb-5">
                    {{ isEditing ? 'Editar Utilizador' : 'Criar Novo Acesso' }}
                </h3>
                
                <form @submit.prevent="submitUser" class="space-y-4">
                    
                    <!-- DADOS DE ACESSO -->
                    <div>
                        <InputLabel value="Nome Completo *" />
                        <TextInput type="text" class="mt-1 block w-full text-sm" v-model="form.name" required autofocus />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Email de Login *" />
                            <TextInput type="email" class="mt-1 block w-full text-sm" v-model="form.email" required />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>
                        <div>
                            <InputLabel :value="isEditing ? 'Nova Senha (opcional)' : 'Senha de Acesso *'" />
                            <TextInput type="password" class="mt-1 block w-full text-sm" v-model="form.password" :required="!isEditing" placeholder="Mín. 8 caract." />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Perfil de Acesso (Cargo) *" />
                        <select v-model="form.role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 rounded-lg shadow-sm text-sm" required>
                            <option value="" disabled>Selecione o cargo...</option>
                            <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>

                    <!-- DADOS CLÍNICOS (Sempre visíveis agora) -->
                    <div class="mt-6 pt-4 border-t border-gray-100 bg-gray-50 -mx-6 px-6 pb-2">
                        <h4 class="text-sm font-black text-indigo-700 mb-4 flex items-center gap-2">
                            ⚕️ Dados do Profissional Clínico (Preencha se for o caso)
                        </h4>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <InputLabel value="Especialidade Principal" />
                                <TextInput type="text" class="mt-1 block w-full text-sm" v-model="form.specialty" placeholder="Ex: Cardiologia" />
                            </div>
                            <div>
                                <InputLabel value="Telefone / Telemóvel" />
                                <TextInput type="text" class="mt-1 block w-full text-sm" v-model="form.phone" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <InputLabel value="Órgão de Classe" />
                                <select v-model="form.council_type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 rounded-lg shadow-sm text-sm">
                                    <option value="">Selecione...</option>
                                    <option value="CRM">CRM (Médico)</option>
                                    <option value="COREN">COREN (Enfermagem)</option>
                                    <option value="CRO">CRO (Odontologia)</option>
                                    <option value="CREFITO">CREFITO (Fisioterapia)</option>
                                    <option value="CRP">CRP (Psicologia)</option>
                                    <option value="Outro">Outro</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Número de Registo" />
                                <TextInput type="text" class="mt-1 block w-full text-sm" v-model="form.council_number" placeholder="Ex: 12345-MG" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t gap-3 mt-6">
                        <button type="button" @click="showingModal = false" class="text-sm font-bold text-gray-600 hover:text-gray-900">Cancelar</button>
                        <PrimaryButton :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700">
                            {{ isEditing ? 'Guardar Alterações' : 'REGISTAR UTILIZADOR' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>