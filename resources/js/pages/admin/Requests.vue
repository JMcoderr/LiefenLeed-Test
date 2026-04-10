<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, EventType, PaginatedResponse, type Request, SharedData, User } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button, buttonVariants } from '@/components/ui/button';
import { DateFormatter } from '@internationalized/date';
import Pagination from '@/components/Pagination.vue';
import { ref, watch } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import { toTypedSchema } from '@vee-validate/zod';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';
import * as z from 'zod';
import { Label } from '@/components/ui/label';
import debounce from 'lodash.debounce';

const page = usePage<SharedData>();
const user = (page.props.auth.user as User) ?? null;

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Beoordelen', href: '/requests' }];

interface Props {
    requests: PaginatedResponse<Request>;
    events: EventType[];
    selectedEvent: string | null;
    selectedStatuses: [] | null;
    search: string | undefined;
    defaultSepa: {account_name: string, iban: string} | null;
}

const df = new DateFormatter('nl-NL', {
    dateStyle: 'long',
});

const props = defineProps<Props>();

const selectedEvent = ref<string | null>(props.selectedEvent ?? null);
const selectedStatuses = ref<string[]>(props.selectedStatuses ?? []);
const search = ref<string| undefined>(props.search ?? undefined);

watch(selectedEvent, (event_id: string | null) => {
    selectedEvent.value = event_id;

    const data: Record<string, any> = {};
    if (selectedStatuses.value.length > 0) data.statuses = selectedStatuses.value;
    if (search.value != null || search.value != '') data.search = search.value;
    if (event_id) data.event_id = event_id;

    router.visit(route('admin.requests.index'), {
        data,
        preserveState: true,
        preserveScroll: true,
    });
});

watch(
    () => props.selectedStatuses,
    (newStatuses) => {
        selectedStatuses.value = newStatuses ?? [];
    },
);

