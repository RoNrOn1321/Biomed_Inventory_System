<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

interface Equipment {
    id: number;
    location: string | null;
    description: string;
    brand: string | null;
    model: string | null;
    serial_number: string | null;
    tag_number: string | null;
    pm_date_done: string | null;
    status: string | null;
    updated_at: string | null;
}

interface EquipmentDocument {
    id: number;
    equipment_id: number;
    file_name: string;
    file_path: string;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    from: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
    to: number;
    total: number;
}

const props = defineProps<{
    equipments: PaginatedData<Equipment>;
    filters: { search?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pre Inspection', href: '/pre-inspection' }];

const page = usePage<SharedData>();
const isAdmin = computed(() => page.props.auth.user.account_type === 'Admin');
const isModerator = computed(() => page.props.auth.user.account_type === 'Moderator');
const isTechnician = computed(() => page.props.auth.user.account_type === 'Biomed_Technician');
const canAct = computed(() => isAdmin.value || isModerator.value || isTechnician.value);

// Error modal
const errorModalVisible = ref(false);
const errorMessage = ref('');
const showErrorModal = (msg: string) => {
    errorMessage.value = msg;
    errorModalVisible.value = true;
};

// Generic confirm modal
const confirmModalVisible = ref(false);
const confirmModalTitle = ref('');
const confirmModalMessage = ref('');
const confirmModalAction = ref<(() => void) | null>(null);
const confirmModalDanger = ref(false);

const openConfirmModal = (title: string, message: string, action: () => void, danger = false) => {
    confirmModalTitle.value = title;
    confirmModalMessage.value = message;
    confirmModalAction.value = action;
    confirmModalDanger.value = danger;
    confirmModalVisible.value = true;
};
const runConfirmAction = () => {
    confirmModalVisible.value = false;
    confirmModalAction.value?.();
};

// Toast
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

// Search
const search = ref(props.filters.search || '');
let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/pre-inspection', { search: search.value || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
});

// Restore to Functional
const restoreEquipment = (item: Equipment) => {
    openConfirmModal('Restore Equipment', `Restore "${item.description}" to Functional?`, () =>
        router.put(
            `/pre-inspection/${item.id}/restore`,
            {},
            { preserveScroll: true, onSuccess: () => showToast(`${item.description} restored to Functional.`) },
        ),
    );
};

// Condemn
const condemnEquipment = (item: Equipment) => {
    openConfirmModal(
        'Condemn Equipment',
        `Mark "${item.description}" as Condemned? This cannot be undone.`,
        () =>
            router.put(
                `/pre-inspection/${item.id}/condemn`,
                {},
                { preserveScroll: true, onSuccess: () => showToast(`${item.description} has been condemned.`) },
            ),
        true,
    );
};

