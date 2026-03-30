<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ManagedUser extends Pick<User, 'id' | 'name' | 'email' | 'account_type' | 'avatar'> {
    created_at: string | null;
}

const props = defineProps<{
    users: ManagedUser[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manage Accounts',
        href: '/manage-accounts',
    },
];

const page = usePage<SharedData>();
const currentUserId = computed(() => Number(page.props.auth.user.id));
const isAdmin = computed(() => page.props.auth.user.account_type === 'Admin');
const accountTypes: ManagedUser['account_type'][] = ['End_User', 'Biomed_Technician', 'Admin'];
const roleFilters = ['All', 'Biomed_Technician', 'End_User', 'Admin'] as const;
const selectedRole = ref<(typeof roleFilters)[number]>('All');
const search = ref('');
const passwordDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedTechnician = ref<ManagedUser | null>(null);

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

const deleteForm = useForm({});

const filteredUsers = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.users.filter((user) => {
        const matchesRole = selectedRole.value === 'All' || user.account_type === selectedRole.value;

        if (!matchesRole) {
            return false;
        }

        if (!query) {
            return true;
        }

        return [user.name, user.email, user.account_type].some((value) => value.toLowerCase().includes(query));
    });
});
const filteredCount = computed(() => filteredUsers.value.length);

const updateAccountType = (userId: number, event: Event) => {
    const value = (event.target as HTMLSelectElement).value as ManagedUser['account_type'];

    router.put(
        `/manage-accounts/${userId}`,
        { account_type: value },
        {
            preserveScroll: true,
        },
    );
};

const canManageTechnician = (user: ManagedUser) => isAdmin.value && user.account_type === 'Biomed_Technician';

const openPasswordDialog = (user: ManagedUser) => {
    selectedTechnician.value = user;
    passwordForm.reset();
    passwordForm.clearErrors();
    passwordDialogOpen.value = true;
};

const closePasswordDialog = () => {
    passwordDialogOpen.value = false;
    passwordForm.reset();
    passwordForm.clearErrors();
    selectedTechnician.value = null;
};

const submitPasswordUpdate = () => {
    if (!selectedTechnician.value) {
        return;
    }

    passwordForm.put(`/manage-accounts/${selectedTechnician.value.id}/password`, {
        preserveScroll: true,
        onSuccess: () => closePasswordDialog(),
    });
};

const openDeleteDialog = (user: ManagedUser) => {
    selectedTechnician.value = user;
    deleteDialogOpen.value = true;
};

const closeDeleteDialog = () => {
    deleteDialogOpen.value = false;
    deleteForm.clearErrors();
    selectedTechnician.value = null;
};

