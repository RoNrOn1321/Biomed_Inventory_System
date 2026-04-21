<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { ClipboardList, History, Search } from 'lucide-vue-next';
import { computed, ref, nextTick } from 'vue';

interface JobRequestHistoryItem {
    id: number;
    control_no: string | null;
    equipment_name: string;
    status: 'Pending' | 'Accepted' | 'Done';
    priority: 'Low' | 'Medium' | 'High' | 'Urgent';
    requested_at: string;
    accepted_by: string | null;
    requester_name?: string;
    department?: string | null;
    issue_summary?: string;
    biomedicalServiceDoc?: any;
    request_type?: string[] | null;
    repair_type?: string | null;
    request_complaints?: string | null;
    job_report?: string | null;
    location?: string | null;
    date?: string | null;
    equipment_id?: number | null;
    repair_outcome?: 'Repaired' | 'Unserviceable' | null;
}

const props = defineProps<{
    jobRequests: JobRequestHistoryItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Request History',
        href: '/request-history',
    },
];

const search = ref('');

// Dialog and form state
const isViewDialogOpen = ref(false);
const selectedViewRequest = ref<JobRequestHistoryItem | null>(null);

const filteredRequests = computed(() => {
    const query = search.value.trim().toLowerCase();
    if (!query) {
        return props.jobRequests;
    }

    return props.jobRequests.filter((req) => {
        return [req.control_no || '', req.equipment_name || '', req.status, req.priority].some((value) => value.toLowerCase().includes(query));
    });
});

const openViewDialog = (jobRequest: JobRequestHistoryItem) => {
    selectedViewRequest.value = jobRequest;
    isViewDialogOpen.value = true;
};

const closeViewDialog = () => {
    isViewDialogOpen.value = false;
    selectedViewRequest.value = null;
};

