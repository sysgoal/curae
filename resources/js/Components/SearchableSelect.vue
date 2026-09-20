<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, required: true },
    placeholder: { type: String, default: 'Selecione...' },
    labelKey: { type: String, default: 'name' },
    valueKey: { type: String, default: 'id' }
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const searchQuery = ref('');
const dropdownRef = ref(null);
const searchInput = ref(null);

const selectedOption = computed(() => {
    return props.options.find(opt => String(opt[props.valueKey]) === String(props.modelValue));
});

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(opt =>
        String(opt[props.labelKey]).toLowerCase().includes(query)
    );
});

const toggleDropdown = async () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = '';
        await nextTick();
        if (searchInput.value) searchInput.value.focus();
    }
};

const selectOption = (option) => {
    emit('update:modelValue', option[props.valueKey]);
    emit('change', option[props.valueKey]);
    searchQuery.value = '';
    isOpen.value = false;
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <div class="relative w-full" ref="dropdownRef">
        <div
            @click="toggleDropdown"
            class="mt-1 flex items-center justify-between w-full border border-gray-300 bg-white px-3 py-2 text-sm rounded-lg shadow-sm cursor-pointer focus:outline-none transition-colors"
            :class="{'ring-2 ring-indigo-500 border-indigo-500': isOpen}"
        >
            <span :class="{ 'text-gray-900 font-medium': selectedOption, 'text-gray-500': !selectedOption }">
                {{ selectedOption ? selectedOption[labelKey] : placeholder }}
            </span>
            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{'transform rotate-180': isOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </div>

        <div v-if="isOpen" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-y-auto">
            <div class="sticky top-0 bg-white p-2 border-b border-gray-100 shadow-sm">
                <input
                    type="text"
                    ref="searchInput"
                    v-model="searchQuery"
                    class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Comece a digitar para procurar..."
                    @click.stop
                />
            </div>
            <ul class="py-1">
                <li v-if="filteredOptions.length === 0" class="px-3 py-4 text-sm text-center text-gray-500">
                    Nenhum resultado encontrado para "{{ searchQuery }}"
                </li>
                <li
                    v-for="opt in filteredOptions"
                    :key="opt[valueKey]"
                    @click="selectOption(opt)"
                    class="px-3 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 cursor-pointer transition-colors"
                    :class="{'bg-indigo-50 font-bold text-indigo-700': String(modelValue) === String(opt[valueKey])}"
                >
                    {{ opt[labelKey] }}
                </li>
            </ul>
        </div>
    </div>
</template>