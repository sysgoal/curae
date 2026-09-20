<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

// Importações do FullCalendar
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

const props = defineProps({
    appointments: Array,
    scheduleBlocks: Array,
    professionals: Array,
    patients: Array,
    filters: Object
});

const page = usePage();
// Começamos com a aba do calendário aberta por padrão!
const activeTab = ref('calendar'); 

const canManageOthers = computed(() => {
    const roles = page.props.auth?.roles || [];
    return roles.includes('admin') || roles.includes('secretaria');
});

const selectedFilterProfessional = ref(props.filters?.professional_id || '');
const filterAppointments = () => {
    router.get(route('appointments.index'), { professional_id: selectedFilterProfessional.value }, { preserveState: true });
};

// ==========================================
// LÓGICA DE CONSULTAS E AUTOMAÇÃO
// ==========================================
const showingCreateModal = ref(false);
const form = useForm({
    patient_id: '',
    professional_id: canManageOthers.value ? '' : (props.professionals.length > 0 ? props.professionals[0].id : ''),
    start_time: '',
    end_time: '',
    status: 'agendado',
    notes: ''
});

// Watch corrigido para não quebrar a string ISO do Date
watch(() => form.start_time, (newStart) => {
    if (newStart) {
        const startDate = new Date(newStart);
        if (isNaN(startDate.getTime())) return;
        
        const endDate = new Date(startDate.getTime() + 30 * 60000);
        
        const year = endDate.getFullYear();
        const month = String(endDate.getMonth() + 1).padStart(2, '0');
        const day = String(endDate.getDate()).padStart(2, '0');
        const hours = String(endDate.getHours()).padStart(2, '0');
        const minutes = String(endDate.getMinutes()).padStart(2, '0');
        
        form.end_time = `${year}-${month}-${day}T${hours}:${minutes}`;
    }
});

const openCreateModal = () => {
    form.clearErrors();
    form.reset();
    if (!canManageOthers.value && props.professionals.length > 0) form.professional_id = props.professionals[0].id;
    showingCreateModal.value = true;
};

const submitAppointment = () => {
    form.post(route('appointments.store'), {
        onSuccess: () => showingCreateModal.value = false
    });
};

const updateStatus = (id, newStatus) => router.patch(route('appointments.updateStatus', id), { status: newStatus }, { preserveScroll: true });
const deleteAppointment = (id) => { if (confirm('Tem a certeza que deseja remover este agendamento permanentemente?')) router.delete(route('appointments.destroy', id)); };

// ==========================================
// LÓGICA DE BLOQUEIOS (INDISPONIBILIDADES)
// ==========================================
const showingBlockModal = ref(false);
const blockForm = useForm({
    professional_id: canManageOthers.value ? '' : (props.professionals.length > 0 ? props.professionals[0].id : ''),
    start_time: '',
    end_time: '',
    reason: ''
});

const openBlockModal = () => {
    blockForm.clearErrors();
    blockForm.reset();
    if (!canManageOthers.value && props.professionals.length > 0) blockForm.professional_id = props.professionals[0].id;
    showingBlockModal.value = true;
};

const submitBlock = () => {
    blockForm.post(route('schedule-blocks.store'), {
        onSuccess: () => showingBlockModal.value = false
    });
};

const deleteBlock = (id) => { if (confirm('Remover este bloqueio e liberar a agenda?')) router.delete(route('schedule-blocks.destroy', id)); };

// ==========================================
// LÓGICA DO FULLCALENDAR
// ==========================================
const calendarEvents = computed(() => {
    const events = [];
    
    props.appointments.forEach(appt => {
        if (appt.status !== 'cancelado') {
            events.push({
                id: 'appt-' + appt.id,
                title: `${appt.patient?.name} (${appt.status}) - [${appt.professional?.name}]`,
                start: appt.start_time,
                end: appt.end_time,
                backgroundColor: appt.status === 'concluido' ? '#10B981' : '#4F46E5', 
                borderColor: 'transparent',
            });
        }
    });

    props.scheduleBlocks.forEach(block => {
        events.push({
            id: 'block-' + block.id,
            title: '🚫 Bloqueado: ' + block.reason,
            start: block.start_time,
            end: block.end_time,
            backgroundColor: '#EF4444', 
            borderColor: 'transparent',
        });
    });

    return events;
});

