<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';

interface Client {
    id: number;
    name: string;
    email: string;
}

interface Property {
    id: number;
    name?: string;
    property_name: string;
    estimated_monthly: string;
    category: { name: string };
    location: { name: string };
}

interface Props {
    clients: Client[];
    properties: Property[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Rental Management',
        href: '/admin/rented',
    },
    {
        title: 'Create Rental',
        href: '/admin/rented/create',
    },
];

const form = useForm({
    client_id: '',
    property_id: '',
    monthly_rent: '',
    security_deposit: '',
    start_date: '',
    end_date: '',
    status: 'active',
    terms_conditions: '',
    notes: '',
    contract_signed_at: '',
});

const isSubmitting = ref(false);
const selectedProperty = ref<Property | null>(null);
const isLoadingPropertyRate = ref(false);
const rateAutoFilled = ref(false);

// Watch for property selection to auto-fill monthly rent from API
watch(() => form.property_id, async (propertyId) => {
    if (propertyId) {
        selectedProperty.value = props.properties.find(p => p.id.toString() === propertyId) || null;
        
        // Fetch property rate from API
        try {
            isLoadingPropertyRate.value = true;
            rateAutoFilled.value = false;
            
            const response = await axios.get(`/admin/api/properties/${propertyId}/rate`);
            
            if (response.data.estimated_monthly) {
                form.monthly_rent = response.data.estimated_monthly.toString();
                rateAutoFilled.value = true;
                
                // Show success message briefly
                setTimeout(() => {
                    rateAutoFilled.value = false;
                }, 3000);
            }
        } catch (error) {
            console.error('Failed to fetch property rate:', error);
            // Fallback to local data if API fails
            if (selectedProperty.value && selectedProperty.value.estimated_monthly) {
                form.monthly_rent = selectedProperty.value.estimated_monthly;
            }
        } finally {
            isLoadingPropertyRate.value = false;
        }
    } else {
        selectedProperty.value = null;
        form.monthly_rent = '';
        rateAutoFilled.value = false;
    }
});

