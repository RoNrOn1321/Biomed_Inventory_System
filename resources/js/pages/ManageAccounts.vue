<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Plus, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const toastVisible = ref(false);
const toastMessage = ref('');
const toastType = ref<'success' | 'error'>('success');
let toastTimeout: ReturnType<typeof setTimeout>;
const showToast = (message: string, type: 'success' | 'error' = 'success') => {
    toastMessage.value = message;
    toastType.value = type;
    toastVisible.value = true;
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        toastVisible.value = false;
    }, 3500);
};

interface ManagedUser extends Pick<User, 'id' | 'name' | 'email' | 'account_type' | 'avatar'> {
    created_at: string | null;
    department: string | null;
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
const isModerator = computed(() => page.props.auth.user.account_type === 'Moderator');
const canManageAccounts = computed(() => isAdmin.value || isModerator.value);
const accountTypes: ManagedUser['account_type'][] = ['End_User', 'Biomed_Technician', 'Admin', 'Moderator'];
const creatableAccountTypes = computed<ManagedUser['account_type'][]>(() =>
    isAdmin.value ? ['End_User', 'Biomed_Technician', 'Admin', 'Moderator'] : ['End_User', 'Biomed_Technician', 'Moderator'],
);
const roleFilters = ['All', 'Biomed_Technician', 'End_User', 'Admin', 'Moderator'] as const;
const selectedRole = ref<(typeof roleFilters)[number]>('All');
const search = ref('');
const passwordDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const editDialogOpen = ref(false);
const selectedTechnician = ref<ManagedUser | null>(null);

const editForm = useForm({
    name: '',
    department: '',
});

const openEditDialog = (user: ManagedUser) => {
    selectedTechnician.value = user;
    editForm.name = user.name;
    editForm.department = user.department ?? '';
    editForm.clearErrors();
    editDialogOpen.value = true;
};

const closeEditDialog = () => {
    editDialogOpen.value = false;
    editForm.reset();
    editForm.clearErrors();
    selectedTechnician.value = null;
};

const submitEdit = () => {
    if (!selectedTechnician.value) return;
    editForm.patch(`/manage-accounts/${selectedTechnician.value.id}/profile`, {
        preserveScroll: true,
        onSuccess: () => closeEditDialog(),
    });
};

const createDialogOpen = ref(false);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    account_type: 'End_User' as ManagedUser['account_type'],
    department: '',
});

const openCreateDialog = () => {
    createForm.reset();
    createForm.clearErrors();
    createDialogOpen.value = true;
};

