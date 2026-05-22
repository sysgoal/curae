<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    appointments: Array,
    patients: Array,
    professionals: Array // Recebe a lista de profissionais do Controller
});

const selectedDate = ref(new Date().toISOString().substring(0, 10));

const form = useForm({
    patient_id: '',
    professional_id: '', // Novo campo adicionado
    appointment_date: selectedDate.value,
    start_time: '',
    end_time: '',
    type: 'Consulta',
    notes: ''
});

const showingCancelModal = ref(false);
const appointmentToCancel = ref(null);
const cancellationReason = ref('');

const dailyAppointments = computed(() => {
    return props.appointments.filter(app => {
        if (!app.appointment_date) return false;
        const appDateStr = app.appointment_date.substring(0, 10);
        return appDateStr === selectedDate.value;
    });
});

const handleStatusChange = (id, newStatus) => {
    if (newStatus === 'cancelado') {
        appointmentToCancel.value = id;
        cancellationReason.value = '';
        showingCancelModal.value = true;
    } else {
        router.patch(route('appointments.updateStatus', id), { status: newStatus }, {
            preserveScroll: true,
            onError: (errors) => alert('Erro: ' + Object.values(errors).join('\n'))
        });
    }
};

const confirmCancellation = () => {
    if (!cancellationReason.value.trim()) {
        alert('Por favor, informe o motivo do cancelamento.');
        return;
    }
    router.patch(route('appointments.updateStatus', appointmentToCancel.value), {
        status: 'cancelado',
        cancellation_reason: cancellationReason.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showingCancelModal.value = false;
            appointmentToCancel.value = null;
        },
        onError: (errors) => alert('Erro: ' + Object.values(errors).join('\n'))
    });
};

const deleteAppointment = (id) => {
    if (confirm('Tem certeza que deseja remover este agendamento do histórico?')) {
        router.delete(route('appointments.destroy', id), {
            preserveScroll: true
        });
    }
};

const submit = () => {
    form.post(route('appointments.store'), {
        onSuccess: () => {
            form.reset('patient_id', 'start_time', 'end_time', 'type', 'notes');
            // Mantemos o professional_id preenchido caso a secretária queira marcar outro para o mesmo médico
        }
    });
};

