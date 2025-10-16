<script setup lang="ts">
import { HardDrive, Calendar, FileArchive, ExternalLink } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';

interface BackupData {
    filename: string;
    size: string;
    size_bytes: number;
    created_at: string;
    created_at_human: string;
    timestamp: number;
}

interface Props {
    data?: BackupData | null;
    loading?: boolean;
}

defineProps<Props>();
</script>

<template>
    <div class="space-y-4">
        <div v-if="loading" class="space-y-3">
            <div class="h-4 w-32 animate-pulse rounded bg-muted"></div>
            <div class="h-4 w-48 animate-pulse rounded bg-muted"></div>
            <div class="h-4 w-24 animate-pulse rounded bg-muted"></div>
        </div>

        <div v-else-if="!data" class="flex flex-col items-center justify-center py-8 text-center">
            <FileArchive class="mb-3 size-12 text-muted-foreground/50" />
            <p class="text-sm text-muted-foreground">No backups found</p>
            <p class="text-xs text-muted-foreground">Create your first backup</p>
        </div>

        <div v-else class="space-y-3">
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 rounded-md bg-primary/10 p-2">
                        <HardDrive class="size-4 text-primary" />
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium leading-none">{{ data.filename }}</p>
                        <p class="text-xs text-muted-foreground">Latest database backup</p>
                    </div>
                </div>
                <Badge variant="secondary">{{ data.size }}</Badge>
            </div>

            <div class="flex items-center gap-2 rounded-md bg-muted/50 p-3">
                <Calendar class="size-4 text-muted-foreground" />
                <div class="flex-1">
                    <p class="text-xs text-muted-foreground">Created</p>
                    <p class="text-sm font-medium">{{ data.created_at_human }}</p>
                </div>
            </div>

            <Link href="/admin/database-backup" class="block w-full">
                <Button
                    variant="outline"
                    size="sm"
                    class="w-full"
                >
                    <ExternalLink class="mr-2 size-4" />
                    View All Backups
                </Button>
            </Link>
        </div>
    </div>
</template>
