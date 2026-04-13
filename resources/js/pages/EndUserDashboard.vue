<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Activity, CheckCircle, ClipboardList, Clock, Package } from 'lucide-vue-next';
import { computed } from 'vue';

interface EndUserStats {
    my_equipment: number;
    my_pending: number;
    my_in_progress: number;
    my_completed: number;
}

interface RecentRequest {
    id: number;
    control_no: string | null;
    equipment_name: string;
    status: string;
    priority: 'Low' | 'Medium' | 'High' | 'Urgent';
    requested_at: string;
    accepted_by: string | null;
}

const props = defineProps<{
    stats: EndUserStats;
    recentRequests: RecentRequest[];
}>();

const page = usePage<any>();
const userName = computed(() => page.props.auth?.user?.name ?? 'User');

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const metricCards = computed(() => [
    {
        title: 'My Equipment',
        value: props.stats.my_equipment,
        description: 'Equipment assigned to your location.',
        icon: Package,
        className: 'border-orange-300 bg-gradient-to-br from-orange-500 to-amber-400 text-white shadow-md shadow-orange-200',
        valueClass: 'text-white',
        descriptionClass: 'text-orange-50/90',
        iconClass: 'text-white/90',
        href: '/Inventory',
    },
    {
        title: 'Pending Requests',
        value: props.stats.my_pending,
        description: 'Submitted requests awaiting biomed action.',
        icon: Clock,
        className: 'border-amber-300 bg-gradient-to-br from-amber-400 to-yellow-300 text-amber-950 shadow-md shadow-amber-200',
        valueClass: 'text-amber-950',
        descriptionClass: 'text-amber-900/80',
        iconClass: 'text-amber-900',
        href: '/request-history',
    },
    {
        title: 'In Progress',
        value: props.stats.my_in_progress,
        description: 'Requests currently being handled.',
        icon: Activity,
        className: 'border-sky-300 bg-gradient-to-br from-sky-500 to-blue-500 text-white shadow-md shadow-sky-200',
        valueClass: 'text-white',
        descriptionClass: 'text-sky-50/90',
        iconClass: 'text-white/90',
        href: '/request-history',
    },
    {
        title: 'Completed',
        value: props.stats.my_completed,
        description: 'Requests that have been resolved.',
        icon: CheckCircle,
        className: 'border-emerald-300 bg-gradient-to-br from-emerald-500 to-green-400 text-white shadow-md shadow-emerald-200',
        valueClass: 'text-white',
        descriptionClass: 'text-emerald-50/90',
        iconClass: 'text-white/90',
        href: '/request-history',
    },
]);

