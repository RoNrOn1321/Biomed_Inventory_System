<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { Activity, ClipboardList, Package, Users, Wrench, BellRing } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface DashboardStats {
    equipment_count: number;
    pending_job_requests: number;
    accepted_job_requests: number;
    biomed_technicians: number;
}

interface RecentRequest {
    id: number;
    requester_name: string;
    department: string | null;
    equipment_name: string;
    priority: 'Low' | 'Medium' | 'High' | 'Urgent';
    requested_at: string | null;
    location?: string | null;
    end_user?: string | null;
}

const props = defineProps<{
    stats: DashboardStats;
    recentRequests: RecentRequest[];
}>();

const page = usePage<any>();
const canAcceptRequests = computed(() => ['Admin', 'Biomed_Technician'].includes(page.props.auth?.user?.account_type));

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
        router.reload({ only: ['stats', 'recentRequests'] });
        
        setTimeout(() => {
            showToast.value = false;
        }, 5000);
    });
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const metricCards = [
    {
        title: 'Equipment Registered',
        value: props.stats.equipment_count,
        description: 'Total inventory assets tracked in the system.',
        icon: Package,
        className: 'border-orange-300 bg-gradient-to-br from-orange-500 to-amber-400 text-white shadow-md shadow-orange-200',
        valueClass: 'text-white',
        descriptionClass: 'text-orange-50/90',
        iconClass: 'text-white/90',
    },
    {
        title: 'Pending Job Requests',
        value: props.stats.pending_job_requests,
        description: 'Open service requests waiting for acceptance.',
        icon: ClipboardList,
        className: 'border-amber-300 bg-gradient-to-br from-amber-400 to-yellow-300 text-amber-950 shadow-md shadow-amber-200',
        valueClass: 'text-amber-950',
        descriptionClass: 'text-amber-900/80',
        iconClass: 'text-amber-900',
    },
    {
        title: 'Accepted Requests',
        value: props.stats.accepted_job_requests,
        description: 'Requests already assigned to the biomed team.',
        icon: Activity,
        className: 'border-sky-300 bg-gradient-to-br from-sky-500 to-blue-500 text-white shadow-md shadow-sky-200',
        valueClass: 'text-white',
        descriptionClass: 'text-sky-50/90',
        iconClass: 'text-white/90',
    },
    {
        title: 'Biomed Technicians',
        value: props.stats.biomed_technicians,
        description: 'Accounts currently assigned to technical support.',
        icon: Users,
        className: 'border-emerald-300 bg-gradient-to-br from-emerald-500 to-green-400 text-white shadow-md shadow-emerald-200',
        valueClass: 'text-white',
        descriptionClass: 'text-emerald-50/90',
        iconClass: 'text-white/90',
    },
];

const formatDateTime = (value: string | null) => {
    if (!value) {
        return 'Not scheduled';
    }

    return new Date(value).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

const priorityClass = (priority: RecentRequest['priority']) => {
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
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-[linear-gradient(180deg,_#fffaf5_0%,_#ffffff_30%,_#fffaf3_100%)]">
            <section class="border-b-4 border-orange-400 bg-gradient-to-r from-orange-50 to-orange-100 px-4 py-5 shadow-md">
                <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                            <p class="text-sm font-medium text-orange-700">
                                Monitor equipment activity, job requests, and biomed workload from one place.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Button as-child class="bg-orange-600 text-white hover:bg-orange-700">
                                <Link href="/JobRequests">
                                    <Wrench class="mr-2 h-4 w-4" />
                                    Review Job Requests
                                </Link>
                            </Button>
                            <Button as-child variant="secondary" class="border-orange-200 bg-white text-slate-700 hover:bg-orange-50">
                                <Link href="/Inventory">
                                    <Package class="mr-2 h-4 w-4" />
                                    Open Inventory
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto px-4 py-8 sm:px-6 lg:px-8">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article v-for="card in metricCards" :key="card.title" :class="['rounded-2xl border p-5 shadow-sm', card.className]">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] opacity-95">{{ card.title }}</p>
                                <p :class="['mt-3 text-3xl font-bold', card.valueClass]">{{ card.value }}</p>
                                <p :class="['mt-2 text-sm', card.descriptionClass]">{{ card.description }}</p>
                            </div>
                            <component :is="card.icon" :class="['h-8 w-8', card.iconClass]" />
                        </div>
                    </article>
                </div>

                <div class="mt-8 grid gap-6 xl:grid-cols-[1.4fr_0.9fr]">
                    <section class="overflow-hidden rounded-2xl border border-orange-200 bg-white shadow-lg shadow-orange-100/60">
                        <div class="border-b border-orange-200 bg-gradient-to-r from-orange-100 via-white to-amber-100 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900">Pending Job Requests</h2>
                            <p class="mt-1 text-sm text-slate-600">Recent requests that still need action from the biomed team.</p>
                        </div>

                        <div class="divide-y divide-gray-100">
                            <article
                                v-for="request in props.recentRequests"
                                :key="request.id"
                                class="flex flex-col gap-4 px-6 py-5 transition-colors hover:bg-orange-50/60 md:flex-row md:items-center md:justify-between"
                            >
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <h3 class="text-base font-semibold text-slate-900">{{ request.equipment_name }}</h3>
                                        <span
                                            :class="[
                                                'inline-flex rounded-full border px-3 py-1 text-xs font-semibold',
                                                priorityClass(request.priority),
                                            ]"
                                        >
                                            {{ request.priority }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-600">
                                        {{ request.requester_name }} • {{ request.department || 'No department listed' }}
                                    </p>
                                    <p class="text-xs text-slate-500">Requested {{ formatDateTime(request.requested_at) }}</p>
                                    <p class="text-xs text-slate-500">Location: {{ request.location || 'No location listed' }}</p>
                                    <p class="text-xs text-slate-500">End User: {{ request.end_user || 'No end user listed' }}</p>
                                </div>

                                <Button as-child class="bg-orange-600 text-white hover:bg-orange-700">
                                    <Link href="/JobRequests">Open queue</Link>
                                </Button>
                            </article>

                            <div v-if="props.recentRequests.length === 0" class="px-6 py-12 text-center">
                                <p class="text-lg font-semibold text-slate-900">No pending requests</p>
                                <p class="mt-2 text-sm text-slate-600">Accepted requests will move out of the queue automatically.</p>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-2xl border border-orange-300 bg-gradient-to-br from-orange-100 via-amber-50 to-white p-6 shadow-sm shadow-orange-100/70"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-800">Daily Focus</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Keep preventive maintenance and urgent support visible.</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Use this dashboard to watch open requests, jump into inventory records, and keep the technical team aligned on what needs
                            immediate attention.
                        </p>

                        <div class="mt-6 space-y-3">
                            <div class="rounded-xl border border-orange-200 bg-white p-4 shadow-sm">
                                <p class="font-semibold text-slate-900">Priority handling</p>
                                <p class="mt-1 text-sm text-slate-600">
                                    Urgent and high-priority requests should be accepted from the Job Requests queue first.
                                </p>
                            </div>
                            <div class="rounded-xl border border-orange-200 bg-white p-4 shadow-sm">
                                <p class="font-semibold text-slate-900">Inventory visibility</p>
                                <p class="mt-1 text-sm text-slate-600">Cross-check the affected equipment record before dispatching service work.</p>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
            
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
        </div>
    </AppLayout>
</template>