const statusMeta = (status) => {
    if (!status) return { label: 'Indefinido', class: 'bg-gray-100 text-gray-800 border-gray-200' };
    const safeStatus = String(status).toLowerCase().trim();
    const map = {
        agendado: { label: 'Agendado', class: 'bg-blue-100 text-blue-800 border-blue-200' },
        confirmado: { label: 'Confirmado', class: 'bg-indigo-100 text-indigo-800 border-indigo-200' },
        espera: { label: 'Em Espera', class: 'bg-amber-100 text-amber-800 border-amber-200' },
        atendimento: { label: 'Em Atendimento', class: 'bg-purple-100 text-purple-800 border-purple-200' },
        finalizado: { label: 'Finalizado', class: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
        falta: { label: 'Falta', class: 'bg-orange-100 text-orange-800 border-orange-200' },
        cancelado: { label: 'Cancelado', class: 'bg-red-100 text-red-800 border-red-200' },
    };
    return map[safeStatus] || { label: status, class: 'bg-gray-100 text-gray-800 border-gray-200' };
};

const formatTime = (timeStr) => {
    if (!timeStr) return '';
    return timeStr.substring(0, 5);
};
</script>

<template>
    <Head title="Agenda de Consultas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Agenda e Marcações
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-1 bg-white p-6 shadow-sm sm:rounded-lg h-fit">
                        <h3 class="text-lg font-bold text-gray-900 border-b pb-2 mb-4">Marcar Consulta</h3>
                        
                        <form @submit.prevent="submit" class="space-y-4 text-sm">
                            
                            <div>
                                <InputLabel value="Profissional *" />
                                <select v-model="form.professional_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm bg-indigo-50" required>
                                    <option value="" disabled>-- Quem vai atender? --</option>
                                    <option v-for="prof in professionals" :key="prof.id" :value="prof.id">
                                        {{ prof.name }} ({{ prof.specialty || prof.profession }})
                                    </option>
                                </select>
                            </div>

                            <div>
                                <InputLabel value="Paciente *" />
                                <select v-model="form.patient_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">-- Escolha o Paciente --</option>
                                    <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                                        {{ patient.name }} (CPF: {{ patient.cpf }})
                                    </option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel value="Início *" />
                                    <TextInput type="time" v-model="form.start_time" class="mt-1 block w-full text-xs" required />
                                </div>
                                <div>
                                    <InputLabel value="Término *" />
                                    <TextInput type="time" v-model="form.end_time" class="mt-1 block w-full text-xs" required />
                                </div>
                            </div>

                            <div>
                                <InputLabel value="Tipo *" />
                                <select v-model="form.type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="Consulta">Consulta</option>
                                    <option value="Retorno">Retorno</option>
                                    <option value="Procedimento">Procedimento</option>
                                    <option value="Exame">Exame</option>
                                </select>
                            </div>

                            <div>
                                <InputLabel value="Observações da Secretária" />
                                <textarea v-model="form.notes" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Opcional..."></textarea>
                            </div>

                            <PrimaryButton :disabled="form.processing" class="w-full justify-center py-3">
                                Agendar Horário
                            </PrimaryButton>
                        </form>
                    </div>

                    <div class="lg:col-span-2 space-y-4">
                        
                        <div class="bg-white p-4 shadow-sm sm:rounded-lg flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="font-bold text-gray-700 text-sm">Navegar pelo Dia:</span>
                                <input type="date" v-model="selectedDate" @change="form.appointment_date = selectedDate" class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md text-sm p-1.5" />
                            </div>
                            <span class="text-xs text-gray-500 font-medium">Total do dia: {{ dailyAppointments.length }}</span>
                        </div>

                        <div class="bg-white p-6 shadow-sm sm:rounded-lg min-h-[55vh]">
                            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded text-sm transition-all">
                                {{ $page.props.flash.success }}
                            </div>

                            <div v-if="dailyAppointments.length > 0" class="space-y-4">
                                <div v-for="app in dailyAppointments" :key="app.id" class="border border-gray-200 rounded-lg p-4 flex flex-wrap items-center justify-between bg-gray-50 hover:bg-white hover:shadow transition-all border-l-4 border-l-indigo-500">
                                    
                                    <div class="flex items-center gap-4">
                                        <div class="text-center">
                                            <div class="text-base font-mono font-bold text-indigo-600 bg-indigo-50 border border-indigo-100 px-2.5 py-1 rounded">
                                                {{ formatTime(app.start_time) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-bold text-gray-900 text-base">{{ app.patient?.name || 'Paciente Removido' }}</h4>
                                                <span class="text-[10px] font-bold uppercase bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded tracking-wide">{{ app.type }}</span>
                                            </div>
                                            <div class="text-xs font-semibold text-indigo-700 mt-1 flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                                </svg>
                                                {{ app.professional?.name || 'Profissional não atribuído' }}
                                            </div>
                                            <p v-if="app.notes" class="text-xs text-gray-500 italic mt-0.5">Obs: {{ app.notes }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3 mt-3 sm:mt-0">
                                        <span :class="[statusMeta(app.status).class, 'text-xs font-bold px-2.5 py-1 rounded-full border']">
                                            {{ statusMeta(app.status).label }}
                                        </span>

                                        <select :value="app.status" @change="handleStatusChange(app.id, $event.target.value)" class="text-xs border-gray-300 rounded p-1 focus:ring-indigo-500 bg-white shadow-sm">
                                            <option value="agendado">Agendado</option>
                                            <option value="confirmado">Confirmado</option>
                                            <option value="espera">Em Espera</option>
                                            <option value="atendimento">Em Atendimento</option>
                                            <option value="finalizado">Finalizado</option>
                                            <option value="falta">Falta</option>
                                            <option value="cancelado">Cancelado</option>
                                        </select>

                                        <Link v-if="app.patient_id" :href="route('patients.show', app.patient_id)" class="text-xs bg-white border border-gray-300 hover:bg-gray-100 font-semibold px-2.5 py-1.5 rounded transition shadow-sm">
                                            Prontuário
                                        </Link>

                                        <button @click="deleteAppointment(app.id)" class="text-gray-300 hover:text-red-600 px-1 font-bold text-sm transition-colors" title="Remover Registro">✕</button>
                                    </div>

                                </div>
                            </div>

                            <div v-else class="py-16 text-center text-gray-400 italic text-sm">
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 002-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Não existem consultas marcadas para o dia selecionado.
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showingCancelModal" @close="showingCancelModal = false" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center gap-2 text-red-600">Cancelar Agendamento</h3>
                <p class="text-sm text-gray-500 mb-4">Por favor, informe a justificativa do cancelamento.</p>
                <textarea v-model="cancellationReason" rows="3" class="block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm text-sm" required></textarea>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showingCancelModal = false">Voltar</SecondaryButton>
                    <button @click="confirmCancellation" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded font-medium text-sm transition-colors shadow-sm">Confirmar Cancelamento</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>