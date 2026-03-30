<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';

interface Props {
    class?: string;
}

const { class: containerClass = '' } = defineProps<Props>();

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light', description: 'Bright workspace for daytime use.' },
    { value: 'dark', Icon: Moon, label: 'Dark', description: 'Low-glare mode for focused work.' },
    { value: 'system', Icon: Monitor, label: 'System', description: 'Matches your device preference.' },
] as const;
</script>

<template>
    <div :class="['grid gap-4 md:grid-cols-3', containerClass]">
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            @click="updateAppearance(value)"
            :class="[
                'group rounded-2xl border p-5 text-left transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2',
                appearance === value
                    ? 'border-orange-300 bg-gradient-to-br from-orange-500 to-amber-500 text-white shadow-lg shadow-orange-200'
                    : 'border-orange-100 bg-white text-slate-700 shadow-sm shadow-orange-100 hover:-translate-y-0.5 hover:border-orange-200 hover:bg-orange-50/80 hover:shadow-md',
            ]"
        >
            <div class="mb-4 flex items-center justify-between">
                <div
                    :class="[
                        'flex h-11 w-11 items-center justify-center rounded-xl border transition-colors',
                        appearance === value
                            ? 'border-white/30 bg-white/15 text-white'
                            : 'border-orange-200 bg-orange-100 text-orange-700 group-hover:bg-orange-200',
                    ]"
                >
                    <component :is="Icon" class="h-5 w-5" />
                </div>

                <span
                    :class="[
                        'rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.18em]',
                        appearance === value ? 'bg-white/15 text-white' : 'bg-orange-100 text-orange-700',
                    ]"
                >
                    {{ appearance === value ? 'Active' : 'Select' }}
                </span>
            </div>

            <div class="space-y-2">
                <p class="text-base font-semibold">
                    {{ label }}
                </p>
                <p :class="appearance === value ? 'text-sm text-orange-50/90' : 'text-sm text-slate-500'">
                    {{ tabs.find((tab) => tab.value === value)?.description }}
                </p>
            </div>

            <div class="mt-5 grid grid-cols-3 gap-2">
                <span
                    v-for="index in 3"
                    :key="`${value}-${index}`"
                    :class="[
                        'h-10 rounded-lg border',
                        appearance === value
                            ? 'bg-white/12 border-white/20'
                            : value === 'dark'
                              ? 'border-slate-700 bg-slate-800'
                              : value === 'light'
                                ? 'border-orange-200 bg-orange-100'
                                : 'border-orange-200 bg-gradient-to-br from-orange-100 to-slate-200',
                    ]"
                />
            </div>
        </button>
    </div>
</template>