const submitDelete = () => {
    if (!selectedTechnician.value) {
        return;
    }

    deleteForm.delete(`/manage-accounts/${selectedTechnician.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeDeleteDialog(),
    });
};

const badgeClass = (accountType: ManagedUser['account_type']) => {
    if (accountType === 'Admin') {
        return 'bg-red-100 text-red-700 border-red-200';
    }

    if (accountType === 'Biomed_Technician') {
        return 'bg-blue-100 text-blue-700 border-blue-200';
    }

    return 'bg-orange-100 text-orange-700 border-orange-200';
};

const initials = (name: string) =>
    name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
</script>

<template>
    <Head title="Manage Accounts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white">
            <section class="border-b-4 border-orange-400 bg-gradient-to-r from-orange-50 to-orange-100 px-4 py-5 shadow-md">
                <div class="mx-auto flex max-w-7xl flex-col gap-3 sm:px-6 lg:px-8">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Manage Accounts</h1>
                        <p class="text-sm font-medium text-orange-700">Review registered users and assign the correct account type.</p>
                    </div>
                </div>
            </section>

            <section class="mx-auto px-4 py-8 sm:px-6 lg:px-8">
                <div class="mb-6 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-orange-200 bg-orange-50 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-700">Default Access</p>
                        <p class="mt-2 text-sm text-slate-700">New registrations start as <span class="font-semibold">End_User</span>.</p>
                    </div>
                    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Technical Role</p>
                        <p class="mt-2 text-sm text-slate-700">
                            Use <span class="font-semibold">Biomed_Technician</span> for users handling equipment workflows.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-700">Administrative Role</p>
                        <p class="mt-2 text-sm text-slate-700">Assign <span class="font-semibold">Admin</span> only to trusted administrators.</p>
                    </div>
                </div>

                <div class="mb-6 rounded-2xl border border-orange-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-700">Role Filter</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-900">
                                Showing {{ filteredCount }} account{{ filteredCount === 1 ? '' : 's' }}
                            </h2>
                            <p class="text-sm text-slate-600">
                                The list defaults to Biomed technicians so admin actions stay focused on the technical team.
                            </p>
                        </div>

                        <div class="grid gap-4 md:w-[32rem] md:grid-cols-[minmax(0,1fr)_12rem]">
                            <div>
                                <label for="accountSearch" class="mb-2 block text-sm font-medium text-slate-700">Search account</label>
                                <div class="relative">
                                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-orange-500" />
                                    <Input
                                        id="accountSearch"
                                        v-model="search"
                                        type="text"
                                        placeholder="Search by name, email, or role"
                                        class="h-12 rounded-xl border-orange-200 bg-white pl-10 text-slate-700 placeholder:text-slate-400 focus-visible:ring-orange-200"
                                    />
                                </div>
                            </div>

                            <div>
                                <label for="roleFilter" class="mb-2 block text-sm font-medium text-slate-700">Filter by role</label>
                                <select
                                    id="roleFilter"
                                    v-model="selectedRole"
                                    class="h-12 w-full rounded-xl border border-orange-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                >
                                    <option v-for="roleFilter in roleFilters" :key="roleFilter" :value="roleFilter">
                                        {{ roleFilter }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lg">
                    <div class="border-b border-orange-100 bg-gradient-to-r from-white to-orange-50 px-6 py-4">
                        <h2 class="text-lg font-semibold text-slate-900">User Accounts</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-orange-500 to-amber-500">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-white">User</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-white">Email</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-white">Current Type</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-white">Created</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-white">Update Type</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-white">
                                        Technician Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-orange-50/60">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border border-orange-200 bg-orange-100 text-sm font-semibold text-orange-700"
                                            >
                                                <img v-if="user.avatar" :src="user.avatar" :alt="user.name" class="h-full w-full object-cover" />
                                                <span v-else>{{ initials(user.name) }}</span>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-900">{{ user.name }}</p>
                                                <p v-if="currentUserId === user.id" class="text-xs font-medium text-orange-700">Current account</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ user.email }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            :class="[
                                                'inline-flex rounded-full border px-3 py-1 text-xs font-semibold',
                                                badgeClass(user.account_type),
                                            ]"
                                        >
                                            {{ user.account_type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ user.created_at || 'N/A' }}</td>
                                    <td class="px-6 py-4">
                                        <select
                                            :value="user.account_type"
                                            class="rounded-lg border border-orange-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                            :disabled="!isAdmin"
                                            @change="updateAccountType(user.id, $event)"
                                        >
                                            <option v-for="accountType in accountTypes" :key="accountType" :value="accountType">
                                                {{ accountType }}
                                            </option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="canManageTechnician(user)" class="flex flex-wrap gap-2">
                                            <Button type="button" class="bg-blue-600 text-white hover:bg-blue-700" @click="openPasswordDialog(user)">
                                                Change password
                                            </Button>
                                            <Button type="button" variant="destructive" @click="openDeleteDialog(user)">Delete</Button>
                                        </div>
                                        <p v-else class="text-sm text-slate-400">Technician-only action</p>
                                    </td>
                                </tr>
                                <tr v-if="filteredUsers.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">
                                        No accounts found for the selected role.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        <Dialog v-model:open="passwordDialogOpen">
            <DialogContent class="overflow-hidden border-orange-200 bg-white p-0 shadow-2xl shadow-orange-200/60 sm:max-w-xl sm:rounded-2xl">
                <form class="space-y-6" @submit.prevent="submitPasswordUpdate">
                    <DialogHeader class="space-y-3 border-b-4 border-orange-400 bg-gradient-to-r from-orange-50 via-white to-amber-100 px-6 py-5">
                        <DialogTitle>Change technician password</DialogTitle>
                        <DialogDescription class="text-slate-600">
                            Set a new password for {{ selectedTechnician?.name || 'this technician' }}. This action is limited to Admin accounts.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-5 px-6 pb-6">
                        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-700">
                            Update the technician password here without changing any other account settings.
                        </div>

                        <div class="grid gap-4">
                            <div class="grid gap-2">
                                <label for="password" class="text-sm font-medium text-slate-700">New password</label>
                                <Input
                                    id="password"
                                    v-model="passwordForm.password"
                                    type="password"
                                    autocomplete="new-password"
                                    placeholder="New password"
                                />
                                <InputError :message="passwordForm.errors.password" />
                            </div>

                            <div class="grid gap-2">
                                <label for="password_confirmation" class="text-sm font-medium text-slate-700">Confirm password</label>
                                <Input
                                    id="password_confirmation"
                                    v-model="passwordForm.password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    placeholder="Confirm password"
                                />
                            </div>
                        </div>

                        <DialogFooter class="border-t border-orange-100 pt-5">
                            <DialogClose as-child>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    class="border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                                    @click="closePasswordDialog"
                                >
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button type="submit" class="bg-orange-600 text-white hover:bg-orange-700" :disabled="passwordForm.processing">
                                Update password
                            </Button>
                        </DialogFooter>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="overflow-hidden border-red-200 bg-white p-0 shadow-2xl shadow-red-200/60 sm:max-w-xl sm:rounded-2xl">
                <form class="space-y-6" @submit.prevent="submitDelete">
                    <DialogHeader class="space-y-3 border-b-4 border-red-400 bg-gradient-to-r from-red-50 via-white to-orange-50 px-6 py-5">
                        <DialogTitle>Delete technician account</DialogTitle>
                        <DialogDescription class="text-slate-600">
                            Permanently remove {{ selectedTechnician?.name || 'this technician' }} from the system. This cannot be undone.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-5 px-6 pb-6">
                        <div class="rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-700">
                            Only technician accounts can be deleted from this screen.
                        </div>

                        <DialogFooter class="border-t border-red-100 pt-5">
                            <DialogClose as-child>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    class="border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                                    @click="closeDeleteDialog"
                                >
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button type="submit" variant="destructive" :disabled="deleteForm.processing">Delete technician</Button>
                        </DialogFooter>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
