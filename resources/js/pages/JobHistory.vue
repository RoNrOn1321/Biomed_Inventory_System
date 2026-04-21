<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch, nextTick } from 'vue';

interface HistoryItem {
    id: number;
    control_no: string | null;
    location: string | null;
    equipment_name: string;
    brand: string | null;
    model: string | null;
    serial_number: string | null;
    tag_number: string | null;
    accepted_by: string | null;
    assigned_to_name: string | null;
    repair_category: string | null;
    repair_outcome: string | null;
    admin_approval: string | null;
    completed_at: string | null;
    requested_at: string | null;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    from: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
    to: number;
    total: number;
    per_page: number;
}

const props = defineProps<{
    history: PaginatedData<HistoryItem>;
    filters: { search?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Job Request History', href: '/job-request-history' }];

const search = ref(props.filters.search || '');
let debounce: ReturnType<typeof setTimeout>;
watch(search, (val) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get('/job-request-history', { search: val || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
});

const fmt = (d: string | null) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

// ─── Export panel (identical pattern to Inventory) ───────────────
const now = new Date();
const currentYear = String(now.getFullYear());
const currentMonth = String(now.getMonth() + 1).padStart(2, '0');
const currentMonthValue = `${currentYear}-${currentMonth}`;
const defaultFromDate = new Date(now.getFullYear(), now.getMonth() - 2, 1);
const defaultFromMonthValue = `${defaultFromDate.getFullYear()}-${String(defaultFromDate.getMonth() + 1).padStart(2, '0')}`;

const exportPanelVisible = ref(false);
const exportFormat = ref<'pdf' | 'excel' | 'word'>('pdf');
const exportFrom = ref(defaultFromMonthValue);
const exportTo = ref(currentMonthValue);

const exportRangeLabel = computed(() => {
    const from = new Date(`${exportFrom.value}-01T00:00:00`);
    const to = new Date(`${exportTo.value}-01T00:00:00`);
    return `${from.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })} to ${to.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}`;
});

const toggleExportPanel = () => {
    exportPanelVisible.value = !exportPanelVisible.value;
};

const downloadHistory = () => {
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
    window.open(`/job-request-history/export?${params.toString()}`, '_blank');
};

// Job history modal (same behavior as Inventory.vue)
const jobHistoryModalOpen = ref(false);
const jobHistoryEquipment = ref(null);
const jobHistoryItems = ref([]);
const jobHistoryLoading = ref(false);
const selectedHistoryId = ref<number | null>(null);

const selectedHistoryItem = computed(() => jobHistoryItems.value.find((h) => h.id === selectedHistoryId.value) ?? null);

const formatHistoryDate = (val: string | null) => {
    if (!val) return 'N/A';
    return new Date(val).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

const openJobHistoryModal = async (payload: any | null) => {
    // payload can be either an equipment-like object { id, description, status }
    // or a single history item (with id, equipment_name, status, etc.).
    console.log('openJobHistoryModal called with', payload);
    if (!payload) return;

    const equipmentId = payload.equipment_id ?? payload.id ?? null;

    jobHistoryEquipment.value = { id: equipmentId, description: payload.equipment_name ?? payload.description, status: payload.status };
    jobHistoryModalOpen.value = true;
    selectedHistoryId.value = null;
    await nextTick();
    console.log('jobHistoryModalOpen set; DOM updated');

    // If we have a numeric equipment id (real equipment), fetch linked history from server.
    if (payload.equipment_id) {
        jobHistoryLoading.value = true;
        try {
            const res = await axios.get(`/api/equipment/${payload.equipment_id}/job-history`);
            jobHistoryItems.value = res.data;
            if (jobHistoryItems.value.length > 0) selectedHistoryId.value = jobHistoryItems.value[0].id;
        } catch (e) {
            jobHistoryItems.value = [];
        } finally {
            jobHistoryLoading.value = false;
        }
        return;
    }

    // No equipment_id available: treat payload as a single history record and display it.
    jobHistoryItems.value = [
        {
            id: payload.id,
            equipment_name: payload.equipment_name ?? payload.equipment_name,
            requester_name: payload.requester_name ?? null,
            department: payload.department ?? null,
            issue_summary: payload.issue_summary ?? payload.issue_summary ?? null,
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
    selectedHistoryId.value = payload.id;
};

const downloadHistoryPdf = () => {
    if (!selectedHistoryItem.value) return;
    const id = selectedHistoryItem.value.id;
    window.open(`/JobRequests/${id}/export`, '_blank');
};
</script>

<template>
    <Head title="Job Request History" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50/50 p-4 sm:p-6 lg:p-8">
            <!-- Page Header -->
            <section class="mb-8 overflow-hidden rounded-2xl bg-orange-50 shadow-sm ring-1 ring-orange-200">
                <div class="flex items-center gap-6 p-6 sm:p-8">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-orange-600 shadow">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Job Request History</h1>
                        <p class="mt-1 text-sm font-medium text-orange-600">All completed job requests — newest first</p>
                    </div>
                </div>
            </section>

            <!-- Toolbar -->
            <div
                class="mb-5 flex flex-col gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5 sm:flex-row sm:items-center sm:justify-between"
            >
                <!-- Search -->
                <div
                    class="flex w-full items-center rounded-lg bg-white px-4 py-2.5 shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-orange-500 sm:max-w-xs"
                >
                    <svg class="h-4 w-4 shrink-0 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search equipment, location, control no..."
                        class="ml-3 w-full border-0 bg-transparent text-sm text-gray-900 placeholder-gray-400 outline-none"
                    />
                </div>

                <!-- Stats -->
                <div class="flex flex-col items-end gap-3 sm:flex-row sm:items-center">
                    <span class="text-sm text-gray-500">{{ history.total }} records</span>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="bg-orange-500">
                            <th
                                class="border-b border-orange-400 py-3.5 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-white sm:pl-6"
                            >
                                No.
                            </th>
                            <th
                                class="border-b border-l border-orange-400 px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-white"
                            >
                                Control No.
                            </th>
                            <th
                                class="border-b border-l border-orange-400 px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-white"
                            >
                                Location
                            </th>
                            <th
                                class="border-b border-l border-orange-400 px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-white"
                            >
                                Equipment Description
                            </th>
                            <th
                                class="border-b border-l border-orange-400 px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-white"
                            >
                                Brand
                            </th>
                            <th
                                class="border-b border-l border-orange-400 px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-white"
                            >
                                Model
                            </th>
                            <th
                                class="border-b border-l border-orange-400 px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-white"
                            >
                                Serial #
                            </th>
                            <th
                                class="border-b border-l border-orange-400 px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-white"
                            >
                                TAG #
                            </th>
                            <th
                                class="border-b border-l border-orange-400 px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-white"
                            >
                                Accepted By
                            </th>
                            <th
                                class="border-b border-l border-orange-400 px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-white"
                            >
                                Technician
                            </th>
                            <th
                                class="border-b border-l border-orange-400 px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-white"
                            >
                                Category
                            </th>
                            <th
                                class="border-b border-l border-orange-400 px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-white"
                            >
                                Outcome
                            </th>
                            <th
                                class="border-b border-l border-orange-400 px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-white"
                            >
                                Completed
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Empty state -->
                        <tr v-if="history.data.length === 0">
                            <td colspan="13" class="py-16 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        vector-effect="non-scaling-stroke"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                                <p class="mt-3 text-sm font-semibold text-gray-900">No completed job requests found</p>
                                <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria.</p>
                            </td>
                        </tr>

                        <tr
                            v-for="(item, index) in history.data"
                            :key="item.id"
                            class="border-b border-gray-100 transition-colors hover:bg-orange-50/40"
                            :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50/50'"
                        >
                            <!-- No. (sequential, newest first = 1) -->
                            <td class="whitespace-nowrap py-3.5 pl-4 pr-3 text-sm font-semibold text-gray-900 sm:pl-6">
                                {{ (history.current_page - 1) * history.per_page + index + 1 }}
                            </td>

                            <td class="whitespace-nowrap border-l border-gray-100 px-3 py-3.5 text-sm text-gray-600">
                                {{ item.control_no || '—' }}
                            </td>

                            <td class="whitespace-nowrap border-l border-gray-100 px-3 py-3.5 text-sm text-gray-700">
                                {{ item.location || '—' }}
                            </td>

                            <td class="border-l border-gray-100 px-3 py-3.5 text-sm font-medium text-gray-900">
                                {{ item.equipment_name }}
                            </td>

                            <td class="whitespace-nowrap border-l border-gray-100 px-3 py-3.5 text-sm text-gray-600">
                                {{ item.brand || '—' }}
                            </td>

                            <td class="whitespace-nowrap border-l border-gray-100 px-3 py-3.5 text-sm text-gray-600">
                                {{ item.model || '—' }}
                            </td>

                            <td class="whitespace-nowrap border-l border-gray-100 px-3 py-3.5 text-sm text-gray-600">
                                {{ item.serial_number || '—' }}
                            </td>

                            <td class="whitespace-nowrap border-l border-gray-100 px-3 py-3.5 text-sm text-gray-600">
                                {{ item.tag_number || '—' }}
                            </td>

                            <!-- Accepted By -->
                            <td class="whitespace-nowrap border-l border-gray-100 px-3 py-3.5 text-sm text-gray-700">
                                <span v-if="item.accepted_by" class="inline-flex items-center gap-1.5">
                                    <span
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700"
                                    >
                                        {{ item.accepted_by.charAt(0).toUpperCase() }}
                                    </span>
                                    {{ item.accepted_by }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </td>

                            <!-- Technician -->
                            <td class="whitespace-nowrap border-l border-gray-100 px-3 py-3.5 text-sm text-gray-700">
                                <span v-if="item.assigned_to_name" class="inline-flex items-center gap-1.5">
                                    <span
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-semibold text-orange-700"
                                    >
                                        {{ item.assigned_to_name.charAt(0).toUpperCase() }}
                                    </span>
                                    {{ item.assigned_to_name }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </td>

                            <!-- Repair Category -->
                            <td class="whitespace-nowrap border-l border-gray-100 px-3 py-3.5 text-sm">
                                <span
                                    v-if="item.repair_category === 'Minor'"
                                    class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20"
                                    >Minor</span
                                >
                                <span
                                    v-else-if="item.repair_category === 'Major'"
                                    class="inline-flex items-center rounded-md bg-orange-50 px-2 py-0.5 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-600/20"
                                    >Major</span
                                >
                                <span v-else class="text-sm text-gray-400">—</span>
                            </td>

                            <!-- Repair Outcome (clickable to open equipment job-history modal) -->
                            <td class="whitespace-nowrap border-l border-gray-100 px-3 py-3.5 text-sm">
                                <button
                                    type="button"
                                    class="focus:outline-none"
                                    @click="openJobHistoryModal(item)"
                                >
                                    <span
                                        v-if="item.repair_outcome === 'Repaired'"
                                        class="inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 hover:bg-green-100"
                                        >Repaired</span
                                    >
                                    <span
                                        v-else-if="item.repair_outcome === 'Unserviceable'"
                                        class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 hover:bg-red-100"
                                        >Unserviceable</span
                                    >
                                    <span v-else class="text-sm text-gray-400">—</span>
                                </button>
                            </td>

                            <!-- Completed Date -->
                            <td class="whitespace-nowrap border-l border-gray-100 px-3 py-3.5 text-sm text-gray-600">
                                {{ fmt(item.completed_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="history.last_page > 1" class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium">{{ history.from }}</span> to <span class="font-medium">{{ history.to }}</span> of
                            <span class="font-medium">{{ history.total }}</span> results
                        </p>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <template v-for="(link, idx) in history.links" :key="idx">
                                <span
                                    v-if="link.url === null"
                                    class="relative inline-flex cursor-not-allowed items-center bg-gray-50 px-4 py-2 text-sm font-medium text-gray-400 ring-1 ring-inset ring-gray-300"
                                    :class="{ 'rounded-l-md': idx === 0, 'rounded-r-md': idx === history.links.length - 1 }"
                                    v-html="link.label"
                                />
                                <Link
                                    v-else
                                    :href="link.url"
                                    preserve-scroll
                                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium ring-1 ring-inset ring-gray-300 focus:z-20 focus:outline-offset-0"
                                    :class="{
                                        'rounded-l-md': idx === 0,
                                        'rounded-r-md': idx === history.links.length - 1,
                                        'z-10 bg-orange-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600':
                                            link.active,
                                        'text-gray-900 hover:bg-gray-50': !link.active,
                                    }"
                                    v-html="link.label"
                                />
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Download FAB (same as Inventory) -->
        <div class="fixed bottom-6 right-6 z-40 flex flex-col items-end gap-3">
            <div v-if="exportPanelVisible" class="w-[22rem] rounded-2xl border border-orange-200 bg-white p-5 shadow-2xl shadow-orange-200/50">
                <div class="mb-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-700">Export Table</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Download job history report</h3>
                    <p class="mt-1 text-sm text-slate-600">Choose a month range and file format.</p>
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
                            @click="downloadHistory"
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
                title="Download job history report"
            >
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l4-4m-4 4l-4-4m-5 7h18" />
                </svg>
            </button>
        </div>

        <!-- Job History Modal -->
        <Teleport to="body">
            <div
                v-if="jobHistoryModalOpen"
                class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/40 backdrop-blur-sm"
                @click.self="jobHistoryModalOpen = false"
            >
                <div class="mx-4 flex h-[85vh] w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <!-- Left: date/title list -->
                    <div class="flex w-72 shrink-0 flex-col border-r border-gray-100 bg-slate-50">
                        <div class="border-b border-gray-200 bg-gradient-to-r from-orange-50 to-amber-50 px-5 py-4">
                            <p class="text-xs font-semibold uppercase tracking-widest text-orange-600">Job History</p>
                            <h2 class="mt-0.5 text-base font-bold leading-snug text-slate-900">{{ jobHistoryEquipment?.description }}</h2>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ jobHistoryEquipment?.status }} · {{ jobHistoryItems.length }} record{{ jobHistoryItems.length === 1 ? '' : 's' }}
                            </p>
                        </div>

                        <div class="flex-1 overflow-y-auto">
                            <div v-if="jobHistoryLoading" class="flex h-32 items-center justify-center text-sm text-slate-400">Loading…</div>
                            <div v-else-if="jobHistoryItems.length === 0" class="flex h-32 items-center justify-center px-4 text-center text-sm text-slate-400">
                                No job requests linked to this equipment yet.
                            </div>
                            <button
                                v-for="h in jobHistoryItems"
                                :key="h.id"
                                type="button"
                                class="w-full border-b border-gray-100 px-5 py-4 text-left transition hover:bg-orange-50"
                                :class="selectedHistoryId === h.id ? 'border-l-4 border-l-orange-500 bg-orange-100' : ''"
                                @click="selectedHistoryId = h.id"
                            >
                                <p class="text-xs text-slate-400">{{ formatHistoryDate(h.requested_at) }}</p>
                                <p class="mt-0.5 text-sm font-semibold leading-snug text-slate-800">{{ h.equipment_name }}</p>
                                <div class="mt-1.5 flex flex-wrap gap-1">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                            h.status === 'Done'
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : h.status === 'Accepted'
                                                  ? 'bg-blue-100 text-blue-700'
                                                  : 'bg-orange-100 text-orange-700',
                                        ]"
                                        >{{ h.status }}</span
                                    >
                                    <span
                                        v-if="h.repair_outcome"
                                        :class="[
                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                            h.repair_outcome === 'Repaired' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600',
                                        ]"
                                        >{{ h.repair_outcome }}</span
                                    >
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Right: details panel -->
                    <div class="flex flex-1 flex-col overflow-hidden">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h3 class="text-lg font-bold text-slate-900">Request Details</h3>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg px-3 py-1.5 text-sm font-medium bg-white border border-slate-200 text-slate-700 hover:bg-slate-50"
                                    @click="downloadHistoryPdf"
                                    v-if="selectedHistoryItem"
                                >
                                    Download PDF
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                                    @click="jobHistoryModalOpen = false"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto px-6 py-6">
                            <div v-if="!selectedHistoryItem" class="flex h-full items-center justify-center text-sm text-slate-400">
                                Select a record on the left to view details.
                            </div>
                            <div v-else class="space-y-5">
                                <!-- Status badges row -->
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full border px-3 py-1 text-xs font-semibold',
                                            selectedHistoryItem.status === 'Done'
                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                : selectedHistoryItem.status === 'Accepted'
                                                  ? 'border-blue-200 bg-blue-50 text-blue-700'
                                                  : 'border-orange-200 bg-orange-50 text-orange-700',
                                        ]"
                                        >{{ selectedHistoryItem.status }}</span
                                    >
                                    <span
                                        v-if="selectedHistoryItem.repair_outcome"
                                        :class="[
                                            'inline-flex rounded-full border px-3 py-1 text-xs font-semibold',
                                            selectedHistoryItem.repair_outcome === 'Repaired'
                                                ? 'border-emerald-300 bg-emerald-50 text-emerald-700'
                                                : 'border-red-300 bg-red-50 text-red-700',
                                        ]"
                                        >{{ selectedHistoryItem.repair_outcome }}</span
                                    >
                                    <span
                                        v-if="selectedHistoryItem.repair_category"
                                        class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"
                                        >{{ selectedHistoryItem.repair_category }} Repair</span
                                    >
                                    <span
                                        v-if="selectedHistoryItem.admin_approval"
                                        class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600"
                                    >
                                        Admin: {{ selectedHistoryItem.admin_approval }}
                                    </span>
                                    <span
                                        :class="[
                                            'inline-flex rounded-full border px-3 py-1 text-xs font-semibold',
                                            selectedHistoryItem.priority === 'Urgent'
                                                ? 'border-red-200 bg-red-50 text-red-700'
                                                : selectedHistoryItem.priority === 'High'
                                                  ? 'border-amber-200 bg-amber-50 text-amber-700'
                                                  : selectedHistoryItem.priority === 'Medium'
                                                    ? 'border-blue-200 bg-blue-50 text-blue-700'
                                                    : 'border-slate-200 bg-slate-50 text-slate-600',
                                        ]"
                                        >{{ selectedHistoryItem.priority }} priority</span
                                    >
                                </div>

                                <div class="grid gap-4 rounded-xl border border-orange-100 bg-orange-50/40 p-4 text-sm md:grid-cols-2">
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-slate-400">Requester</p>
                                        <p class="mt-0.5 font-medium text-slate-800">{{ selectedHistoryItem.requester_name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-slate-400">Department</p>
                                        <p class="mt-0.5 font-medium text-slate-800">{{ selectedHistoryItem.department || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-slate-400">Assigned To</p>
                                        <p class="mt-0.5 font-medium text-slate-800">{{ selectedHistoryItem.assigned_to_name || 'Not assigned' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-slate-400">Accepted By</p>
                                        <p class="mt-0.5 font-medium text-slate-800">{{ selectedHistoryItem.accepted_by || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-slate-400">Date Requested</p>
                                        <p class="mt-0.5 font-medium text-slate-800">{{ formatHistoryDate(selectedHistoryItem.requested_at) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-slate-400">Date Completed</p>
                                        <p class="mt-0.5 font-medium text-slate-800">{{ formatHistoryDate(selectedHistoryItem.completed_at) }}</p>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                    <p class="text-xs font-semibold uppercase text-slate-400">Issue Summary</p>
                                    <p class="mt-1.5 text-sm leading-6 text-slate-700">{{ selectedHistoryItem.issue_summary }}</p>
                                </div>

                                <div v-if="selectedHistoryItem.remarks" class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                    <p class="text-xs font-semibold uppercase text-slate-400">Remarks</p>
                                    <p class="mt-1.5 text-sm leading-6 text-slate-700">{{ selectedHistoryItem.remarks }}</p>
                                </div>

                                <div v-if="selectedHistoryItem.pre_inspection_documents && selectedHistoryItem.pre_inspection_documents.length" class="rounded-xl border border-orange-100 bg-orange-50/40 p-4">
                                    <p class="text-xs font-semibold uppercase text-orange-600">Pre-Inspection Documents</p>
                                    <div class="mt-3 space-y-2">
                                        <div
                                            v-for="doc in selectedHistoryItem.pre_inspection_documents"
                                            :key="doc.id"
                                            class="flex items-center justify-between rounded-md border border-orange-100 bg-white px-3 py-2 text-sm"
                                        >
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