const formatDateTime = (value: string | null) => {
    if (!value) return '—';
    return new Date(value).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

const statusClass = (status: string) => {
    switch (status) {
        case 'Pending':
            return 'bg-amber-100 text-amber-700 ring-amber-200';
        case 'Accepted':
        case 'In Progress':
            return 'bg-sky-100 text-sky-700 ring-sky-200';
        case 'Completed':
            return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
        case 'Rejected':
            return 'bg-red-100 text-red-700 ring-red-200';
        default:
            return 'bg-gray-100 text-gray-700 ring-gray-200';
    }
};

const priorityClass = (priority: RecentRequest['priority']) => {
    switch (priority) {
        case 'Urgent':
            return 'bg-red-100 text-red-700 ring-red-200';
        case 'High':
            return 'bg-amber-100 text-amber-700 ring-amber-200';
        case 'Medium':
            return 'bg-blue-100 text-blue-700 ring-blue-200';
        default:
            return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-[linear-gradient(180deg,_#fffaf5_0%,_#ffffff_30%,_#fffaf3_100%)]">
            <!-- Header banner -->
            <section class="border-b-4 border-orange-400 bg-gradient-to-r from-orange-50 to-orange-100 px-4 py-5 shadow-md">
                <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Welcome back, {{ userName }}!</h1>
                            <p class="text-sm font-medium text-orange-700">Here's an overview of your equipment and service requests.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <Link
                                href="/request-service"
                                class="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600"
                            >
                                <ClipboardList class="h-4 w-4" />
                                New Service Request
                            </Link>
                            <Link
                                href="/request-history"
                                class="inline-flex items-center gap-2 rounded-lg border border-orange-300 bg-white px-5 py-2.5 text-sm font-semibold text-orange-700 shadow-sm hover:bg-orange-50"
                            >
                                <Activity class="h-4 w-4" />
                                My Request History
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto px-4 py-8 sm:px-6 lg:px-8">
                <!-- Stat cards -->
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Link
                        v-for="card in metricCards"
                        :key="card.title"
                        :href="card.href"
                        :class="['rounded-2xl border p-5 shadow-sm transition-transform hover:scale-[1.02]', card.className]"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] opacity-95">{{ card.title }}</p>
                                <p :class="['mt-3 text-3xl font-bold', card.valueClass]">{{ card.value }}</p>
                                <p :class="['mt-2 text-sm', card.descriptionClass]">{{ card.description }}</p>
                            </div>
                            <component :is="card.icon" :class="['h-8 w-8', card.iconClass]" />
                        </div>
                    </Link>
                </div>

                <!-- Recent requests + Quick actions -->
                <div class="mt-8 grid gap-6 xl:grid-cols-[1.4fr_0.8fr]">
                    <!-- Recent requests table -->
                    <section class="overflow-hidden rounded-2xl border border-orange-200 bg-white shadow-lg shadow-orange-100/60">
                        <div class="border-b border-orange-200 bg-gradient-to-r from-orange-100 via-white to-amber-100 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900">My Recent Requests</h2>
                            <p class="mt-1 text-sm text-slate-600">Your 5 most recently submitted service requests.</p>
                        </div>

                        <div class="divide-y divide-gray-100">
                            <p v-if="recentRequests.length === 0" class="py-10 text-center text-sm text-gray-400">No requests submitted yet.</p>

                            <article
                                v-for="req in recentRequests"
                                :key="req.id"
                                class="flex flex-col gap-3 px-6 py-5 transition-colors hover:bg-orange-50/60 md:flex-row md:items-center md:justify-between"
                            >
                                <div class="space-y-1.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-base font-semibold text-slate-900">{{ req.equipment_name }}</h3>
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                                priorityClass(req.priority),
                                            ]"
                                        >
                                            {{ req.priority }}
                                        </span>
                                    </div>
                                    <p v-if="req.control_no" class="text-xs text-gray-400">Control #{{ req.control_no }}</p>
                                    <p class="text-xs text-gray-400">{{ formatDateTime(req.requested_at) }}</p>
                                    <p v-if="req.accepted_by" class="text-xs text-gray-500">
                                        Assigned to: <span class="font-medium text-gray-700">{{ req.accepted_by }}</span>
                                    </p>
                                </div>

                                <span
                                    :class="[
                                        'inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset',
                                        statusClass(req.status),
                                    ]"
                                >
                                    {{ req.status }}
                                </span>
                            </article>
                        </div>

                        <div class="border-t border-orange-100 bg-orange-50/40 px-6 py-3">
                            <Link href="/request-history" class="text-sm font-medium text-orange-600 hover:text-orange-800">
                                View full history →
                            </Link>
                        </div>
                    </section>

                    <!-- Quick links / info panel -->
                    <aside class="space-y-4">
                        <!-- Quick actions -->
                        <div class="overflow-hidden rounded-2xl border border-orange-200 bg-white shadow-lg shadow-orange-100/60">
                            <div class="border-b border-orange-200 bg-gradient-to-r from-orange-100 via-white to-amber-100 px-6 py-4">
                                <h2 class="text-lg font-semibold text-slate-900">Quick Actions</h2>
                            </div>
                            <div class="divide-y divide-gray-100">
                                <Link
                                    href="/request-service"
                                    class="flex items-center gap-3 px-6 py-4 text-sm font-medium text-gray-700 transition-colors hover:bg-orange-50"
                                >
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                        <ClipboardList class="h-4 w-4" />
                                    </span>
                                    Submit a New Service Request
                                </Link>
                                <Link
                                    href="/Inventory"
                                    class="flex items-center gap-3 px-6 py-4 text-sm font-medium text-gray-700 transition-colors hover:bg-orange-50"
                                >
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                        <Package class="h-4 w-4" />
                                    </span>
                                    View My Equipment
                                </Link>
                                <Link
                                    href="/request-history"
                                    class="flex items-center gap-3 px-6 py-4 text-sm font-medium text-gray-700 transition-colors hover:bg-orange-50"
                                >
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                        <Activity class="h-4 w-4" />
                                    </span>
                                    Track My Request History
                                </Link>
                            </div>
                        </div>

                        <!-- Status legend -->
                        <div class="overflow-hidden rounded-2xl border border-orange-200 bg-white shadow-lg shadow-orange-100/60">
                            <div class="border-b border-orange-200 bg-gradient-to-r from-orange-100 via-white to-amber-100 px-6 py-4">
                                <h2 class="text-lg font-semibold text-slate-900">Request Status Guide</h2>
                            </div>
                            <div class="space-y-3 px-6 py-4">
                                <div class="flex items-center gap-3 text-sm">
                                    <span
                                        class="inline-flex rounded-full bg-amber-100 px-3 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200"
                                        >Pending</span
                                    >
                                    <span class="text-gray-600">Waiting for biomed team action</span>
                                </div>
                                <div class="flex items-center gap-3 text-sm">
                                    <span
                                        class="inline-flex rounded-full bg-sky-100 px-3 py-0.5 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200"
                                        >Accepted</span
                                    >
                                    <span class="text-gray-600">Assigned to a technician</span>
                                </div>
                                <div class="flex items-center gap-3 text-sm">
                                    <span
                                        class="inline-flex rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200"
                                        >Completed</span
                                    >
                                    <span class="text-gray-600">Issue resolved and closed</span>
                                </div>
                                <div class="flex items-center gap-3 text-sm">
                                    <span
                                        class="inline-flex rounded-full bg-red-100 px-3 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-200"
                                        >Rejected</span
                                    >
                                    <span class="text-gray-600">Request was declined</span>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