const selectStatus = (status: string) => {
    const set = new Set(selectedStatuses.value);
    if (set.has(status)) set.delete(status);
    else set.add(status);
    selectedStatuses.value = Array.from(set);

    const data: Record<string, any> = {};
    if (selectedStatuses.value.length > 0) data.statuses = selectedStatuses.value;
    if (selectedEvent.value) data.event_id = selectedEvent.value;
    if (search.value != null || search.value != '') data.search = search.value;

    router.visit(route('admin.requests.index'), {
        data,
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

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

const statusForm = useForm<{
    status: string;
    reason: string | null;
}>({
    status: '',
    reason: null
});

const changeStatus = (id: number, status: statuses, reason?: string) => {
    statusForm.status = status;
    statusForm.reason = reason ?? null;
    statusForm.put(route('admin.requests.update', id));
    console.log(`[SERVER] try update status for request with id: ${id} to new status: ${status}`);
};


/**
 * Search bar
 */
const searchEmailsAndID = debounce(() => {
    const data: Record<string, any> = {};
    if (selectedStatuses.value.length > 0) data.statuses = selectedStatuses.value;
    if (selectedEvent.value) data.event_id = selectedEvent.value;
    if (search.value) data.search = search.value;

    router.visit(route('admin.requests.index'), {
        data,
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);


/**
 * Global Reject Reason Modal
 */
const selectedReasonRow = ref<Request>(null!);
const reasonModal = ref(false);
const reason = ref<string | undefined>(undefined!);

const openReasonModal = (row: Request) => {
    selectedReasonRow.value = row;
    reasonModal.value = true;
}


/* Necessary code for download export/sepa*/

// Set initial state of modals. Used to close modals after a submit.
const isSepaModalOpen = ref(false);
const isRapportModalOpen = ref(false);

// Zod front-end validation schemas
const debtorFormSchema = toTypedSchema(
    z.object({
        debtorName: z.string().min(2, 'Name must contain at least 2 characters').max(250, 'Name cannot exceed 250 characters'),
        debtorIban: z.string().regex(/^NL\d{2}[A-Z]{4}\d{10}$/, 'NL followed by 2 digits, 4 uppercase letters, and 10 digits'),
    }),
);

const rapportFormSchema = toTypedSchema(
    z.object({
        startDate: z.date(),
        endDate: z.date(),
    }),
);

// Form state for debtor data
const debtorFormData = useForm({
    debtorName: props.defaultSepa?.account_name ?? '',
    debtorIban: props.defaultSepa?.iban ?? '',
});

// Form state for rapport data
const rapportFormData = useForm({
    startDate: '',
    endDate: '',
});

/* Handles submission of the sepa modal form data & sepa batch export.
 * Calls a validation method on form POST,
 * if successful calls GET method that handles the sepa export.
 */
const onSubmitSepa = () => {
    // Submit form for back-end validation
    debtorFormData.post(route('admin.download.validate-sepa'), {
        onSuccess: () => {
            isSepaModalOpen.value = false;

            router.get(
                route('admin.download.sepa'),
                { validate: 1 },
                {
                    replace: true,
                    onSuccess: () => {
                        window.open(route('admin.download.sepa'), '_blank');
                        toast.success('SEPA batch has been generated.');
                        // router.visit(route('admin.requests.index'))
                        router.reload()
                    },
                    onError: (error: any) => {
                        toast.error(error.toast['message']);
                    },
                },
            );
        },
        onError: () => {
            if (debtorFormData.hasErrors) {
                toast.error('Validation failed, please check your inputs.');
            }
        },
    });
};

/* Handles submission of the rapport modal form data & rapport excel export.
 * Calls a validation method on form POST,
 * if successful calls GET method that handles the rapport excel export.
 */
const onSubmitRapport = () => {
    // Submit form for back-end validation
    rapportFormData.post(route('admin.download.validate-rapport'), {
        onSuccess: () => {
            isRapportModalOpen.value = false;

            router.get(
                route('admin.download.rapport'),
                { validate: 1 },
                {
                    onSuccess: () => {
                        window.open(route('admin.download.rapport'), '_blank');
                        toast.success('Rapport has been generated.');
                    },
                    onError: (error: any) => {
                        toast.error(error.toast['message']);
                    },
                },
            );
        },
        onError: () => {
            if (rapportFormData.hasErrors) {
                toast.error('Validation failed, please check your inputs.');
            }
        },
    });
};
</script>

<template>
    <Head title="Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative flex min-h-[100vh] flex-1 flex-col rounded-xl border md:min-h-min"
            >
                <!-- Filterknoppen -->
                <div class="relative mt-4 flex flex-col justify-center gap-4 ">
                    <!-- Centered buttons -->
                    <div class="mx-4 flex flex-col gap-2 xl:flex-row justify-center">
                        <template v-for="status in Object.values(statuses)" :key="status">
                            <Button
                                v-if="(status === statuses.Paid || status === statuses.Exported) ? user.admin.isSuper : true"
                                @click="selectStatus(status)"
                                :class="buttonVariants({ variant: selectedStatuses.includes(status) ? 'default' : 'secondary' })"
                            >
                                {{ statusLabels[status as statuses] }}
                            </Button>
                        </template>
                    </div>

                    <!-- Absolutely positioned right button -->
                    <div class="mx-4 flex gap-4 flex-col sm:flex-row">
                        <div class="flex flex-row gap-2 justify-center">
                            <Label class="pl-0.5"> Zoek op ID of Email </Label>
                            <Input class="max-w-64" placeholder="ID of Email" v-model="search" @input="searchEmailsAndID" />
                        </div>
                        <div v-if="user.admin.isSuper" class="flex gap-2 ml-auto">
                            <!-- SEPA BUTTON -->
                            <Form as="" :validation-schema="debtorFormSchema">
                                <!-- Start Modal -->
                                <Dialog v-model:open="isSepaModalOpen">
                                    <DialogTrigger as-child>
                                        <Button variant="outline" class="hover:cursor-pointer"> Download SEPA </Button>
                                    </DialogTrigger>
                                    <DialogContent class="sm:max-w-[425px]">
                                        <DialogHeader>
                                            <DialogTitle>Voer gegevens in voor batchbestand</DialogTitle>
                                            <DialogDescription
                                                >Controleer alsjeblieft of de gegevens kloppen. Het is van belang dat alles juist is.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <!-- Visible form -->
                                        <form id="batchForm" @submit.prevent="onSubmitSepa">
                                            <div class="flex flex-col gap-4">
                                                <FormField name="debtorName">
                                                    <FormItem>
                                                        <FormLabel>Tenaamstelling</FormLabel>
                                                        <FormControl>
                                                            <Input
                                                                tabindex="-1"
                                                                type="text"
                                                                required
                                                                placeholder="Voer hier de naam van de rekeninghouder in."
                                                                v-model="debtorFormData.debtorName"
                                                            />
                                                            <InputError :message="debtorFormData.errors.debtorName" />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                </FormField>
                                                <FormField name="debtorIban">
                                                    <FormItem>
                                                        <FormLabel>IBAN-nummer</FormLabel>
                                                        <FormControl>
                                                            <Input
                                                                tabindex="-1"
                                                                type="text"
                                                                required
                                                                placeholder="Voorbeeld IBAN: NL92ABNA1234123412"
                                                                v-model="debtorFormData.debtorIban"
                                                            />
                                                            <InputError :message="debtorFormData.errors.debtorIban" />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                </FormField>
                                            </div>
                                        </form>

                                        <DialogFooter>
                                            <Button type="submit" form="batchForm" class="hover:cursor-pointer"> Download Batch </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </Form>
                            <!-- XML BUTTON -->
                            <Form as="" :validation-schema="rapportFormSchema">
                                <!-- Start Modal -->
                                <Dialog v-model:open="isRapportModalOpen">
                                    <DialogTrigger as-child>
                                        <Button class="hover:cursor-pointer">Download Excel</Button>
                                    </DialogTrigger>
                                    <DialogContent class="sm:max-w-[425px]">
                                        <DialogHeader>
                                            <DialogTitle>Kies het tijdvak voor het Excelrapport</DialogTitle>
                                            <DialogDescription
                                                >Alleen betaalde aanvragen binnen dit tijdvak worden meegenomen in het rapport.</DialogDescription
                                            >
                                        </DialogHeader>
                                        <!-- Visible form -->
                                        <form id="rapportForm" @submit.prevent="onSubmitRapport">
                                            <div class="flex flex-col gap-4">
                                                <FormField name="startDate">
                                                    <FormItem>
                                                        <FormLabel>Start datum</FormLabel>
                                                        <FormControl>
                                                            <Input type="date" v-model="rapportFormData.startDate" />
                                                            <InputError :message="rapportFormData.errors.startDate" />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                </FormField>
                                                <FormField name="endDate">
                                                    <FormItem>
                                                        <FormLabel>Eind datum</FormLabel>
                                                        <FormControl>
                                                            <Input type="date" v-model="rapportFormData.endDate" />
                                                            <InputError :message="rapportFormData.errors.endDate" />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                </FormField>
                                            </div>
                                        </form>

                                        <DialogFooter>
                                            <Button type="submit" form="rapportForm" class="hover:cursor-pointer"> Download Rapport </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </Form>
                        </div>
                    </div>
                </div>

                <!-- Tabel -->
                <div class="flex-1 p-2">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead><strong>#</strong></TableHead>
                                <TableHead>Aanvrager</TableHead>
                                <TableHead>Ontvanger</TableHead>
                                <TableHead>
                                    <select id="eventFilter" v-model="selectedEvent" class="rounded border px-2 py-1">
                                        <option :value="null">Alle Gebeurtenissen</option>
                                        <option v-for="event in events" :key="event.id" :value="event.id">
                                            {{ event.title }}
                                        </option>
                                    </select>
                                </TableHead>
                                <TableHead>Kosten</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Aangevraagd op</TableHead>
                                <TableHead v-if="user.admin.isSuper">Uitbetaald op</TableHead>
                                <TableHead>Acties</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="request in requests.data" :key="request.id">
                                <TableCell class="font-medium">{{ request.id }}</TableCell>
                                <TableCell
                                    :class="{
                                        'text-red-700': request.employee_requester === null,
                                        'font-medium': request.employee_requester === null,
                                    }"
                                >
                                    <template v-if="request.employee_requester !== null">
                                        <a :href="'mailto:' + request.employee_requester" class="underline">
                                            {{ request.employee_requester }}
                                        </a>
                                    </template>
                                    <template v-else>
                                        <span class="text-red-700 font-medium">
                                            Geredigeerd
                                        </span>
                                    </template>
                                </TableCell>
                                <TableCell>{{ request.member?.full_name }}</TableCell>
                                <TableCell>{{ request.event_cost.event.title }}</TableCell>
                                <TableCell>&euro; {{ request.event_cost.amount }}</TableCell>
                                <TableCell>
                                    <span
                                        :class="{
                                            'text-yellow-500': request.status === statuses.Pending,
                                            'text-green-600': request.status === statuses.Accepted,
                                            'text-red-600': request.status === statuses.Rejected,
                                            'text-blue-500': request.status === statuses.Exported,
                                            'text-black': request.status === statuses.Paid,
                                            'dark:text-white': request.status === statuses.Paid,
                                            'font-bold': true,
                                        }"
                                    >
                                        {{ statusLabels[request.status as statuses] }}
                                    </span>
                                </TableCell>
                                <TableCell>{{ df.format(new Date(request.created_at)) }}</TableCell>
                                <TableCell v-if="user.admin.isSuper">{{request.paid_at ? df.format(new Date(request.paid_at)) : 'n.v.t' }}</TableCell>
                                <TableCell class="flex-row flex gap-2">
                                    <template v-if="request.status === statuses.Pending">
                                        <Button @click="changeStatus(request.id, statuses.Accepted)" class="cursor-pointer">Goedkeuren</Button>
                                        <Button @click="openReasonModal(request)" class="cursor-pointer" variant="destructive">Afwijzen</Button>
                                    </template>
                                    <template v-if="request.status === statuses.Exported && user.admin.isSuper">
                                        <Button @click="changeStatus(request.id, statuses.Paid)" class="cursor-pointer">Uitbetaald</Button>
                                    </template>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Paginatie -->
                    <div class="w-full max-w-full overflow-x-auto">
                        <Pagination :resource="requests" />
                    </div>
                </div>
            </div>
            <Dialog v-model:open="reasonModal">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Reden voor afwijzing van aanvraag.</DialogTitle>
                    </DialogHeader>
                    <DialogDescription>
                        <ul>
                            <li><strong>Aanvrager:</strong> {{ selectedReasonRow.employee_requester}}</li>
                            <li><strong>Ontvanger:</strong> {{ selectedReasonRow.member?.full_name }}</li>
                            <li><strong>Gebeurtenis:</strong> {{ selectedReasonRow.event_cost.event.title }}</li>
                        </ul>
                    </DialogDescription>
                    <div class="flex h-full w-full flex-col">
                        <textarea v-model="reason" class="border-2 text-sm" placeholder="Vul hier relevante informatie in." rows="5"></textarea>
                        <Button class="cursor-pointer ml-auto mt-2" variant="destructive" @click="changeStatus(selectedReasonRow.id, statuses.Rejected, reason); reasonModal = false; reason = undefined;">Afwijzen</Button>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
