<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface EquipmentDocument {
    id: number;
    equipment_id: number;
    file_name: string;
    file_path: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventory',
        href: '/Inventory',
    },
];

interface Equipment {
    id: number;
    location: string | null;
    description: string;
    brand: string | null;
    model: string | null;
    serial_number: string | null;
    tag_number: string | null;
    pm_date_done: string | null;
    calibration: string | null;
    status: string | null;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

const props = defineProps<{
    equipments: PaginatedData<Equipment>;
    filters: {
        year?: string;
        month?: string;
        search?: string;
        status?: string;
    };
}>();

const addModalVisible = ref(false);
const editModalVisible = ref(false);
const deleteModalVisible = ref(false);
const exportPanelVisible = ref(false);

const toastVisible = ref(false);
const toastMessage = ref('');
let toastTimeout: ReturnType<typeof setTimeout>;

const showToast = (message: string) => {
    toastMessage.value = message;
    toastVisible.value = true;

    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        toastVisible.value = false;
    }, 3000);
};

const now = new Date();
const currentYear = String(now.getFullYear());
const currentMonth = String(now.getMonth() + 1).padStart(2, '0');
const currentMonthValue = `${currentYear}-${currentMonth}`;
const defaultFromDate = new Date(now.getFullYear(), now.getMonth() - 2, 1);
const defaultFromMonthValue = `${defaultFromDate.getFullYear()}-${String(defaultFromDate.getMonth() + 1).padStart(2, '0')}`;
const todayDate = `${currentYear}-${currentMonth}-${String(now.getDate()).padStart(2, '0')}`;

const addItemForm = useForm({
    location: '',
    description: '',
    brand: '',
    model: '',
    serial_number: '',
    tag_number: '',
    pm_date_done: todayDate,
    status: 'Functional',
});

const editItemForm = useForm({
    id: 0,
    location: '',
    description: '',
    brand: '',
    model: '',
    serial_number: '',
    tag_number: '',
    pm_date_done: '',
    status: '',
});

const deleteForm = useForm({
    id: 0,
});

const search = ref(props.filters.search || '');
const filterYear = ref(props.filters.year || currentYear);
const filterMonth = ref(props.filters.month || currentMonth);
const filterStatus = ref(props.filters.status || 'all');
const exportFormat = ref<'pdf' | 'excel' | 'word'>('pdf');
const exportFrom = ref(defaultFromMonthValue);
const exportTo = ref(currentMonthValue);

const exportRangeLabel = computed(() => {
    const from = new Date(`${exportFrom.value}-01T00:00:00`);
    const to = new Date(`${exportTo.value}-01T00:00:00`);

    return `${from.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })} to ${to.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}`;
});

let searchTimeout: ReturnType<typeof setTimeout>;

