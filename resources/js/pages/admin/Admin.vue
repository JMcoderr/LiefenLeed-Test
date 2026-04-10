<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Admin, BreadcrumbItem } from '@/types';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { ref } from 'vue';
import { Switch } from '@/components/ui/switch';
import { toast } from 'vue-sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'admin',
        href: '/admin',
    },
];

interface Props {
    admins: Admin[];
}

const props = defineProps<Props>();

const modalOpen = ref(false);
const formAdmin = useForm({
    employee: '',
    super: false,
});
const submitNewAdmin = () => {
    formAdmin.post(route('admin.admins.store'), {
        onSuccess: () => {
            formAdmin.reset()
            modalOpen.value = false

        },
        onError: (error: any) => console.log(error),
    });
};

const superStatus = (admin: number, to: boolean) => {
    router.patch(route('admin.admins.update', admin), {
        super: to
    }, {
        preserveState: true,
        preserveScroll: true,

        onError: (error: any) => toast.error(error.super_delete)
    });
}


/**
 * Delete admin Modal and Functions
 *
 */
const selectedAdmin = ref<Admin>(null!);
const deleteAdminModal = ref(false);

const openAdminModal = (admin: Admin) => {
    selectedAdmin.value = admin;
    deleteAdminModal.value = true;
}

const closeAdminModal = () => deleteAdminModal.value = false;

const deleteAdmin = (admin: number) => {
    router.delete(route('admin.admins.destroy', admin), {
        onSuccess: () => deleteAdminModal.value = false,
        onError: (error: any) => toast.error(error.super_delete)
    })
}
</script>

<template>
    <Head title="Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative flex min-h-[100vh] flex-1 flex-col rounded-xl border md:min-h-min"
            >
                <div class="flex w-full justify-end">
                    <Dialog v-model:open="modalOpen">
                        <DialogTrigger class="ml-auto p-4">
                            <Button>Admin Toevoegen</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Admin Toevoegen</DialogTitle>
                            </DialogHeader>
                            <DialogDescription></DialogDescription>
                            <div>
                                <form @submit.prevent="submitNewAdmin">
                                    <div class="mb-4">
                                        <label>E-mail medewerker</label>
                                        <input v-model="formAdmin.employee" type="email" class="w-full rounded border p-2" />
                                        <div v-if="formAdmin.errors.employee" class="text-sm text-red-600">{{ formAdmin.errors.employee }}</div>
                                    </div>
                                    <div class="mb-4">
                                        <label>Super</label>
                                        <input type="checkbox" v-model="formAdmin.super" class="w-full rounded border p-2" />
                                        <div v-if="formAdmin.errors.super" class="text-sm text-red-600">{{ formAdmin.errors.super }}</div>
                                    </div>
                                    <Button type="submit" :disabled="formAdmin.processing">
                                        {{ formAdmin.processing ? 'Toevoegen...' : 'Creëren' }}
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
                                <TableHead>#</TableHead>
                                <TableHead>E-mail medewerker</TableHead>
                                <TableHead>Super</TableHead>
                                <TableHead>Acties</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="admin in props.admins" :key="admin.id">
                                <TableCell>{{ admin.id }}</TableCell>
                                <TableCell>{{ admin.employee }}</TableCell>
                                <TableCell>
                                    <Switch :model-value="admin.super" @update:model-value="superStatus(admin.id, !admin.super)" />
<!--                                    <template v-if="admin.super">{{ admin.super }}</template>-->
<!--                                    <template v-else>false</template>-->
                                </TableCell>
                                <TableCell>
                                    <Button variant="destructive" @click="openAdminModal(admin)">Verwijder</Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- Global Modal -->
            <Dialog v-model:open="deleteAdminModal">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Verwijder Admin?</DialogTitle>
                    </DialogHeader>
                    <DialogDescription>Weet u zeker dat u de administrator <b><i>{{ selectedAdmin.employee }}</i></b> wilt verwijderen?</DialogDescription>
                    <div class="flex flex-row w-full justify-around">
                        <Button class="cursor-pointer" @click="closeAdminModal">
                            Nee
                        </Button>
                        <Button class="cursor-pointer" variant="destructive" @click="deleteAdmin(selectedAdmin.id)" >
                            Ja
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
