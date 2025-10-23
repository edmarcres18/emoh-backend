<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import Icon from '@/components/Icon.vue';
import { computed } from 'vue';
import { formatRelativeTime } from '@/utils/formatters';

interface Inquiry {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    subject: string;
    message: string;
    status: 'pending' | 'in_progress' | 'resolved' | 'closed';
    ip_address?: string;
    user_agent?: string;
    admin_notes?: string;
    created_at: string;
    updated_at: string;
    resolved_at?: string;
    resolved_by?: number;
    resolver?: {
        id: number;
        name: string;
        email: string;
    };
}

interface Props {
    inquiry: Inquiry;
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Guest Inquiries', href: '/admin/guest-inquiries' },
    { title: `Inquiry #${props.inquiry.id}`, href: `/admin/guest-inquiries/${props.inquiry.id}` },
];

// Form for updating status
const form = useForm({
    status: props.inquiry.status,
    admin_notes: props.inquiry.admin_notes || '',
});

// Update inquiry status
const updateStatus = () => {
    form.patch(`/admin/guest-inquiries/${props.inquiry.id}/status`, {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by Inertia
        },
    });
};

// Status badge variant
const getStatusVariant = (status: string): 'default' | 'secondary' | 'outline' | 'destructive' => {
    const variants: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
        pending: 'default',
        in_progress: 'secondary',
        resolved: 'default',
        closed: 'outline',
    };
    return variants[status] || 'default';
};

// Copy to clipboard helper
const copyToClipboard = (text: string) => {
    if (navigator && navigator.clipboard) {
        navigator.clipboard.writeText(text);
    }
};

// Subject badge color
const getSubjectColor = (subject: string) => {
    const colors: Record<string, string> = {
        rental: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        lease: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        general: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        support: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    };
    return colors[subject] || colors.general;
};

// Format status text
const formatStatus = (status: string) => {
    return status.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
};

// Format subject text
const formatSubject = (subject: string) => {
    return subject.charAt(0).toUpperCase() + subject.slice(1);
};

// Full name
const fullName = computed(() => `${props.inquiry.first_name} ${props.inquiry.last_name}`);

