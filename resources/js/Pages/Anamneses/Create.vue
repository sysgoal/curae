<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ patient: Object });

// Motor do Checklist: Mapeamento completo do seu .odt Integrativo
const symptomCategories = [
    {
        title: 'Fígado e Vesícula',
        items: [
            'TPM (Fígado)', 'Unhas fracas, secas ou rachadas', 'Músculo tenso e dor lombar', 'Hemorroidas', 
            'Visão fraca / Olhos secos ou lacrimejando', 'Irritabilidade / Impaciência', 'Falta de menstruação', 
            'Boca amarga', 'Doenças inflamatórias / Coceira no corpo', 'Rigidez e tremores', 'Enxaqueca localizada no olho'
        ]
    },
    {
        title: 'Baço, Pâncreas e Estômago',
        items: [
            'Falta de apetite ou Fome frequente', 'Náuseas e vômitos', 'Azia e queimação', 'Arrotos e soluços', 
            'Mau hálito', 'Preferência excessiva por doces', 'Obesidade / Dificuldade de emagrecer', 
            'Hiperacidez gástrica (Hipercloridria)', 'Peso nas pernas', 'Emagrecimento excessivo', 'Dores nas pernas'
        ]
    },
    {
        title: 'Rim, Bexiga e Adrenal',
        items: [
            'Ossos e Dentes fracos', 'Lombalgia (dor lombar)', 'Impotência sexual / Baixa libido', 
            'Insegurança / Medo excessivo', 'Urinar excessivamente ou Retenção', 'Incontinência urinária', 
            'Má circulação nas pernas', 'Frio nas costas', 'Desmaios constantes', 'Cansaço pela manhã', 'Tontura ao levantar'
        ]
    },
    {
        title: 'Pulmão e Respiração',
        items: [
            'Resfriados ou gripes frequentes', 'Sensação de frio por todo o corpo', 'Tosse excessiva com expectoração', 
            'Congestão nasal ou no peito', 'Respiração forte e ofegante / Asma / Bronquite', 'Tristeza e apatia', 
            'Voz fraca', 'Falta de energia constante', 'Pele seca (Pulmão)', 'Febre e calafrios'
        ]
    },
    {
        title: 'Coração e Pericárdio',
        items: [
            'Insônia / Despertares noturnos', 'Tonteiras', 'Dores no peito e costas', 'Transpiração nas mãos', 
            'Transpiração abundante / Suor noturno', 'Taquicardia / Palpitações', 'Falta de memória / Esquecimentos', 
            'Ansiedade / Nervosismo', 'Rosto muito pálido ou muito avermelhado'
        ]
    },
    {
        title: 'Intestinos (Grosso e Delgado)',
        items: [
            'Prisão de ventre (Constipação)', 'Diarreia crônica', 'Acne / Psoríase / Rosácea', 'Boca seca', 
            'Dores, inchaço ou peso abdominal', 'Muco nas fezes', 'Gases em excesso', 'Falta de ânimo', 
            'Má digestão / Digestão difícil'
        ]
    },
    {
        title: 'Hormônios (Masculino e Feminino)',
        items: [
            'Testosterona Baixa: Fraqueza, apatia, ganho de gordura abdominal', 
            'Testosterona Alta: Espinhas em excesso, calvície', 
            'Estrogênio Baixo: Secura vaginal, ondas de calor, osteoporose', 
            'Estrogênio Alto: Miomas, TPM forte, endometriose, ganho de peso', 
            'Progesterona Baixa: Menstruação longa, escape, ciclo irregular, aborto',
            'Progesterona Alta: Cistos no ovário, hiperplasia adrenal'
        ]
    },
    {
        title: 'Deficiências Vitamínicas (A a K)',
        items: [
            'Vit D: Baixa imunidade, fraqueza muscular, raquitismo',
            'Vit C: Sangramento na gengiva, feridas que não cicatrizam, imunidade baixa',
            'Vit A: Olho seco, cegueira noturna, infecções respiratórias',
            'Complexo B (B1 a B9): Dermatite, confusão mental, formigamento, insônia',
            'Vit B12: Fadiga crônica, memória ruim (esquece palavras), doença autoimune, procrastinação',
            'Vit E / K: Sangramentos fáceis, hematomas, perda de coordenação motora'
        ]
    },
    {
        title: 'Deficiência de Minerais',
        items: [
            'Ferro (Anemia): Palidez, taquicardia, queda de cabelo, falta de ar',
            'Magnésio: Câimbras, tremores, arritmia, vontade excessiva de doces',
            'Zinco: Falta de paladar/olfato, queda de cabelo, letargia mental',
            'Iodo / Tireoide: Ganho de peso, pele seca, intolerância ao frio, letargia',
            'Fósforo / Cálcio: Ossos fracos, desejo por alimentos gordurosos',
            'Selênio: Imunidade baixa, infertilidade, problemas na tireoide'
        ]
    },
    {
        title: 'SIBO, SIFO e Disbiose',
        items: [
            'SIBO: Distensão abdominal após refeições, sensação de peso no estômago, fadiga mental',
            'SIFO: Desejo incontrolável por doces/carboidratos, candidíase de repetição, coceira na pele',
            'Alergias diurnas / Dores articulares',
            'Intolerância a medicamentos / Alimentos'
        ]
    },
    {
        title: 'Saúde Mental, Emocional e TDAH',
        items: [
            'Depressão / Humor depressivo (Falta de B12/Estradiol)', 
            'Dificuldade de concentração / TDAH / Se distrai facilmente', 
            'Comportamento antissocial (Falta de Serotonina/B12)', 
            'Procrastinação (Adrenal/Cortisol)', 
            'Perde objetos frequentemente (Celular, chaves)', 
            'Inquietude / Dificuldade de esperar a vez'
        ]
    },
    {
        title: 'Análise Corporal e Facial',
        items: [
            'Gordura no tríceps (Desequilíbrio de estradiol)', 
            'Gordura abdominal / Flancos (Adrenalina + Insulina)', 
            'Gordura no culote (Desequilíbrio misto)', 
            'Melasma na face (Predominância estrogênica)', 
            'Bolsa abaixo dos olhos (Rins + Fígado)', 
            'Língua com rachaduras / Marcas de dente (Microbiota/Enzimas)',
            'Língua com saburra branca/amarelada (Fungos/Fígado)'
        ]
    }
];