watch([search, filterYear, filterMonth, filterStatus], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            '/Inventory',
            {
                search: search.value || undefined,
                year: filterYear.value || undefined,
                month: filterMonth.value || undefined,
                status: filterStatus.value !== 'all' ? filterStatus.value : undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

const clearFilters = () => {
    search.value = '';
    filterYear.value = currentYear;
    filterMonth.value = currentMonth;
    filterStatus.value = 'all';
};

const toggleExportPanel = () => {
    exportPanelVisible.value = !exportPanelVisible.value;
};

const downloadInventory = () => {
    if (exportFrom.value > exportTo.value) {
        alert('The export date range is invalid.');
        return;
    }

    const params = new URLSearchParams({
        format: exportFormat.value,
        from: exportFrom.value,
        to: exportTo.value,
        search: search.value,
    });
    if (filterStatus.value !== 'all') {
        params.set('status', filterStatus.value);
    }

    window.open(`/equipment/export?${params.toString()}`, '_blank');
};

const openAddModal = () => {
    addItemForm.reset();
    addItemForm.pm_date_done = todayDate;
    addModalVisible.value = true;
};

const closeAddModal = () => {
    addModalVisible.value = false;
};

const submitAddModal = () => {
    addItemForm.post('/equipment', {
        onSuccess: () => {
            closeAddModal();
            showToast('Equipment added successfully!');
        },
    });
};

const openEditModal = (item: Equipment) => {
    editItemForm.id = item.id;
    editItemForm.location = item.location || '';
    editItemForm.description = item.description || '';
    editItemForm.brand = item.brand || '';
    editItemForm.model = item.model || '';
    editItemForm.serial_number = item.serial_number || '';
    editItemForm.tag_number = item.tag_number || '';
    editItemForm.pm_date_done = item.pm_date_done || '';
    editItemForm.status = item.status || '';
    editModalVisible.value = true;
};

const closeEditModal = () => {
    editModalVisible.value = false;
};

const submitEditModal = () => {
    editItemForm.put(`/equipment/${editItemForm.id}`, {
        onSuccess: () => {
            closeEditModal();
            showToast('Equipment updated successfully!');
        },
    });
};

const equipmentToDelete = ref<Equipment | null>(null);

const openDeleteModal = (item: Equipment) => {
    equipmentToDelete.value = item;
    deleteForm.id = item.id;
    deleteModalVisible.value = true;
};

const closeDeleteModal = () => {
    deleteModalVisible.value = false;
};

const confirmDelete = () => {
    deleteForm.delete(`/equipment/${deleteForm.id}`, {
        onSuccess: () => {
            closeDeleteModal();
            showToast('Equipment deleted successfully!');
        },
    });
};

const formatDate = (dateStr: string | null): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

// Calibration modal
interface EquipmentCalibrationFile {
    id: number;
    equipment_id: number;
    file_name: string;
    file_path: string;
}

const calibrationModalVisible = ref(false);
const selectedEquipmentForCal = ref<Equipment | null>(null);
const calibrationFiles = ref<EquipmentCalibrationFile[]>([]);
const previewCalibrationFile = ref<EquipmentCalibrationFile | null>(null);
const uploadingCalibration = ref(false);

const openCalibrationModal = async (item: Equipment) => {
    selectedEquipmentForCal.value = item;
    previewCalibrationFile.value = null;
    calibrationFiles.value = [];
    calibrationModalVisible.value = true;
    await fetchCalibrationFiles(item.id);
};

const closeCalibrationModal = () => {
    calibrationModalVisible.value = false;
    previewCalibrationFile.value = null;
    selectedEquipmentForCal.value = null;
};

const fetchCalibrationFiles = async (equipmentId: number) => {
    const res = await fetch(`/equipment/${equipmentId}/calibrations`);
    calibrationFiles.value = await res.json();
};

const uploadCalibrationFiles = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (!input.files || !input.files.length || !selectedEquipmentForCal.value) return;
    uploadingCalibration.value = true;
    const formData = new FormData();
    for (const file of input.files) {
        formData.append('files[]', file);
    }
    try {
        const res = await fetch(`/equipment/${selectedEquipmentForCal.value.id}/calibrations`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' },
            body: formData,
        });
        if (!res.ok) {
            const err = await res.text();
            console.error('Upload failed:', res.status, err);
            alert(`Upload failed (${res.status}). Check console for details.`);
        } else {
            showToast('Calibration files uploaded successfully!');
            router.reload({ only: ['equipments'] });
        }
    } catch (e) {
        console.error('Upload error:', e);
        alert('Upload error. Check console for details.');
    } finally {
        uploadingCalibration.value = false;
        input.value = '';
    }
    if (selectedEquipmentForCal.value) await fetchCalibrationFiles(selectedEquipmentForCal.value.id);
};

