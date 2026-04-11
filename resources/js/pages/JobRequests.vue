<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { CalendarDays, ClipboardCheck, Search, ShieldCheck, Wrench, BellRing } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface JobRequestItem {
    id: number;
    requester_name: string;
    department: string | null;
    equipment_name: string;
    issue_summary: string;
    priority: 'Low' | 'Medium' | 'High' | 'Urgent';
    status: 'Pending' | 'Accepted' | 'Done';
    requested_at: string | null;
    accepted_at: string | null;
    accepted_by: string | null;
    biomedicalServiceDoc?: any;
    request_type?: string[] | null;
    repair_type?: string | null;
    request_complaints?: string | null;
    job_report?: string | null;
    control_no?: string | null;
    location?: string | null;
    date?: string | null;
    end_user?: string | null;
}

const props = defineProps<{
    jobRequests: JobRequestItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Job Requests',
        href: '/JobRequests',
    },
];

const page = usePage<SharedData>();
const search = ref('');
const statusFilter = ref<'All' | 'Pending' | 'Accepted' | 'Done'>('All');
const canAcceptRequests = computed(() => ['Admin', 'Biomed_Technician'].includes(page.props.auth.user.account_type));

const showToast = ref(false);
const toastMessage = ref('');

const playNotificationSound = () => {
    const audio = new Audio('/sounds/notification.mp3');
    audio.play().catch(e => console.error("Audio play failed:", e));
};

if (canAcceptRequests.value) {
    useEcho('job-requests', '.JobRequestCreated', (payload: any) => {
        toastMessage.value = payload.message || 'A new Job Request has been created!';
        showToast.value = true;
        playNotificationSound();
        router.reload({ only: ['jobRequests'] });

        setTimeout(() => {
            showToast.value = false;
        }, 5000);
    });
}

// Dialog and form state
const selectedJobRequestId = ref<number | null>(null);
const isDocsDialogOpen = ref(false);

// View Form Dialog state
const isViewDialogOpen = ref(false);
const selectedViewRequest = ref<JobRequestItem | null>(null);

const serviceDocsForm = ref({
    receive_by: '',
    performed_by: '',
    date_receive: '',
    date_performed: '',
    estimated_no_days: '',
    technician_date_received: '',
    date_started: '',
    date_finished: '',
    date_returned: '',
    receive_by_end_user: '',
    remarks: '',
});

const filteredRequests = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.jobRequests.filter((jobRequest) => {
        const matchesStatus = statusFilter.value === 'All' || jobRequest.status === statusFilter.value;

        if (!matchesStatus) {
            return false;
        }

        if (!query) {
            return true;
        }

        return [
            jobRequest.requester_name,
            jobRequest.department || '',
            jobRequest.equipment_name,
            jobRequest.issue_summary,
            jobRequest.priority,
        ].some((value) => value.toLowerCase().includes(query));
    });
});

const pendingCount = computed(() => props.jobRequests.filter((jobRequest) => jobRequest.status === 'Pending').length);
const acceptedCount = computed(() => props.jobRequests.filter((jobRequest) => jobRequest.status === 'Accepted').length);
const doneCount = computed(() => props.jobRequests.filter((jobRequest) => jobRequest.status === 'Done').length);

const acceptRequest = (jobRequestId: number) => {
    router.put(
        `/JobRequests/${jobRequestId}/accept`,
        {},
        {
            preserveScroll: true,
        },
    );
};

const openServiceDocsDialog = (jobRequest: JobRequestItem) => {
    selectedJobRequestId.value = jobRequest.id;
    const authUserName = page.props.auth.user.name ?? '';
    const acceptedDate = jobRequest.accepted_at ? jobRequest.accepted_at.slice(0, 10) : '';
    serviceDocsForm.value = {
        receive_by: authUserName,
        performed_by: authUserName,
        date_receive: acceptedDate,
        date_performed: '',
        estimated_no_days: '',
        technician_date_received: '',
        date_started: '',
        date_finished: '',
        date_returned: '',
        receive_by_end_user: jobRequest.requester_name ?? '',
        remarks: '',
    };
    isDocsDialogOpen.value = true;
};

