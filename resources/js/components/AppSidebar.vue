<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import categories from '@/routes/categories';
import locations from '@/routes/locations';
import properties from '@/routes/properties';
import admin from '@/routes/admin';
import databaseBackup from '@/routes/database-backup';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Tags, Navigation, House, Lock, Users, Shield, Settings, Home, Database, MessageSquare } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { useAuth } from '@/composables/useAuth';
import { computed, onMounted, ref } from 'vue';

const {
    isAdminOrSystemAdmin,
    isSystemAdmin,
    canViewCategories,
    canViewLocations,
    canViewProperties,
    fetchUser
} = useAuth();

const clientNewCount = ref<number>(0);
const inquiryUnviewedCount = ref<number>(0);

const fetchCounters = async () => {
    try {
        const response = await fetch('/admin/api/counters', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (response.ok) {
            const data = await response.json();
            clientNewCount.value = Number(data?.clients_new_last_2_days ?? 0);
            inquiryUnviewedCount.value = Number(data?.inquiries_unviewed ?? 0);
        }
    } catch (e) {
        console.error('Failed to fetch admin counters:', e);
    }
};

onMounted(() => {
    fetchUser();
    fetchCounters();
});

const mainNavItems = computed((): NavItem[] => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    // Add business entity items based on permissions
    if (canViewCategories.value) {
        items.push({
            title: 'Categories',
            href: categories.index(),
            icon: Tags,
        });
    }

    if (canViewLocations.value) {
        items.push({
            title: 'Locations',
            href: locations.index(),
            icon: Navigation,
        });
    }

    if (canViewProperties.value) {
        items.push({
            title: 'Properties',
            href: properties.index(),
            icon: House,
        });
    }

    // Add admin management items based on role
    if (isAdminOrSystemAdmin.value) {
        items.push(
            {
                title: 'Clients',
                href: admin.clients.index(),
                icon: Users,
                badge: clientNewCount.value,
            },
            {
                title: 'Inquiries',
                href: admin.inquiries.index(),
                icon: MessageSquare,
                badge: inquiryUnviewedCount.value,
            },
            {
                title: 'Rented',
                href: admin.rented.index(),
                icon: Home,
            },
            {
                title: 'Roles',
                href: admin.roles.index(),
                icon: Shield,
            },
            {
                title: 'Users',
                href: admin.users.index(),
                icon: Users,
            }
        );

        // Only System Admin can access permissions management
        if (isSystemAdmin.value) {
            items.push({
                title: 'Permissions',
                href: admin.permissions.index(),
                icon: Lock,
            });
        }

        // Add site settings for admins
        items.push({
            title: 'Site Settings',
            href: admin.siteSettings.index(),
            icon: Settings,
        });

        // Add database backup for system admin
        if (isSystemAdmin.value) {
            items.push({
                title: 'Database Backup',
                href: databaseBackup.index(),
                icon: Database,
            });
        }
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/edmarcres18',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: '/documentation/overview',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