const formatDateTime = (value: string | null) => {
    if (!value) {
        return 'Not set';
    }

    return new Date(value).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

const priorityClass = (priority: string) => {
    if (priority === 'Urgent') {
        return 'border-red-200 bg-red-100 text-red-700';
    }

    if (priority === 'High') {
        return 'border-amber-200 bg-amber-100 text-amber-700';
    }

    if (priority === 'Medium') {
        return 'border-blue-200 bg-blue-100 text-blue-700';
    }

    return 'border-emerald-200 bg-emerald-100 text-emerald-700';
};

const statusClass = (status: string) => {
    if (status === 'Done') return 'border-emerald-200 bg-emerald-100 text-emerald-700';
    if (status === 'Accepted') return 'border-blue-200 bg-blue-100 text-blue-700';
    return 'border-orange-200 bg-orange-100 text-orange-700';
};

// Job history modal (namespaced for this page to avoid collisions)
const jrJobHistoryModalOpen = ref(false);
const jrJobHistoryEquipment = ref(null);
const jrJobHistoryItems = ref([]);
const jrJobHistoryLoading = ref(false);
const jrSelectedHistoryId = ref<number | null>(null);

const jrSelectedHistoryItem = computed(() => jrJobHistoryItems.value.find((h) => h.id === jrSelectedHistoryId.value) ?? null);

const formatHistoryDate = (val: string | null) => {
    if (!val) return 'N/A';
    return new Date(val).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

const openJrJobHistoryModal = async (payload: any | null) => {
    if (!payload) return;

    const equipmentId = payload.equipment_id ?? payload.id ?? null;

    jrJobHistoryEquipment.value = { id: equipmentId, description: payload.equipment_name ?? payload.description, status: payload.status };
    jrJobHistoryModalOpen.value = true;
    jrSelectedHistoryId.value = null;
    await nextTick();
    console.log('jrJobHistoryModalOpen set; DOM updated');

    if (payload.equipment_id) {
        jrJobHistoryLoading.value = true;
        try {
            const res = await axios.get(`/api/equipment/${payload.equipment_id}/job-history`);
            jrJobHistoryItems.value = res.data;
            if (jrJobHistoryItems.value.length > 0) jrSelectedHistoryId.value = jrJobHistoryItems.value[0].id;
        } catch (e) {
            jrJobHistoryItems.value = [];
        } finally {
            jrJobHistoryLoading.value = false;
        }
        return;
    }

    jrJobHistoryItems.value = [
        {
            id: payload.id,
            equipment_name: payload.equipment_name ?? null,
            requester_name: payload.requester_name ?? null,
            department: payload.department ?? null,
            issue_summary: payload.issue_summary ?? null,
            priority: payload.priority ?? null,
            status: payload.status ?? null,
            repair_category: payload.repair_category ?? null,
            repair_outcome: payload.repair_outcome ?? null,
            admin_approval: payload.admin_approval ?? null,
            assigned_to_name: payload.assigned_to_name ?? null,
            accepted_by: payload.accepted_by ?? null,
            requested_at: payload.requested_at ?? null,
            completed_at: payload.completed_at ?? null,
            remarks: payload.remarks ?? null,
            pre_inspection_documents: payload.pre_inspection_documents ?? [],
        },
    ];
    jrSelectedHistoryId.value = payload.id;
};

const jrDownloadHistoryPdf = () => {
    if (!jrSelectedHistoryItem.value) return;
    const id = jrSelectedHistoryItem.value.id;
    console.log('jrDownloadHistoryPdf called for', id);
    window.open(`/JobRequests/${id}/export`, '_blank');
};
</script>

<template>
    <Head title="Request History" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white pb-12">
            <section class="border-b-4 border-orange-400 bg-gradient-to-r from-orange-50 to-orange-100 px-4 py-5 shadow-md">
                <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Request History</h1>
                            <p class="text-sm font-medium text-orange-700">View and track all your previously submitted job requests.</p>
                        </div>
                        <History class="h-8 w-8 text-orange-600 opacity-80" />
                    </div>
                </div>
            </section>

            <div class="mx-auto mt-8 max-w-5xl px-4 sm:px-6 lg:px-8">
                <!-- Header/Filter Area -->
                <div class="mb-6 rounded-2xl border border-orange-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Total Requests: {{ filteredRequests.length }}</h2>
                            <p class="text-sm text-slate-600">Search by control no, equipment, or status.</p>
                        </div>

                        <div class="w-full md:w-80">
                            <label for="search" class="sr-only">Search</label>
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-orange-500" />
                                <input
                                    id="search"
                                    v-model="search"
                                    type="text"
                                    placeholder="Search history..."
                                    class="h-10 w-full rounded-lg border border-orange-200 bg-white pl-10 pr-4 text-sm text-slate-700 shadow-sm outline-none ring-0 placeholder:text-slate-400 focus:border-orange-400 focus:ring-2 focus:ring-orange-200"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- List Area -->
                <div class="space-y-4">
                    <article
                        v-for="req in filteredRequests"
                        :key="req.id"
                        class="rounded-2xl border border-orange-200 bg-white p-6 shadow-sm transition-all hover:border-orange-300 hover:shadow-md"
                    >
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex-1 space-y-4">
                                <div class="flex flex-wrap items-center gap-3">
                                    <div
                                        class="flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700"
                                    >
                                        <ClipboardList class="h-3.5 w-3.5" />
                                        No. {{ req.control_no || req.id }}
                                    </div>
                                    <span :class="['rounded-full border px-3 py-1 text-xs font-semibold', statusClass(req.status)]">
                                        {{ req.status }}
                                    </span>
                                    <span :class="['rounded-full border px-3 py-1 text-xs font-semibold', priorityClass(req.priority)]">
                                        {{ req.priority }}
                                    </span>
                                    <button v-if="req.repair_outcome" type="button" @click="openJrJobHistoryModal(req)" class="focus:outline-none">
                                        <span
                                            :class="[
                                                'rounded-full border px-3 py-1 text-xs font-semibold',
                                                req.repair_outcome === 'Repaired' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700',
                                            ]"
                                        >
                                            {{ req.repair_outcome }}
                                        </span>
                                    </button>
                                </div>

                                <div>
                                    <h3 class="text-xl font-bold text-slate-900">{{ req.equipment_name }}</h3>
                                    <div class="mt-2 grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                                        <div>
                                            <p class="text-slate-500">Date Requested</p>
                                            <p class="font-medium text-slate-900">{{ formatDateTime(req.requested_at) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-slate-500">Priority</p>
                                            <p class="font-medium text-slate-900">{{ req.priority }}</p>
                                        </div>
                                        <div class="col-span-2">
                                            <p class="text-slate-500">Accepted By</p>
                                            <p class="font-medium text-slate-900">
                                                {{ req.accepted_by || 'Waiting for technician' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="mt-4 flex shrink-0 sm:ml-auto sm:mt-0">
                                <Button type="button" variant="outline" class="border-slate-300 text-slate-700 hover:bg-slate-50" @click="openViewDialog(req)">
                                    View Form
                                </Button>
                            </div>
                        </div>
                    </article>

                    <!-- Empty State -->
                    <div
                        v-if="filteredRequests.length === 0"
                        class="rounded-2xl border border-dashed border-orange-200 bg-orange-50/60 px-6 py-12 text-center"
                    >
                        <History class="mx-auto h-12 w-12 text-orange-300" />
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">No requests found</h3>
                        <p class="mt-2 text-sm text-slate-600">You haven't made any requests matching your search.</p>
                        <div class="mt-6">
                            <Link href="/request-service">
                                <Button class="bg-orange-600 text-white hover:bg-orange-700"> Create New Request </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Details Dialog -->
        <Dialog :open="isViewDialogOpen" @update:open="isViewDialogOpen = $event">
            <DialogContent class="max-h-[90vh] max-w-3xl overflow-y-auto bg-white p-6 sm:rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="text-xl font-bold text-slate-900">Job Request Details</DialogTitle>
                </DialogHeader>

                <div v-if="selectedViewRequest" class="space-y-6 py-4">
                    <!-- Basic Request Information -->
                    <div>
                        <h4 class="mb-4 border-b border-orange-100 pb-2 text-lg font-semibold text-orange-700">Basic Information</h4>
                        <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                            <div>
                                <span class="block font-medium text-slate-500">Requester:</span>
                                <span class="font-medium text-slate-900">{{ selectedViewRequest.requester_name || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="block font-medium text-slate-500">Department:</span>
                                <span class="text-slate-900">{{ selectedViewRequest.department || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="block font-medium text-slate-500">Equipment Name:</span>
                                <span class="text-slate-900">{{ selectedViewRequest.equipment_name }}</span>
                            </div>
                            <div>
                                <span class="block font-medium text-slate-500">Priority:</span>
                                <span
                                    :class="priorityClass(selectedViewRequest.priority)"
                                    class="mt-1 inline-flex rounded-full border px-2 py-0.5 text-xs font-semibold"
                                >
                                    {{ selectedViewRequest.priority }}
                                </span>
                            </div>
                            <div>
                                <span class="block font-medium text-slate-500">Requested At:</span>
                                <span class="text-slate-900">{{ formatDateTime(selectedViewRequest.requested_at) }}</span>
                            </div>
                            <div>
                                <span class="block font-medium text-slate-500">Location:</span>
                                <span class="text-slate-900">{{ selectedViewRequest.location || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="block font-medium text-slate-500">Control N°:</span>
                                <span class="text-slate-900">{{ selectedViewRequest.control_no || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="block font-medium text-slate-500">Form Date:</span>
                                <span class="text-slate-900">{{ selectedViewRequest.date || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="block font-medium text-slate-500">Status:</span>
                                <span
                                    :class="statusClass(selectedViewRequest.status)"
                                    class="mt-1 inline-flex rounded-full border px-2 py-0.5 text-xs font-semibold"
                                >
                                    {{ selectedViewRequest.status }}
                                </span>
                            </div>
                            <div class="col-span-1 md:col-span-2">
                                <span class="mb-1 block font-medium text-slate-500">Issue Summary:</span>
                                <p class="rounded-lg border border-slate-100 bg-slate-50 p-3 text-slate-900">
                                    {{ selectedViewRequest.issue_summary || 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Request Detail -->
                    <div>
                        <h4 class="mb-4 border-b border-orange-100 pb-2 text-lg font-semibold text-orange-700">Request Detail</h4>
                        <div class="grid grid-cols-1 gap-4 text-sm">
                            <div v-if="selectedViewRequest.request_type && selectedViewRequest.request_type.length">
                                <span class="mb-2 block font-medium text-slate-500">Services Desired / Requested:</span>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="req in selectedViewRequest.request_type"
                                        :key="req"
                                        class="inline-flex rounded-md border border-blue-200 bg-blue-50 px-2 py-1 text-sm font-medium text-blue-700"
                                    >
                                        ✓ {{ req }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="selectedViewRequest.repair_type">
                                <span class="mb-1 block font-medium text-slate-500">Repair Type:</span>
                                <span class="inline-flex rounded-md border border-red-200 bg-red-50 px-2 py-1 text-sm font-medium text-red-700">
                                    {{ selectedViewRequest.repair_type }}
                                </span>
                            </div>
                            <div>
                                <span class="mb-1 block font-medium text-slate-500">Nature of Work Requested / Complaints:</span>
                                <p class="min-h-[3rem] rounded-lg border border-slate-100 bg-slate-50 p-3 text-slate-900">
                                    {{ selectedViewRequest.request_complaints || 'No complaints recorded.' }}
                                </p>
                            </div>
                            <div>
                                <span class="mb-1 block font-medium text-slate-500">Job Report:</span>
                                <p class="min-h-[3rem] rounded-lg border border-slate-100 bg-slate-50 p-3 text-slate-900">
                                    {{ selectedViewRequest.job_report || 'No job report recorded.' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Biomedical Service Documentation (Only if Done and available) -->
                    <div v-if="selectedViewRequest.status === 'Done' && selectedViewRequest.biomedicalServiceDoc">
                        <h4 class="mb-4 border-b border-emerald-100 pb-2 text-lg font-semibold text-emerald-700">Biomedical Service Documentation</h4>
                        <div
                            class="grid grid-cols-1 gap-x-4 gap-y-5 rounded-xl border border-emerald-100 bg-emerald-50/50 p-5 text-sm md:grid-cols-2"
                        >
                            <div>
                                <span class="mb-1 block font-medium text-slate-500">Received By:</span>
                                <span class="text-slate-900">{{ selectedViewRequest.biomedicalServiceDoc.receive_by || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="mb-1 block font-medium text-slate-500">Performed By:</span>
                                <span class="text-slate-900">{{ selectedViewRequest.biomedicalServiceDoc.performed_by || 'N/A' }}</span>
                            </div>

                            <div>
                                <span class="mb-1 block font-medium text-slate-500">Date Received:</span>
                                <span class="text-slate-900">{{ formatDateTime(selectedViewRequest.biomedicalServiceDoc.date_receive) }}</span>
                            </div>
                            <div>
                                <span class="mb-1 block font-medium text-slate-500">Date Performed:</span>
                                <span class="text-slate-900">{{ formatDateTime(selectedViewRequest.biomedicalServiceDoc.date_performed) }}</span>
                            </div>

                            <div>
                                <span class="mb-1 block font-medium text-slate-500">Estimated Days:</span>
                                <span class="text-slate-900">{{ selectedViewRequest.biomedicalServiceDoc.estimated_no_days || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="mb-1 block font-medium text-slate-500">Technician Date Received:</span>
                                <span class="text-slate-900">{{
                                    formatDateTime(selectedViewRequest.biomedicalServiceDoc.technician_date_received)
                                }}</span>
                            </div>

                            <div>
                                <span class="mb-1 block font-medium text-slate-500">Date Started:</span>
                                <span class="text-slate-900">{{ formatDateTime(selectedViewRequest.biomedicalServiceDoc.date_started) }}</span>
                            </div>
                            <div>
                                <span class="mb-1 block font-medium text-slate-500">Date Finished:</span>
                                <span class="text-slate-900">{{ formatDateTime(selectedViewRequest.biomedicalServiceDoc.date_finished) }}</span>
                            </div>

                            <div>
                                <span class="mb-1 block font-medium text-slate-500">Date Returned:</span>
                                <span class="text-slate-900">{{ formatDateTime(selectedViewRequest.biomedicalServiceDoc.date_returned) }}</span>
                            </div>
                            <div>
                                <span class="mb-1 block font-medium text-slate-500">Received By (End User):</span>
                                <span class="text-slate-900">{{ selectedViewRequest.biomedicalServiceDoc.receive_by_end_user || 'N/A' }}</span>
                            </div>

                            <div class="col-span-1 md:col-span-2">
                                <span class="mb-1 block font-medium text-slate-500">Remarks:</span>
                                <p class="rounded-lg border border-emerald-200 bg-white p-3 text-slate-900">
                                    {{ selectedViewRequest.biomedicalServiceDoc.remarks || 'No remarks provided.' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Pre-Inspection Uploaded Files -->
                    <div v-if="selectedViewRequest.pre_inspection_documents && selectedViewRequest.pre_inspection_documents.length">
                        <h4 class="mb-4 border-b border-slate-100 pb-2 text-lg font-semibold text-slate-700">Pre-Inspection Documents</h4>
                        <div class="space-y-2">
                            <div
                                v-for="doc in selectedViewRequest.pre_inspection_documents"
                                :key="doc.id"
                                class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 p-3 text-sm"
                            >
                                <div class="truncate">{{ doc.file_name }}</div>
                                <div class="flex items-center gap-2">
                                    <a :href="doc.preview_url" target="_blank" class="text-sm text-blue-600 hover:underline">Preview</a>
                                    <a :href="doc.download_url" class="text-sm text-slate-700 hover:underline">Download</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeViewDialog">Close Form</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

            <!-- Job History Modal (teleport) -->
            <Teleport to="body">
                <div
                    v-if="jrJobHistoryModalOpen"
                    class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/40 backdrop-blur-sm"
                    @click.self="jrJobHistoryModalOpen = false"
                >
                    <div class="mx-4 flex h-[85vh] w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                        <div class="flex w-72 shrink-0 flex-col border-r border-gray-100 bg-slate-50">
                                <div class="border-b border-gray-200 bg-gradient-to-r from-orange-50 to-amber-50 px-5 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-widest text-orange-600">Job History</p>
                                    <h2 class="mt-0.5 text-base font-bold leading-snug text-slate-900">{{ jrJobHistoryEquipment?.description }}</h2>
                                    <p class="mt-1 text-xs text-slate-500">{{ jrJobHistoryEquipment?.status }} · {{ jrJobHistoryItems.length }} record{{ jrJobHistoryItems.length === 1 ? '' : 's' }}</p>
                                </div>

                            <div class="flex-1 overflow-y-auto">
                                <div v-if="jrJobHistoryLoading" class="flex h-32 items-center justify-center text-sm text-slate-400">Loading…</div>
                                <div v-else-if="jrJobHistoryItems.length === 0" class="flex h-32 items-center justify-center px-4 text-center text-sm text-slate-400">No job requests linked to this equipment yet.</div>
                                <button v-for="h in jrJobHistoryItems" :key="h.id" type="button" class="w-full border-b border-gray-100 px-5 py-4 text-left transition hover:bg-orange-50" :class="jrSelectedHistoryId === h.id ? 'border-l-4 border-l-orange-500 bg-orange-100' : ''" @click="jrSelectedHistoryId = h.id">
                                    <p class="text-xs text-slate-400">{{ formatHistoryDate(h.requested_at) }}</p>
                                    <p class="mt-0.5 text-sm font-semibold leading-snug text-slate-800">{{ h.equipment_name }}</p>
                                    <div class="mt-1.5 flex flex-wrap gap-1">
                                        <span :class="['inline-flex rounded-full px-2 py-0.5 text-xs font-medium', h.status === 'Done' ? 'bg-emerald-100 text-emerald-700' : h.status === 'Accepted' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700']">{{ h.status }}</span>
                                        <span v-if="h.repair_outcome" :class="['inline-flex rounded-full px-2 py-0.5 text-xs font-medium', h.repair_outcome === 'Repaired' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600']">{{ h.repair_outcome }}</span>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col overflow-hidden">
                            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                                <h3 class="text-lg font-bold text-slate-900">Request Details</h3>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="rounded-lg px-3 py-1.5 text-sm font-medium bg-white border border-slate-200 text-slate-700 hover:bg-slate-50" @click="jrDownloadHistoryPdf" v-if="jrSelectedHistoryItem">Download PDF</button>
                                    <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" @click="jrJobHistoryModalOpen = false">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex-1 overflow-y-auto px-6 py-6">
                                <div v-if="!jrSelectedHistoryItem" class="flex h-full items-center justify-center text-sm text-slate-400">Select a record on the left to view details.</div>
                                <div v-else class="space-y-5">
                                    <div class="flex flex-wrap gap-2">
                                        <span :class="['inline-flex rounded-full border px-3 py-1 text-xs font-semibold', jrSelectedHistoryItem.status === 'Done' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : jrSelectedHistoryItem.status === 'Accepted' ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-orange-200 bg-orange-50 text-orange-700']">{{ jrSelectedHistoryItem.status }}</span>
                                        <span v-if="jrSelectedHistoryItem.repair_outcome" :class="['inline-flex rounded-full border px-3 py-1 text-xs font-semibold', jrSelectedHistoryItem.repair_outcome === 'Repaired' ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-red-300 bg-red-50 text-red-700']">{{ jrSelectedHistoryItem.repair_outcome }}</span>
                                    </div>

                                    <div class="grid gap-4 rounded-xl border border-orange-100 bg-orange-50/40 p-4 text-sm md:grid-cols-2">
                                        <div>
                                            <p class="text-xs font-semibold uppercase text-slate-400">Requester</p>
                                            <p class="mt-0.5 font-medium text-slate-800">{{ jrSelectedHistoryItem.requester_name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase text-slate-400">Department</p>
                                            <p class="mt-0.5 font-medium text-slate-800">{{ jrSelectedHistoryItem.department || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase text-slate-400">Assigned To</p>
                                            <p class="mt-0.5 font-medium text-slate-800">{{ jrSelectedHistoryItem.assigned_to_name || 'Not assigned' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase text-slate-400">Accepted By</p>
                                            <p class="mt-0.5 font-medium text-slate-800">{{ jrSelectedHistoryItem.accepted_by || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase text-slate-400">Date Requested</p>
                                            <p class="mt-0.5 font-medium text-slate-800">{{ formatHistoryDate(jrSelectedHistoryItem.requested_at) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase text-slate-400">Date Completed</p>
                                            <p class="mt-0.5 font-medium text-slate-800">{{ formatHistoryDate(jrSelectedHistoryItem.completed_at) }}</p>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                        <p class="text-xs font-semibold uppercase text-slate-400">Issue Summary</p>
                                        <p class="mt-1.5 text-sm leading-6 text-slate-700">{{ jrSelectedHistoryItem.issue_summary }}</p>
                                    </div>

                                    <div v-if="jrSelectedHistoryItem.remarks" class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                        <p class="text-xs font-semibold uppercase text-slate-400">Remarks</p>
                                        <p class="mt-1.5 text-sm leading-6 text-slate-700">{{ jrSelectedHistoryItem.remarks }}</p>
                                    </div>

                                    <div v-if="jrSelectedHistoryItem.pre_inspection_documents && jrSelectedHistoryItem.pre_inspection_documents.length" class="rounded-xl border border-orange-100 bg-orange-50/40 p-4">
                                        <p class="text-xs font-semibold uppercase text-orange-600">Pre-Inspection Documents</p>
                                        <div class="mt-3 space-y-2">
                                            <div v-for="doc in jrSelectedHistoryItem.pre_inspection_documents" :key="doc.id" class="flex items-center justify-between rounded-md border border-orange-100 bg-white px-3 py-2 text-sm">
                                                <div class="truncate text-slate-800">{{ doc.file_name }}</div>
                                                <div class="flex items-center gap-3">
                                                    <a :href="doc.preview_url" target="_blank" class="text-sm text-orange-600 hover:underline">Preview</a>
                                                    <a :href="doc.download_url" class="text-sm text-orange-700 hover:underline">Download</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>
    </AppLayout>
</template>