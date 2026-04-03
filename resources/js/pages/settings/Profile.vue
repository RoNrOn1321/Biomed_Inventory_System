<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    className?: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user as User);
const uploadedPreviewUrl = ref<string | null>(null);

const form = useForm({
    name: user.value.name,
    email: user.value.email,
    avatar: null as File | null,
});

const avatarSrc = computed(() => uploadedPreviewUrl.value || user.value.avatar || '');
const initials = computed(() =>
    user.value.name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase(),
);

const revokePreviewUrl = () => {
    if (uploadedPreviewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(uploadedPreviewUrl.value);
    }
};

const updateAvatar = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    revokePreviewUrl();
    form.avatar = file;
    uploadedPreviewUrl.value = file ? URL.createObjectURL(file) : user.value.avatar || null;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post(route('profile.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('avatar');
            revokePreviewUrl();
            uploadedPreviewUrl.value = null;
        },
    });
};

onBeforeUnmount(() => {
    revokePreviewUrl();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-8">
                <div
                    class="rounded-2xl border border-orange-200 bg-gradient-to-r from-orange-50 via-white to-amber-100 p-6 shadow-sm shadow-orange-100"
                >
                    <HeadingSmall title="Profile information" description="Update your name, email address, and profile photo" />

                    <div class="mt-5 flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                        <div class="flex items-center gap-4">
                            <Avatar class="size-20 overflow-hidden border-4 border-white shadow-md shadow-orange-200">
                                <AvatarImage v-if="avatarSrc" :src="avatarSrc" :alt="user.name" />
                                <AvatarFallback class="bg-orange-100 text-lg font-semibold text-orange-700">
                                    {{ initials }}
                                </AvatarFallback>
                            </Avatar>

                            <div>
                                <p class="text-base font-semibold text-slate-900">Profile photo</p>
                                <p class="text-sm text-slate-600">Upload a JPG, PNG, or WEBP image up to 2 MB.</p>
                            </div>
                        </div>

                        <label
                            for="avatar"
                            class="inline-flex cursor-pointer items-center rounded-xl border border-orange-300 bg-white px-4 py-2.5 text-sm font-semibold text-orange-700 transition hover:border-orange-400 hover:bg-orange-50"
                        >
                            Choose image
                        </label>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6 rounded-2xl border border-orange-100 bg-white p-6 shadow-sm shadow-orange-100">
                    <div class="grid gap-2">
                        <Label for="avatar">Profile photo</Label>
                        <input id="avatar" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="updateAvatar" />
                        <div class="rounded-xl border border-dashed border-orange-200 bg-orange-50/70 p-4 text-sm text-slate-600">
                            Select an image to replace your current profile photo across the app header and account menus.
                        </div>
                        <InputError class="mt-2" :message="form.errors.avatar" />
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input id="name" class="mt-1 block w-full" v-model="form.name" required autocomplete="name" placeholder="Full name" />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.email"
                                required
                                autocomplete="username"
                                placeholder="Email address"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="mt-2 text-sm text-neutral-800">
                            Your email address is unverified.
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="focus:outline-hidden rounded-md text-sm text-neutral-600 underline hover:text-neutral-900 focus:ring-2 focus:ring-offset-2"
                            >
                                Click here to re-send the verification email.
                            </Link>
                        </p>

                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            A new verification link has been sent to your email address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <p v-if="form.progress" class="text-sm text-orange-700">Uploading {{ form.progress.percentage }}%</p>

                        <TransitionRoot
                            :show="form.recentlySuccessful"
                            enter="transition ease-in-out"
                            enter-from="opacity-0"
                            leave="transition ease-in-out"
                            leave-to="opacity-0"
                        >
                            <p class="text-sm text-neutral-600">Saved.</p>
                        </TransitionRoot>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
