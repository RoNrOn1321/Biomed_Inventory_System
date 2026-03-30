<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: '/settings/profile',
    },
    {
        title: 'Password',
        href: '/settings/password',
    },
    {
        title: 'Appearance',
        href: '/settings/appearance',
    },
];

const currentPath = window.location.pathname;
</script>

<template>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-3xl border border-orange-200 bg-white shadow-xl shadow-orange-100/60">
            <div class="border-b-4 border-orange-400 bg-gradient-to-r from-orange-50 via-white to-amber-100 px-6 py-6 sm:px-8">
                <Heading title="Settings" description="Manage your profile and account settings" />
            </div>

            <div class="flex flex-col gap-8 bg-gradient-to-br from-white via-orange-50/40 to-amber-50/70 px-6 py-6 sm:px-8 lg:flex-row lg:gap-10">
                <aside class="w-full lg:max-w-xs">
                    <div class="rounded-2xl border border-orange-200 bg-white/90 p-3 shadow-sm shadow-orange-100">
                        <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.22em] text-orange-700">Preferences</p>

                        <nav class="flex flex-col gap-2">
                            <Button
                                v-for="item in sidebarNavItems"
                                :key="item.href"
                                variant="ghost"
                                :class="[
                                    'h-auto w-full justify-start rounded-xl border px-4 py-3 text-left transition-all duration-200',
                                    currentPath === item.href
                                        ? 'border-orange-300 bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-md shadow-orange-200 hover:from-orange-600 hover:to-amber-600'
                                        : 'border-transparent bg-orange-50/70 text-slate-700 hover:border-orange-200 hover:bg-orange-100/80 hover:text-orange-900',
                                ]"
                                as-child
                            >
                                <Link :href="item.href">
                                    {{ item.title }}
                                </Link>
                            </Button>
                        </nav>
                    </div>
                </aside>

                <Separator class="bg-orange-200 md:hidden" />

                <div class="flex-1">
                    <section class="rounded-2xl border border-orange-100 bg-white/95 p-6 shadow-sm shadow-orange-100 sm:p-8">
                        <slot />
                    </section>
                </div>
            </div>
        </section>
    </div>
</template>
