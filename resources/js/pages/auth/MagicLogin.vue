<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';
import { updateTheme } from '@/composables/useAppearance';

updateTheme('light');

defineProps<{
    status?: string;
}>();
const form = useForm({
    email: '',
});
const submit = () => {
    form.post(route('magic-login.send-link'), {
        onSuccess: () => {
            toast.success('Uw inlog link is verzonden naar uw e-mail. Dit kan enkele minuten duren.');
        },
        onError: (error: any) => {
            if (error.email) {
                toast.error('Invalide e-mail ingevoerd.', {
                    description: error.email,
                });
            } else {
                toast.error('Er is iets fout gegaan.', {
                    description: 'Bekijk alstublieft het ingevoerde e-mail.',
                });
            }
        },
    });
};

const backgroundStyle = {
    backgroundImage: "url('/images/liefenleed_bg_2.png')",
    'background-size': 'contain',
    'background-repeat': 'no-repeat',
    'background-position': 'center',
    // 'background-color': '#f7dca5',
}
const bgColor = {
    'background-color': '#f7dca5'
}
</script>

<template>
    <Head title="Inloggen" />

    <Toaster position="top-right" expand rich-colors />
    <div class="grid grid-cols-3 grid-rows-3 min-h-screen bg-gray-100" :style="bgColor">
        <!-- 🖼️ Image at row 1, col 2 -->
        <div class="row-start-1 col-start-2 flex items-center justify-center" :style="backgroundStyle">
<!--            <img-->
<!--                src="/images/liefenleed_bg_2.png"-->
<!--                alt="Lief & Leed Logo"-->
<!--                class="max-w-full max-h-full object-contain"-->
<!--            />-->
        </div>

        <!-- 💬 Center card at row 2, col 2 -->
        <div
            class="h-fit mx-auto col-start-1 row-start-2 col-span-3 w-full max-w-md rounded-xl border border-gray-500/80 bg-white/60 backdrop-blur-md p-8 shadow-lg"
        >
            <h1 class="mb-4 text-2xl font-bold outlined-text">Lief & Leed</h1>
            <p class="mb-6 font-bold text-gray-600">
                Voer je e-mailadres in om een loginlink te ontvangen.
            </p>

            <form @submit.prevent="submit">
                <input
                    type="email"
                    v-model="form.email"
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    class="mb-4 w-full rounded border border-gray-300 px-4 py-2"
                    placeholder="mail@almere.nl"
                    required
                />
                <Button
                    type="submit"
                    class="w-full rounded bg-slate-900 px-6 py-2 text-white hover:bg-slate-700"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Inlog aanvraag verwerken...' : 'Verstuur link om in te loggen' }}
                </Button>
            </form>
        </div>
    </div>
</template>