const closeServiceDocsDialog = () => {
    isDocsDialogOpen.value = false;
    selectedJobRequestId.value = null;
};

const openViewDialog = (jobRequest: JobRequestItem) => {
    selectedViewRequest.value = jobRequest;
    isViewDialogOpen.value = true;
};

const closeViewDialog = () => {
    isViewDialogOpen.value = false;
    selectedViewRequest.value = null;
};

const submitServiceDocs = () => {
    if (!selectedJobRequestId.value) return;

    router.post(`/JobRequests/${selectedJobRequestId.value}/complete`, serviceDocsForm.value, {
        onSuccess: () => {
            closeServiceDocsDialog();
        },
    });
};

const formatDateTime = (value: string | null) => {
    if (!value) {
        return 'Not set';
    }

    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatFormDate = (value: string) => {
    if (!value) return '';
    const [y, m, d] = value.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const priorityClass = (priority: JobRequestItem['priority']) => {
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

const statusBadgeClass = (status: JobRequestItem['status']) => {
    if (status === 'Done') {
        return 'border-emerald-200 bg-emerald-100 text-emerald-700';
    }
    if (status === 'Accepted') {
        return 'border-blue-200 bg-blue-100 text-blue-700';
    }
    return 'border-orange-200 bg-orange-100 text-orange-700';
};
</script>

<template>
    <Head title="Job Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white">
            <section class="border-b-4 border-orange-400 bg-gradient-to-r from-orange-50 to-orange-100 px-4 py-5 shadow-md">
                <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <h1 class="text-2xl font-bold text-gray-900">Job Requests</h1>
                    <p class="text-sm font-medium text-orange-700">Review incoming service requests and accept jobs that need biomed attention.</p>
                </div>
            </section>

            <section class="mx-auto px-4 py-8 sm:px-6 lg:px-8">
                <div class="mb-6 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-orange-200 bg-orange-50 p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-700">Pending</p>
                                <p class="mt-2 text-3xl font-bold text-slate-900">{{ pendingCount }}</p>
                            </div>
                            <ClipboardCheck class="h-8 w-8 text-orange-500" />
                        </div>
                    </div>
                    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Accepted</p>
                                <p class="mt-2 text-3xl font-bold text-slate-900">{{ acceptedCount }}</p>
                            </div>
                            <ShieldCheck class="h-8 w-8 text-blue-500" />
                        </div>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Completed</p>
                                <p class="mt-2 text-3xl font-bold text-slate-900">{{ doneCount }}</p>
                            </div>
                            <Wrench class="h-8 w-8 text-emerald-500" />
                        </div>
                    </div>
                </div>

                <div class="mb-6 rounded-2xl border border-orange-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-700">Request Queue</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-900">
                                Showing {{ filteredRequests.length }} request{{ filteredRequests.length === 1 ? '' : 's' }}
                            </h2>
                            <p class="text-sm text-slate-600">Search by requester, equipment, department, issue, or priority.</p>
                        </div>

                        <div class="grid gap-4 md:w-[32rem] md:grid-cols-[minmax(0,1fr)_12rem]">
                            <div>
                                <label for="jobRequestSearch" class="mb-2 block text-sm font-medium text-slate-700">Search request</label>
                                <div class="relative">
                                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-orange-500" />
                                    <input
                                        id="jobRequestSearch"
                                        v-model="search"
                                        type="text"
                                        placeholder="Search job requests"
                                        class="h-12 w-full rounded-xl border border-orange-200 bg-white pl-10 pr-4 text-sm text-slate-700 shadow-sm outline-none ring-0 placeholder:text-slate-400 focus:border-orange-400 focus:ring-2 focus:ring-orange-200"
                                    />
                                </div>
                            </div>

                            <div>
                                <label for="statusFilter" class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                                <select
                                    id="statusFilter"
                                    v-model="statusFilter"
                                    class="h-12 w-full rounded-xl border border-orange-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                >
                                    <option value="All">All</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Accepted">Accepted</option>
                                    <option value="Done">Done</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5">
                    <article
                        v-for="jobRequest in filteredRequests"
                        :key="jobRequest.id"
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-orange-200 hover:shadow-md"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="space-y-4">
                                <div class="flex flex-wrap items-center gap-3">
                                    <h3 class="text-xl font-semibold text-slate-900">{{ jobRequest.equipment_name }}</h3>
                                    <span
                                        :class="[
                                            'inline-flex rounded-full border px-3 py-1 text-xs font-semibold',
                                            priorityClass(jobRequest.priority),
                                        ]"
                                    >
                                        {{ jobRequest.priority }} priority
                                    </span>
                                    <span
                                        :class="[
                                            'inline-flex rounded-full border px-3 py-1 text-xs font-semibold',
                                            statusBadgeClass(jobRequest.status),
                                        ]"
                                    >
                                        {{ jobRequest.status }}
                                    </span>
                                </div>

                                <div class="grid gap-4 text-sm text-slate-600 md:grid-cols-2 xl:grid-cols-4">
                                    <div>
                                        <p class="font-semibold text-slate-900">Requester</p>
                                        <p>{{ jobRequest.requester_name }}</p>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900">Department</p>
                                        <p>{{ jobRequest.department || 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900">Location</p>
                                        <p>{{ jobRequest.location || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900">End User</p>
                                        <p>{{ jobRequest.end_user || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900">Requested</p>
                                        <p>{{ formatDateTime(jobRequest.requested_at) }}</p>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900">Accepted By</p>
                                        <p>{{ jobRequest.accepted_by || 'Waiting for acceptance' }}</p>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-orange-100 bg-orange-50/60 p-4">
                                    <p class="text-sm font-semibold text-slate-900">Issue Summary</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ jobRequest.issue_summary }}</p>
                                </div>
                            </div>

                            <div class="flex w-full flex-col gap-3 lg:w-56">
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="border-slate-300 text-slate-700 hover:bg-slate-50"
                                    @click="openViewDialog(jobRequest)"
                                >
                                    View Form
                                </Button>

                                <Button
                                    v-if="jobRequest.status === 'Pending'"
                                    type="button"
                                    class="bg-orange-600 text-white hover:bg-orange-700"
                                    :disabled="!canAcceptRequests"
                                    @click="acceptRequest(jobRequest.id)"
                                >
                                    Accept Request
                                </Button>
                                <Button
                                    v-else-if="jobRequest.status === 'Accepted'"
                                    type="button"
                                    class="bg-emerald-600 text-white hover:bg-emerald-700"
                                    @click="openServiceDocsDialog(jobRequest)"
                                >
                                    Fill Service Docs & Complete
                                </Button>
                                <div v-else class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-700">
                                    Completed - Service doc filed
                                </div>

                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                                    <div v-if="jobRequest.status === 'Pending'">
                                        Once accepted, the request is assigned to the current logged-in technician or admin.
                                    </div>
                                    <div v-else-if="jobRequest.status === 'Accepted'">
                                        Accepted on {{ formatDateTime(jobRequest.accepted_at) }}. Fill service docs to complete.
                                    </div>
                                    <div v-else>Service documentation has been filed and archived.</div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <div
                        v-if="filteredRequests.length === 0"
                        class="rounded-2xl border border-dashed border-orange-200 bg-orange-50/60 px-6 py-12 text-center"
                    >
                        <p class="text-lg font-semibold text-slate-900">No job requests found</p>
                        <p class="mt-2 text-sm text-slate-600">Adjust the search or status filter to view more requests.</p>
                    </div>
                </div>
            </section>
        </div>

        <!-- Service Docs Dialog -->
        <Dialog :open="isDocsDialogOpen" @update:open="isDocsDialogOpen = $event">
            <DialogContent class="max-h-[90vh] max-w-4xl overflow-y-auto bg-white p-6 sm:rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="text-xl font-bold text-slate-900">Fill Service Documentation</DialogTitle>
                </DialogHeader>

                <div class="grid gap-6 py-4">
                    <!-- Row 1 -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="receive_by" class="mb-2 block text-sm font-medium text-slate-700">Receive By</label>
                            <input
                                id="receive_by"
                                v-model="serviceDocsForm.receive_by"
                                type="text"
                                placeholder="Name who received"
                                class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                            />
                        </div>
                        <div>
                            <label for="performed_by" class="mb-2 block text-sm font-medium text-slate-700">Performed By</label>
                            <input
                                id="performed_by"
                                v-model="serviceDocsForm.performed_by"
                                type="text"
                                placeholder="Technician name"
                                class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                            />
                        </div>
                    </div>

                    <!-- Row 2 -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="date_receive" class="mb-2 block text-sm font-medium text-slate-700">Date Received</label>
                            <div class="relative">
                                <input
                                    id="date_receive"
                                    v-model="serviceDocsForm.date_receive"
                                    type="date"
                                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                />
                                <div
                                    class="flex w-full cursor-pointer items-center justify-between rounded-lg border border-orange-200 px-3 py-2 text-sm shadow-sm hover:border-orange-400 hover:ring-2 hover:ring-orange-100"
                                    :class="serviceDocsForm.date_receive ? 'text-slate-700' : 'text-slate-400'"
                                >
                                    <span>{{ serviceDocsForm.date_receive ? formatFormDate(serviceDocsForm.date_receive) : 'Select a date' }}</span>
                                    <CalendarDays class="h-4 w-4 shrink-0 text-orange-400" />
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="date_performed" class="mb-2 block text-sm font-medium text-slate-700">Date Performed</label>
                            <div class="relative">
                                <input
                                    id="date_performed"
                                    v-model="serviceDocsForm.date_performed"
                                    type="date"
                                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                />
                                <div
                                    class="flex w-full cursor-pointer items-center justify-between rounded-lg border border-orange-200 px-3 py-2 text-sm shadow-sm hover:border-orange-400 hover:ring-2 hover:ring-orange-100"
                                    :class="serviceDocsForm.date_performed ? 'text-slate-700' : 'text-slate-400'"
                                >
                                    <span>{{
                                        serviceDocsForm.date_performed ? formatFormDate(serviceDocsForm.date_performed) : 'Select a date'
                                    }}</span>
                                    <CalendarDays class="h-4 w-4 shrink-0 text-orange-400" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3 -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="estimated_no_days" class="mb-2 block text-sm font-medium text-slate-700">Estimated Days</label>
                            <input
                                id="estimated_no_days"
                                v-model="serviceDocsForm.estimated_no_days"
                                type="number"
                                placeholder="Number of days"
                                class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                            />
                        </div>
                        <div>
                            <label for="technician_date_received" class="mb-2 block text-sm font-medium text-slate-700">
                                Technician Date Received
                            </label>
                            <div class="relative">
                                <input
                                    id="technician_date_received"
                                    v-model="serviceDocsForm.technician_date_received"
                                    type="date"
                                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                />
                                <div
                                    class="flex w-full cursor-pointer items-center justify-between rounded-lg border border-orange-200 px-3 py-2 text-sm shadow-sm hover:border-orange-400 hover:ring-2 hover:ring-orange-100"
                                    :class="serviceDocsForm.technician_date_received ? 'text-slate-700' : 'text-slate-400'"
                                >
                                    <span>{{
                                        serviceDocsForm.technician_date_received
                                            ? formatFormDate(serviceDocsForm.technician_date_received)
                                            : 'Select a date'
                                    }}</span>
                                    <CalendarDays class="h-4 w-4 shrink-0 text-orange-400" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 4 -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="date_started" class="mb-2 block text-sm font-medium text-slate-700">Date Started</label>
                            <div class="relative">
                                <input
                                    id="date_started"
                                    v-model="serviceDocsForm.date_started"
                                    type="date"
                                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                />
                                <div
                                    class="flex w-full cursor-pointer items-center justify-between rounded-lg border border-orange-200 px-3 py-2 text-sm shadow-sm hover:border-orange-400 hover:ring-2 hover:ring-orange-100"
                                    :class="serviceDocsForm.date_started ? 'text-slate-700' : 'text-slate-400'"
                                >
                                    <span>{{ serviceDocsForm.date_started ? formatFormDate(serviceDocsForm.date_started) : 'Select a date' }}</span>
                                    <CalendarDays class="h-4 w-4 shrink-0 text-orange-400" />
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="date_finished" class="mb-2 block text-sm font-medium text-slate-700">Date Finished</label>
                            <div class="relative">
                                <input
                                    id="date_finished"
                                    v-model="serviceDocsForm.date_finished"
                                    type="date"
                                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                />
                                <div
                                    class="flex w-full cursor-pointer items-center justify-between rounded-lg border border-orange-200 px-3 py-2 text-sm shadow-sm hover:border-orange-400 hover:ring-2 hover:ring-orange-100"
                                    :class="serviceDocsForm.date_finished ? 'text-slate-700' : 'text-slate-400'"
                                >
                                    <span>{{ serviceDocsForm.date_finished ? formatFormDate(serviceDocsForm.date_finished) : 'Select a date' }}</span>
                                    <CalendarDays class="h-4 w-4 shrink-0 text-orange-400" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 5 -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="date_returned" class="mb-2 block text-sm font-medium text-slate-700">Date Returned</label>
                            <div class="relative">
                                <input
                                    id="date_returned"
                                    v-model="serviceDocsForm.date_returned"
                                    type="date"
                                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                />
                                <div
                                    class="flex w-full cursor-pointer items-center justify-between rounded-lg border border-orange-200 px-3 py-2 text-sm shadow-sm hover:border-orange-400 hover:ring-2 hover:ring-orange-100"
                                    :class="serviceDocsForm.date_returned ? 'text-slate-700' : 'text-slate-400'"
                                >
                                    <span>{{ serviceDocsForm.date_returned ? formatFormDate(serviceDocsForm.date_returned) : 'Select a date' }}</span>
                                    <CalendarDays class="h-4 w-4 shrink-0 text-orange-400" />
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="receive_by_end_user" class="mb-2 block text-sm font-medium text-slate-700">Received By (End User)</label>
                            <input
                                id="receive_by_end_user"
                                v-model="serviceDocsForm.receive_by_end_user"
                                type="text"
                                placeholder="End user name"
                                class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                            />
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <label for="remarks" class="mb-2 block text-sm font-medium text-slate-700">Remarks</label>
                        <textarea
                            id="remarks"
                            v-model="serviceDocsForm.remarks"
                            placeholder="Add any remarks or notes about the service"
                            rows="4"
                            class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeServiceDocsDialog">Cancel</Button>
                    <Button type="button" class="bg-emerald-600 text-white hover:bg-emerald-700" @click="submitServiceDocs">
                        Complete Service & Save Docs
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- View Details Dialog -->
        <Dialog :open="isViewDialogOpen" @update:open="isViewDialogOpen = $event">
            <DialogContent class="max-h-[90vh] max-w-3xl overflow-y-auto bg-white p-6 sm:rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="text-xl font-bold text-slate-900">Job Request Details</DialogTitle>
                </DialogHeader>

                <div v-if="selectedViewRequest" class="space-y-6 py-4">
                    <!-- Basic Request Information -->
                    <div class="overflow-hidden rounded-xl border border-orange-200">
                        <h4 class="bg-gradient-to-r from-orange-100 to-amber-50 px-4 py-3 text-lg font-semibold text-orange-700">
                            Basic Information
                        </h4>
                        <div class="grid grid-cols-1 gap-4 p-4 text-sm md:grid-cols-2">
                            <div>
                                <span class="block font-medium text-slate-500">Requester:</span>
                                <span class="mt-0.5 block rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 font-medium text-slate-900">{{
                                    selectedViewRequest.requester_name
                                }}</span>
                            </div>
                            <div>
                                <span class="block font-medium text-slate-500">Department:</span>
                                <span class="mt-0.5 block rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-slate-900">{{
                                    selectedViewRequest.department || 'N/A'
                                }}</span>
                            </div>
                            <div>
                                <span class="block font-medium text-slate-500">Equipment Name:</span>
                                <span class="mt-0.5 block rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-slate-900">{{
                                    selectedViewRequest.equipment_name
                                }}</span>
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
                                <span class="mt-0.5 block rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-slate-900">{{
                                    formatDateTime(selectedViewRequest.requested_at)
                                }}</span>
                            </div>
                            <div>
                                <span class="block font-medium text-slate-500">Location:</span>
                                <span class="mt-0.5 block rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-slate-900">{{
                                    selectedViewRequest.location || 'N/A'
                                }}</span>
                            </div>
                            <div>
                                <span class="block font-medium text-slate-500">Control N°:</span>
                                <span class="mt-0.5 block rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-slate-900">{{
                                    selectedViewRequest.control_no || 'N/A'
                                }}</span>
                            </div>
                            <div>
                                <span class="block font-medium text-slate-500">Form Date:</span>
                                <span class="mt-0.5 block rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-slate-900">{{
                                    selectedViewRequest.date || 'N/A'
                                }}</span>
                            </div>
                            <div>
                                <span class="block font-medium text-slate-500">Status:</span>
                                <span
                                    :class="statusBadgeClass(selectedViewRequest.status)"
                                    class="mt-1 inline-flex rounded-full border px-2 py-0.5 text-xs font-semibold"
                                >
                                    {{ selectedViewRequest.status }}
                                </span>
                            </div>
                            <div class="col-span-1 md:col-span-2">
                                <span class="mb-1 block font-medium text-slate-500">Issue Summary:</span>
                                <p class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-slate-900">
                                    {{ selectedViewRequest.issue_summary }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Request Detail -->
                    <div class="overflow-hidden rounded-xl border border-orange-200">
                        <h4 class="bg-gradient-to-r from-orange-100 to-amber-50 px-4 py-3 text-lg font-semibold text-orange-700">Request Detail</h4>
                        <div class="grid grid-cols-1 gap-4 p-4 text-sm">
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
                                <p class="min-h-[3rem] rounded-lg border border-slate-200 bg-slate-50 p-3 text-slate-900">
                                    {{ selectedViewRequest.request_complaints || 'No complaints recorded.' }}
                                </p>
                            </div>
                            <div>
                                <span class="mb-1 block font-medium text-slate-500">Job Report:</span>
                                <p class="min-h-[3rem] rounded-lg border border-slate-200 bg-slate-50 p-3 text-slate-900">
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
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeViewDialog">Close Form</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Real-time Toast Notification -->
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
                class="fixed bottom-4 right-4 z-[9999] flex w-full max-w-sm items-center gap-3 overflow-hidden rounded-lg border border-orange-200 bg-white px-4 py-3 shadow-xl ring-1 ring-black/5 sm:bottom-6 sm:right-6"
            >
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-100">
                    <BellRing class="h-5 w-5 text-orange-600 animate-pulse" />
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-slate-900">New Request</p>
                    <p class="mt-0.5 text-sm text-slate-600">{{ toastMessage }}</p>
                </div>
                <button
                    @click="showToast = false"
                    class="ml-auto flex shrink-0 items-center justify-center text-slate-400 hover:text-slate-600 focus:outline-none"
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
                <div class="absolute bottom-0 left-0 h-1 w-full bg-orange-100">
                    <div class="h-full animate-[shrink_5s_linear_forwards] bg-orange-500"></div>
                </div>
            </div>
        </transition>

    </AppLayout>
</template>
