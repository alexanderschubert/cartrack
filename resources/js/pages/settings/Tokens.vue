<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Copy, KeyRound, Trash2 } from '@lucide/vue';
import HeadingSmall from '@/components/Heading.vue';

type ApiToken = { id: number; name: string; abilities: string[]; last_used_at: string | null; created_at: string };
const props = defineProps<{ tokens: ApiToken[]; createdToken: string | null }>();
const form = useForm({ name: '', abilities: ['odometer:read', 'odometer:write'] });
function createToken() { form.post('/settings/api-tokens', { preserveScroll: true, onSuccess: () => form.reset('name') }); }
function revokeToken(id: number) { router.delete(`/settings/api-tokens/${id}`, { preserveScroll: true }); }
function date(value: string | null) { return value ? new Intl.DateTimeFormat('de-DE', { dateStyle: 'medium' }).format(new Date(value)) : 'Noch nie verwendet'; }
async function copyToken() { if (props.createdToken) await navigator.clipboard.writeText(props.createdToken); }
</script>

<template>
    <Head title="API-Tokens" />
    <div class="space-y-6">
        <HeadingSmall title="API-Tokens" description="Widerrufbare Zugangsdaten für iOS-Kurzbefehle und Home Assistant." />
        <div v-if="createdToken" class="rounded-lg border border-amber-500/40 bg-amber-500/10 p-4 text-sm">
            <p class="mb-2 font-semibold">Token wird nur jetzt im Klartext angezeigt. Kopiere ihn und verwahre ihn sicher.</p>
            <div class="flex flex-wrap items-center gap-2"><code class="max-w-full break-all rounded bg-black/20 p-2">{{ createdToken }}</code><button type="button" class="rounded border px-3 py-2" @click="copyToken"><Copy class="mr-1 inline size-4" />Kopieren</button></div>
        </div>
        <form class="space-y-4 rounded-lg border border-sidebar-border p-4" @submit.prevent="createToken">
            <label class="grid gap-2 text-sm font-medium">Tokenname<input v-model="form.name" class="rounded-md border border-input bg-background px-3 py-2 font-normal" placeholder="iOS Kurzbefehl Polo" required maxlength="100" /></label>
            <fieldset class="grid gap-2"><legend class="text-sm font-medium">Berechtigungen</legend><label v-for="ability in ['odometer:read', 'odometer:write', 'fuel:read', 'fuel:write']" :key="ability" class="flex items-center gap-2 text-sm"><input v-model="form.abilities" type="checkbox" :value="ability" />{{ ability }}</label></fieldset>
            <p v-if="form.errors.abilities" class="text-sm text-destructive">{{ form.errors.abilities }}</p>
            <button class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground" :disabled="form.processing"><KeyRound class="size-4" />Token erzeugen</button>
        </form>
        <div class="space-y-3">
            <h3 class="text-sm font-semibold">Aktive Tokens</h3>
            <div v-if="tokens.length" class="divide-y divide-sidebar-border rounded-lg border border-sidebar-border">
                <div v-for="token in tokens" :key="token.id" class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div><p class="font-medium">{{ token.name }}</p><p class="mt-1 text-xs text-muted-foreground">{{ token.abilities.join(' · ') }} · zuletzt verwendet: {{ date(token.last_used_at) }}</p></div>
                    <button type="button" class="rounded-md border border-destructive/40 px-3 py-2 text-sm text-destructive" @click="revokeToken(token.id)"><Trash2 class="mr-1 inline size-4" />Widerrufen</button>
                </div>
            </div>
            <p v-else class="rounded-lg border border-sidebar-border p-4 text-sm text-muted-foreground">Noch keine API-Tokens erstellt.</p>
        </div>
    </div>
</template>
