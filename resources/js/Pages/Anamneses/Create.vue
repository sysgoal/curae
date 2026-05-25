<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref } from 'vue';

const props = defineProps({ patient: Object });

// 1. Cálculo rigoroso da idade
const getAge = (dob) => {
    if (!dob) return 20; // Se não tiver data, assume adulto
    const today = new Date(); const birthDate = new Date(dob);
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
    return age;
};

const patientAge = getAge(props.patient.date_of_birth);

// 2. Define e bloqueia o tipo de ficha (<= 6 anos = Infantil)
const lockedType = patientAge <= 6 ? 'child' : 'adult';

const form = useForm({
    patient_id: props.patient.id,
    type: lockedType, // Tipo agora é estático e definido pela idade
    chief_complaint: '', family_history: '', patient_routine: '', symptoms_checklist: [],
    child_data: { weight: '', parents_names: '', previous_diagnosis: '', diet_description: '', water_intake: '', supplements: '', allergies: '', pain_complaint: '' },
    adult_data: { diet_routine: '', sleep_routine: '', medications: '', sun_exposure: '', past_trauma: '', birth_type: '', gastric_issues: '' }
});

const currentStep = ref(0);

const adultSteps = ["📋 Dados Iniciais", "🍎 Gastro e Digestão", "⚡ Energia e Sono", "🩸 Hormônios e Pele", "📉 Sinais e Deficiências", "🧠 Emocional", "🦴 Dores e Corpo"];
const childSteps = ["🧸 Dados Básicos", "🍼 Rotina e Alimentação", "🧩 Sinais e Comportamentos"];

const adultCategories = [
    { title: "Gastrointestinal e Digestão", items: ["Excesso de gases / Arrotos", "Distensão abdominal pós-refeição", "Diarreia crônica ou Constipação", "Sensação de peso no estômago", "Desejo incontrolável por doces / Carboidratos", "Infecção fúngica recorrente", "Azia / Queimação", "Muco nas fezes", "Hemorroidas"] },
    { title: "Energia, Sono e Disposição", items: ["Cansaço extremo / Fadiga", "Acorda cansado", "Fraqueza muscular", "Não consegue ficar 3h em jejum", "Tontura ao levantar", "Fome absurda às 17h/18h", "Desperta às 3h da manhã", "Passa mal no calor", "Cansaço pós-almoço"] },
    { title: "Metabolismo, Pele e Hormônios", items: ["TPM Intensa", "Colicas menstruais / Endometriose", "Ciclo irregular", "Secura vaginal / Ondas de calor", "Baixa libido", "Dificuldade de ganhar/perder peso", "Queda de cabelo", "Unhas fracas / Rachadas", "Pele seca / Melasma", "Calvície excessiva", "Ganho de gordura abdominal"] },
    { title: "Sintomas Gerais e Deficiências", items: ["Câimbras / Espasmos", "Língua inchada / Dolorosa", "Desejo de comer gelo / Barro", "Sangramento na gengiva", "Rachadura no calcanhar", "Falta de paladar / Olfato", "Zumbido no ouvido", "Cegueira noturna", "Imunidade baixa / Resfriados"] },
    { title: "Emocional e Cognitivo", items: ["Ansiedade / Nervosismo", "Depressão / Apatia", "Falta de memória", "Déficit de atenção", "Procrastinação", "Irritabilidade", "Medo excessivo", "Perde objetos facilmente", "Névoa mental (Brain fog)"] },
    { title: "Dores e Análise Corporal", items: ["Dor lombar / Ciático", "Dor de cabeça na testa / Têmporas", "Dor articular", "Mãos e pés gelados", "Transpiração excessiva", "Gordura no tríceps", "Acantose (manchas escuras)", "Bolsas sob os olhos", "Língua rachada"] }
];

const childSymptoms = ["Parto Cesariana", "Não teve amamentação até 6m", "Fórmula antes dos 6m", "Uso precoce de antibióticos", "Intestino preso", "Fezes ressecadas/bolinhas", "Diarreia", "Muco nas fezes", "Seletividade alimentar", "Vício em açúcar", "Consome glúten/laticínios", "Atraso no crescimento", "Falta de apetite", "Dermatite / Manchas", "Acantose", "Olheiras / Respiração oral", "Bruxismo", "Língua com saburra", "Unhas fracas / Queda cabelo", "Usa óculos", "Baixa imunidade", "Sintomas parasitários", "Má qualidade de sono", "Sede noturna", "Suor noturno", "Desatenção", "Hiperatividade", "Atraso desenvolvimento", "Manias", "Acessos de raiva", "Medos excessivos", "Traço Oral", "Ambiente familiar tenso", "Exposição a telas"];

