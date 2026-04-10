<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, EventCost, SharedData, User } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Aanvragen', href: '/request' },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User ?? null;

interface Props {
    events: EventCost[]
}

const props = defineProps<Props>();

const form = useForm({
    employee_requester: user.email,
    employee_recipient: '',
    event_cost_id: 0,
    iban: '',
    account_name: ''
});

/**
 * IBAN
 */

/**
 * Validate IBAN using MOD-97 check (ISO 7064 MOD 97-10)
 * @param {string} iban - IBAN string (may include spaces)
 * @returns {boolean}
 */
function validateIBAN(iban: string): boolean {
    if (!iban || typeof iban !== 'string') return false;

    // 1. Normalize: remove spaces, upper-case
    const ref = iban.replace(/\s+/g, '').toUpperCase();

    // Basic format check: 2 letters (country), 2 digits (check), rest alnum, length between 15 and 34
    if (!/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/.test(ref)) return false;

    // 2. Rearrange: move first 4 chars to the end
    const rearranged = ref.slice(4) + ref.slice(0, 4);

    // 3. Convert letters to numbers: A=10, B=11, ... Z=35
    // Build a numeric string
    let numeric = '';
    for (let i = 0; i < rearranged.length; i++) {
        const ch = rearranged.charAt(i);
        if (ch >= 'A' && ch <= 'Z') {
            numeric += (ch.charCodeAt(0) - 55).toString(); // 'A'->65 -> 10
        } else {
            numeric += ch;
        }
    }

    // 4. Compute mod 97 on the numeric string in a chunked manner to avoid huge integers
    // Process by taking up to 9 digits at a time (safe for Number)
    let remainder = 0;
    const chunkSize = 9;
    for (let pos = 0; pos < numeric.length; pos += chunkSize) {
        const chunk = numeric.substring(pos, pos + chunkSize);
        // combine remainder and next chunk as string, then mod
        const combined = remainder.toString() + chunk;
        // parseInt is safe here because combined length <= ~16 digits (remainder small + chunkSize)
        remainder = parseInt(combined, 10) % 97;
    }

    // Valid IBAN if remainder === 1
    return remainder === 1;
}

/**
 * Compute IBAN check digits for country code + BBAN (returns string of two digits)
 * Use when generating IBAN: pass countryCode like 'NL' and BBAN like 'ABNA0417164300' (without check digits)
 * @param {string} countryCode - two-letter country code
 * @param {string} bban - BBAN string (alphanumeric)
 * @returns {string} two-digit check digits (like "91")
 */
function computeIBANCheckDigits(countryCode: string, bban: string) {
    if (!/^[A-Z]{2}$/i.test(countryCode)) {
        throw new Error('countryCode must be two letters');
    }
    const cc = countryCode.toUpperCase();
    const normalizedBBAN = (bban || '').replace(/\s+/g, '').toUpperCase();

    // assemble with placeholder '00' for check digits
    const tentative = normalizedBBAN + cc + '00';

    // convert to numeric string (A=10..Z=35)
    let numeric = '';
    for (let i = 0; i < tentative.length; i++) {
        const ch = tentative.charAt(i);
        if (ch >= 'A' && ch <= 'Z') {
            numeric += (ch.charCodeAt(0) - 55).toString();
        } else {
            numeric += ch;
        }
    }

    // compute mod 97
    let remainder = 0;
    const chunkSize = 9;
    for (let pos = 0; pos < numeric.length; pos += chunkSize) {
        const chunk = numeric.substring(pos, pos + chunkSize);
        const combined = remainder.toString() + chunk;
        remainder = parseInt(combined, 10) % 97;
    }

    const checkDigits = 98 - remainder;
    return checkDigits < 10 ? '0' + checkDigits : '' + checkDigits;
}

// Form submit handler
function submitForm() {

    // Basic IBAN format validation (NL only)
    // const ibanRegex = /^NL\d{2}[A-Z]{4}\d{10}$/;
    // if (!ibanRegex.test(iban.value.toUpperCase())) {
    //     alert('Ongeldig IBAN formaat. Gebruik NL gevolgd door 2 cijfers, 4 hoofdletters en 10 cijfers.');
    //     return;
    // }
    const iban = form.iban.replace(/\s+/g, '').toUpperCase();
    const valid = validateIBAN(iban);
    if (!valid) {
        toast.error("IBAN is niet valide")
        return;
    }

    const countryCode = iban.slice(0, 2);
    const checkSum = iban.slice(2, 4)
    const bban = iban.slice(4);
    const checkDigit = computeIBANCheckDigits(countryCode, bban)
    if (checkSum != checkDigit) {
        toast.error(`IBAN is niet valide, cijfer controle: ${checkSum} uw opgegeven: ${checkDigit}`);
        return;
    }

    form.post(route('requests.store'), {
        onSuccess: () => {
            form.reset()
            // toast.success('Aanvraag succesvol ingediend');
        },
        onError: (err) => console.error(err)
    })

}

const backgroundStyle = {
    backgroundImage: "url('/images/liefenleed_bg.png')",
    'background-color': '#f7dca5',
}
</script>
<template>
    <Head title="Aanvragen" />
    <AppLayout :breadcrumbs="breadcrumbs">