const formatDate = (dateStr: string | null): string => {
    if (!dateStr) return '—';
    const date = new Date(dateStr + (dateStr.includes('T') ? '' : 'T00:00:00'));
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

// Documents modal
const documentsModalVisible = ref(false);
const selectedEquipment = ref<Equipment | null>(null);
const documents = ref<EquipmentDocument[]>([]);
const previewDocument = ref<EquipmentDocument | null>(null);
const uploading = ref(false);

const openDocumentsModal = async (item: Equipment) => {
    selectedEquipment.value = item;
    previewDocument.value = null;
    documents.value = [];
    documentsModalVisible.value = true;
    await fetchDocuments(item.id);
};

const closeDocumentsModal = () => {
    documentsModalVisible.value = false;
    previewDocument.value = null;
    selectedEquipment.value = null;
};

const fetchDocuments = async (equipmentId: number) => {
    const res = await axios.get(`/equipment/${equipmentId}/documents`);
    documents.value = res.data;
};

const uploadDocuments = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (!input.files || !input.files.length || !selectedEquipment.value) return;
    uploading.value = true;
    const formData = new FormData();
    for (const file of input.files) {
        formData.append('files[]', file);
    }
    try {
        await axios.post(`/equipment/${selectedEquipment.value.id}/documents`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        showToast('Documents uploaded successfully!');
    } catch (e: any) {
        showErrorModal(`Upload failed. Server responded with status ${e.response?.status ?? 'unknown'}.`);
    } finally {
        uploading.value = false;
        input.value = '';
    }
    if (selectedEquipment.value) await fetchDocuments(selectedEquipment.value.id);
};

const deleteDocument = async (doc: EquipmentDocument) => {
    openConfirmModal(
        'Delete Document',
        `Delete "${doc.file_name}"?`,
        async () => {
            await axios.delete(`/equipment/documents/${doc.id}`);
            showToast('Document deleted.');
            if (previewDocument.value?.id === doc.id) previewDocument.value = null;
            if (selectedEquipment.value) await fetchDocuments(selectedEquipment.value.id);
        },
        true,
    );
};
</script>

<template>
    <Head title="Pre Inspection" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50/50 p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <section class="mb-8 overflow-hidden rounded-2xl bg-orange-50 shadow-sm ring-1 ring-orange-200">
                <div class="flex items-center gap-6 p-6 sm:p-8">
                    <div
                        class="relative flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-white p-2 shadow-inner ring-1 ring-orange-200"
                    >
                        <img src="/logo.JPG" alt="" class="h-full w-full object-contain" onerror="this.style.display='none'" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Pre Inspection Queue</h1>
                        <p class="mt-2 text-sm font-medium uppercase tracking-wide text-orange-600">Final assessment before disposition</p>
                    </div>
                </div>
            </section>

            <!-- Info banner -->
            <div class="mb-6 rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-800">
                <p>
                    Equipment listed here has been flagged as <strong>Defective</strong> and requires a final inspection. You can restore equipment to
                    <strong class="text-green-700">Functional</strong> or mark it as <strong class="text-red-700">Condemned</strong> for disposal.
                </p>
            </div>

            <!-- Search bar -->
            <div class="mb-4 flex items-center justify-between rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-900/5">
                <div
                    class="flex items-center rounded-lg bg-white px-4 py-2 shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-orange-600 sm:max-w-xs"
                >
                    <svg class="size-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        v-model="search"
                        placeholder="Search equipment..."
                        class="ml-3 w-48 border-0 bg-transparent py-1 text-sm text-gray-900 placeholder-gray-400 outline-none"
                    />
                </div>
                <p class="text-sm text-gray-500">{{ equipments.total }} equipment in queue</p>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
                <table class="min-w-full border-collapse border border-gray-300">
                    <thead class="bg-orange-500">
                        <tr>
                            <th class="border-r border-orange-400 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-white sm:pl-6">Item #</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Location</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Equipment Description</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Brand</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Model</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Serial #</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">TAG #</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Sent On</th>
                            <th class="border-r border-orange-400 px-3 py-3.5 text-left text-sm font-semibold text-white">Status</th>
                            <th class="px-3 py-3.5 text-center text-sm font-semibold text-white">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <tr v-if="equipments.data.length === 0">
                            <td colspan="10" class="border-b border-gray-200 py-12 text-center text-sm text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        vector-effect="non-scaling-stroke"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900">No equipment in Pre Inspection queue</h3>
                            </td>
                        </tr>
                        <tr
                            v-for="(item, index) in equipments.data"
                            :key="item.id"
                            class="border-b border-gray-200 transition-colors hover:bg-gray-50"
                        >
                            <td class="whitespace-nowrap border-r border-gray-200 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                {{ equipments.from + index }}
                            </td>
                            <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-700">{{ item.location || '—' }}</td>
                            <td class="border-r border-gray-200 px-3 py-4 text-sm font-medium text-gray-900">{{ item.description }}</td>
                            <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-700">{{ item.brand || '—' }}</td>
                            <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-700">{{ item.model || '—' }}</td>
                            <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-700">
                                {{ item.serial_number || '—' }}
                            </td>
                            <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-700">{{ item.tag_number || '—' }}</td>
                            <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm text-gray-700">
                                {{ formatDate(item.updated_at) }}
                            </td>
                            <td class="whitespace-nowrap border-r border-gray-200 px-3 py-4 text-sm">
                                <span
                                    class="inline-flex items-center rounded-md bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-600/10"
                                >
                                    Pre Inspection
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm font-medium">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Documents -->
                                    <button
                                        type="button"
                                        @click="openDocumentsModal(item)"
                                        class="rounded bg-blue-50 px-2 py-2 text-blue-600 hover:bg-blue-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        title="View / Upload Documents"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                                            />
                                        </svg>
                                    </button>

                                    <!-- Restore to Functional -->
                                    <button
                                        v-if="canAct"
                                        type="button"
                                        @click="restoreEquipment(item)"
                                        class="rounded bg-green-50 px-2 py-2 text-green-600 hover:bg-green-100 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                                        title="Restore to Functional"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </button>

                                    <!-- Condemn -->
                                    <button
                                        v-if="canAct"
                                        type="button"
                                        @click="condemnEquipment(item)"
                                        class="rounded bg-red-50 px-2 py-2 text-red-600 hover:bg-red-100 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                                        title="Condemn (Dispose)"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="equipments.links.length > 3" class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium">{{ equipments.from }}</span> to <span class="font-medium">{{ equipments.to }}</span> of
                            <span class="font-medium">{{ equipments.total }}</span> results
                        </p>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <template v-for="(link, idx) in equipments.links" :key="idx">
                                <div
                                    v-if="link.url === null"
                                    class="relative inline-flex cursor-not-allowed items-center bg-gray-50 px-4 py-2 text-sm font-medium text-gray-500 opacity-50 ring-1 ring-inset ring-gray-300"
                                    :class="{ 'rounded-l-md': idx === 0, 'rounded-r-md': idx === equipments.links.length - 1 }"
                                    v-html="link.label"
                                />
                                <Link
                                    v-else
                                    :href="link.url"
                                    preserve-scroll
                                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium ring-1 ring-inset ring-gray-300 focus:z-20 focus:outline-offset-0"
                                    :class="{
                                        'rounded-l-md': idx === 0,
                                        'rounded-r-md': idx === equipments.links.length - 1,
                                        'z-10 bg-orange-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600':
                                            link.active,
                                        'text-gray-900 hover:bg-gray-50 focus-visible:outline-0': !link.active,
                                    }"
                                    v-html="link.label"
                                />
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <transition name="toast">
            <div
                v-if="toastVisible"
                class="fixed right-6 top-6 z-50 flex items-center gap-3 rounded-xl bg-gray-900 px-5 py-3 text-sm font-medium text-white shadow-lg"
            >
                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ toastMessage }}
            </div>
        </transition>

        <!-- Documents Modal -->
        <div v-if="documentsModalVisible" class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="modal-content flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Pre Inspection Documents</h2>
                        <p v-if="selectedEquipment" class="text-sm text-gray-500">{{ selectedEquipment.description }}</p>
                    </div>
                    <button @click="closeDocumentsModal" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex flex-1 overflow-hidden">
                    <!-- File list -->
                    <div class="flex w-64 flex-col border-r border-gray-200 bg-gray-50">
                        <div class="flex-1 overflow-y-auto p-4">
                            <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Documents</h3>
                            <div v-if="documents.length === 0" class="py-8 text-center text-sm text-gray-400">No documents yet</div>
                            <div
                                v-for="doc in documents"
                                :key="doc.id"
                                @click="previewDocument = doc"
                                class="group mb-2 flex w-full cursor-pointer items-center gap-2 rounded-lg p-2 text-left hover:bg-white"
                                :class="previewDocument?.id === doc.id ? 'bg-white shadow-sm ring-1 ring-orange-200' : ''"
                            >
                                <svg class="h-4 w-4 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z" />
                                </svg>
                                <span class="flex-1 truncate text-xs text-gray-700">{{ doc.file_name }}</span>
                                <button
                                    @click.stop="deleteDocument(doc)"
                                    class="hidden shrink-0 rounded p-0.5 text-gray-400 hover:bg-red-50 hover:text-red-500 group-hover:block"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Upload -->
                        <div class="border-t border-gray-200 p-4">
                            <label
                                class="flex cursor-pointer flex-col items-center gap-2 rounded-lg border-2 border-dashed border-gray-300 p-3 text-center text-xs text-gray-500 hover:border-orange-400 hover:text-orange-600"
                            >
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>{{ uploading ? 'Uploading…' : 'Upload PDFs' }}</span>
                                <input type="file" accept=".pdf" multiple class="hidden" :disabled="uploading" @change="uploadDocuments" />
                            </label>
                        </div>
                    </div>

                    <!-- PDF preview -->
                    <div class="flex flex-1 items-center justify-center bg-gray-100 p-4">
                        <div v-if="!previewDocument" class="text-center text-sm text-gray-400">
                            <svg class="mx-auto mb-2 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            Select a document to preview
                        </div>
                        <iframe
                            v-else
                            :src="`/storage/${previewDocument.file_path}`"
                            class="h-full w-full rounded border border-gray-200"
                            style="min-height: 500px"
                        />
                    </div>
                </div>
            </div>
        </div>
        <!-- Confirm Modal -->
        <Teleport to="body">
            <div v-if="confirmModalVisible" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/40" @click="confirmModalVisible = false" />
                <div class="relative z-10 mx-4 w-full max-w-md rounded-2xl bg-white shadow-xl ring-1 ring-gray-200">
                    <div
                        class="flex items-center justify-between rounded-t-2xl px-6 py-4"
                        :class="confirmModalDanger ? 'bg-gradient-to-r from-red-500 to-red-600' : 'bg-gradient-to-r from-orange-500 to-orange-600'"
                    >
                        <h3 class="text-lg font-bold text-white">{{ confirmModalTitle }}</h3>
                        <button @click="confirmModalVisible = false" class="rounded-lg p-1 text-white hover:bg-white/20">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-5">
                        <p class="text-sm text-gray-700">{{ confirmModalMessage }}</p>
                    </div>
                    <div class="flex gap-3 rounded-b-2xl border-t bg-gray-50 px-6 py-4">
                        <button
                            type="button"
                            @click="runConfirmAction"
                            class="flex-1 rounded-lg px-4 py-2 font-semibold text-white transition-all duration-200"
                            :class="
                                confirmModalDanger
                                    ? 'bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800'
                                    : 'bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700'
                            "
                        >
                            Confirm
                        </button>
                        <button
                            type="button"
                            @click="confirmModalVisible = false"
                            class="flex-1 rounded-lg bg-gray-300 px-4 py-2 font-semibold text-gray-800 hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Error Modal -->
        <Teleport to="body">
            <div v-if="errorModalVisible" class="fixed inset-0 z-50 flex items-center justify-center">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/40" @click="errorModalVisible = false" />
                <!-- Dialog -->
                <div class="relative z-10 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl ring-1 ring-gray-200">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-gray-900">Upload Error</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ errorMessage }}</p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button
                            @click="errorModalVisible = false"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                        >
                            OK
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style>
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
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
.modal-content {
    animation: slideScale 0.3s ease-out;
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
</style>
