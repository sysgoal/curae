<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    patient: Object,
});

// ==========================================
// CÁLCULO DE IDADE PARA DIRECIONAMENTO AUTOMÁTICO
// ==========================================
const getAge = (dob) => {
    if (!dob) return 20; // Se não tiver data de nascimento cadastrada, assume adulto por padrão
    const today = new Date();
    const birthDate = new Date(dob);
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
};

// Calcula a idade do paciente logado
const patientAge = getAge(props.patient.date_of_birth);

// Define a idade de corte. Neste caso, menores de 14 anos abrem a ficha Infantil. 
// (Pode alterar o "14" para "18" ou "12", conforme a rotina da sua clínica).
const defaultType = patientAge < 14 ? 'child' : 'adult';

// Inicializa o formulário já com a aba correta selecionada
const form = useForm({
    patient_id: props.patient.id,
    type: defaultType,
    chief_complaint: '',
    family_history: '',
    patient_routine: '',
    symptoms_checklist: [],
    child_data: {
        weight: '',
        parents_names: '',
        previous_diagnosis: '',
        diet_description: '',
        water_intake: '',
        supplements: '',
        allergies: '',
        pain_complaint: ''
    }
});

// Sintomas base para a ficha de Adulto (Pode alterar como quiser)
const adultSymptoms = [
    "TPM (Fígado)", "Músculo tenso e dor lombar", "Enxaqueca / Dor de cabeça",
    "Insônia / Dificuldade para dormir", "Fadiga crônica / Cansaço", "Ansiedade / Estresse crônico",
    "Intestino preso / Constipação", "Refluxo / Azia", "Queda de cabelo", "Unhas fracas",
    "Baixa libido", "Ganho de peso inexplicável"
];

// Sintomas e Sinais mapeados do Método PCA Infantil
const childSymptoms = [
    "Parto Cesariana", "Não teve amamentação exclusiva até 6m", "Fórmula infantil antes dos 6m",
    "Uso precoce/frequente de antibióticos", "Intestino preso (dias sem ir)", "Fezes ressecadas/bolinhas",
    "Diarreia", "Presença de muco nas fezes", "Seletividade alimentar recusa texturas", "Vício em açúcar/carboidrato",
    "Consumo frequente de glúten/laticínios", "Atraso no crescimento/peso", "Falta de apetite",
    "Dermatite / Pele seca / Manchas / Descamação", "Acantose (manchas escuras pescoço/axilas)",
    "Olheiras / Respiração oral / Bolinhas bochecha", "Bruxismo / Tártaro / Dentes tortos",
    "Língua com saburra / rachaduras / marcas", "Unhas fracas / Queda de cabelo", "Usa óculos",
    "Baixa imunidade (resfriados, asma, rinite)", "Sintomas parasitários (coceira, baba)",
    "Má qualidade de sono (acorda, terror noturno)", "Sede noturna (acorda para beber água)",
    "Suor noturno excessivo / Fadiga", "Desatenção / Vive no mundo da lua",
    "Hiperatividade / Agitação extrema", "Atraso desenvolvimento / Anda nas pontas",
    "Comportamento repetitivo / Manias", "Acessos de raiva frequentes (Fígado)", "Medos excessivos (Rins) / Insegurança",
    "Traço Oral (Bochechudo/Comida afetiva)", "Ambiente familiar tenso/ansioso", "Exposição excessiva a telas"
];

const toggleSymptom = (symptom) => {
    const index = form.symptoms_checklist.indexOf(symptom);
    if (index === -1) form.symptoms_checklist.push(symptom);
    else form.symptoms_checklist.splice(index, 1);
};

const submit = () => {
    // Limpa os dados não usados consoante o tipo escolhido antes de enviar para o banco
    if (form.type === 'adult') form.child_data = null;
    else form.patient_routine = null;
    
    form.post(route('anamneses.store'));
};
</script>

