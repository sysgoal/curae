<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    cpf: '',
    date_of_birth: '',
    phone: '',
    email: '',
    gender: '',
});

const submit = () => {
    form.post(route('patients.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Novo Paciente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cadastrar Novo Paciente</h2>
                <Link :href="route('patients.index')" class="text-gray-600 hover:underline">
                    Voltar para lista
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div>
                            <InputLabel for="name" value="Nome Completo *" />
                            <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="cpf" value="CPF *" />
                                <TextInput id="cpf" type="text" class="mt-1 block w-full" v-model="form.cpf" required placeholder="000.000.000-00" />
                                <InputError class="mt-2" :message="form.errors.cpf" />
                            </div>

                            <div>
                                <InputLabel for="date_of_birth" value="Data de Nascimento *" />
                                <TextInput id="date_of_birth" type="date" class="mt-1 block w-full" v-model="form.date_of_birth" required />
                                <InputError class="mt-2" :message="form.errors.date_of_birth" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="phone" value="Telefone / WhatsApp" />
                                <TextInput id="phone" type="text" class="mt-1 block w-full" v-model="form.phone" placeholder="(00) 00000-0000" />
                                <InputError class="mt-2" :message="form.errors.phone" />
                            </div>

                            <div>
                                <InputLabel for="email" value="E-mail" />
                                <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4 border-t pt-4">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Salvar Paciente
                            </PrimaryButton>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>