const calendarOptions = ref({
    plugins: [ dayGridPlugin, timeGridPlugin, interactionPlugin ],
    initialView: 'timeGridWeek',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    locale: 'pt-br',
    buttonText: {
        today: 'Hoje',
        month: 'Mês',
        week: 'Semana',
        day: 'Dia'
    },
    events: calendarEvents,
    allDaySlot: false,
    eventDisplay: 'block',     // Força os eventos a terem fundo colorido na visão de Mês
    displayEventTime: true,    
    
    // AJUSTES PARA TELAS MENORES (14")
    slotMinTime: '07:00:00',
    slotMaxTime: '19:00:00',
    aspectRatio: 2.0,
    height: 600,
    expandRows: true,
    stickyHeaderDates: true,
    
    eventClick: (info) => {
        alert('Detalhes: ' + info.event.title);
    }
});
</script>

<template>
    <Head title="Agenda e Horários" />
    <AuthenticatedLayout>
        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 font-bold shadow-sm">
                {{ $page.props.flash.success }}
            </div>

            <div v-if="Object.keys($page.props.errors).length > 0" class="mb-6 bg-red-50 text-red-700 p-4 rounded-xl border border-red-200 font-bold shadow-sm">
                <div v-for="(error, index) in $page.props.errors" :key="index">{{ error }}</div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                
                <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white">
                    <div>
                        <h3 class="text-lg font-black text-gray-900">Gestão de Agenda</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Controlo de marcações e disponibilidade clínica.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                        <button @click="openBlockModal" class="flex-1 sm:flex-none bg-red-50 hover:bg-red-100 text-red-700 px-4 py-2.5 rounded-lg font-bold text-sm transition-colors border border-red-200 flex justify-center items-center gap-2">
                            🚫 Bloquear Horário
                        </button>
                        <button @click="openCreateModal" class="flex-1 sm:flex-none bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-bold text-sm shadow-sm transition-all flex justify-center items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Nova Consulta
                        </button>
                    </div>
                </div>

                <div class="p-4 border-b border-gray-100 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex space-x-2 bg-white p-1 rounded-xl border border-gray-200 shadow-sm w-full md:w-auto">
                        <button @click="activeTab = 'calendar'" :class="activeTab === 'calendar' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2 rounded-lg text-sm transition-colors flex-1">
                            📅 Calendário
                        </button>
                        <button @click="activeTab = 'appointments'" :class="activeTab === 'appointments' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2 rounded-lg text-sm transition-colors flex-1">
                            📄 Lista de Consultas
                        </button>
                        <button @click="activeTab = 'blocks'" :class="activeTab === 'blocks' ? 'bg-red-50 text-red-700 font-bold' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2 rounded-lg text-sm transition-colors flex-1">
                            🚫 Indisponibilidades
                        </button>
                    </div>

                    <div v-if="canManageOthers" class="w-full md:w-auto flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Filtrar Agenda:</span>
                        <select v-model="selectedFilterProfessional" @change="filterAppointments" class="border-gray-300 rounded-lg text-sm w-full md:w-56 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Equipe Completa</option>
                            <option v-for="prof in professionals" :key="prof.id" :value="prof.id">{{ prof.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- 1. CALENDÁRIO FULLCALENDAR (USANDO v-show PARA NÃO PERDER A RENDERIZAÇÃO) -->
                <!-- REMOVIDO min-h-[600px] PARA QUE O CALENDÁRIO GERENCIE A ALTURA -->
                <div v-show="activeTab === 'calendar'" class="p-6 bg-white overflow-x-auto">
                    <FullCalendar :options="calendarOptions" />
                </div>

                <!-- 2. LISTA DE CONSULTAS (TABELA) -->
                <table v-show="activeTab === 'appointments'" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white border-b text-xs uppercase tracking-wider text-gray-500 font-black">
                            <th class="p-4">Data e Hora</th>
                            <th class="p-4">Paciente</th>
                            <th v-if="canManageOthers" class="p-4">Profissional</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-100">
                        <tr v-if="appointments.length === 0">
                            <td :colspan="canManageOthers ? 5 : 4" class="p-8 text-center text-gray-400">Nenhum agendamento ativo localizado.</td>
                        </tr>
                        <tr v-for="appt in appointments" :key="appt.id" class="hover:bg-gray-50/50 transition-colors" :class="appt.status === 'cancelado' ? 'opacity-40 grayscale bg-gray-50/50' : ''">
                            <td class="p-4">
                                <div class="font-bold text-indigo-700" :class="appt.status === 'cancelado' ? 'line-through text-gray-400' : ''">{{ appt.formatted_start }}</div>
                                <div class="text-[10px] uppercase font-bold text-gray-400 mt-0.5 tracking-wider">Até às {{ appt.formatted_end_time_only }}</div>
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-gray-900">{{ appt.patient?.name }}</div>
                                <div class="text-xs text-gray-500">{{ appt.patient?.phone }}</div>
                            </td>
                            <td v-if="canManageOthers" class="p-4 text-gray-700 font-medium">{{ appt.professional?.name }}</td>
                            <td class="p-4">
                                <span v-if="appt.status === 'agendado'" class="px-2 py-1 text-[10px] font-black uppercase tracking-wider rounded-md bg-amber-50 text-amber-700 border border-amber-200">Agendado</span>
                                <span v-else-if="appt.status === 'concluido'" class="px-2 py-1 text-[10px] font-black uppercase tracking-wider rounded-md bg-green-50 text-green-700 border border-green-200">Concluído</span>
                                <span v-else class="px-2 py-1 text-[10px] font-black uppercase tracking-wider rounded-md bg-red-50 text-red-700 border border-red-200">Cancelado</span>
                            </td>
                            <td class="p-4 text-right space-x-2 flex items-center justify-end">
                                <select v-if="appt.status === 'agendado'" @change="updateStatus(appt.id, $event.target.value)" class="text-xs font-bold border-gray-300 rounded-lg py-1.5 px-3 bg-white shadow-sm cursor-pointer">
                                    <option value="agendado">Ações...</option>
                                    <option value="concluido">Concluir Consulta</option>
                                    <option value="cancelado">Cancelar Consulta</option>
                                </select>
                                <button @click="deleteAppointment(appt.id)" class="text-red-500 hover:text-red-700 font-bold text-xs bg-red-50 hover:bg-red-100 p-2 rounded-lg transition-colors">
                                    ✕
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- 3. LISTA DE INDISPONIBILIDADES (TABELA) -->
                <table v-show="activeTab === 'blocks'" class="w-full text-left border-collapse bg-red-50/10">
                    <thead>
                        <tr class="bg-white border-b text-xs uppercase tracking-wider text-red-500 font-black">
                            <th class="p-4">Início</th>
                            <th class="p-4">Fim</th>
                            <th class="p-4">Justificação</th>
                            <th v-if="canManageOthers" class="p-4">Profissional</th>
                            <th class="p-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-100">
                        <tr v-if="scheduleBlocks.length === 0">
                            <td :colspan="canManageOthers ? 5 : 4" class="p-8 text-center text-gray-400">Nenhum bloqueio de horário configurado.</td>
                        </tr>
                        <tr v-for="block in scheduleBlocks" :key="'block-'+block.id" class="hover:bg-red-50/30 transition-colors">
                            <td class="p-4 font-bold text-gray-800">{{ block.formatted_start }}</td>
                            <td class="p-4 font-bold text-gray-800">{{ block.formatted_end }}</td>
                            <td class="p-4 text-red-700 font-medium">{{ block.reason }}</td>
                            <td v-if="canManageOthers" class="p-4 text-gray-600">{{ block.professional?.name }}</td>
                            <td class="p-4 text-right">
                                <button @click="deleteBlock(block.id)" class="text-gray-600 hover:text-green-700 font-bold text-xs border border-gray-300 bg-white hover:bg-green-50 px-3 py-1.5 rounded-lg transition-colors">
                                    Desbloquear
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL DE NOVA MARCAÇÃO -->
        <Modal :show="showingCreateModal" @close="showingCreateModal = false" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-black text-gray-900 border-b pb-3 mb-5">Nova Marcação</h3>
                
                <form @submit.prevent="submitAppointment" class="space-y-5">
                    <div>
                        <InputLabel value="Paciente *" />
                        <select v-model="form.patient_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 rounded-lg shadow-sm" required>
                            <option value="" disabled>Selecione o paciente...</option>
                            <option v-for="pat in patients" :key="pat.id" :value="pat.id">{{ pat.name }}</option>
                        </select>
                    </div>

                    <div v-if="canManageOthers">
                        <InputLabel value="Profissional Responsável *" />
                        <select v-model="form.professional_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 rounded-lg shadow-sm" required>
                            <option value="" disabled>Selecione o profissional...</option>
                            <option v-for="prof in professionals" :key="prof.id" :value="prof.id">{{ prof.name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Início *" />
                            <TextInput type="datetime-local" class="mt-1 block w-full text-sm" v-model="form.start_time" required />
                        </div>
                        <div>
                            <InputLabel value="Fim (Automático) *" />
                            <TextInput type="datetime-local" class="mt-1 block w-full text-sm bg-gray-50" v-model="form.end_time" readonly required />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Notas e Observações" />
                        <textarea v-model="form.notes" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 rounded-lg shadow-sm"></textarea>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t gap-3 mt-6">
                        <button type="button" @click="showingCreateModal = false" class="text-sm font-bold text-gray-600 hover:text-gray-900">Cancelar</button>
                        <PrimaryButton :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700">Confirmar Registro</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL DE BLOQUEIO DE HORÁRIO -->
        <Modal :show="showingBlockModal" @close="showingBlockModal = false" maxWidth="md">
            <div class="p-0 overflow-hidden">
                <div class="bg-gray-900 text-white p-5 flex justify-between items-center">
                    <h3 class="font-black text-lg flex items-center gap-2">🚫 Definir Indisponibilidade</h3>
                </div>
                <form @submit.prevent="submitBlock" class="p-6 space-y-5 bg-white">
                    <div v-if="canManageOthers">
                        <InputLabel value="Profissional *" />
                        <select v-model="blockForm.professional_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm" required>
                            <option value="" disabled>Selecione o membro...</option>
                            <option v-for="prof in professionals" :key="prof.id" :value="prof.id">{{ prof.name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Início *" />
                            <TextInput type="datetime-local" v-model="blockForm.start_time" class="mt-1 block w-full text-sm" required />
                        </div>
                        <div>
                            <InputLabel value="Fim *" />
                            <TextInput type="datetime-local" v-model="blockForm.end_time" class="mt-1 block w-full text-sm" required />
                        </div>
                    </div>
                    
                    <div>
                        <InputLabel value="Motivo / Justificação *" />
                        <TextInput type="text" v-model="blockForm.reason" class="mt-1 block w-full" placeholder="Ex: Almoço, Curso..." required />
                    </div>

                    <div class="flex items-center justify-end pt-4 gap-3 mt-4 border-t border-gray-100">
                        <button type="button" @click="showingBlockModal = false" class="text-sm font-bold text-gray-600 hover:text-gray-900">Cancelar</button>
                        <PrimaryButton :disabled="blockForm.processing" class="bg-red-600 hover:bg-red-700 border-none">Aplicar Bloqueio</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>