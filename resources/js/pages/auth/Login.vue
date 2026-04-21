<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Lock, Mail } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="flex min-h-screen bg-gradient-to-br from-orange-50 via-white to-amber-50">
        <!-- Left panel – branding -->
        <div class="hidden flex-col items-center justify-center bg-gradient-to-br from-orange-600 to-amber-500 px-12 lg:flex lg:w-5/12">
            <div class="flex flex-col items-center text-center">
                <div class="mb-8 flex h-48 w-48 items-center justify-center overflow-hidden rounded-full border-4 border-white/30 shadow-2xl">
                    <img src="/logo.JPG" alt="ASTMMC Logo" class="h-full w-full object-cover" />
                </div>
                <h1 class="text-2xl font-bold leading-tight text-white drop-shadow">
                    Adela Serra Ty<br />Memorial Medical Center
                </h1>
                <p class="mt-2 text-sm font-medium text-orange-100">Department of Health · Tandag City, Surigao del Sur</p>
                <div class="mt-10 w-full max-w-xs rounded-2xl border border-white/20 bg-white/10 p-5 text-left backdrop-blur-sm">
                    <p class="text-xs font-semibold uppercase tracking-widest text-orange-100">Biomedical Inventory System</p>
                    <p class="mt-2 text-sm leading-relaxed text-white/90">
                        Manage equipment, service requests, and calibration records all in one place.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right panel – form -->
        <div class="flex flex-1 flex-col items-center justify-center px-6 py-12">
            <!-- Mobile logo -->
            <div class="mb-8 flex flex-col items-center lg:hidden">
                <div class="mb-4 flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border-4 border-orange-200 shadow-lg">
                    <img src="/logo.JPG" alt="ASTMMC Logo" class="h-full w-full object-cover" />
                </div>
                <h2 class="text-lg font-bold text-orange-700">Adela Serra Ty Memorial Medical Center</h2>
                <p class="text-xs text-orange-500">Biomedical Inventory System</p>
            </div>

            <div class="w-full max-w-md">
                <!-- Card -->
                <div class="rounded-2xl border border-orange-200 bg-white px-8 py-10 shadow-xl shadow-orange-100/60">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-slate-900">Welcome back</h2>
                        <p class="mt-1 text-sm text-slate-500">Sign in to your account to continue</p>
                    </div>

                    <!-- Status message -->
                    <div v-if="status" class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-700 ring-1 ring-green-200">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Email -->
                        <div class="space-y-1.5">
                            <Label for="email" class="text-sm font-medium text-slate-700">Email address</Label>
                            <div class="relative">
                                <Mail class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-orange-400" />
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    autofocus
                                    tabindex="1"
                                    autocomplete="email"
                                    v-model="form.email"
                                    placeholder="email@example.com"
                                    class="pl-9 border-orange-200 focus:border-orange-500 focus:ring-orange-500"
                                />
                            </div>
                            <InputError :message="form.errors.email" />
                        </div>

                        <!-- Password -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <Label for="password" class="text-sm font-medium text-slate-700">Password</Label>
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="text-xs font-medium text-orange-600 hover:text-orange-700"
                                    tabindex="5"
                                >
                                    Forgot password?
                                </Link>
                            </div>
                            <div class="relative">
                                <Lock class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-orange-400" />
                                <Input
                                    id="password"
                                    type="password"
                                    required
                                    tabindex="2"
                                    autocomplete="current-password"
                                    v-model="form.password"
                                    placeholder="••••••••"
                                    class="pl-9 border-orange-200 focus:border-orange-500 focus:ring-orange-500"
                                />
                            </div>
                            <InputError :message="form.errors.password" />
                        </div>

                        <!-- Remember me -->
                        <div class="flex items-center space-x-2" tabindex="3">
                            <Checkbox
                                id="remember"
                                v-model:checked="form.remember"
                                tabindex="4"
                                class="border-orange-300 data-[state=checked]:border-orange-600 data-[state=checked]:bg-orange-600"
                            />
                            <Label for="remember" class="text-sm text-slate-600">Remember me</Label>
                        </div>

                        <!-- Submit -->
                        <Button
                            type="submit"
                            tabindex="4"
                            :disabled="form.processing"
                            class="w-full bg-gradient-to-r from-orange-600 to-amber-500 py-2.5 text-sm font-semibold text-white shadow-md hover:from-orange-700 hover:to-amber-600 disabled:opacity-60"
                        >
                            <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            Sign in
                        </Button>
                    </form>
                </div>

                <p class="mt-6 text-center text-xs text-slate-400">
                    HOPSS-EFMS-BIO · Biomedical Engineering Section
                </p>
            </div>
        </div>
    </div>
</template>