<template>
    <Head title="Preencher Anamnese" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                Nova Anamnese: <span class="font-bold text-indigo-700">{{ patient.name }}</span>
                <span v-if="patientAge < 14" class="text-xs bg-pink-100 text-pink-700 px-2 py-0.5 rounded-full font-black uppercase tracking-wide ml-2">Paciente Pediátrico</span>
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                
                <form @submit.prevent="submit" class="space-y-6">
                    
                    <div class="bg-white p-6 rounded-lg shadow-sm border text-center flex justify-center gap-4">
                        <button type="button" @click="form.type = 'adult'; form.symptoms_checklist = []" :class="['px-6 py-3 rounded-md font-bold transition-all border-2', form.type === 'adult' ? 'bg-indigo-50 border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:bg-gray-50']">
                            👩🏽‍🦱 Anamnese Adulto
                        </button>
                        <button type="button" @click="form.type = 'child'; form.symptoms_checklist = []" :class="['px-6 py-3 rounded-md font-bold transition-all border-2', form.type === 'child' ? 'bg-pink-50 border-pink-500 text-pink-700' : 'border-transparent text-gray-500 hover:bg-gray-50']">
                            🧸 Anamnese Infantil (PCA)
                        </button>
                    </div>

                    <div v-if="form.type === 'adult'" class="bg-white p-6 rounded-lg shadow-sm border space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Queixa Principal / Motivo da Consulta</label>
                            <textarea v-model="form.chief_complaint" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Rotina & Estilo de Vida</label>
                            <textarea v-model="form.patient_routine" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Histórico Familiar</label>
                            <textarea v-model="form.family_history" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>
                        
                        <div class="pt-4 border-t">
                            <label class="block text-sm font-extrabold text-gray-800 mb-3">Checklist de Sintomas</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <label v-for="(symp, idx) in adultSymptoms" :key="idx" class="flex items-start gap-2 p-3 bg-gray-50 rounded border cursor-pointer hover:bg-indigo-50 transition-colors">
                                    <input type="checkbox" :checked="form.symptoms_checklist.includes(symp)" @change="toggleSymptom(symp)" class="mt-1 text-indigo-600 rounded border-gray-300">
                                    <span class="text-sm text-gray-700 font-medium">{{ symp }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div v-if="form.type === 'child'" class="bg-white p-6 rounded-lg shadow-sm border border-pink-200 space-y-8">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Nomes dos Pais / Responsáveis</label>
                                <input type="text" v-model="form.child_data.parents_names" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Peso Atual (kg)</label>
                                <input type="text" v-model="form.child_data.weight" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Queixa Principal (Por que buscaram atendimento?)</label>
                            <textarea v-model="form.chief_complaint" rows="2" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Há algum diagnóstico prévio?</label>
                            <textarea v-model="form.child_data.previous_diagnosis" rows="2" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500"></textarea>
                        </div>

                        <div class="space-y-4 pt-4 border-t border-pink-100">
                            <h3 class="font-bold text-pink-700 uppercase tracking-wider text-sm">Histórico Clínico e Diário</h3>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">O que a criança come e bebe (lanche da manhã ao jantar)?</label>
                                <textarea v-model="form.child_data.diet_description" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500"></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Consumo de Água Diário (ml)</label>
                                    <input type="text" v-model="form.child_data.water_intake" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Alergias ou Intolerâncias?</label>
                                    <input type="text" v-model="form.child_data.allergies" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Uso de Suplemento / Medicação? (Quais e Desde quando)</label>
                                <textarea v-model="form.child_data.supplements" rows="2" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">A criança queixa-se de alguma dor ou desconforto? Qual e Desde quando?</label>
                                <textarea v-model="form.child_data.pain_complaint" rows="2" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500"></textarea>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-pink-100">
                            <h3 class="font-bold text-pink-700 uppercase tracking-wider text-sm mb-4">Sinais, Sintomas e Comportamentos (Assinale os Positivos)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <label v-for="(symp, idx) in childSymptoms" :key="'child-'+idx" class="flex items-start gap-2 p-3 bg-white rounded border border-gray-200 cursor-pointer hover:bg-pink-50 hover:border-pink-300 transition-colors">
                                    <input type="checkbox" :checked="form.symptoms_checklist.includes(symp)" @change="toggleSymptom(symp)" class="mt-1 text-pink-600 rounded border-gray-300 focus:ring-pink-500">
                                    <span class="text-xs text-gray-700 font-bold leading-tight">{{ symp }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        <Link :href="route('patients.show', patient.id)" class="text-sm font-bold text-gray-600 hover:text-gray-900">Cancelar</Link>
                        <PrimaryButton :disabled="form.processing" class="bg-gray-900 hover:bg-black py-3 px-6">
                            Gravar Anamnese
                        </PrimaryButton>
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>