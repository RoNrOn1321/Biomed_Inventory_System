<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, FileText, Plus, Send, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import axios from 'axios';

const page = usePage<SharedData>();

const showToast = ref(false);
const toastMessage = ref('');

const props = defineProps<{
    nextControlNumber?: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Job Request Form',
        href: '/request-service',
    },
];

const form = useForm({
    requester_name: page.props.auth.user.name,
    date: new Date().toISOString().split('T')[0],
    department: '',
    department_other: '',
    location: '',

    equipments: [
        {
            equipment_name: '',
            brand: '',
            model: '',
            serial_number: '',
            end_user: '',
        },
    ],

    problem_description: '',
    services_desired: [] as string[],
    other_service: '',
    nature_of_work: '',
});

const departments = ['HOPSS', 'MEDICAL', 'NURSING', 'ALLIED HEALTH PROFESSIONAL SERVICE', 'FINANCE', 'Other'];
const services = [
    'Repair',
    'Pre-Inspection',
    'Install Equipment',
    'Calibrate Equipment',
    'Performance Test',
    'New Equipment',
    'Delivery Inspection & Acceptance',
];

const submit = () => {
    console.log('Form submitted:', form.data());

    // Convert array structure to what controller expects.
    // Wait, the backend already expects my new `equipments` array. I just need to post it.
    form.post('/request-service', {
        preserveScroll: true,
        onSuccess: () => {
            showToast.value = true;
            toastMessage.value = 'Job request submitted successfully!';

            // Clean equipments specifically
            form.equipments = [
                {
                    equipment_name: '',
                    brand: '',
                    model: '',
                    serial_number: '',
                    end_user: '',
                },
            ];

            // Keep the name when resetting other fields
            const currentName = form.requester_name;
            form.reset();
            form.requester_name = currentName;

            setTimeout(() => {
                showToast.value = false;
            }, 3000);
        },
        onError: (errors) => {
            console.error(errors);
            showToast.value = true;
            toastMessage.value = 'Validation failed. Please check your inputs.';
            setTimeout(() => {
                showToast.value = false;
            }, 3000);
        },
    });
};

const addEquipment = () => {
    form.equipments.push({
        equipment_name: '',
        brand: '',
        model: '',
        serial_number: '',
        end_user: '',
    });
};

const removeEquipment = (index: number) => {
    if (form.equipments.length > 1) {
        form.equipments.splice(index, 1);
    }
};

// --- Add your tracking reactives ---
const activeSearchIndex = ref<number | null>(null);
const searchResults = ref<any[]>([]);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

// --- Add the search function ---
const handleEquipmentSearch = (index: number) => {
    const query = form.equipments[index].equipment_name;
    activeSearchIndex.value = index;
    
    if (!query || query.length < 2) {
        searchResults.value = [];
        return;
    }

    if (searchTimeout) clearTimeout(searchTimeout);
    
    // Debounce to avoid spamming the backend
    searchTimeout = setTimeout(async () => {
        try {
            const { data } = await axios.get('/api/equipments/search', { params: { q: query } });
            searchResults.value = data;
        } catch (e) {
            console.error('Failed to search inventory', e);
        }
    }, 300); 
};

// --- Add function to select one of the equipment results ---
const selectEquipment = (index: number, equipment: any) => {
    const item = form.equipments[index];
    
    // Map backend attributes to frontend form payload
    item.equipment_name = equipment.description || '';
    item.brand = equipment.brand || '';
    item.model = equipment.model || '';
    item.serial_number = equipment.serial_number || '';
    item.location = equipment.location || ''; // Depending on what "end_user" maps to on DB
    
    // Hide dropdown
    activeSearchIndex.value = null;
    searchResults.value = [];
};
</script>

