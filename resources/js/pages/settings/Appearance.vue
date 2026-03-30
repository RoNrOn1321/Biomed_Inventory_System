<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: '/settings/appearance',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Appearance settings" />

        <SettingsLayout>
            <div class="space-y-8">
                <div
                    class="rounded-2xl border border-orange-200 bg-gradient-to-r from-orange-50 via-white to-amber-100 p-6 shadow-sm shadow-orange-100"
                >
                    <HeadingSmall title="Appearance settings" description="Choose a display mode that matches the Inventory workspace" />

                    <div class="mt-5 grid gap-4 text-sm text-slate-600 md:grid-cols-3">
                        <div class="rounded-xl border border-orange-200 bg-white/90 p-4">
                            <p class="font-semibold text-slate-900">Inventory palette</p>
                            <p class="mt-1">Warm orange headers, amber highlights, and clean white panels stay consistent across modules.</p>
                        </div>
                        <div class="rounded-xl border border-orange-200 bg-white/90 p-4">
                            <p class="font-semibold text-slate-900">Comfortable contrast</p>
                            <p class="mt-1">Each mode keeps controls readable while preserving the app’s branding and visual hierarchy.</p>
                        </div>
                        <div class="rounded-xl border border-orange-200 bg-white/90 p-4">
                            <p class="font-semibold text-slate-900">Device-aware option</p>
                            <p class="mt-1">Use System if you want the interface to follow your operating system automatically.</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-orange-100 bg-white p-6 shadow-sm shadow-orange-100">
                    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Theme selection</h3>
                            <p class="text-sm text-slate-500">
                                Switch between light, dark, or system mode using the same color language as Inventory.
                            </p>
                        </div>
                        <span
                            class="inline-flex rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-orange-700"
                        >
                            Display preferences
                        </span>
                    </div>

                    <AppearanceTabs />
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
