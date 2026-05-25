<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Ficha Recebida" />
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center items-center p-6">
        <div class="bg-white p-8 rounded-2xl shadow-lg max-w-md w-full text-center border-t-8 border-indigo-600">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Ficha Clínica Recebida!</h2>
            <p class="text-gray-600 mb-6">Os seus dados foram enviados de forma segura para o seu profissional de saúde. Já pode fechar esta janela.</p>
        </div>
    </div>
</template>