const toggleSymptom = (symptom) => {
    const index = form.symptoms_checklist.indexOf(symptom);
    if (index === -1) form.symptoms_checklist.push(symptom);
    else form.symptoms_checklist.splice(index, 1);
};

const submit = () => {
    if (form.type === 'adult') form.child_data = null;
    else { form.patient_routine = null; form.adult_data = null; }
    form.post(route('anamneses.store'));
};
</script>

<template>
    <Head title="Preencher Anamnese" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    Anamnese: <span class="font-black text-indigo-700">{{ patient.name }}</span>
                    <span v-if="form.type === 'child'" class="text-[10px] bg-pink-100 text-pink-700 px-2.5 py-1 rounded-full font-black uppercase tracking-widest shadow-sm border border-pink-200">Ficha Pediátrica PCA</span>
                    <span v-else class="text-[10px] bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-full font-black uppercase tracking-widest shadow-sm border border-indigo-200">Ficha Adulto Integrativa</span>
                </h2>
                </div>
        </template>

        <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col md:flex-row gap-6">
            
            <div class="w-full md:w-1/4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sticky top-6">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-wider mb-4 px-2">Navegação da Ficha</h3>
                    <nav class="space-y-1">
                        <template v-if="form.type === 'adult'">
                            <button v-for="(step, index) in adultSteps" :key="index" @click="currentStep = index" :class="currentStep === index ? 'bg-indigo-50 text-indigo-700 font-bold border-indigo-200' : 'text-gray-600 hover:bg-gray-50 border-transparent'" class="w-full text-left px-4 py-3 rounded-xl border text-sm transition-all flex items-center justify-between">
                                {{ step }}
                                <svg v-if="currentStep === index" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </button>
                        </template>
                        <template v-else>
                            <button v-for="(step, index) in childSteps" :key="index" @click="currentStep = index" :class="currentStep === index ? 'bg-pink-50 text-pink-700 font-bold border-pink-200' : 'text-gray-600 hover:bg-gray-50 border-transparent'" class="w-full text-left px-4 py-3 rounded-xl border text-sm transition-all flex items-center justify-between">
                                {{ step }}
                                <svg v-if="currentStep === index" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </button>
                        </template>
                    </nav>
                </div>
            </div>

            <div class="w-full md:w-3/4">
                <form @submit.prevent="submit" class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    
                    <div v-if="form.type === 'adult'">
                        <div v-if="currentStep === 0" class="space-y-5 animate-fade-in">
                            <h2 class="text-2xl font-black text-gray-800 mb-6">📋 Dados e Histórico Geral</h2>
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">Queixa Principal / Motivo da Consulta</label><textarea v-model="form.chief_complaint" rows="3" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 bg-gray-50"></textarea></div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div><label class="block text-sm font-bold text-gray-700 mb-1">Rotina de Trabalho / Exercícios</label><textarea v-model="form.patient_routine" rows="2" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 bg-gray-50"></textarea></div>
                                <div><label class="block text-sm font-bold text-gray-700 mb-1">Rotina Alimentar</label><textarea v-model="form.adult_data.diet_routine" rows="2" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 bg-gray-50"></textarea></div>
                                <div><label class="block text-sm font-bold text-gray-700 mb-1">Dorme a que horas? (Celular no quarto?)</label><input type="text" v-model="form.adult_data.sleep_routine" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 bg-gray-50"></div>
                                <div><label class="block text-sm font-bold text-gray-700 mb-1">Costuma tomar Sol?</label><input type="text" v-model="form.adult_data.sun_exposure" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 bg-gray-50"></div>
                                <div class="md:col-span-2"><label class="block text-sm font-bold text-gray-700 mb-1">Remédios Diários / Anticoncepcional / Diurético</label><textarea v-model="form.adult_data.medications" rows="2" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 bg-gray-50"></textarea></div>
                                <div><label class="block text-sm font-bold text-gray-700 mb-1">Histórico Familiar de Doenças</label><textarea v-model="form.family_history" rows="2" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 bg-gray-50"></textarea></div>
                                <div><label class="block text-sm font-bold text-gray-700 mb-1">Traumas ou Parto (Cesárea/Normal)</label><input type="text" v-model="form.adult_data.birth_type" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 bg-gray-50" placeholder="Ex: Parto Cesárea. Trauma na infância..."></div>
                            </div>
                        </div>

                        <div v-for="catIndex in 6" :key="catIndex">
                            <div v-if="currentStep === catIndex" class="animate-fade-in">
                                <h2 class="text-2xl font-black text-indigo-900 mb-2">{{ adultSteps[catIndex] }}</h2>
                                <p class="text-gray-500 text-sm mb-6">Assinale os sinais e sintomas relatados ou observados.</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div v-for="(symp, idx) in adultCategories[catIndex - 1].items" :key="idx" 
                                         @click="toggleSymptom(symp)"
                                         :class="form.symptoms_checklist.includes(symp) ? 'border-indigo-500 bg-indigo-50 text-indigo-900 ring-1 ring-indigo-500' : 'border-gray-200 bg-white hover:border-indigo-300'" 
                                         class="cursor-pointer border-2 rounded-xl p-4 transition-all flex flex-col justify-center items-center text-center h-24 shadow-sm select-none">
                                        <span class="text-sm font-bold leading-tight">{{ symp }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="form.type === 'child'">
                        <div v-if="currentStep === 0" class="space-y-5 animate-fade-in">
                            <h2 class="text-2xl font-black text-gray-800 mb-6">🧸 Dados e Queixas Principais</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div><label class="block text-sm font-bold text-gray-700 mb-1">Nomes dos Pais / Responsáveis</label><input type="text" v-model="form.child_data.parents_names" class="w-full border-gray-300 rounded-xl bg-gray-50"></div>
                                <div><label class="block text-sm font-bold text-gray-700 mb-1">Peso Atual (kg)</label><input type="text" v-model="form.child_data.weight" class="w-full border-gray-300 rounded-xl bg-gray-50"></div>
                                <div class="md:col-span-2"><label class="block text-sm font-bold text-gray-700 mb-1">Queixa Principal</label><textarea v-model="form.chief_complaint" rows="3" class="w-full border-gray-300 rounded-xl bg-gray-50"></textarea></div>
                                <div class="md:col-span-2"><label class="block text-sm font-bold text-gray-700 mb-1">Há algum diagnóstico prévio?</label><textarea v-model="form.child_data.previous_diagnosis" rows="2" class="w-full border-gray-300 rounded-xl bg-gray-50"></textarea></div>
                            </div>
                        </div>
                        <div v-if="currentStep === 1" class="space-y-5 animate-fade-in">
                            <h2 class="text-2xl font-black text-gray-800 mb-6">🍼 Rotina, Dieta e Histórico</h2>
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">O que a criança come e bebe?</label><textarea v-model="form.child_data.diet_description" rows="3" class="w-full border-gray-300 rounded-xl bg-gray-50"></textarea></div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div><label class="block text-sm font-bold text-gray-700 mb-1">Consumo Água (ml)</label><input type="text" v-model="form.child_data.water_intake" class="w-full border-gray-300 rounded-xl bg-gray-50"></div>
                                <div><label class="block text-sm font-bold text-gray-700 mb-1">Alergias?</label><input type="text" v-model="form.child_data.allergies" class="w-full border-gray-300 rounded-xl bg-gray-50"></div>
                            </div>
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">Suplemento / Medicação?</label><textarea v-model="form.child_data.supplements" rows="2" class="w-full border-gray-300 rounded-xl bg-gray-50"></textarea></div>
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">Queixa-se de alguma dor?</label><textarea v-model="form.child_data.pain_complaint" rows="2" class="w-full border-gray-300 rounded-xl bg-gray-50"></textarea></div>
                        </div>
                        <div v-if="currentStep === 2" class="animate-fade-in">
                            <h2 class="text-2xl font-black text-pink-700 mb-2">🧩 Sinais e Comportamentos (PCA)</h2>
                            <p class="text-gray-500 text-sm mb-6">Assinale os itens que se aplicam à rotina da criança.</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="(symp, idx) in childSymptoms" :key="'child-'+idx" 
                                     @click="toggleSymptom(symp)"
                                     :class="form.symptoms_checklist.includes(symp) ? 'border-pink-500 bg-pink-50 text-pink-900 ring-1 ring-pink-500' : 'border-gray-200 bg-white hover:border-pink-300'" 
                                     class="cursor-pointer border-2 rounded-xl p-4 transition-all flex flex-col justify-center items-center text-center h-20 shadow-sm select-none">
                                    <span class="text-xs font-bold leading-tight">{{ symp }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-10 pt-6 border-t border-gray-100">
                        <button type="button" @click="currentStep--" :disabled="currentStep === 0" class="px-6 py-2.5 font-bold text-gray-600 hover:bg-gray-100 rounded-xl disabled:opacity-30 transition-all">&larr; Anterior</button>
                        <button type="button" v-if="(form.type === 'adult' && currentStep < 6) || (form.type === 'child' && currentStep < 2)" @click="currentStep++" class="px-8 py-2.5 bg-gray-800 hover:bg-black text-white font-bold rounded-xl shadow-md transition-all">Próximo Passo &rarr;</button>
                        <PrimaryButton v-else :disabled="form.processing" class="bg-indigo-700 hover:bg-indigo-800 px-8 py-3 rounded-xl shadow-md text-sm uppercase">💾 Gravar Ficha Completa</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>