// Delete inquiry
const deleteInquiry = () => {
    if (confirm('Are you sure you want to delete this inquiry? This action cannot be undone.')) {
        router.delete(`/admin/guest-inquiries/${props.inquiry.id}`, {
            onSuccess: () => {
                router.visit('/admin/guest-inquiries');
            },
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Inquiry from ${fullName}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Inquiry from {{ fullName }}
                    </h1>
                    <p class="text-muted-foreground mt-1">
                        Inquiry #{{ inquiry.id }} • {{ formatRelativeTime(inquiry.created_at) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        as="a"
                        href="/admin/guest-inquiries"
                        variant="outline"
                    >
                        <Icon name="arrow-left" class="mr-2 h-4 w-4" />
                        Back to Inquiries
                    </Button>
                    <Button
                        @click="deleteInquiry"
                        variant="destructive"
                    >
                        <Icon name="trash" class="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Inquiry Details -->
                    <Card>
                        <CardHeader>
                            <div class="flex justify-between items-start">
                                <div>
                                    <CardTitle>Inquiry Details</CardTitle>
                                    <CardDescription>Message from the guest</CardDescription>
                                </div>
                                <div class="flex gap-2">
                                    <Badge :class="getSubjectColor(inquiry.subject)" variant="outline">
                                        {{ formatSubject(inquiry.subject) }}
                                    </Badge>
                                    <Badge :variant="getStatusVariant(inquiry.status)">
                                        {{ formatStatus(inquiry.status) }}
                                    </Badge>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label class="text-muted-foreground">First Name</Label>
                                    <p class="font-medium">{{ inquiry.first_name }}</p>
                                </div>
                                <div>
                                    <Label class="text-muted-foreground">Last Name</Label>
                                    <p class="font-medium">{{ inquiry.last_name }}</p>
                                </div>
                            </div>
                            <div>
                                <Label class="text-muted-foreground">Email Address</Label>
                                <a :href="`mailto:${inquiry.email}`" class="font-medium text-primary hover:underline flex items-center gap-2">
                                    {{ inquiry.email }}
                                    <Icon name="external-link" class="h-3 w-3" />
                                </a>
                            </div>
                            <div>
                                <Label class="text-muted-foreground">Message</Label>
                                <div class="mt-2 p-4 bg-muted rounded-lg">
                                    <p class="whitespace-pre-wrap">{{ inquiry.message }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Update Status -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Update Status</CardTitle>
                            <CardDescription>Change the inquiry status and add notes</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="updateStatus" class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="status">Status</Label>
                                    <Select v-model="form.status" id="status">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pending">Pending</SelectItem>
                                            <SelectItem value="in_progress">In Progress</SelectItem>
                                            <SelectItem value="resolved">Resolved</SelectItem>
                                            <SelectItem value="closed">Closed</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-2">
                                    <Label for="admin_notes">Admin Notes</Label>
                                    <Textarea
                                        id="admin_notes"
                                        v-model="form.admin_notes"
                                        placeholder="Add notes about this inquiry..."
                                        rows="4"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Internal notes visible only to administrators
                                    </p>
                                </div>

                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full"
                                >
                                    <Icon v-if="!form.processing" name="save" class="mr-2 h-4 w-4" />
                                    <Icon v-else name="loader" class="mr-2 h-4 w-4 animate-spin" />
                                    {{ form.processing ? 'Updating...' : 'Update Status' }}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Metadata -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Metadata</CardTitle>
                            <CardDescription>Additional information</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label class="text-muted-foreground flex items-center gap-2">
                                    <Icon name="clock" class="h-4 w-4" />
                                    Created At
                                </Label>
                                <p class="font-medium mt-1">
                                    {{ new Date(inquiry.created_at).toLocaleString() }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatRelativeTime(inquiry.created_at) }}
                                </p>
                            </div>

                            <div>
                                <Label class="text-muted-foreground flex items-center gap-2">
                                    <Icon name="refresh-cw" class="h-4 w-4" />
                                    Last Updated
                                </Label>
                                <p class="font-medium mt-1">
                                    {{ new Date(inquiry.updated_at).toLocaleString() }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatRelativeTime(inquiry.updated_at) }}
                                </p>
                            </div>

                            <div v-if="inquiry.resolved_at">
                                <Label class="text-muted-foreground flex items-center gap-2">
                                    <Icon name="check-circle" class="h-4 w-4" />
                                    Resolved At
                                </Label>
                                <p class="font-medium mt-1">
                                    {{ new Date(inquiry.resolved_at).toLocaleString() }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatRelativeTime(inquiry.resolved_at) }}
                                </p>
                            </div>

                            <div v-if="inquiry.resolver">
                                <Label class="text-muted-foreground flex items-center gap-2">
                                    <Icon name="user" class="h-4 w-4" />
                                    Resolved By
                                </Label>
                                <p class="font-medium mt-1">{{ inquiry.resolver.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ inquiry.resolver.email }}
                                </p>
                            </div>

                            <div v-if="inquiry.ip_address">
                                <Label class="text-muted-foreground flex items-center gap-2">
                                    <Icon name="globe" class="h-4 w-4" />
                                    IP Address
                                </Label>
                                <p class="font-mono text-sm font-medium mt-1">
                                    {{ inquiry.ip_address }}
                                </p>
                            </div>

                            <div v-if="inquiry.user_agent">
                                <Label class="text-muted-foreground flex items-center gap-2">
                                    <Icon name="monitor" class="h-4 w-4" />
                                    User Agent
                                </Label>
                                <p class="text-xs font-mono mt-1 break-all">
                                    {{ inquiry.user_agent }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Button
                                as="a"
                                :href="`mailto:${inquiry.email}?subject=Re: ${formatSubject(inquiry.subject)} Inquiry`"
                                variant="outline"
                                class="w-full justify-start"
                            >
                                <Icon name="mail" class="mr-2 h-4 w-4" />
                                Reply via Email
                            </Button>
                            <Button
                                @click="copyToClipboard(inquiry.email)"
                                variant="outline"
                                class="w-full justify-start"
                            >
                                <Icon name="copy" class="mr-2 h-4 w-4" />
                                Copy Email
                            </Button>
                            <Button
                                @click="copyToClipboard(`${fullName}\n${inquiry.email}\n\n${inquiry.message}`)"
                                variant="outline"
                                class="w-full justify-start"
                            >
                                <Icon name="clipboard" class="mr-2 h-4 w-4" />
                                Copy Details
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