<!--        <div class="h-full bg-contain bg-center bg-no-repeat" :style="backgroundStyle">-->
<!--        <div class="h-full bg-cover bg-center bg-no-repeat" :style="backgroundStyle">-->
<!--        <div class="h-full bg-contain bg-center" :style="backgroundStyle">-->
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
<!--                <div class="h-full bg-center bg-contain bg-no-repeat" :style="backgroundStyle">-->
<!--                            <div class="h-full bg-contain bg-center bg-no-repeat" :style="backgroundStyle">-->
                <div class="h-full bg-contain bg-left bg-no-repeat" :style="backgroundStyle">
                    <div class="h-full flex flex-col justify-center">
                        <div class="mx-auto max-w-lg rounded-xl border border-gray-500/80 bg-white/40 backdrop-blur-sm p-8 shadow-lg">
                            <form @submit.prevent="submitForm">
                                <!-- Your Email -->
                                <div class="relative z-0 w-full mb-5 group">
                                    <label for="your-email" class="outlined-text block mb-2 font-medium ">Jouw e-mailadres</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 16">
                                                <path d="m10.036 8.278 9.258-7.79A1.979 1.979 0 0 0 18 0H2A1.987 1.987 0 0 0 .641.541l9.395 7.737Z"/>
                                                <path d="M11.241 9.817c-.36.275-.801.425-1.255.427-.428 0-.845-.138-1.187-.395L0 2.6V14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V2.5l-8.759 7.317Z"/>
                                            </svg>
                                        </div>
                                        <input
                                            v-model="form.employee_requester"
                                            type="email"
                                            id="your-email"
                                            required
                                            readonly
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="naam@almere.nl"
                                        />
                                    </div>
                                    <InputError :message="form.errors.employee_requester" />
                                </div>

                                <!-- Recipient Email -->
                                <div class="relative z-0 w-full mb-5 group">
                                    <label for="recipient-email" class="outlined-text block mb-2 font-medium text-gray-900 dark:text-white">E-mailadres van degene voor wie je het aanvraagd <em>(Almere e-mail)</em>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 16">
                                                <path d="m10.036 8.278 9.258-7.79A1.979 1.979 0 0 0 18 0H2A1.987 1.987 0 0 0 .641.541l9.395 7.737Z"/>
                                                <path d="M11.241 9.817c-.36.275-.801.425-1.255.427-.428 0-.845-.138-1.187-.395L0 2.6V14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V2.5l-8.759 7.317Z"/>
                                            </svg>
                                        </div>
                                        <input
                                            v-model="form.employee_recipient"
                                            type="email"
                                            id="recipient-email"
                                            required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="ontvanger@almere.nl"
                                        />
                                    </div>
                                    <InputError :message="form.errors.employee_recipient" />
                                </div>

                                <!-- Occasion -->
                                <div class="relative z-0 w-full mb-5 group">
                                    <label for="occasion" class="outlined-text block mb-2 font-medium text-gray-900 dark:text-white">Gelegenheid</label>
                                    <select
                                        v-model="form.event_cost_id"
                                        id="occasion"
                                        required
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    >
                                        <option disabled value="">Gelegenheid</option>
                                        <template v-for="event in props.events" :key="event.id">
                                            <option :value="event.id">{{ event.event.title }} </option> <!--(&euro;{{ event.amount }}) -->
                                        </template>
                                    </select>
                                    <InputError :message="form.errors.event_cost_id" />
                                </div>

                                <!-- Full Name & IBAN -->
                                <div class="relative z-0 w-full mb-5 group">
                                    <p class="outlined-text pb-2 text-gray-700 dark:text-gray-400">De bijdrage wordt naar jou rekening overgemaakt en daar hebben we de onderstaande gegevens voor nodig.</p>
                                    <div class="mb-6 flex flex-col justify-around sm:flex-row gap-4">
                                        <div class="flex flex-col justify-between">
                                            <label for="full_name" class="outlined-text mb-2 block text-sm font-medium text-gray-900 dark:text-white">Volledige naam (zoals op bankpas)</label>
                                            <input
                                                v-model="form.account_name"
                                                type="text"
                                                id="full_name"
                                                required
                                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                                                placeholder="Te naamstelling"
                                            />
                                            <InputError :message="form.errors.account_name" />
                                        </div>
                                        <div class="flex flex-col justify-between">
                                            <label for="iban-input" class="outlined-text mb-2 block text-sm font-medium text-gray-900 dark:text-white">IBAN-nummer</label>
                                            <input
                                                v-model="form.iban"
                                                type="text"
                                                id="iban-input"
                                                required
                                                title="Format: NL followed by 2 digits, 4 uppercase letters, and 10 digits"
                                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pe-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 uppercase dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                placeholder="NL02ABNA0123456789"
                                                @input="form.iban = form.iban.toUpperCase()"
                                            />
<!--                                                pattern="^NL\d{2}[A-Z]{4}\d{10}$"-->
                                        </div>
                                    </div>
                                </div>

                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                    >
                                    {{ form.processing ? 'Verwerken...' : 'Aanvragen' }}
                                </Button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
