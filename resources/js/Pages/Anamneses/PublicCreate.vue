<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({ patient: Object, submitUrl: String });

const getAge = (dob) => {
    if (!dob) return 20;
    const today = new Date(); const birthDate = new Date(dob);
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
    return age;
};

// 1. Definição do Tipo Bloqueado
const patientAge = getAge(props.patient.date_of_birth);
const lockedType = patientAge <= 6 ? 'child' : 'adult';

const form = useForm({
    type: lockedType, // Sem possibilidade de alternar
    chief_complaint: '', family_history: '', patient_routine: '', symptoms_checklist: [],
    child_data: { weight: '', parents_names: '', previous_diagnosis: '', diet_description: '', water_intake: '', supplements: '', allergies: '', pain_complaint: '' },
    adult_data: { diet_routine: '', sleep_routine: '', medications: '', sun_exposure: '', past_trauma: '', birth_type: '', gastric_issues: '' }
});

const currentStep = ref(0);
const totalAdultSteps = 6;
const totalChildSteps = 2;

const adultCategories = [
    { title: "Digestão", items: ["Excesso de gases / Arrotos", "Distensão abdominal pós-refeição", "Diarreia crônica ou Constipação", "Sensação de peso no estômago", "Desejo incontrolável por doces / Carboidratos", "Infecção fúngica recorrente", "Azia / Queimação", "Muco nas fezes", "Hemorroidas"] },
    { title: "Energia", items: ["Cansaço extremo / Fadiga", "Acorda cansado", "Fraqueza muscular", "Não consegue ficar 3h em jejum", "Tontura ao levantar", "Fome absurda às 17h/18h", "Desperta às 3h da manhã", "Passa mal no calor", "Cansaço pós-almoço"] },
    { title: "Hormônios", items: ["TPM Intensa", "Colicas menstruais / Endometriose", "Ciclo irregular", "Secura vaginal / Ondas de calor", "Baixa libido", "Dificuldade de ganhar/perder peso", "Queda de cabelo", "Unhas fracas / Rachadas", "Pele seca / Melasma", "Calvície excessiva", "Ganho de gordura abdominal"] },
    { title: "Gerais", items: ["Câimbras / Espasmos", "Língua inchada / Dolorosa", "Desejo de comer gelo / Barro", "Sangramento na gengiva", "Rachadura no calcanhar", "Falta de paladar / Olfato", "Zumbido no ouvido", "Cegueira noturna", "Imunidade baixa / Resfriados"] },
    { title: "Emocional", items: ["Ansiedade / Nervosismo", "Depressão / Apatia", "Falta de memória", "Déficit de atenção", "Procrastinação", "Irritabilidade", "Medo excessivo", "Perde objetos facilmente", "Névoa mental (Brain fog)"] },
    { title: "Dores", items: ["Dor lombar / Ciático", "Dor de cabeça na testa / Têmporas", "Dor articular", "Mãos e pés gelados", "Transpiração excessiva", "Gordura no tríceps", "Acantose (manchas escuras)", "Bolsas sob os olhos", "Língua rachada"] }
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
    form.post(props.submitUrl);
};
</script>

