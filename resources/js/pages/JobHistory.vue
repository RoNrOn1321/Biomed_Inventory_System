<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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

                            <!-- Repair Outcome -->
                            <td class="whitespace-nowrap border-l border-gray-100 px-3 py-3.5 text-sm">
                                <span
                                    v-if="item.repair_outcome === 'Repaired'"
                                    class="inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20"
                                    >Repaired</span
                                >
                                <span
                                    v-else-if="item.repair_outcome === 'Unserviceable'"
                                    class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10"
                                    >Unserviceable</span
                                >
                                <span v-else class="text-sm text-gray-400">—</span>
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
    </AppLayout>
</template>
