<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
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
import { ClipboardList, LayoutDashboard, Package, Users, Wrench } from 'lucide-vue-next';
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
        title: 'Manage Accounts',
        href: '/manage-accounts',
        icon: Users,
    },
];

const page = usePage<SharedData>();

const visibleNavItems = computed(() => {
    const isEndUser = page.props.auth.user.account_type === 'End_User';

    if (isEndUser) {
        return mainNavItems.filter((item) => ['/request-service', '/request-history'].includes(item.href));
    }

    return mainNavItems.filter((item) => {
        if (item.href === '/request-service' || item.href === '/request-history') return false; // Hide from admin/tech to avoid clutter, or leave it?
        if (item.href === '/manage-accounts' && page.props.auth.user.account_type !== 'Admin') return false;
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
