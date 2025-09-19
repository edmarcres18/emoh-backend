<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

interface Props {
    options: any[];
    modelValue: string | number;
    placeholder?: string;
    searchPlaceholder?: string;
    primaryKey?: string;
    valueKey?: string;
    secondaryKey?: string;
    disabled?: boolean;
    error?: string;
    required?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Select an option...',
    searchPlaceholder: 'Search...',
    primaryKey: 'name',
    valueKey: 'id',
    secondaryKey: '',
    disabled: false,
    required: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const isOpen = ref(false);
const searchQuery = ref('');
const dropdownRef = ref<HTMLElement>();
const inputRef = ref<HTMLInputElement>();

const selectedOption = computed(() => {
    return props.options.find(option => option[props.valueKey]?.toString() === props.modelValue?.toString()) || null;
});

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(option => {
        const primaryText = getDisplayText(option).toLowerCase();
        const secondaryText = getSecondaryText(option).toLowerCase();
        return primaryText.includes(query) || secondaryText.includes(query);
    });
});

const getDisplayText = (option: any) => {
    return option[props.primaryKey] || '';
};

const getSecondaryText = (option: any) => {
    if (props.secondaryKey && option[props.secondaryKey]) {
        return option[props.secondaryKey];
    }
    if (option.email) return option.email;
    if (option.location?.name) return option.location.name;
    return '';
};

const selectOption = (option: any) => {
    emit('update:modelValue', option[props.valueKey]);
    isOpen.value = false;
    searchQuery.value = '';
};

const toggleDropdown = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = '';
        setTimeout(() => {
            inputRef.value?.focus();
        }, 50);
    }
};

const closeDropdown = () => {
    isOpen.value = false;
    searchQuery.value = '';
};

const handleClickOutside = (event: Event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        closeDropdown();
    }
};

watch(isOpen, (newValue) => {
    if (newValue) {
        document.addEventListener('click', handleClickOutside);
    } else {
        document.removeEventListener('click', handleClickOutside);
    }
});

onMounted(() => {
    if (isOpen.value) {
        document.addEventListener('click', handleClickOutside);
    }
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="dropdownRef" class="relative">
        <!-- Selected Value Display -->
        <button
            type="button"
            @click="toggleDropdown"
            :disabled="disabled"
            :class="[
                'relative w-full cursor-default rounded-lg bg-white dark:bg-gray-800 py-3 pl-4 pr-10 text-left shadow-sm ring-1 ring-inset transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:text-sm',
                error 
                    ? 'ring-red-300 dark:ring-red-600 text-red-900 dark:text-red-100' 
                    : 'ring-gray-300 dark:ring-gray-600 text-gray-900 dark:text-gray-100',
                disabled 
                    ? 'cursor-not-allowed opacity-50' 
                    : 'hover:ring-gray-400 dark:hover:ring-gray-500'
            ]"
        >
            <span v-if="selectedOption" class="block truncate">
                <span class="font-medium">{{ getDisplayText(selectedOption) }}</span>
                <span v-if="getSecondaryText(selectedOption)" class="text-gray-500 dark:text-gray-400 ml-2">
                    ({{ getSecondaryText(selectedOption) }})
                </span>
            </span>
            <span v-else class="block truncate text-gray-500 dark:text-gray-400">
                {{ placeholder }}
            </span>
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04L10 14.148l2.7-1.908a.75.75 0 111.1 1.02l-3.25 2.5a.75.75 0 01-1.1 0l-3.25-2.5a.75.75 0 01.04-1.06z" clip-rule="evenodd" />
                </svg>
            </span>
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white dark:bg-gray-800 py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
            >
                <!-- Search Input -->
                <div class="sticky top-0 z-10 bg-white dark:bg-gray-800 px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            ref="inputRef"
                            v-model="searchQuery"
                            type="text"
                            :placeholder="searchPlaceholder"
                            class="block w-full pl-10 pr-3 py-2 border-0 rounded-md bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                        />
                    </div>
                </div>

                <!-- Options List -->
                <div class="max-h-48 overflow-auto">
                    <div
                        v-for="option in filteredOptions"
                        :key="option.id"
                        @click="selectOption(option)"
                        :class="[
                            'relative cursor-default select-none py-3 px-4 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer transition-colors duration-150',
                            selectedOption?.id === option.id 
                                ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-900 dark:text-indigo-100' 
                                : 'text-gray-900 dark:text-gray-100'
                        ]"
                    >
                        <div class="flex flex-col">
                            <span class="font-medium truncate">{{ getDisplayText(option) }}</span>
                            <span v-if="getSecondaryText(option)" class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                {{ getSecondaryText(option) }}
                            </span>
                            <span v-if="option.category?.name" class="text-xs text-gray-400 dark:text-gray-500 truncate">
                                Category: {{ option.category.name }}
                            </span>
                        </div>
                        <span
                            v-if="selectedOption?.id === option.id"
                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-indigo-600 dark:text-indigo-400"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </div>
                    
                    <!-- No Results -->
                    <div v-if="filteredOptions.length === 0" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        No results found
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Error Message -->
        <div v-if="error" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
            <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ error }}
        </div>
    </div>
</template>