const deleteCalibrationFile = async (file: EquipmentCalibrationFile) => {
    if (!confirm(`Delete "${file.file_name}"?`)) return;
    await fetch(`/equipment/calibrations/${file.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' },
    });
    showToast('Calibration file deleted successfully!');
    if (previewCalibrationFile.value?.id === file.id) previewCalibrationFile.value = null;
    if (selectedEquipmentForCal.value) {
        await fetchCalibrationFiles(selectedEquipmentForCal.value.id);
        router.reload({ only: ['equipments'] });
    }
};

// Documents modal
const documentsModalVisible = ref(false);
const selectedEquipmentForDocs = ref<Equipment | null>(null);
const documents = ref<EquipmentDocument[]>([]);
const previewDocument = ref<EquipmentDocument | null>(null);
const uploading = ref(false);

const openDocumentsModal = async (item: Equipment) => {
    selectedEquipmentForDocs.value = item;
    previewDocument.value = null;
    documents.value = [];
    documentsModalVisible.value = true;
    await fetchDocuments(item.id);
};

const closeDocumentsModal = () => {
    documentsModalVisible.value = false;
    previewDocument.value = null;
    selectedEquipmentForDocs.value = null;
};

const fetchDocuments = async (equipmentId: number) => {
    const res = await fetch(`/equipment/${equipmentId}/documents`);
    documents.value = await res.json();
};

const uploadDocuments = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (!input.files || !input.files.length || !selectedEquipmentForDocs.value) return;
    uploading.value = true;
    const formData = new FormData();
    for (const file of input.files) {
        formData.append('files[]', file);
    }
    try {
        const res = await fetch(`/equipment/${selectedEquipmentForDocs.value.id}/documents`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' },
            body: formData,
        });
        if (!res.ok) {
            const err = await res.text();
            console.error('Upload failed:', res.status, err);
            alert(`Upload failed (${res.status}). Check console for details.`);
        } else {
            showToast('Documents uploaded successfully!');
        }
    } catch (e) {
        console.error('Upload error:', e);
        alert('Upload error. Check console for details.');
    } finally {
        uploading.value = false;
        input.value = '';
    }
    if (selectedEquipmentForDocs.value) await fetchDocuments(selectedEquipmentForDocs.value.id);
};

const deleteDocument = async (doc: EquipmentDocument) => {
    if (!confirm(`Delete "${doc.file_name}"?`)) return;
    await fetch(`/equipment/documents/${doc.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' },
    });
    showToast('Document deleted successfully!');
    if (previewDocument.value?.id === doc.id) previewDocument.value = null;
    if (selectedEquipmentForDocs.value) await fetchDocuments(selectedEquipmentForDocs.value.id);
};
</script>
<style>
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}

