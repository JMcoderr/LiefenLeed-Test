<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, EventType } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface Props {
    events: EventType[];
    cost_statuses: object;
}

const props = defineProps<Props>();

enum statuses {
    Upcoming = 'upcoming',
    Active = 'active',
    Passed = 'passed',
}

const statusLabels: Record<statuses, string> = {
    [statuses.Upcoming]: 'Toekomstig',
    [statuses.Active]: 'Actief',
    [statuses.Passed]: 'Verlopen',
};

/**
 * Global Modal for event costs
 */
const selectedRow = ref<EventType>(null!);
const modalOpen = ref(false);

const openModal = (row: EventType) => {
    selectedRow.value = row;
    modalOpen.value = true;
};

/**
 * Create new Event
 */
const formEvent = useForm({
    title: '',
});
const modalOpenNewEvent = ref(false);

const submitNewEvent = () => {
    formEvent.post(route('admin.events.store'), {
        onSuccess: () => {
            console.log('[SERVER] Created new event');
            modalOpenNewEvent.value = false;
            formEvent.reset();
        },
    });
};

/**
 * Create new Cost
 */

/// CHATGPT
const minDateTime = ref(formatDateTimeLocal(new Date()));

function formatDateTimeLocal(date: Date) {
    const pad = (n: any) => n.toString().padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}
/// END CHATGPT
const formCost = useForm({
    amount: 0,
    start_date: '',
    end_date: '',
});
const modalOpenNewCost = ref(false);

const submitNewCost = (event_id: number) => {
    formCost.post(route('admin.events.costs.store', event_id), {
        onSuccess: () => {
            console.log('[SERVER] Created new Cost for event: ' + event_id);
            modalOpen.value = false;
            modalOpenNewCost.value = false;
            formCost.reset();
        },
    });
};

/**
 * Destroy a cost for an event
 */
const destoryCost = (event_id: number, cost_id: number) => {
    router.delete(route('admin.events.costs.destroy', [event_id, cost_id]), {
        onSuccess: () => {
            modalOpen.value = false;
            console.log(`[SERVER] Deleted Cost: ${cost_id} for event: ${event_id}`);
            console.log(`[LOG] route: ${route('admin.events.costs.destroy', [event_id, cost_id])}`);
        },
        onError: (error: any) => {
            console.log(`[LOG] Error deleting Cost: ${cost_id} for event: ${event_id}`);
            console.error(error);
        },
    });
};