const submit = () => {
    isSubmitting.value = true;
    form.post('/admin/rented', {
        onSuccess: () => {
            // Form will redirect automatically on success
        },
        onError: () => {
            isSubmitting.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

const cancel = () => {
    window.history.back();
};

const formatCurrency = (value: string) => {
    if (!value) return '';
    const num = parseFloat(value.replace(/[^\d.]/g, ''));
    return isNaN(num) ? '' : num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const handleMonthlyRentInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = target.value.replace(/[^\d.]/g, '');
    form.monthly_rent = value;
    rateAutoFilled.value = false; // Reset auto-fill indicator when manually changed
};

// Validate rental data in real-time
const validateRentalData = async () => {
    if (!form.property_id || !form.monthly_rent) return;
    
    try {
        const response = await axios.post('/admin/api/rented/validate', {
            property_id: form.property_id,
            monthly_rent: parseFloat(form.monthly_rent)
        });
        
        return response.data.valid;
    } catch (error) {
        console.error('Validation error:', error);
        return false;
    }
};

const handleSecurityDepositInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = target.value.replace(/[^\d.]/g, '');
    form.security_deposit = value;
};
</script>

<template>
    <Head title="Create Rental" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Create Rental</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add a new rental record for property management</p>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <!-- Client and Property Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Client Selection -->
                        <div>
                            <label for="client_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Client <span class="text-red-500">*</span>
                            </label>
                            <SearchableSelect
                                v-model="form.client_id"
                                :options="clients"
                                placeholder="Select a client..."
                                search-placeholder="Search clients..."
                                display-key="name"
                                secondary-key="email"
                                :disabled="isSubmitting"
                                :error="form.errors.client_id"
                                :required="true"
                            />
                        </div>

                        <!-- Property Selection -->
                        <div>
                            <label for="property_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Property <span class="text-red-500">*</span>
                            </label>
                            <SearchableSelect
                                v-model="form.property_id"
                                :options="properties.map(p => ({ ...p, name: p.property_name }))"
                                placeholder="Select a property..."
                                search-placeholder="Search properties..."
                                display-key="name"
                                :disabled="isSubmitting"
                                :error="form.errors.property_id"
                                :required="true"
                            />
                            <div v-if="selectedProperty" class="mt-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                <div class="text-sm text-blue-800 dark:text-blue-200">
                                    <div><strong>Category:</strong> {{ selectedProperty.category.name }}</div>
                                    <div><strong>Location:</strong> {{ selectedProperty.location.name }}</div>
                                    <div v-if="selectedProperty.estimated_monthly"><strong>Estimated Monthly:</strong> ₱{{ formatCurrency(selectedProperty.estimated_monthly) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Monthly Rent -->
                        <div>
                            <label for="monthly_rent" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Monthly Rent (₱) <span class="text-red-500">*</span>
                                <span v-if="isLoadingPropertyRate" class="ml-2 text-xs text-blue-600 dark:text-blue-400">
                                    <svg class="inline h-3 w-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Loading rate...
                                </span>
                                <span v-else-if="rateAutoFilled" class="ml-2 text-xs text-green-600 dark:text-green-400">
                                    <svg class="inline h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Auto-filled from property
                                </span>
                            </label>
                            <div class="relative">
                                <input
                                    id="monthly_rent"
                                    :value="form.monthly_rent"
                                    @input="handleMonthlyRentInput"
                                    type="text"
                                    placeholder="0.00"
                                    :class="[
                                        'block w-full px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                        form.errors.monthly_rent 
                                            ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10 text-red-900 dark:text-red-100 placeholder-red-400' 
                                            : rateAutoFilled
                                                ? 'border-green-300 dark:border-green-600 bg-green-50 dark:bg-green-900/10 text-green-900 dark:text-green-100'
                                                : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500'
                                    ]"
                                    :disabled="isSubmitting || isLoadingPropertyRate"
                                    required
                                />
                                <div v-if="isLoadingPropertyRate" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg class="h-4 w-4 animate-spin text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </div>
                            </div>
                            <div v-if="form.errors.monthly_rent" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.monthly_rent }}
                            </div>
                            <div v-else-if="rateAutoFilled && form.monthly_rent" class="mt-2 flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Monthly rent automatically set to ₱{{ formatCurrency(form.monthly_rent) }} from property's estimated rate
                            </div>
                        </div>

                        <!-- Security Deposit -->
                        <div>
                            <label for="security_deposit" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Security Deposit (₱)
                            </label>
                            <input
                                id="security_deposit"
                                :value="form.security_deposit"
                                @input="handleSecurityDepositInput"
                                type="text"
                                placeholder="0.00"
                                :class="[
                                    'block w-full px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                    form.errors.security_deposit 
                                        ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10 text-red-900 dark:text-red-100 placeholder-red-400' 
                                        : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500'
                                ]"
                                :disabled="isSubmitting"
                            />
                            <div v-if="form.errors.security_deposit" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.security_deposit }}
                            </div>
                        </div>
                    </div>

                    <!-- Date and Status -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Start Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="start_date"
                                v-model="form.start_date"
                                type="date"
                                :class="[
                                    'block w-full px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                    form.errors.start_date 
                                        ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10 text-red-900 dark:text-red-100' 
                                        : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-indigo-500'
                                ]"
                                :disabled="isSubmitting"
                                required
                            />
                            <div v-if="form.errors.start_date" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.start_date }}
                            </div>
                        </div>

                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                End Date
                            </label>
                            <input
                                id="end_date"
                                v-model="form.end_date"
                                type="date"
                                :class="[
                                    'block w-full px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                    form.errors.end_date 
                                        ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10 text-red-900 dark:text-red-100' 
                                        : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-indigo-500'
                                ]"
                                :disabled="isSubmitting"
                            />
                            <div v-if="form.errors.end_date" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.end_date }}
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Leave empty for open-ended rental
                            </p>
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="status"
                                v-model="form.status"
                                type="text"
                                readonly
                                :class="[
                                    'block w-full px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 cursor-not-allowed',
                                    'border-gray-300 dark:border-gray-600'
                                ]"
                                disabled
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Status is automatically set to Active for all rentals
                            </p>
                            <div v-if="form.errors.status" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.status }}
                            </div>
                        </div>
                    </div>

                    <!-- Contract Signed Date -->
                    <div>
                        <label for="contract_signed_at" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Contract Signed Date
                        </label>
                        <input
                            id="contract_signed_at"
                            v-model="form.contract_signed_at"
                            type="date"
                            :class="[
                                'block w-full max-w-md px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                form.errors.contract_signed_at 
                                    ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10 text-red-900 dark:text-red-100' 
                                    : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-indigo-500'
                            ]"
                            :disabled="isSubmitting"
                        />
                        <div v-if="form.errors.contract_signed_at" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ form.errors.contract_signed_at }}
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div>
                        <label for="terms_conditions" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Terms and Conditions
                        </label>
                        <textarea
                            id="terms_conditions"
                            v-model="form.terms_conditions"
                            rows="4"
                            placeholder="Enter rental terms and conditions..."
                            :class="[
                                'block w-full px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none',
                                form.errors.terms_conditions 
                                    ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10 text-red-900 dark:text-red-100 placeholder-red-400' 
                                    : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500'
                            ]"
                            :disabled="isSubmitting"
                        />
                        <div v-if="form.errors.terms_conditions" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ form.errors.terms_conditions }}
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Notes
                        </label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            placeholder="Additional notes about this rental..."
                            :class="[
                                'block w-full px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none',
                                form.errors.notes 
                                    ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10 text-red-900 dark:text-red-100 placeholder-red-400' 
                                    : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500'
                            ]"
                            :disabled="isSubmitting"
                        />
                        <div v-if="form.errors.notes" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ form.errors.notes }}
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button
                            type="button"
                            @click="cancel"
                            :disabled="isSubmitting"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting || !form.client_id || !form.property_id || !form.monthly_rent || !form.start_date"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 disabled:cursor-not-allowed"
                        >
                            <svg v-if="isSubmitting" class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ isSubmitting ? 'Creating...' : 'Create Rental' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Help Section -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">Rental Creation Guidelines</h3>
                        <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                            <li>• Select an active client and available property</li>
                            <li>• Monthly rent will auto-fill from property's estimated amount</li>
                            <li>• Start date must be today or in the future for new rentals</li>
                            <li>• End date is optional for open-ended rentals</li>
                            <li>• All rentals are automatically set to Active status</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