@keyframes slideScale {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes slideScaleClose {
    from {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
    to {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
    }
}

.toast-enter-active {
    animation: toastIn 0.3s ease-out;
}

.toast-leave-active {
    animation: toastOut 0.2s ease-in forwards;
}

@keyframes toastIn {
    from {
        opacity: 0;
        transform: translateY(-12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes toastOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-12px);
    }
}

.modal-backdrop {
    animation: fadeIn 0.3s ease-out;
}

.modal-backdrop.closing {
    animation: fadeOut 0.2s ease-in forwards;
}

.modal-content {
    animation: slideScale 0.3s ease-out;
}

.modal-backdrop.closing .modal-content {
    animation: slideScaleClose 0.2s ease-in forwards;
}
</style>

<template>
    <Head title="Inventory" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50/50 p-4 sm:p-6 lg:p-8">
            <section class="mb-8 overflow-hidden rounded-2xl bg-orange-50 shadow-sm ring-1 ring-orange-200">
                <div class="flex items-center gap-6 p-6 sm:p-8">
                    <div
                        class="relative flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-white p-2 shadow-inner ring-1 ring-orange-200"
                    >
                        <img src="/logo.JPG" alt="" class="h-full w-full object-contain" onerror="this.style.display='none'" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Adela Serra Ty Memorial Medical Center</h1>
                        <p class="mt-2 text-sm font-medium uppercase tracking-wide text-orange-600">Biomed Preventive Maintenance</p>
                    </div>
                </div>
            </section>

            <section class="mx-auto rounded-xl drop-shadow-sm">
                <div
                    class="mb-6 flex flex-col gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <label for="filterYear" class="block text-sm font-medium text-gray-800">Filter By:</label>
                        <div class="mt-1 flex items-end gap-2">
                            <select
                                id="filterYear"
                                v-model="filterYear"
                                class="block w-28 rounded-lg border-0 py-2.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-orange-600 sm:text-sm sm:leading-6"
                            >
                                <option value="all">All</option>
                                <option value="2022">2022</option>
                                <option value="2023">2023</option>
                                <option value="2024">2024</option>
                                <option value="2025">2025</option>
                                <option value="2026">2026</option>
                            </select>
                            <select
                                id="filterMonth"
                                v-model="filterMonth"
                                class="block w-36 rounded-lg border-0 py-2.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-orange-600 sm:text-sm sm:leading-6"
                            >
                                <option value="all">All</option>
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <option value="04">April</option>
                                <option value="05">May</option>
                                <option value="06">June</option>
                                <option value="07">July</option>
                                <option value="08">August</option>
                                <option value="09">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                            <select
                                id="filterStatus"
                                v-model="filterStatus"
                                class="block w-40 rounded-lg border-0 py-2.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-orange-600 sm:text-sm sm:leading-6"
                            >
                                <option value="all">All Status</option>
                                <option value="Functional">Functional</option>
                                <option value="Defective">Defective</option>
                                <option value="Unserviceable">Unserviceable</option>
                            </select>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                            >
                                Clear
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div
                            class="flex items-center rounded-lg bg-white px-4 py-2 shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-orange-600 sm:max-w-xs"
                        >
                            <svg class="size-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                            <input
                                type="text"
                                v-model="search"
                                placeholder="Search equipment..."
                                class="ml-3 w-48 border-0 bg-transparent py-1 text-sm text-gray-900 placeholder-gray-400 outline-none"
                            />
                        </div>

                        <button
                            type="button"
                            @click="openAddModal"
                            class="inline-flex items-center gap-x-2 rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600"
                        >
                            <svg class="mr-2 size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Equipment
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
                    <table class="min-w-full border-collapse border border-gray-300">
                        <thead class="bg-orange-500">
                            <tr>
                                <th
                                    scope="col"
                                    class="border-r border-orange-400 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-white sm:pl-6"
                                >
                                    Item #
                                </th>
                                <th scope="col" class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">
                                    Location
                                </th>
                                <th scope="col" class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">
                                    Equipment Description
                                </th>
                                <th scope="col" class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Brand</th>
                                <th scope="col" class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Model</th>
                                <th scope="col" class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">
                                    Serial #
                                </th>
                                <th scope="col" class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">TAG #</th>
                                <th scope="col" class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">
                                    PM Date Done
                                </th>
                                <th scope="col" class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">
                                    Calibration
                                </th>
                                <th scope="col" class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Status</th>

                                <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-white">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <tr v-if="equipments.data.length === 0">
                                <td colspan="11" class="border-b border-gray-200 py-12 text-center text-sm text-gray-500">
                                    <svg
                                        class="mx-auto h-12 w-12 text-gray-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            vector-effect="non-scaling-stroke"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"
                                        />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No equipment found</h3>
                                </td>
                            </tr>
                            <tr
                                v-for="(item, index) in equipments.data"
                                :key="item.id"
                                class="border-b border-gray-200 transition-colors hover:bg-gray-50"
                            >
                                <td class="whitespace-nowrap border-r border-gray-200 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                    {{ (equipments.current_page - 1) * equipments.per_page + index + 1 }}
                                </td>
                                <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-500">{{ item.location }}</td>
                                <td
                                    class="cursor-pointer whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm font-medium text-orange-600 hover:text-orange-900"
                                    @click="openDocumentsModal(item)"
                                >
                                    {{ item.description }}
                                </td>
                                <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-500">{{ item.brand }}</td>
                                <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-500">{{ item.model }}</td>
                                <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-500">{{ item.serial_number }}</td>
                                <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-500">{{ item.tag_number }}</td>
                                <!-- <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ item.pm_date_done }}</td> -->
                                <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-500">
                                    {{ formatDate(item.pm_date_done) }}
                                </td>
                                <td
                                    class="cursor-pointer whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm font-medium"
                                    :class="
                                        item.calibration === 'Calibrated' ? 'text-green-600 hover:text-green-900' : 'text-red-500 hover:text-red-900'
                                    "
                                    @click="openCalibrationModal(item)"
                                >
                                    {{ item.calibration || 'Uncalibrated' }}
                                </td>
                                <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm">
                                    <span
                                        v-if="item.status === 'Functional'"
                                        class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20"
                                        >{{ item.status }}</span
                                    >
                                    <span
                                        v-else-if="item.status === 'Defective'"
                                        class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10"
                                        >{{ item.status }}</span
                                    >
                                    <span
                                        v-else
                                        class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20"
                                        >{{ item.status }}</span
                                    >
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-center text-sm font-medium">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            type="button"
                                            @click="openEditModal(item)"
                                            class="rounded bg-green-50 px-2 py-2 text-green-600 hover:bg-green-100 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                                            title="Edit"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                                ></path>
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="openDeleteModal(item)"
                                            class="rounded bg-red-50 px-2 py-2 text-red-600 hover:bg-red-100 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                                            title="Delete"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                ></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination Controls -->
                    <div
                        v-if="equipments.links.length > 3"
                        class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
                    >
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ equipments.from }}</span>
                                    to
                                    <span class="font-medium">{{ equipments.to }}</span>
                                    of
                                    <span class="font-medium">{{ equipments.total }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                    <template v-for="(link, index) in equipments.links" :key="index">
                                        <div
                                            v-if="link.url === null"
                                            class="relative inline-flex cursor-not-allowed items-center bg-gray-50 px-4 py-2 text-sm font-medium text-gray-500 opacity-50 ring-1 ring-inset ring-gray-300"
                                            :class="{
                                                'rounded-l-md': index === 0,
                                                'rounded-r-md': index === equipments.links.length - 1,
                                            }"
                                            v-html="link.label"
                                        />
                                        <Link
                                            v-else
                                            :href="link.url"
                                            preserve-scroll
                                            class="relative inline-flex items-center px-4 py-2 text-sm font-medium focus:z-20 focus:outline-offset-0"
                                            :class="[
                                                link.active
                                                    ? 'z-10 bg-orange-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600'
                                                    : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-orange-600',
                                                {
                                                    'rounded-l-md': index === 0,
                                                    'rounded-r-md': index === equipments.links.length - 1,
                                                },
                                            ]"
                                            v-html="link.label"
                                        />
                                    </template>
                                </nav>
                            </div>
                        </div>

                        <!-- Mobile pagination -->
                        <div class="flex flex-1 justify-between sm:hidden">
                            <Link
                                v-if="equipments.prev_page_url"
                                :href="equipments.prev_page_url"
                                preserve-scroll
                                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Previous
                            </Link>
                            <span
                                v-else
                                class="relative inline-flex cursor-not-allowed items-center rounded-md border border-gray-300 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-400"
                            >
                                Previous
                            </span>

                            <Link
                                v-if="equipments.next_page_url"
                                :href="equipments.next_page_url"
                                preserve-scroll
                                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Next
                            </Link>
                            <span
                                v-else
                                class="relative ml-3 inline-flex cursor-not-allowed items-center rounded-md border border-gray-300 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-400"
                            >
                                Next
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Add Equipment Modal -->
            <div v-show="addModalVisible" class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm">
                <div class="modal-content mx-4 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white shadow-xl">
                    <div class="sticky top-0 flex items-center justify-between border-b bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
                        <h2 class="text-xl font-bold text-white">Add New Equipment</h2>
                        <button @click="closeAddModal" class="rounded-lg p-2 text-white transition-colors hover:bg-orange-700">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitAddModal" class="space-y-4 p-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Location</label>
                                <input
                                    type="text"
                                    v-model="addItemForm.location"
                                    placeholder="Enter location"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Equipment Description</label>
                                <input
                                    type="text"
                                    v-model="addItemForm.description"
                                    required
                                    placeholder="Equipment name"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Brand</label>
                                <input
                                    type="text"
                                    v-model="addItemForm.brand"
                                    placeholder="Brand name"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Model</label>
                                <input
                                    type="text"
                                    v-model="addItemForm.model"
                                    placeholder="Model number"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Serial #</label>
                                <input
                                    type="text"
                                    v-model="addItemForm.serial_number"
                                    placeholder="Serial number"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">TAG #</label>
                                <input
                                    type="text"
                                    v-model="addItemForm.tag_number"
                                    placeholder="TAG number"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">PM Date Done</label>
                                <input
                                    type="date"
                                    v-model="addItemForm.pm_date_done"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                                <select
                                    v-model="addItemForm.status"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                >
                                    <option value="Functional">Functional</option>
                                    <option value="Defective">Defective</option>
                                    <option value="Unserviceable">Unserviceable</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex gap-3 border-t pt-4">
                            <button
                                :disabled="addItemForm.processing"
                                type="submit"
                                class="flex-1 rounded-lg bg-gradient-to-r from-orange-600 to-amber-600 px-4 py-2 font-semibold text-white transition-all duration-200 hover:from-orange-700 hover:to-amber-700"
                            >
                                Add Equipment
                            </button>
                            <button
                                type="button"
                                @click="closeAddModal"
                                class="flex-1 rounded-lg bg-gray-300 px-4 py-2 font-semibold text-gray-800 transition-colors hover:bg-gray-400"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Equipment Modal -->
            <div v-show="editModalVisible" class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm">
                <div class="modal-content mx-4 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white shadow-xl">
                    <div class="sticky top-0 flex items-center justify-between border-b bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
                        <h2 class="text-xl font-bold text-white">Edit Equipment</h2>
                        <button @click="closeEditModal" class="rounded-lg p-2 text-white transition-colors hover:bg-orange-700">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitEditModal" class="space-y-4 p-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Item #</label>
                                <input
                                    type="text"
                                    :value="editItemForm.id"
                                    placeholder="Item number"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                    disabled
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Location</label>
                                <input
                                    type="text"
                                    v-model="editItemForm.location"
                                    placeholder="Enter location"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Equipment Description</label>
                                <input
                                    type="text"
                                    v-model="editItemForm.description"
                                    required
                                    placeholder="Equipment name"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Brand</label>
                                <input
                                    type="text"
                                    v-model="editItemForm.brand"
                                    placeholder="Brand name"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Model</label>
                                <input
                                    type="text"
                                    v-model="editItemForm.model"
                                    placeholder="Model number"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Serial #</label>
                                <input
                                    type="text"
                                    v-model="editItemForm.serial_number"
                                    placeholder="Serial number"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">TAG #</label>
                                <input
                                    type="text"
                                    v-model="editItemForm.tag_number"
                                    placeholder="TAG number"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">PM Date Done</label>
                                <input
                                    type="date"
                                    v-model="editItemForm.pm_date_done"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                                <select
                                    v-model="editItemForm.status"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                >
                                    <option value="Functional">Functional</option>
                                    <option value="Defective">Defective</option>
                                    <option value="Unserviceable">Unserviceable</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex gap-3 border-t pt-4">
                            <button
                                :disabled="editItemForm.processing"
                                type="submit"
                                class="flex-1 rounded-lg bg-gradient-to-r from-orange-600 to-amber-600 px-4 py-2 font-semibold text-white transition-all duration-200 hover:from-orange-700 hover:to-amber-700"
                            >
                                Save Changes
                            </button>
                            <button
                                type="button"
                                @click="closeEditModal"
                                class="flex-1 rounded-lg bg-gray-300 px-4 py-2 font-semibold text-gray-800 transition-colors hover:bg-gray-400"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Documents Modal -->
            <div v-show="documentsModalVisible" class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm">
                <div class="modal-content mx-4 flex h-[90vh] w-full max-w-5xl flex-col rounded-lg bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
                        <div>
                            <h2 class="text-xl font-bold text-white">Documents</h2>
                            <p v-if="selectedEquipmentForDocs" class="text-sm text-orange-100">{{ selectedEquipmentForDocs.description }}</p>
                        </div>
                        <button @click="closeDocumentsModal" class="rounded-lg p-2 text-white transition-colors hover:bg-orange-700">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="flex flex-1 overflow-hidden">
                        <!-- Left: File list -->
                        <div class="flex w-72 flex-shrink-0 flex-col border-r bg-gray-50">
                            <div class="border-b p-4">
                                <label
                                    class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-orange-600 to-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:from-orange-700 hover:to-amber-700"
                                >
                                    <svg v-if="!uploading" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                                        />
                                    </svg>
                                    <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                    <span>{{ uploading ? 'Uploading...' : 'Upload PDFs' }}</span>
                                    <input
                                        type="file"
                                        accept="application/pdf"
                                        multiple
                                        class="hidden"
                                        @change="uploadDocuments"
                                        :disabled="uploading"
                                    />
                                </label>
                            </div>

                            <div class="flex-1 overflow-y-auto p-2">
                                <p v-if="documents.length === 0" class="px-2 py-4 text-center text-sm text-gray-400">No files uploaded yet.</p>
                                <div
                                    v-for="doc in documents"
                                    :key="doc.id"
                                    class="group mb-1 flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 transition-colors"
                                    :class="previewDocument?.id === doc.id ? 'bg-orange-100 text-orange-800' : 'hover:bg-gray-100'"
                                    @click="previewDocument = doc"
                                >
                                    <svg class="h-4 w-4 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <span class="flex-1 truncate text-sm">{{ doc.file_name }}</span>
                                    <div class="flex gap-1 opacity-0 group-hover:opacity-100">
                                        <a
                                            :href="`/equipment/documents/${doc.id}/download`"
                                            @click.stop
                                            class="rounded p-1 text-gray-500 hover:bg-blue-100 hover:text-blue-600"
                                            title="Download"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                                />
                                            </svg>
                                        </a>
                                        <button
                                            @click.stop="deleteDocument(doc)"
                                            class="rounded p-1 text-gray-500 hover:bg-red-100 hover:text-red-600"
                                            title="Delete"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: PDF Preview -->
                        <div class="flex flex-1 items-center justify-center bg-gray-100 p-4">
                            <div v-if="!previewDocument" class="text-center text-gray-400">
                                <svg class="mx-auto mb-3 h-16 w-16 opacity-30" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <p class="text-sm">Select a file to preview</p>
                            </div>
                            <iframe
                                v-else
                                :key="previewDocument.id"
                                :src="`/equipment/documents/${previewDocument.id}/preview`"
                                class="h-full w-full rounded-lg border bg-white shadow"
                                type="application/pdf"
                            ></iframe>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calibration Modal -->
            <div v-show="calibrationModalVisible" class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm">
                <div class="modal-content mx-4 flex h-[90vh] w-full max-w-5xl flex-col rounded-lg bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
                        <div>
                            <h2 class="text-xl font-bold text-white">Calibration Documents</h2>
                            <p v-if="selectedEquipmentForCal" class="text-sm text-orange-100">{{ selectedEquipmentForCal.description }}</p>
                        </div>
                        <button @click="closeCalibrationModal" class="rounded-lg p-2 text-white transition-colors hover:bg-orange-700">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="flex flex-1 overflow-hidden">
                        <!-- Left: File list -->
                        <div class="flex w-72 flex-shrink-0 flex-col border-r bg-gray-50">
                            <div class="border-b p-4">
                                <label
                                    class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-orange-600 to-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:from-orange-700 hover:to-amber-700"
                                >
                                    <svg v-if="!uploadingCalibration" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                                        />
                                    </svg>
                                    <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                    <span>{{ uploadingCalibration ? 'Uploading...' : 'Upload PDFs' }}</span>
                                    <input
                                        type="file"
                                        accept="application/pdf"
                                        multiple
                                        class="hidden"
                                        @change="uploadCalibrationFiles"
                                        :disabled="uploadingCalibration"
                                    />
                                </label>
                            </div>

                            <div class="flex-1 overflow-y-auto p-2">
                                <p v-if="calibrationFiles.length === 0" class="px-2 py-4 text-center text-sm text-gray-400">
                                    No calibration files uploaded yet.
                                </p>
                                <div
                                    v-for="file in calibrationFiles"
                                    :key="file.id"
                                    class="group mb-1 flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 transition-colors"
                                    :class="previewCalibrationFile?.id === file.id ? 'bg-orange-100 text-orange-800' : 'hover:bg-gray-100'"
                                    @click="previewCalibrationFile = file"
                                >
                                    <svg class="h-4 w-4 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <span class="flex-1 truncate text-sm">{{ file.file_name }}</span>
                                    <div class="flex gap-1 opacity-0 group-hover:opacity-100">
                                        <a
                                            :href="`/equipment/calibrations/${file.id}/download`"
                                            @click.stop
                                            class="rounded p-1 text-gray-500 hover:bg-blue-100 hover:text-blue-600"
                                            title="Download"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                                />
                                            </svg>
                                        </a>
                                        <button
                                            @click.stop="deleteCalibrationFile(file)"
                                            class="rounded p-1 text-gray-500 hover:bg-red-100 hover:text-red-600"
                                            title="Delete"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: PDF Preview -->
                        <div class="flex flex-1 items-center justify-center bg-gray-100 p-4">
                            <div v-if="!previewCalibrationFile" class="text-center text-gray-400">
                                <svg class="mx-auto mb-3 h-16 w-16 opacity-30" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <p class="text-sm">Select a file to preview</p>
                            </div>
                            <iframe
                                v-else
                                :key="previewCalibrationFile.id"
                                :src="`/equipment/calibrations/${previewCalibrationFile.id}/preview`"
                                class="h-full w-full rounded-lg border bg-white shadow"
                                type="application/pdf"
                            ></iframe>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div v-show="deleteModalVisible" class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm">
                <div class="modal-content mx-4 w-full max-w-md rounded-lg bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
                        <h2 class="text-xl font-bold text-white">Delete Equipment</h2>
                        <button @click="closeDeleteModal" class="rounded-lg p-2 text-white transition-colors hover:bg-red-700">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="p-6">
                        <p class="mb-2 text-gray-700">Are you sure you want to delete this item?</p>
                        <p class="text-sm text-gray-600">
                            <strong v-if="equipmentToDelete">{{ equipmentToDelete.description }}</strong>
                        </p>
                        <p class="mt-4 text-xs text-red-600">This action cannot be undone.</p>
                    </div>
                    <div class="flex gap-3 rounded-b-lg border-t bg-gray-50 px-6 py-4">
                        <button
                            type="button"
                            @click="confirmDelete"
                            :disabled="deleteForm.processing"
                            class="flex-1 rounded-lg bg-gradient-to-r from-red-600 to-red-700 px-4 py-2 font-semibold text-white transition-all duration-200 hover:from-red-700 hover:to-red-800"
                        >
                            Delete
                        </button>
                        <button
                            type="button"
                            @click="closeDeleteModal"
                            class="flex-1 rounded-lg bg-gray-300 px-4 py-2 font-semibold text-gray-800 transition-colors hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="fixed bottom-6 right-6 z-40 flex flex-col items-end gap-3">
            <div v-if="exportPanelVisible" class="w-[22rem] rounded-2xl border border-orange-200 bg-white p-5 shadow-2xl shadow-orange-200/50">
                <div class="mb-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-700">Export Table</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Download inventory report</h3>
                    <p class="mt-1 text-sm text-slate-600">Choose a month range and file format for the current inventory table.</p>
                </div>

                <div class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="exportFrom" class="mb-2 block text-sm font-medium text-slate-700">From</label>
                            <input
                                id="exportFrom"
                                v-model="exportFrom"
                                type="month"
                                class="w-full rounded-xl border border-orange-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                            />
                        </div>
                        <div>
                            <label for="exportTo" class="mb-2 block text-sm font-medium text-slate-700">To</label>
                            <input
                                id="exportTo"
                                v-model="exportTo"
                                type="month"
                                class="w-full rounded-xl border border-orange-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="exportFormat" class="mb-2 block text-sm font-medium text-slate-700">Format</label>
                        <select
                            id="exportFormat"
                            v-model="exportFormat"
                            class="w-full rounded-xl border border-orange-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                        >
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="word">Word</option>
                        </select>
                    </div>

                    <div class="rounded-xl border border-orange-100 bg-orange-50 p-3 text-sm text-orange-800">
                        <strong>Range:</strong> {{ exportRangeLabel }}
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="downloadInventory"
                            class="flex-1 rounded-xl bg-gradient-to-r from-orange-600 to-amber-600 px-4 py-3 text-sm font-semibold text-white transition hover:from-orange-700 hover:to-amber-700"
                        >
                            Download
                        </button>
                        <button
                            type="button"
                            @click="exportPanelVisible = false"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <button
                type="button"
                @click="toggleExportPanel"
                class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-r from-orange-600 to-amber-600 text-white shadow-2xl shadow-orange-300 transition hover:scale-105 hover:from-orange-700 hover:to-amber-700"
                title="Download inventory report"
            >
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l4-4m-4 4l-4-4m-5 7h18" />
                </svg>
            </button>
        </div>
    </AppLayout>

    <!-- Toast Notification (teleported to body to avoid parent opacity/blur issues) -->
    <Teleport to="body">
        <div
            v-if="toastVisible"
            style="
                position: fixed;
                right: 24px;
                top: 24px;
                z-index: 999999;
                display: flex;
                align-items: center;
                gap: 12px;
                border-radius: 8px;
                padding: 12px 20px;
                background-color: #16a34a;
                color: white;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            "
        >
            <svg style="height: 20px; width: 20px; flex-shrink: 0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span style="font-size: 14px; font-weight: 500">{{ toastMessage }}</span>
            <button
                @click="toastVisible = false"
                style="margin-left: 8px; border-radius: 4px; padding: 4px; background: transparent; border: none; cursor: pointer; color: white"
            >
                <svg style="height: 16px; width: 16px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </Teleport>
</template>