</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative flex min-h-[100vh] flex-1 flex-col rounded-xl border md:min-h-min"
            >
                <div class="flex w-full justify-end">
                    <Dialog v-model:open="modalOpenNewEvent">
                        <DialogTrigger class="ml-auto p-4">
                            <Button>Nieuwe Gebeurtenis</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Nieuw Gebeurtenis Maken</DialogTitle>
                            </DialogHeader>
                            <DialogDescription></DialogDescription>
                            <div>
                                <form @submit.prevent="submitNewEvent">
                                    <div class="mb-4">
                                        <label>Titel</label>
                                        <input v-model="formEvent.title" type="text" class="w-full rounded border p-2" />
                                        <div v-if="formEvent.errors.title" class="text-sm text-red-600">{{ formEvent.errors.title }}</div>
                                    </div>
                                    <Button type="submit" :disabled="formEvent.processing">
                                        {{ formEvent.processing ? 'Toevoegen...' : 'Creëren' }}
                                    </Button>
                                </form>
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>

                <div class="flex-1 px-2">
                    <!-- Unique Events table -->
                    <Table>
                        <TableCaption>Lijst van gebeurtenissen</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Titel</TableHead>
                                <TableHead>Huidige prijs</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Kosten geschiedenis</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="event in props.events" :key="event.id">
                                <TableCell class="font-medium">
                                    {{ event.title }}
                                </TableCell>
                                <TableCell>
                                    <div v-if="event.current_cost">&euro; {{ event.current_cost['amount'] }}</div>
                                    <div v-else>&euro; 0,-</div>
                                </TableCell>
                                <TableCell>
                                    <div
                                        v-if="event.current_cost"
                                        :class="{
                                            'text-green-500': event.current_cost['status'] === 'active',
                                            'text-red-500': event.current_cost['status'] === 'passed',
                                            'text-blue-400': event.current_cost['status'] === 'upcoming',
                                        }"
                                    >
                                        {{ statusLabels[event.current_cost['status'] as statuses] }}
                                    </div>
                                    <div v-else class="text-orange-400">Nog geen prijs</div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button @click="openModal(event)">Bekijk kosten</Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Global Modal -->
                    <Dialog v-model:open="modalOpen">
                        <DialogContent class="max-w-full md:max-w-[640px]">
                            <DialogHeader>
                                <DialogTitle>{{ selectedRow?.title }} prijs geschiedenis</DialogTitle>
                            </DialogHeader>
                            <DialogDescription> Geschiedenis van de gebeurtenissen prijs (veranderingen). </DialogDescription>
                            <div class="flex h-full w-full flex-col">
                                <!-- Dialog (Modal) for creating a new price for a event -->
                                <div class="flex w-full justify-end">
                                    <Dialog v-model:open="modalOpenNewCost">
                                        <DialogTrigger class="ml-auto py-4">
                                            <Button>Nieuwe Prijs</Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>Nieuw {{ selectedRow?.title }} Prijs Maken</DialogTitle>
                                            </DialogHeader>
                                            <DialogDescription></DialogDescription>
                                            <div>
                                                <form @submit.prevent="submitNewCost(selectedRow.id)">
                                                    <div class="mb-4">
                                                        <label>Prijs</label>
                                                        <div class="relative">
                                                            <input
                                                                v-model="formCost.amount"
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                placeholder="0.00"
                                                                class="w-full rounded border p-2 pl-8"
                                                            />
                                                            <span class="absolute top-2 left-3">€</span>
                                                        </div>
                                                        <div v-if="formCost.errors.amount" class="text-sm text-red-600">
                                                            {{ formCost.errors.amount }}
                                                        </div>
                                                    </div>
                                                    <div class="mb-4">
                                                        <label>Start datum</label>
                                                        <input
                                                            v-model="formCost.start_date"
                                                            type="datetime-local"
                                                            :min="minDateTime"
                                                            class="w-full rounded border p-2"
                                                        />
                                                        <div v-if="formCost.errors.start_date" class="text-sm text-red-600">
                                                            {{ formCost.errors.start_date }}
                                                        </div>
                                                    </div>
                                                    <div class="mb-4">
                                                        <label>Eind datum</label>
                                                        <input
                                                            v-model="formCost.end_date"
                                                            type="datetime-local"
                                                            :min="minDateTime"
                                                            class="w-full rounded border p-2"
                                                        />
                                                        <div v-if="formCost.errors.end_date" class="text-sm text-red-600">
                                                            {{ formCost.errors.end_date }}
                                                        </div>
                                                    </div>
                                                    <Button type="submit" :disabled="formCost.processing">
                                                        {{ formCost.processing ? 'Toevoegen...' : 'Creëren' }}
                                                    </Button>
                                                </form>
                                            </div>
                                        </DialogContent>
                                    </Dialog>
                                </div>
                                <!-- Table of prices for a event -->
                                <div v-if="selectedRow" class="flex-1">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>#</TableHead>
                                                <TableHead>Prijs</TableHead>
                                                <TableHead>Start datum</TableHead>
                                                <TableHead>Eind datum</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Acties</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="(cost, index) in selectedRow.event_costs" :key="index">
                                                <TableCell class="font-bold">{{ cost.id }}</TableCell>
                                                <TableCell>&euro; {{ cost.amount }}</TableCell>
                                                <TableCell>{{ cost.start_date }}</TableCell>
                                                <!-- TODO: Only date not time -->
                                                <TableCell>{{ cost.end_date }}</TableCell>
                                                <TableCell
                                                    :class="{
                                                        'text-green-500': cost.status === 'active',
                                                        'text-red-500': cost.status === 'passed',
                                                        'text-blue-400': cost.status === 'upcoming',
                                                    }"
                                                >
                                                    {{ statusLabels[cost.status as statuses] }}
                                                </TableCell>
                                                <TableCell>
                                                    <template v-if="cost.status === 'upcoming'">
                                                        <Button @click="destoryCost(selectedRow.id, cost.id)">Delete</Button>
                                                    </template>
                                                    <template v-else-if="cost.status === 'active'">
                                                        <Button @click="destoryCost(selectedRow.id, cost.id)">Stop</Button>
                                                    </template>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
