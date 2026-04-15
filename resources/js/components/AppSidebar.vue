<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ClipboardList, History, LayoutDashboard, Package, ShieldAlert, ShieldCheck, Users, Wrench } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutDashboard,
    },
    {
        title: 'Inventory',
        href: '/Inventory',
        icon: Package,
    },
    {
        title: 'Job Requests',
        href: '/JobRequests',
        icon: Wrench,
    },
    {
        title: 'Request Service',
        href: '/request-service',
        icon: ClipboardList,
    },
    {
        title: 'Request History',
        href: '/request-history',
        icon: ClipboardList,
    },
    {
        title: 'Pre Inspection',
        href: '/pre-inspection',
        icon: ShieldAlert,
    },
    {
        title: 'Job Request History',
        href: '/job-request-history',
        icon: History,
    },
    {
        title: 'Manage Accounts',
        href: '/manage-accounts',
        icon: Users,
    },
    {
        title: 'Approvals',
        href: '/approvals',
        icon: ShieldCheck,
    },
];

const page = usePage<SharedData>();

const visibleNavItems = computed(() => {
    const accountType = page.props.auth.user.account_type;
    const isEndUser = accountType === 'End_User';
    const isAdmin = accountType === 'Admin';

    if (isEndUser) {
        return mainNavItems.filter((item) => ['/dashboard', '/request-service', '/request-history', '/Inventory'].includes(item.href));
    }

    return mainNavItems.filter((item) => {
        if (item.href === '/request-service' || item.href === '/request-history') return false;
        if (item.href === '/approvals' && !isAdmin) return false;
        if (item.href === '/manage-accounts' && accountType === 'Biomed_Technician') return false;
        return true;
    });
});

// const footerNavItems: NavItem[] = [
//     {
//         title: 'Github Repo',
//         href: 'https://github.com/laravel/vue-starter-kit',
//         icon: Folder,
//     },
//     {
//         title: 'Documentation',
//         href: 'https://laravel.com/docs/starter-kits',
//         icon: BookOpen,
//     },
// ];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem class="mb-2">
                    <SidebarTrigger />
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="visibleNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