const closeCreateDialog = () => {
    createDialogOpen.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const submitCreate = () => {
    createForm.post('/manage-accounts', {
        preserveScroll: true,
        onSuccess: () => closeCreateDialog(),
    });
};

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

const canManageTechnician = (user: ManagedUser) => canManageAccounts.value && user.id !== currentUserId.value;

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
    if (!isAdmin.value) {
        showToast('Only Admins can delete accounts.', 'error');
        return;
    }
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

    if (accountType === 'Moderator') {
        return 'bg-purple-100 text-purple-700 border-purple-200';
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
                <div class="mb-6 grid gap-4 md:grid-cols-4">
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
                    <div class="rounded-2xl border border-purple-200 bg-purple-50 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-purple-700">Moderator Role</p>
                        <p class="mt-2 text-sm text-slate-700">
                            Assign <span class="font-semibold">Moderator</span> to users who can view and create accounts but not manage roles.
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
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-white">Department</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-white">Current Type</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-white">Created</th>
                                    <th v-if="isAdmin" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-white">
                                        Update Type
                                    </th>
                                    <th
                                        v-if="canManageAccounts"
                                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-white"
                                    >
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
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ user.department || '—' }}</td>
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
                                    <td v-if="isAdmin" class="px-6 py-4">
                                        <select
                                            :value="user.account_type"
                                            class="rounded-lg border border-orange-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                            @change="updateAccountType(user.id, $event)"
                                        >
                                            <option v-for="accountType in accountTypes" :key="accountType" :value="accountType">
                                                {{ accountType }}
                                            </option>
                                        </select>
                                    </td>
                                    <td v-if="canManageAccounts" class="px-6 py-4">
                                        <div v-if="canManageTechnician(user)" class="flex flex-wrap gap-2">
                                            <Button
                                                v-if="isAdmin"
                                                type="button"
                                                class="bg-orange-500 text-white hover:bg-orange-600"
                                                @click="openEditDialog(user)"
                                                >Edit</Button
                                            >
                                            <Button type="button" class="bg-blue-600 text-white hover:bg-blue-700" @click="openPasswordDialog(user)">
                                                Change password
                                            </Button>
                                            <Button type="button" variant="destructive" @click="openDeleteDialog(user)">Delete</Button>
                                        </div>
                                        <p v-else class="text-sm text-slate-400">Cannot edit own account</p>
                                    </td>
                                </tr>
                                <tr v-if="filteredUsers.length === 0">
                                    <td :colspan="isAdmin ? 7 : canManageAccounts ? 6 : 5" class="px-6 py-10 text-center text-sm text-slate-500">
                                        No accounts found for the selected role.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        <button
            v-if="canManageAccounts"
            type="button"
            class="fixed bottom-8 right-8 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-orange-600 text-white shadow-lg transition hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2"
            aria-label="Add account"
            @click="openCreateDialog"
        >
            <Plus class="h-7 w-7" />
        </button>

        <Dialog v-model:open="createDialogOpen">
            <DialogContent class="overflow-hidden border-orange-200 bg-white p-0 shadow-2xl shadow-orange-200/60 sm:max-w-xl sm:rounded-2xl">
                <form class="space-y-6" @submit.prevent="submitCreate">
                    <DialogHeader class="space-y-3 border-b-4 border-orange-400 bg-gradient-to-r from-orange-50 via-white to-amber-100 px-6 py-5">
                        <DialogTitle>Create new account</DialogTitle>
                        <DialogDescription class="text-slate-600">
                            Add a new user account to the system. An account type must be assigned immediately.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-5 px-6 pb-6">
                        <div class="grid gap-4">
                            <div class="grid gap-2">
                                <label for="create_name" class="text-sm font-medium text-slate-700">Full name</label>
                                <Input id="create_name" v-model="createForm.name" type="text" autocomplete="off" placeholder="Full name" />
                                <InputError :message="createForm.errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <label for="create_email" class="text-sm font-medium text-slate-700">Email address</label>
                                <Input id="create_email" v-model="createForm.email" type="email" autocomplete="off" placeholder="email@example.com" />
                                <InputError :message="createForm.errors.email" />
                            </div>

                            <div class="grid gap-2">
                                <label for="create_account_type" class="text-sm font-medium text-slate-700">Account type</label>
                                <select
                                    id="create_account_type"
                                    v-model="createForm.account_type"
                                    class="h-10 w-full rounded-lg border border-orange-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                >
                                    <option v-for="accountType in creatableAccountTypes" :key="accountType" :value="accountType">
                                        {{ accountType }}
                                    </option>
                                </select>
                                <InputError :message="createForm.errors.account_type" />
                            </div>

                            <div v-if="createForm.account_type === 'End_User'" class="grid gap-2">
                                <label for="create_department" class="text-sm font-medium text-slate-700"
                                    >Department <span class="font-normal text-gray-400">(optional)</span></label
                                >
                                <Input
                                    id="create_department"
                                    v-model="createForm.department"
                                    type="text"
                                    autocomplete="off"
                                    placeholder="e.g. Radiology, ICU, Cardiology"
                                />
                                <InputError :message="createForm.errors.department" />
                            </div>

                            <div class="grid gap-2">
                                <label for="create_password" class="text-sm font-medium text-slate-700">Password</label>
                                <Input
                                    id="create_password"
                                    v-model="createForm.password"
                                    type="password"
                                    autocomplete="new-password"
                                    placeholder="Password"
                                />
                                <InputError :message="createForm.errors.password" />
                            </div>

                            <div class="grid gap-2">
                                <label for="create_password_confirmation" class="text-sm font-medium text-slate-700">Confirm password</label>
                                <Input
                                    id="create_password_confirmation"
                                    v-model="createForm.password_confirmation"
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
                                    @click="closeCreateDialog"
                                >
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button type="submit" class="bg-orange-600 text-white hover:bg-orange-700" :disabled="createForm.processing">
                                Create account
                            </Button>
                        </DialogFooter>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="overflow-hidden border-orange-200 bg-white p-0 shadow-2xl shadow-orange-200/60 sm:max-w-xl sm:rounded-2xl">
                <form class="space-y-6" @submit.prevent="submitEdit">
                    <DialogHeader class="space-y-3 border-b-4 border-orange-400 bg-gradient-to-r from-orange-50 via-white to-amber-100 px-6 py-5">
                        <DialogTitle>Edit account</DialogTitle>
                        <DialogDescription class="text-slate-600">
                            Update the name and department for {{ selectedTechnician?.name || 'this user' }}.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-5 px-6 pb-6">
                        <div class="grid gap-4">
                            <div class="grid gap-2">
                                <label for="edit_name" class="text-sm font-medium text-slate-700">Full name</label>
                                <Input id="edit_name" v-model="editForm.name" type="text" autocomplete="off" placeholder="Full name" />
                                <InputError :message="editForm.errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <label for="edit_department" class="text-sm font-medium text-slate-700"
                                    >Department <span class="font-normal text-gray-400">(optional)</span></label
                                >
                                <Input
                                    id="edit_department"
                                    v-model="editForm.department"
                                    type="text"
                                    autocomplete="off"
                                    placeholder="e.g. Radiology, ICU, Cardiology"
                                />
                                <InputError :message="editForm.errors.department" />
                            </div>
                        </div>

                        <DialogFooter class="border-t border-orange-100 pt-5">
                            <DialogClose as-child>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    class="border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                                    @click="closeEditDialog"
                                >
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button type="submit" class="bg-orange-600 text-white hover:bg-orange-700" :disabled="editForm.processing"
                                >Save changes</Button
                            >
                        </DialogFooter>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

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
                            <Button type="submit" variant="destructive" :disabled="deleteForm.processing">Delete account</Button>
                        </DialogFooter>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Toast notification -->
        <transition name="toast">
            <div
                v-if="toastVisible"
                class="fixed right-6 top-6 z-50 flex items-center gap-3 rounded-xl px-5 py-3 text-sm font-medium text-white shadow-lg"
                :class="toastType === 'error' ? 'bg-red-600' : 'bg-gray-900'"
            >
                <svg v-if="toastType === 'error'" class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"
                    />
                </svg>
                <svg v-else class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ toastMessage }}
            </div>
        </transition>
    </AppLayout>
</template>

<style>
.toast-enter-active {
    animation: toastIn 0.3s ease-out;
}
.toast-leave-active {
    animation: toastOut 0.2s ease-in forwards;
}
@keyframes toastIn {
    from {
        opacity: 0;
        transform: translateY(-12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
@keyframes toastOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-12px);
    }
}
</style>
