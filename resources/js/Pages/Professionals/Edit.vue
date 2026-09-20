<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    professional: Object,
    roles: Array
});

const form = useForm({
    name: props.professional.name,
    email: props.professional.user ? props.professional.user.email : '',
    password: '',
    password_confirmation: '',
    role: props.professional.user && props.professional.user.roles.length > 0 ? props.professional.user.roles[0].name : '',
    
    cpf: props.professional.cpf,
    phone: props.professional.phone || '',
    profession: props.professional.profession,
    specialty: props.professional.specialty || '',
    council_type: props.professional.council_type || '',
    council_number: props.professional.council_number || '',
    council_state: props.professional.council_state || '',
    is_active: props.professional.is_active ? 1 : 0,
});

const submit = () => {
    form.put(route('professionals.update', props.professional.id), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Editar Profissional" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Profissional: {{ professional.name }}</h2>
                <Link :href="route('professionals.index')" class="text-gray-600 hover:underline text-sm font-medium">&larr; Voltar</Link>
            </div>
        </template>

        <div class="py-12 max-w-5xl mx-auto sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 p-8 space-y-8">
                
                <div>
                    <h3 class="text-lg font-black text-indigo-900 border-b pb-2 mb-4">1. Dados de Acesso e Login</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel value="Nome Completo *" />
                            <TextInput type="text" class="mt-1 block w-full bg-gray-50" v-model="form.name" required />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <div>
                            <InputLabel value="E-mail (Login) *" />
                            <TextInput type="email" class="mt-1 block w-full bg-gray-50" v-model="form.email" required />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>
                        <div>
                            <InputLabel value="Nova Palavra-passe (Deixe em branco para manter a atual)" />
                            <TextInput type="password" class="mt-1 block w-full bg-gray-50" v-model="form.password" autocomplete="new-password" />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>
                        <div>
                            <InputLabel value="Confirmar Nova Palavra-passe" />
                            <TextInput type="password" class="mt-1 block w-full bg-gray-50" v-model="form.password_confirmation" autocomplete="new-password" />
                        </div>
                        <div>
                            <InputLabel value="Perfil de Permissões (Role) *" />
                            <select v-model="form.role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm bg-gray-50" required>
                                <option disabled value="">Selecione o nível de acesso...</option>
                                <option v-for="role in roles" :key="role.id" :value="role.name">
                                    {{ role.name.charAt(0).toUpperCase() + role.name.slice(1) }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.role" />
                        </div>
                        <div>
                            <InputLabel value="Status do Profissional *" />
                            <select v-model="form.is_active" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm bg-gray-50" required>
                                <option :value="1">Ativo (Acesso Liberado)</option>
                                <option :value="0">Inativo (Acesso Bloqueado)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-black text-indigo-900 border-b pb-2 mb-4">2. Informações do Perfil Profissional</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <InputLabel value="CPF *" />
                            <TextInput type="text" class="mt-1 block w-full" v-model="form.cpf" required />
                            <InputError class="mt-2" :message="form.errors.cpf" />
                        </div>
                        <div>
                            <InputLabel value="Telemóvel / Telefone" />
                            <TextInput type="text" class="mt-1 block w-full" v-model="form.phone" />
                            <InputError class="mt-2" :message="form.errors.phone" />
                        </div>
                        <div>
                            <InputLabel value="Profissão *" />
                            <TextInput type="text" class="mt-1 block w-full" v-model="form.profession" required />
                            <InputError class="mt-2" :message="form.errors.profession" />
                        </div>
                        <div>
                            <InputLabel value="Especialidade" />
                            <TextInput type="text" class="mt-1 block w-full" v-model="form.specialty" />
                            <InputError class="mt-2" :message="form.errors.specialty" />
                        </div>
                        <div>
                            <InputLabel value="Conselho Regional" />
                            <TextInput type="text" class="mt-1 block w-full" v-model="form.council_type" placeholder="Ex: CRM, COREN" />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <InputLabel value="Número" />
                                <TextInput type="text" class="mt-1 block w-full" v-model="form.council_number" />
                            </div>
                            <div>
                                <InputLabel value="UF" />
                                <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.council_state" maxlength="2" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-4 border-t gap-4">
                    <Link :href="route('professionals.index')" class="text-sm font-bold text-gray-600 hover:text-gray-900">Cancelar</Link>
                    <PrimaryButton :disabled="form.processing" class="bg-indigo-700 hover:bg-indigo-800 py-3 px-8 rounded-xl">
                        Atualizar Cadastro
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>