const form = useForm({
    patient_id: props.patient.id,
    chief_complaint: '',
    patient_routine: '',
    family_history: '',
    medications_in_use: '',
    symptoms_checklist: [] // Array que vai guardar as dezenas de itens selecionados
});

const activeTab = ref(0); // Controla qual categoria do menu lateral está aparecendo

const submit = () => {
    form.post(route('anamneses.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Anamnese Integrativa - ${patient.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Anamnese Integrativa: {{ patient.name }}
                </h2>
                <Link :href="route('patients.show', patient.id)" class="text-gray-600 hover:underline">
                    Cancelar e Voltar
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <form @submit.prevent="submit" class="space-y-6">
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 border-b pb-2 mb-4">Informações Gerais</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label for="chief_complaint" class="block font-medium text-sm text-gray-700">Motivo da Consulta / Queixa Principal</label>
                                <textarea id="chief_complaint" v-model="form.chief_complaint" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            </div>

                            <div>
                                <label for="patient_routine" class="block font-medium text-sm text-gray-700">Rotina do Paciente (Sono, Estresse, Alimentação)</label>
                                <textarea id="patient_routine" v-model="form.patient_routine" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Ex: Dorme à 1h da manhã, acorda cansado, come muito doce..."></textarea>
                            </div>

                            <div>
                                <label for="family_history" class="block font-medium text-sm text-gray-700">Histórico Familiar</label>
                                <textarea id="family_history" v-model="form.family_history" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Ex: Pai diabético, mãe com hipotireoidismo..."></textarea>
                            </div>

                            <div class="md:col-span-2">
                                <label for="medications_in_use" class="block font-medium text-sm text-gray-700">Medicamentos e Suplementos em uso</label>
                                <textarea id="medications_in_use" v-model="form.medications_in_use" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex justify-between items-end border-b pb-2 mb-4">
                            <h3 class="text-lg font-bold text-gray-900">Rastreamento Metabólico e Sistêmico</h3>
                            <span class="text-xs text-gray-500 font-medium bg-gray-100 px-2 py-1 rounded">
                                {{ form.symptoms_checklist.length }} sintomas marcados
                            </span>
                        </div>
                        
                        <div class="flex flex-col lg:flex-row gap-6 min-h-[400px]">
                            
                            <div class="w-full lg:w-1/3 flex flex-col space-y-1 lg:border-r lg:pr-4">
                                <button 
                                    v-for="(category, index) in symptomCategories" :key="index"
                                    type="button"
                                    @click="activeTab = index"
                                    :class="[
                                        'text-left px-4 py-3 rounded-md text-sm font-semibold transition-all duration-200', 
                                        activeTab === index 
                                            ? 'bg-indigo-600 text-white shadow-md' 
                                            : 'text-gray-700 hover:bg-indigo-50 hover:text-indigo-700'
                                    ]"
                                >
                                    {{ category.title }}
                                </button>
                            </div>

                            <div class="w-full lg:w-2/3 bg-gray-50 p-6 rounded-lg border border-gray-200 shadow-inner">
                                <h4 class="font-bold text-xl text-indigo-800 mb-6 pb-2 border-b border-indigo-100">
                                    {{ symptomCategories[activeTab].title }}
                                </h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <label 
                                        v-for="(symptom, idx) in symptomCategories[activeTab].items" :key="idx" 
                                        class="flex items-start gap-3 cursor-pointer p-3 bg-white hover:bg-indigo-50 border border-gray-100 rounded-lg shadow-sm transition-colors"
                                    >
                                        <input 
                                            type="checkbox" 
                                            :value="symptom" 
                                            v-model="form.symptoms_checklist"
                                            class="mt-0.5 w-5 h-5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer"
                                        >
                                        <span class="text-sm text-gray-700 font-medium leading-tight select-none">
                                            {{ symptom }}
                                        </span>
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="flex items-center justify-end">
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="px-8 py-4 text-base">
                            Salvar Anamnese Integrativa
                        </PrimaryButton>
                    </div>

                </form>

            </div>
        </div>
    </AuthenticatedLayout>
</template>