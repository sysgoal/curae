<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',       // Novo campo para login
    password: '',    // Novo campo para login
    cpf: '',
    phone: '',
    profession: '',
    specialty: '',
    council_type: '',
    council_number: '',
    council_state: '',
    is_active: true,
});

const submit = () => {
    form.post(route('professionals.store'), {
        onError: () => {
            if (form.errors.password) {
                form.reset('password');
            }
        }
    });
};
</script>

<template>
    <Head title="Novo Profissional" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Cadastrar Novo Profissional
                </h2>
                <Link :href="route('professionals.index')" class="text-gray-600 hover:underline text-sm font-medium">
                    &larr; Voltar para lista
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="Object.keys(form.errors).length > 0" class="mb-4 bg-red-50 border-l-4 border-red-500 p-4">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-red-700 font-bold">Por favor, corrija os erros abaixo:</p>
                            <ul class="list-disc list-inside text-sm text-red-600 mt-1">
                                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 space-y-8">
                    
                    <div class="bg-indigo-50 p-6 rounded-lg border border-indigo-100">
                        <h3 class="text-lg font-bold text-indigo-900 border-b border-indigo-200 pb-2 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            Dados de Acesso ao Sistema
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel value="E-mail de Login *" />
                                <TextInput type="email" class="mt-1 block w-full" v-model="form.email" placeholder="medico@curae.com" required />
                            </div>
                            <div>
                                <InputLabel value="Senha Inicial *" />
                                <TextInput type="password" class="mt-1 block w-full" v-model="form.password" placeholder="Mínimo de 8 caracteres" required minlength="8" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-gray-900 border-b pb-2 mb-4">Dados Cadastrais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <InputLabel value="Nome Completo *" />
                                <TextInput type="text" class="mt-1 block w-full" v-model="form.name" required />
                            </div>
                            <div>
                                <InputLabel value="CPF *" />
                                <TextInput type="text" class="mt-1 block w-full" v-model="form.cpf" placeholder="000.000.000-00" maxlength="14" required />
                            </div>
                            <div>
                                <InputLabel value="Telefone / WhatsApp" />
                                <TextInput type="text" class="mt-1 block w-full" v-model="form.phone" placeholder="(00) 00000-0000" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-gray-900 border-b pb-2 mb-4">Atuação e Registro de Classe</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            
                            <div class="md:col-span-1">
                                <InputLabel value="Profissão *" />
                                <TextInput type="text" class="mt-1 block w-full" v-model="form.profession" placeholder="Ex: Médico, Nutricionista" required />
                            </div>
                            <div class="md:col-span-2">
                                <InputLabel value="Especialidade" />
                                <TextInput type="text" class="mt-1 block w-full" v-model="form.specialty" placeholder="Ex: Cardiologia, Pediatria" />
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 md:col-span-3 grid grid-cols-1 md:grid-cols-4 gap-4 mt-2">
                                <div>
                                    <InputLabel value="Conselho (Sigla)" />
                                    <select v-model="form.council_type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        <option value="">Selecione</option>
                                        <option value="CRM">CRM</option>
                                        <option value="COREN">COREN</option>
                                        <option value="CRN">CRN</option>
                                        <option value="CRP">CRP</option>
                                        <option value="CREFITO">CREFITO</option>
                                        <option value="CRO">CRO</option>
                                        <option value="OUTRO">OUTRO</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <InputLabel value="Número de Registro" />
                                    <TextInput type="text" class="mt-1 block w-full font-mono text-sm" v-model="form.council_number" placeholder="Ex: 123456" />
                                </div>
                                <div>
                                    <InputLabel value="Estado (UF)" />
                                    <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.council_state" placeholder="Ex: MG" maxlength="2" />
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="border-t pt-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" id="is_active" v-model="form.is_active" class="w-5 h-5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <label for="is_active" class="ml-3 text-sm text-gray-700 font-bold">
                                Profissional Ativo (Aparece na Agenda)
                            </label>
                        </div>
                        
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="py-3 px-8 text-sm">
                            Criar Acesso e Perfil
                        </PrimaryButton>
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>