<template>
    <Head title="Job Request Form" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-[linear-gradient(180deg,_#fffaf5_0%,_#ffffff_30%,_#fffaf3_100%)] pb-12">
            <section class="border-b-4 border-orange-400 bg-gradient-to-r from-orange-50 to-orange-100 px-4 py-5 shadow-md">
                <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Job Request Form</h1>
                            <p class="text-sm font-medium text-orange-700">ADELA SERRA TY MEMORIAL MEDICAL CENTER - Department of Health</p>
                        </div>
                        <FileText class="h-8 w-8 text-orange-600 opacity-80" />
                    </div>
                </div>
            </section>

            <div class="mx-auto mt-8 max-w-4xl px-4 sm:px-6 lg:px-8">
                <form
                    @submit.prevent="submit"
                    class="space-y-8 rounded-2xl border border-orange-200 bg-white p-6 shadow-lg shadow-orange-100/60 md:p-8"
                >
                    <!-- Basic Information -->
                    <section class="space-y-4">
                        <div class="border-b border-orange-200 pb-2">
                            <h2 class="text-center text-lg font-semibold uppercase tracking-wider text-orange-900">BASIC INFORMATION</h2>
                        </div>

                        <div class="grid gap-6 md:grid-cols-3">
                            <div class="space-y-2 md:col-span-1">
                                <Label for="requester_name">Requested By (Department)</Label>
                                <Input
                                    id="requester_name"
                                    v-model="form.requester_name"
                                    placeholder="Enter full name"
                                    class="border-orange-200 focus:border-orange-500 focus:ring-orange-500 disabled:opacity-50"
                                    readonly
                                    required
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="date">Date</Label>
                                <Input
                                    id="date"
                                    type="date"
                                    v-model="form.date"
                                    class="border-orange-200 focus:border-orange-500 focus:ring-orange-500"
                                    required
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="control_number">Control #</Label>
                                <Input
                                    id="control_number"
                                    :model-value="props.nextControlNumber"
                                    class="border-slate-200 bg-slate-50 text-slate-500 focus:border-slate-200 focus:ring-slate-200"
                                    readonly
                                    disabled
                                    title="Auto-generated control number based on the next sequence."
                                />
                            </div>
                        </div>

                        <div class="grid gap-6 pt-2 md:grid-cols-2 lg:grid-cols-3">
                            <div class="space-y-3 lg:col-span-2">
                                <Label>Requesting Department</Label>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                    <div v-for="dept in departments" :key="dept" class="flex items-center space-x-2">
                                        <input
                                            type="radio"
                                            :id="dept"
                                            :value="dept"
                                            v-model="form.department"
                                            class="border-orange-300 text-orange-600 focus:ring-orange-500"
                                        />
                                        <Label :for="dept" class="text-sm font-normal">{{ dept }}</Label>
                                    </div>
                                </div>
                                <Input
                                    v-if="form.department === 'Other'"
                                    v-model="form.department_other"
                                    placeholder="Specify other department..."
                                    class="mt-2 border-orange-200 focus:border-orange-500"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="location">Location</Label>
                                <Input
                                    id="location"
                                    v-model="form.location"
                                    placeholder="e.g. IT, Ward, Room"
                                    class="border-orange-200 focus:border-orange-500 focus:ring-orange-500"
                                />
                            </div>
                        </div>
                    </section>

                                                          <!-- Equipment Details -->
                    <section class="space-y-4 pt-4">
                        <div class="flex items-center justify-between border-b border-orange-200 pb-2">
                            <h2 class="inline-block rounded bg-orange-100 px-3 py-1 text-sm font-semibold text-orange-900">
                                Description of Equipment and Accessories:
                            </h2>
                            <Button
                                type="button"
                                @click="addEquipment"
                                variant="outline"
                                size="sm"
                                class="border-orange-300 text-orange-700 hover:bg-orange-50"
                            >
                                <Plus class="mr-1 h-4 w-4" /> Add Item
                            </Button>
                        </div>

                        <div class="space-y-4">
                            <!-- This is the loop where "index" comes from -->
                            <div
                                v-for="(equipment, index) in form.equipments" :key="index"
                                class="relative rounded-lg border border-orange-100 bg-orange-50/30 p-4"
                            >
                                <div class="absolute -right-2 -top-2" v-if="form.equipments.length > 1">
                                    <button
                                        type="button"
                                        @click="removeEquipment(index)"
                                        class="flex h-6 w-6 items-center justify-center rounded-full bg-red-100 text-red-600 shadow-sm transition-colors hover:bg-red-200"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                    </button>
                                </div>
                                
                                <!-- State 1: Equipment Selected -->
                                <div v-if="equipment.serial_number || equipment.brand || equipment.model" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-md border border-orange-200 bg-white p-4 shadow-sm">
                                    <div class="space-y-1">
                                        <h3 class="font-bold text-slate-800">{{ equipment.equipment_name || 'Unnamed Equipment' }}</h3>
                                        <div class="text-sm text-slate-600 flex flex-wrap gap-x-4 gap-y-1">
                                            <span v-if="equipment.brand"><span class="font-semibold text-slate-500">Brand:</span> {{ equipment.brand }}</span>
                                            <span v-if="equipment.model"><span class="font-semibold text-slate-500">Model:</span> {{ equipment.model }}</span>
                                            <span v-if="equipment.serial_number"><span class="font-semibold text-slate-500">SN:</span> {{ equipment.serial_number }}</span>
                                            <span v-if="equipment.location"><span class="font-semibold text-slate-500">Location:</span> {{ equipment.location }}</span>
                                            <div class="mt-2 flex w-full flex-col sm:flex-row sm:items-center gap-2 sm:w-auto">
                                                <Label  class="text-xs font-semibold text-slate-500 whitespace-nowrap">End User:</Label>
                                                <Input
                                                     v-model="equipment.end_user" 
                                                    placeholder="Enter end user"
                                                    class="h-8 w-full sm:w-48 border-orange-200 bg-white text-xs focus:border-orange-400 focus:ring-orange-400" 
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <Button 
                                        type="button" 
                                        variant="outline" 
                                        size="sm" 
                                        class="shrink-0 text-orange-600 border-orange-200 hover:bg-orange-50"
                                        @click="() => {
                                            equipment.equipment_name = '';
                                            equipment.brand = '';
                                            equipment.model = '';
                                            equipment.serial_number = '';
                                            equipment.end_user = '';
                                        }"
                                    >
                                        Change Item
                                    </Button>
                                </div>

                                <!-- State 2: Search Input -->
                                <div v-else class="relative space-y-2">
                                    <Label :for="`eq_name_${index}`" class="text-sm font-medium text-slate-700">Search Equipment</Label>
                                    <Input
                                        :id="`eq_name_${index}`"
                                        v-model="equipment.equipment_name"
                                        placeholder="Type equipment name, brand, or serial number..."
                                        class="h-11 border-orange-200 bg-white text-sm focus:border-orange-400 focus:ring-orange-400"
                                        required
                                        autocomplete="off"
                                        @input="handleEquipmentSearch(index)"
                                        @focus="activeSearchIndex = index; handleEquipmentSearch(index)"
                                        @blur="setTimeout(() => activeSearchIndex = null, 200)"
                                    />
                                    
                                    <!-- Autocomplete Results Dropdown -->
                                    <ul
                                        v-if="activeSearchIndex === index && searchResults.length > 0"
                                        class="absolute top-[4.5rem] z-20 w-full max-h-60 overflow-y-auto rounded-md border border-orange-200 bg-white py-1 shadow-xl"
                                    >
                                        <li
                                            v-for="res in searchResults"
                                            :key="res.id"
                                            @mousedown.prevent="selectEquipment(index, res)"
                                            class="group cursor-pointer px-4 py-3 border-b border-orange-50 last:border-none hover:bg-orange-50 transition-colors"
                                        >
                                            <div class="text-sm font-bold text-slate-800">{{ res.description }}</div>
                                            <div class="mt-1 text-xs text-slate-500 flex gap-3">
                                                <span v-if="res.brand">{{ res.brand }}</span>
                                                <span v-if="res.model">- {{ res.model }}</span> 
                                                <span v-if="res.serial_number" class="text-orange-600/80 font-medium">SN: {{ res.serial_number }}</span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>

                            </div>
                        </div>
                    </section>

                   

                    <!-- Request Detail -->
                    <section class="mt-6 space-y-4 border-t border-orange-300 pt-6">
                        <div class="border-b border-orange-200 pb-2">
                            <h2 class="text-center text-lg font-semibold uppercase tracking-wider text-orange-900">REQUEST DETAIL</h2>
                            <p class="mt-1 text-center text-xs text-slate-600">
                                Provide a detailed indication of the problem in the space below, including symptoms, settings, error codes,
                                circumstances, and services desired.
                            </p>
                        </div>

                        <!-- <div class="space-y-2">
                            <textarea
                                v-model="form.problem_description"
                                placeholder="Describe the problem, error codes, etc. here..."
                                class="min-h-[100px] w-full rounded-md border border-orange-200 bg-orange-50/30 px-3 py-2 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            ></textarea>
                        </div> -->

                        <div class="rounded-xl border border-orange-100 bg-orange-50/50 p-4">
                            <div class="space-y-3">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
                                    <div v-for="service in services" :key="service" class="flex items-center space-x-2">
                                        <Checkbox
                                            :id="service.replace(/\\s+/g, '')"
                                            :value="service"
                                            :checked="form.services_desired.includes(service)"
                                            @update:checked="
                                                (checked) => {
                                                    if (checked) form.services_desired.push(service);
                                                    else form.services_desired = form.services_desired.filter((s) => s !== service);
                                                }
                                            "
                                            class="border-orange-300 data-[state=checked]:border-orange-600 data-[state=checked]:bg-orange-600"
                                        />
                                        <Label :for="service.replace(/\\s+/g, '')" class="text-sm font-normal">{{ service }}</Label>
                                    </div>
                                    <div class="mt-1 flex items-center space-x-2 sm:col-span-2 md:col-span-3">
                                        <Checkbox
                                            id="Others"
                                            :checked="form.services_desired.includes('Others')"
                                            @update:checked="
                                                (checked) => {
                                                    if (checked) form.services_desired.push('Others');
                                                    else form.services_desired = form.services_desired.filter((s) => s !== 'Others');
                                                }
                                            "
                                            class="border-orange-300 data-[state=checked]:border-orange-600 data-[state=checked]:bg-orange-600"
                                        />
                                        <Label for="Others" class="mr-2 text-sm font-normal">Others</Label>
                                        <Input
                                            v-model="form.other_service"
                                            class="h-8 flex-1 border-orange-200 bg-white text-sm"
                                            :disabled="!form.services_desired.includes('Others')"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2 pt-2">
                            <Label for="nature_of_work" class="inline-block rounded bg-orange-100 px-3 py-1 text-sm font-semibold text-orange-900">
                                NATURE OF WORK REQUESTED / COMPLAINTS:
                            </Label>
                            <textarea
                                id="nature_of_work"
                                v-model="form.nature_of_work"
                                class="min-h-[80px] w-full rounded-md border border-orange-200 px-3 py-2 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            ></textarea>
                        </div>
                    </section>

                    <div class="mt-6 flex items-center justify-end border-t border-orange-200 pt-6">
                        <Button type="submit" class="min-w-[150px] bg-orange-600 text-white hover:bg-orange-700" :disabled="form.processing">
                            <Send class="mr-2 h-4 w-4" />
                            Submit Request
                        </Button>
                    </div>
                </form>

                <!-- Information Note -->
                <div class="mt-6 pb-8 text-center text-sm text-slate-500">
                    <p class="font-medium text-red-500">For Emergency Requests - call us with this Fanvil No. 2012</p>
                    <p class="mt-1 text-xs opacity-70">HOPSS-EFMS-BIO-F-04 Rev.2 | Effectivity date: August 15, 2024</p>
                </div>
            </div>

            <!-- Custom Toast Notification -->
            <transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showToast"
                    class="fixed bottom-4 right-4 z-50 flex w-full max-w-sm items-center gap-3 overflow-hidden rounded-lg bg-green-50 px-4 py-3 text-green-900 shadow-lg ring-1 ring-black ring-opacity-5 sm:bottom-6 sm:right-6"
                >
                    <CheckCircle2 class="h-6 w-6 shrink-0 text-green-500" />
                    <div>
                        <p class="text-sm font-semibold text-green-800">Success</p>
                        <p class="mt-0.5 text-sm text-green-700">{{ toastMessage }}</p>
                    </div>
                    <button
                        @click="showToast = false"
                        class="ml-auto flex shrink-0 items-center justify-center text-green-500 hover:text-green-700 focus:outline-none"
                    >
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </div>
            </transition>
        </div>
    </AppLayout>
</template>
