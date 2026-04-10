<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Member, BreadcrumbItem, PaginatedResponse } from '@/types';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { ref } from 'vue';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { formatDate } from '@vueuse/shared';
import Pagination from '@/components/Pagination.vue';
import debounce from 'lodash.debounce';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'members',
        href: '/members',
    },
];

interface Props {
    members: PaginatedResponse<Member>;
    filters: {search: string, deleted: string};
}

const props = defineProps<Props>();

enum statuses {
    Pending = 'pending',
    Rejected = 'rejected',
    Accepted = 'accepted',
    Exported = 'exported',
    Paid = 'paid',
}

const statusLabels: Record<statuses, string> = {
    [statuses.Pending]: 'In behandeling',
    [statuses.Rejected]: 'Afgewezen',
    [statuses.Accepted]: 'Goedgekeurd',
    [statuses.Exported]: 'Geëxporteerd',
    [statuses.Paid]: 'Betaald',
};

const modalOpen = ref(false);
const membersForm = useForm({
    file: null,
});

const onFileChange = (e) => {
    membersForm.file = e.target.files[0];
};

const submitNewMembers = () => {
    membersForm.post(route('admin.members.store'), {
        forceFormData: true,
        onSuccess: () => {
            membersForm.reset();
            modalOpen.value = false;
        },
    });
};

/**
 * Search bar
 */
const filters = ref({
    search: props.filters.search || '',
    deleted: props.filters.deleted || '0',
});

const searchMembers = debounce(() => {
    router.get(
        route('admin.members.index'),
        filters.value.search == '' ? undefined : { search: filters.value.search },
        {
            preserveState: true,
            replace: true,
        }
    );
}, 300);

/**
 * Global Modal for member requests
 */
const selectedMember = ref<Member>(null!);
const memberModalOpen = ref(false);

const openMemberModal = (row: Member) => {
    selectedMember.value = row;
    memberModalOpen.value = true;
};

/**
 * Deleted At Filter
 */
const filterDeleted = () => {
    router.get(
        route('admin.members.index'),
        filters.value.deleted == '0' || filters.value.deleted == '' ? undefined : { deleted: filters.value.deleted },
        {
            preserveState: true,
            replace: true,
        }
    );
}

</script>

<template>
    <Head title="Members" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative flex min-h-[100vh] flex-1 flex-col rounded-xl border md:min-h-min"
            >
                <div class="flex w-full justify-between">
                    <div class="p-4">
                        <Label class="pb-2 pl-0.5"> Zoek op ID of Email </Label>
                        <Input placeholder="ID of Email" v-model="filters.search" @input="searchMembers" />
                    </div>

                    <Dialog v-model:open="modalOpen">
                        <DialogTrigger class="ml-auto p-4">
                            <Button>Leden inladen</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Leden inladen</DialogTitle>
                            </DialogHeader>
                            <DialogDescription></DialogDescription>
                            <div>
                                <form @submit.prevent="submitNewMembers">
                                    <div class="mb-4">
                                        <Label>Leden Excel document</Label>
                                        <Input @change="onFileChange" type="file" class="mt-1 w-full rounded border p-2" />
                                        <p class="mt-1 text-xs text-gray-500">XLS, XLSX, CSV - XLSB is not supported</p>
                                        <InputError v-if="membersForm.errors.file" :message="membersForm.errors.file[0]" />
                                        <!--                                        <div v-if="membersForm.errors.file" class="text-sm text-red-600">{{ membersForm.errors.file }}</div>-->
                                    </div>
                                    <Button type="submit" :disabled="membersForm.processing">
                                        {{ membersForm.processing ? 'Toevoegen...' : 'Importeren' }}
                                    </Button>
                                </form>
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>

                <div class="flex-1 px-2">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Roepnaam</TableHead>
                                <TableHead>Geboortedatum</TableHead>
                                <TableHead>In dienst (dienstjs.)</TableHead>
                                <TableHead>
                                    <select v-model="filters.deleted" @change="filterDeleted" class="rounded border px-2 py-1">
                                        <option selected value="0">Valide (+3 maanden)</option>
                                        <option value="1">Ingeschreven</option>
                                        <option value="2">Uitgeschreven</option>
                                        <option value="3">Alle</option>
                                    </select>
                                </TableHead>
                                <TableHead>Acties</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="member in props.members.data" :key="member.id">
                                <TableCell>{{ member.id }}</TableCell>
                                <TableCell>{{ member.email }}</TableCell>
                                <TableCell>{{ member.name }}</TableCell>
                                <TableCell>{{ formatDate(new Date(member.dob), 'DD-MM-YYYY') }}</TableCell>
                                <TableCell>{{ formatDate(new Date(member.years_of_service), 'DD-MM-YYYY') }}</TableCell>
                                <TableCell>{{ member.deleted_at != null ? formatDate(new Date(member.deleted_at), 'DD-MM-YYYY') : 'n.v.t' }}</TableCell>
                                <TableCell class="text-right">
                                    <Button @click="openMemberModal(member)">Bekijk aanvragen</Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Paginatie -->
                    <div class="w-full max-w-full overflow-x-auto">
                        <Pagination :resource="props.members" />
                    </div>

                    <!-- Global Modal -->
                    <Dialog v-model:open="memberModalOpen">
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle
                                    ><span v-if="selectedMember.full_name">{{ selectedMember.full_name }} -</span> Ontvangst geschiedenis</DialogTitle
                                >
                            </DialogHeader>
                            <DialogDescription>Geschiedenis van ontvangen aanvragen.</DialogDescription>
                            <div class="flex max-h-100 w-full flex-col overflow-x-scroll overflow-y-scroll">
                                <div v-if="selectedMember.requests?.length" class="flex-1">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Aanvrager</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Gebeurtenis</TableHead>
                                                <TableHead>Bedrag</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="request in selectedMember.requests" :key="request.id">
                                                <TableCell>{{ request.employee_requester }}</TableCell>
                                                <TableCell>
                                                    <span :class="{
                                                        'text-yellow-500': request.status === statuses.Pending,
                                                        'text-green-600': request.status === statuses.Accepted,
                                                        'text-red-600': request.status === statuses.Rejected,
                                                        'text-blue-500': request.status === statuses.Exported,
                                                        'text-black': request.status === statuses.Paid,
                                                        'dark:text-white': request.status === statuses.Paid,
                                                        'font-bold': true,
                                                    }">
                                                        {{ statusLabels[request.status as statuses] }}
                                                    </span>
                                                </TableCell>
                                                <TableCell>{{ request.event_cost.event.title }}</TableCell>
                                                <TableCell>&euro;{{ request.event_cost.amount }}</TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                                <template v-else> Geen aanvragen voor deze persoon. </template>
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
