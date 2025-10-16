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
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Tags, Navigation, House, Lock, Users, Shield, Settings, Home, Database } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { useAuth } from '@/composables/useAuth';
import { computed, onMounted } from 'vue';

const {
    isAdminOrSystemAdmin,
    isSystemAdmin,
    canViewCategories,
    canViewLocations,
    canViewProperties,
    fetchUser
} = useAuth();

onMounted(() => {
    fetchUser();
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

        // Only System Admin can access database backup
        if (isSystemAdmin.value) {
            items.push({
                title: 'Database Backup',
                href: '/admin/database-backup',
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
