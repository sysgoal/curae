<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    name: '',
    cpf: '',
    phone: '',
    email: '',
    date_of_birth: '',
});

const submit = () => {
    form.post(route('patients.store'));
};
</script>

<template>
    <Head title="Cadastrar Paciente" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo Registro de Paciente</h2>
                <Link :href="route('patients.index')" class="text-gray-600 hover:underline text-sm font-medium">&larr; Voltar</Link>
            </div>
        </template>

        <div class="py-12 max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <InputLabel value="Nome Completo *" />
                            <TextInput type="text" class="mt-1 block w-full" v-model="form.name" required autofocus />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <div>
                            <InputLabel value="CPF *" />
                            <TextInput type="text" class="mt-1 block w-full" v-model="form.cpf" placeholder="000.000.000-00" required />
                            <InputError class="mt-2" :message="form.errors.cpf" />
                        </div>
                        <div>
                            <InputLabel value="Data de Nascimento *" />
                            <TextInput type="date" class="mt-1 block w-full" v-model="form.date_of_birth" required />
                            <InputError class="mt-2" :message="form.errors.date_of_birth" />
                        </div>
                        <div>
                            <InputLabel value="Telefone / WhatsApp" />
                            <TextInput type="text" class="mt-1 block w-full" v-model="form.phone" placeholder="(00) 00000-0000" />
                            <InputError class="mt-2" :message="form.errors.phone" />
                        </div>
                        <div>
                            <InputLabel value="E-mail" />
                            <TextInput type="email" class="mt-1 block w-full" v-model="form.email" placeholder="paciente@email.com" />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t gap-4">
                        <Link :href="route('patients.index')" class="text-sm font-bold text-gray-600">Cancelar</Link>
                        <PrimaryButton :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 py-3 px-6 rounded-xl">
                            Salvar Registro
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>