<template>
    <Head title="Ficha Clínica Online" />
    <div class="min-h-screen bg-gray-50 py-10 px-4 sm:px-6 flex items-center justify-center">
        <div class="max-w-3xl w-full">
            
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900">Ficha de Triagem Integrativa</h1>
                <p class="mt-2 text-gray-500 font-medium">Paciente: <span class="text-indigo-600 font-bold">{{ patient.name }}</span></p>
                <div class="mt-3">
                    <span v-if="form.type === 'child'" class="text-xs bg-pink-100 text-pink-700 px-3 py-1 rounded-full font-bold uppercase tracking-wider border border-pink-200">Ficha Pediátrica</span>
                    <span v-else class="text-xs bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full font-bold uppercase tracking-wider border border-indigo-200">Ficha Adulto</span>
                </div>
            </div>

            <div class="flex justify-center gap-2 mb-8">
                <div v-for="n in (form.type === 'adult' ? totalAdultSteps + 1 : totalChildSteps + 1)" :key="n" 
                     class="h-2 rounded-full transition-all duration-300"
                     :class="currentStep >= (n-1) ? (form.type === 'child' ? 'bg-pink-500 w-8' : 'bg-indigo-600 w-8') : 'bg-gray-200 w-4'">
                </div>
            </div>

            <form @submit.prevent="submit" class="bg-white p-6 md:p-10 rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100">
                
                <div v-if="form.type === 'adult'">
                    <div v-if="currentStep === 0" class="space-y-5 animate-fade-in">
                        <h2 class="text-xl font-bold text-gray-800 border-b pb-3 mb-5">Dados Iniciais</h2>
                        <div><label class="block text-sm font-bold text-gray-700 mb-1">Qual o motivo da consulta?</label><textarea v-model="form.chief_complaint" rows="2" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-indigo-500"></textarea></div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">Rotina de Trabalho / Exercício</label><input type="text" v-model="form.patient_routine" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-indigo-500"></div>
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">Rotina Alimentar</label><input type="text" v-model="form.adult_data.diet_routine" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-indigo-500"></div>
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">Dorme a que horas? (Celular no quarto?)</label><input type="text" v-model="form.adult_data.sleep_routine" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-indigo-500"></div>
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">Remédios que toma diariamente</label><input type="text" v-model="form.adult_data.medications" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-indigo-500"></div>
                        </div>
                        <div><label class="block text-sm font-bold text-gray-700 mb-1">Histórico Familiar de Doenças (Pais/Avós)</label><textarea v-model="form.family_history" rows="2" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-indigo-500"></textarea></div>
                    </div>

                    <div v-for="catIndex in 6" :key="catIndex">
                        <div v-if="currentStep === catIndex" class="animate-fade-in">
                            <h2 class="text-xl font-bold text-indigo-700 border-b pb-3 mb-5">Sintomas: {{ adultCategories[catIndex - 1].title }}</h2>
                            <p class="text-gray-500 text-sm mb-6">Toque nos itens que costuma sentir frequentemente:</p>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <div v-for="(symp, idx) in adultCategories[catIndex - 1].items" :key="idx" 
                                     @click="toggleSymptom(symp)"
                                     :class="form.symptoms_checklist.includes(symp) ? 'border-indigo-500 bg-indigo-50 text-indigo-900 ring-2 ring-indigo-500 ring-offset-1' : 'border-gray-200 bg-white'" 
                                     class="cursor-pointer border-2 rounded-xl p-3 transition-all flex items-center text-center h-24 shadow-sm select-none justify-center hover:bg-gray-50">
                                    <span class="text-xs md:text-sm font-bold leading-tight">{{ symp }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="form.type === 'child'">
                    <div v-if="currentStep === 0" class="space-y-5 animate-fade-in">
                        <h2 class="text-xl font-bold text-gray-800 border-b pb-3 mb-5">Dados Iniciais da Criança</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">Nome dos Pais</label><input type="text" v-model="form.child_data.parents_names" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-pink-500 focus:border-pink-500"></div>
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">Peso Atual (kg)</label><input type="text" v-model="form.child_data.weight" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-pink-500 focus:border-pink-500"></div>
                        </div>
                        <div><label class="block text-sm font-bold text-gray-700 mb-1">Motivo da Consulta</label><textarea v-model="form.chief_complaint" rows="2" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-pink-500 focus:border-pink-500"></textarea></div>
                        <div><label class="block text-sm font-bold text-gray-700 mb-1">Diagnósticos Prévios?</label><textarea v-model="form.child_data.previous_diagnosis" rows="2" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-pink-500 focus:border-pink-500"></textarea></div>
                    </div>

                    <div v-if="currentStep === 1" class="space-y-5 animate-fade-in">
                        <h2 class="text-xl font-bold text-gray-800 border-b pb-3 mb-5">Rotina e Dieta</h2>
                        <div><label class="block text-sm font-bold text-gray-700 mb-1">O que a criança costuma comer?</label><textarea v-model="form.child_data.diet_description" rows="2" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-pink-500 focus:border-pink-500"></textarea></div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">Toma quanta água por dia?</label><input type="text" v-model="form.child_data.water_intake" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-pink-500 focus:border-pink-500"></div>
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">Tem alergias?</label><input type="text" v-model="form.child_data.allergies" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-pink-500 focus:border-pink-500"></div>
                        </div>
                        <div><label class="block text-sm font-bold text-gray-700 mb-1">Toma suplemento/remédio?</label><input type="text" v-model="form.child_data.supplements" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-pink-500 focus:border-pink-500"></div>
                    </div>

                    <div v-if="currentStep === 2" class="animate-fade-in">
                        <h2 class="text-xl font-bold text-pink-700 border-b pb-3 mb-5">Comportamento e Sinais</h2>
                        <p class="text-gray-500 text-sm mb-6">Toque nos itens que se aplicam à criança:</p>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <div v-for="(symp, idx) in childSymptoms" :key="'child-'+idx" 
                                 @click="toggleSymptom(symp)"
                                 :class="form.symptoms_checklist.includes(symp) ? 'border-pink-500 bg-pink-50 text-pink-900 ring-2 ring-pink-500 ring-offset-1' : 'border-gray-200 bg-white'" 
                                 class="cursor-pointer border-2 rounded-xl p-3 transition-all flex items-center text-center h-20 shadow-sm select-none justify-center hover:bg-gray-50">
                                <span class="text-xs font-bold leading-tight">{{ symp }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-10 pt-6 border-t border-gray-100">
                    <button type="button" v-if="currentStep > 0" @click="currentStep--" class="px-5 py-2.5 font-bold text-gray-500 hover:bg-gray-100 rounded-xl transition-all">&larr; Voltar</button>
                    <div v-else></div> <button type="button" v-if="(form.type === 'adult' && currentStep < totalAdultSteps) || (form.type === 'child' && currentStep < totalChildSteps)" 
                            @click="currentStep++" 
                            :class="form.type === 'child' ? 'bg-pink-600 hover:bg-pink-700' : 'bg-indigo-600 hover:bg-indigo-700'"
                            class="px-8 py-3 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
                        Próximo <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                    
                    <button v-else type="submit" :disabled="form.processing" class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-black rounded-xl shadow-lg transition-all flex items-center gap-2 uppercase">
                        Concluir e Enviar <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
@keyframes fadeIn { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }
</style>