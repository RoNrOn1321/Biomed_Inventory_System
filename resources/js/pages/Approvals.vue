<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle2, ClipboardCheck, ShieldAlert, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';

type Tab = 'job-requests' | 'pre-inspection';

interface BiomedServiceDoc {
    receive_by: string | null;
    performed_by: string | null;
    date_receive: string | null;
    date_performed: string | null;
    estimated_no_days: number | null;
    date_started: string | null;
    date_finished: string | null;
    date_returned: string | null;
    receive_by_end_user: string | null;
    remarks: string | null;
}

interface PendingJobRequest {
    id: number;
    equipment_name: string;
    requester_name: string;
    department: string | null;
    priority: 'Low' | 'Medium' | 'High' | 'Urgent';
    accepted_by: string | null;
    assigned_to_name: string | null;
    completed_at: string | null;
    repair_outcome: 'Repaired' | 'Unserviceable' | null;
    biomedical_service_doc: BiomedServiceDoc | null;
}

interface PendingEquipment {
    id: number;
    description: string;
    brand: string | null;
    model: string | null;
    serial_number: string | null;
    tag_number: string | null;
    location: string | null;
    pending_action: string | null;
    updated_at: string | null;
}

const props = defineProps<{
    pendingJobRequests: PendingJobRequest[];
    pendingEquipment: PendingEquipment[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Approvals', href: '/approvals' }];

const activeTab = ref<Tab>('job-requests');

const tabClass = (tab: Tab) =>
    activeTab.value === tab
        ? 'border-b-2 border-orange-500 text-orange-600 font-semibold'
        : 'border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300';

// Action modal state
const actionModal = ref<{
    open: boolean;
    type: 'job-request' | 'equipment';
    action: 'approve' | 'reject';
    id: number | null;
    label: string;
}>({ open: false, type: 'job-request', action: 'approve', id: null, label: '' });

const notesInput = ref('');
const isSubmitting = ref(false);

const openActionModal = (type: 'job-request' | 'equipment', action: 'approve' | 'reject', id: number, label: string) => {
    actionModal.value = { open: true, type, action, id, label };
    notesInput.value = '';
};

const closeActionModal = () => {
    actionModal.value = { ...actionModal.value, open: false, id: null };
};

const submitAction = () => {
    if (!actionModal.value.id) return;
    isSubmitting.value = true;

    const { type, action, id } = actionModal.value;
    const url = type === 'job-request' ? `/approvals/job-requests/${id}/${action}` : `/approvals/equipment/${id}/${action}`;

    router.put(
        url,
        { notes: notesInput.value || null },
        {
            preserveScroll: true,
            onSuccess: () => closeActionModal(),
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
};

const formatDate = (value: string | null) => {
    if (!value) return 'N/A';
    return new Date(value).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

const priorityClass = (priority: PendingJobRequest['priority']) => {
    if (priority === 'Urgent') return 'border-red-200 bg-red-100 text-red-700';
    if (priority === 'High') return 'border-amber-200 bg-amber-100 text-amber-700';
    if (priority === 'Medium') return 'border-blue-200 bg-blue-100 text-blue-700';
    return 'border-emerald-200 bg-emerald-100 text-emerald-700';
};

const pendingActionClass = (action: string | null) => {
    if (action === 'Condemn') return 'border-red-200 bg-red-100 text-red-700';
    return 'border-emerald-200 bg-emerald-100 text-emerald-700';
};
</script>

<template>
    <Head title="Approvals" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white">
            <!-- Header -->
            <section class="border-b-4 border-orange-400 bg-gradient-to-r from-orange-50 to-orange-100 px-4 py-5 shadow-md">
                <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <h1 class="text-2xl font-bold text-gray-900">Approvals</h1>
                    <p class="text-sm font-medium text-orange-700">Review and approve completed job requests and pre-inspection decisions.</p>
                </div>
            </section>

            <!-- Summary Cards -->
            <section class="mx-auto px-4 pt-8 sm:px-6 lg:px-8">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-orange-200 bg-orange-50 p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-700">Pending Job Requests</p>
                                <p class="mt-2 text-3xl font-bold text-slate-900">{{ pendingJobRequests.length }}</p>
                            </div>
                            <ClipboardCheck class="h-8 w-8 text-orange-500" />
                        </div>
                    </div>
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Pending Equipment Decisions</p>
                                <p class="mt-2 text-3xl font-bold text-slate-900">{{ pendingEquipment.length }}</p>
                            </div>
                            <ShieldAlert class="h-8 w-8 text-amber-500" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Tabs -->
            <div class="mt-6 border-b border-gray-200 bg-white px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <nav class="-mb-px flex gap-6">
                        <button
                            type="button"
                            :class="['flex items-center gap-2 px-1 py-4 text-sm transition', tabClass('job-requests')]"
                            @click="activeTab = 'job-requests'"
                        >
                            <ClipboardCheck class="h-4 w-4" />
                            Job Requests
                            <span
                                v-if="pendingJobRequests.length"
                                class="ml-1 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-semibold text-orange-700"
                                >{{ pendingJobRequests.length }}</span
                            >
                        </button>
                        <button
                            type="button"
                            :class="['flex items-center gap-2 px-1 py-4 text-sm transition', tabClass('pre-inspection')]"
                            @click="activeTab = 'pre-inspection'"
                        >
                            <ShieldAlert class="h-4 w-4" />
                            Pre-Inspection
                            <span
                                v-if="pendingEquipment.length"
                                class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700"
                                >{{ pendingEquipment.length }}</span
                            >
                        </button>
                    </nav>
                </div>
            </div>

            <section class="mx-auto px-4 py-8 sm:px-6 lg:px-8">
                <!-- ── Job Requests Tab ── -->
                <div v-if="activeTab === 'job-requests'">
                    <div
                        v-if="pendingJobRequests.length === 0"
                        class="rounded-2xl border border-dashed border-orange-200 bg-orange-50/60 px-6 py-12 text-center"
                    >
                        <p class="text-lg font-semibold text-slate-900">No pending job request approvals.</p>
                        <p class="mt-1 text-sm text-slate-500">Completed job requests will appear here once submitted for review.</p>
                    </div>

                    <div v-else class="grid gap-5">
                        <article
                            v-for="jr in pendingJobRequests"
                            :key="jr.id"
                            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-orange-200 hover:shadow-md"
                        >
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="flex-1 space-y-3">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <h3 class="text-xl font-semibold text-slate-900">{{ jr.equipment_name }}</h3>
                                        <span
                                            :class="['inline-flex rounded-full border px-3 py-1 text-xs font-semibold', priorityClass(jr.priority)]"
                                        >
                                            {{ jr.priority }} priority
                                        </span>
                                        <span
                                            class="inline-flex rounded-full border border-emerald-200 bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700"
                                        >
                                            Done — Awaiting Approval
                                        </span>
                                        <span
                                            v-if="jr.repair_outcome"
                                            :class="[
                                                'inline-flex rounded-full border px-3 py-1 text-xs font-semibold',
                                                jr.repair_outcome === 'Repaired'
                                                    ? 'border-emerald-300 bg-emerald-50 text-emerald-700'
                                                    : 'border-red-300 bg-red-50 text-red-700',
                                            ]"
                                        >
                                            {{ jr.repair_outcome }}
                                        </span>
                                    </div>

                                    <div class="grid gap-3 text-sm text-slate-600 md:grid-cols-2 xl:grid-cols-4">
                                        <div>
                                            <p class="font-semibold text-slate-900">Requester</p>
                                            <p>{{ jr.requester_name }}</p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">Department</p>
                                            <p>{{ jr.department || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">Accepted By</p>
                                            <p>{{ jr.accepted_by || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">Performed By</p>
                                            <p>{{ jr.assigned_to_name || jr.accepted_by || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">Completed On</p>
                                            <p>{{ formatDate(jr.completed_at) }}</p>
                                        </div>
                                        <div v-if="jr.biomedical_service_doc">
                                            <p class="font-semibold text-slate-900">Performed By (Doc)</p>
                                            <p>{{ jr.biomedical_service_doc.performed_by || 'N/A' }}</p>
                                        </div>
                                    </div>

                                    <div
                                        v-if="jr.biomedical_service_doc"
                                        class="space-y-1 rounded-xl border border-orange-100 bg-orange-50/60 p-4 text-sm"
                                    >
                                        <p class="font-semibold text-slate-900">Service Documentation Summary</p>
                                        <div class="mt-2 grid gap-2 text-slate-600 md:grid-cols-2">
                                            <p>
                                                <span class="font-medium text-slate-700">Date Received:</span>
                                                {{ formatDate(jr.biomedical_service_doc.date_receive) }}
                                            </p>
                                            <p>
                                                <span class="font-medium text-slate-700">Date Performed:</span>
                                                {{ formatDate(jr.biomedical_service_doc.date_performed) }}
                                            </p>
                                            <p>
                                                <span class="font-medium text-slate-700">Date Started:</span>
                                                {{ formatDate(jr.biomedical_service_doc.date_started) }}
                                            </p>
                                            <p>
                                                <span class="font-medium text-slate-700">Date Finished:</span>
                                                {{ formatDate(jr.biomedical_service_doc.date_finished) }}
                                            </p>
                                            <p>
                                                <span class="font-medium text-slate-700">Est. Days:</span>
                                                {{ jr.biomedical_service_doc.estimated_no_days ?? 'N/A' }}
                                            </p>
                                            <p>
                                                <span class="font-medium text-slate-700">Returned:</span>
                                                {{ formatDate(jr.biomedical_service_doc.date_returned) }}
                                            </p>
                                        </div>
                                        <p v-if="jr.biomedical_service_doc.remarks" class="mt-1 text-slate-600">
                                            <span class="font-medium text-slate-700">Remarks:</span> {{ jr.biomedical_service_doc.remarks }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex w-full flex-col gap-3 lg:w-44">
                                    <Button
                                        type="button"
                                        class="gap-1.5 bg-emerald-600 text-white hover:bg-emerald-700"
                                        @click="openActionModal('job-request', 'approve', jr.id, jr.equipment_name)"
                                    >
                                        <CheckCircle2 class="h-4 w-4" /> Approve
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="gap-1.5 border-red-300 text-red-600 hover:bg-red-50"
                                        @click="openActionModal('job-request', 'reject', jr.id, jr.equipment_name)"
                                    >
                                        <XCircle class="h-4 w-4" /> Reject
                                    </Button>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <!-- ── Pre-Inspection Tab ── -->
                <div v-else-if="activeTab === 'pre-inspection'">
                    <div
                        v-if="pendingEquipment.length === 0"
                        class="rounded-2xl border border-dashed border-amber-200 bg-amber-50/60 px-6 py-12 text-center"
                    >
                        <p class="text-lg font-semibold text-slate-900">No pending equipment approval decisions.</p>
                        <p class="mt-1 text-sm text-slate-500">Pre-inspection decisions will appear here once submitted for review.</p>
                    </div>

                    <div v-else class="grid gap-5">
                        <article
                            v-for="eq in pendingEquipment"
                            :key="eq.id"
                            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-amber-200 hover:shadow-md"
                        >
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="flex-1 space-y-3">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <h3 class="text-xl font-semibold text-slate-900">{{ eq.description }}</h3>
                                        <span
                                            v-if="eq.pending_action"
                                            :class="[
                                                'inline-flex rounded-full border px-3 py-1 text-xs font-semibold',
                                                pendingActionClass(eq.pending_action),
                                            ]"
                                        >
                                            Pending: {{ eq.pending_action }}
                                        </span>
                                    </div>

                                    <div class="grid gap-3 text-sm text-slate-600 md:grid-cols-2 xl:grid-cols-4">
                                        <div>
                                            <p class="font-semibold text-slate-900">Brand</p>
                                            <p>{{ eq.brand || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">Model</p>
                                            <p>{{ eq.model || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">Serial No.</p>
                                            <p>{{ eq.serial_number || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">Tag No.</p>
                                            <p>{{ eq.tag_number || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">Location</p>
                                            <p>{{ eq.location || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">Decision Date</p>
                                            <p>{{ formatDate(eq.updated_at) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex w-full flex-col gap-3 lg:w-44">
                                    <Button
                                        type="button"
                                        class="gap-1.5 bg-emerald-600 text-white hover:bg-emerald-700"
                                        @click="openActionModal('equipment', 'approve', eq.id, eq.description)"
                                    >
                                        <CheckCircle2 class="h-4 w-4" /> Approve
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="gap-1.5 border-red-300 text-red-600 hover:bg-red-50"
                                        @click="openActionModal('equipment', 'reject', eq.id, eq.description)"
                                    >
                                        <XCircle class="h-4 w-4" /> Reject
                                    </Button>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>
        </div>

        <!-- Approve / Reject Confirmation Modal -->
        <Dialog :open="actionModal.open" @update:open="actionModal.open = $event">
            <DialogContent class="max-w-md bg-white p-6 sm:rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="text-xl font-bold text-slate-900">
                        {{ actionModal.action === 'approve' ? 'Approve' : 'Reject' }} — {{ actionModal.label }}
                    </DialogTitle>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <p class="text-sm text-slate-600">
                        <template v-if="actionModal.action === 'approve'">
                            You are about to <span class="font-semibold text-emerald-700">approve</span> this item. This action confirms the work has
                            been reviewed and accepted.
                        </template>
                        <template v-else>
                            You are about to <span class="font-semibold text-red-600">reject</span> this item. The submitter will be notified and may
                            need to revise.
                        </template>
                    </p>

                    <div>
                        <label for="approvalNotes" class="mb-2 block text-sm font-medium text-slate-700">
                            Notes <span class="text-slate-400">(optional)</span>
                        </label>
                        <textarea
                            id="approvalNotes"
                            v-model="notesInput"
                            rows="3"
                            placeholder="Add any remarks or reason..."
                            class="w-full rounded-xl border border-orange-200 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                        />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button type="button" variant="outline" @click="closeActionModal">Cancel</Button>
                    <Button
                        v-if="actionModal.action === 'approve'"
                        type="button"
                        class="bg-emerald-600 text-white hover:bg-emerald-700"
                        :disabled="isSubmitting"
                        @click="submitAction"
                    >
                        Confirm Approval
                    </Button>
                    <Button v-else type="button" class="bg-red-600 text-white hover:bg-red-700" :disabled="isSubmitting" @click="submitAction">
                        Confirm Rejection
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
