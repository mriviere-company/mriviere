<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { ArrowLeft, Plus, Power, KeyRound, Copy, Check } from 'lucide-vue-next';
import UiButton from '@/components/atoms/UiButton.vue';
import UiField from '@/components/atoms/UiField.vue';
import UiTag from '@/components/atoms/UiTag.vue';
import { apiClient, call } from '@/api/client';

interface RemoteAdminDto {
  id: number;
  email: string;
  enabled: boolean;
  lastLoginAt: string | null;
  createdAt: string;
}

const route = useRoute();
const admins = ref<RemoteAdminDto[]>([]);
const loading = ref(true);
const newEmail = ref('');
const creating = ref(false);
const createError = ref<string | null>(null);
const tempPassword = ref<string | null>(null);
const tempPasswordEmail = ref<string | null>(null);
const copied = ref(false);

async function load() {
  loading.value = true;
  const res = await call<{ admins: RemoteAdminDto[] }>(() =>
    apiClient.get(`/admin/sites/${route.params.id}/admins`),
  );
  loading.value = false;
  if (res.ok) admins.value = res.data.admins;
}

onMounted(load);

async function create() {
  if (!newEmail.value.trim()) return;
  creating.value = true;
  createError.value = null;
  tempPassword.value = null;
  copied.value = false;
  const res = await call<{ admin: RemoteAdminDto; temporaryPassword: string }>(() =>
    apiClient.post(`/admin/sites/${route.params.id}/admins`, { email: newEmail.value.trim() }),
  );
  creating.value = false;
  if (res.ok) {
    tempPassword.value = res.data.temporaryPassword;
    tempPasswordEmail.value = res.data.admin?.email ?? newEmail.value.trim();
    newEmail.value = '';
    await load();
  } else {
    createError.value = res.error;
  }
}

async function toggle(admin: RemoteAdminDto) {
  const action = admin.enabled ? 'disable' : 'enable';
  const res = await call<{ ok: true }>(() =>
    apiClient.post(`/admin/sites/${route.params.id}/admins/${admin.id}/${action}`),
  );
  if (res.ok) await load();
}

async function copyPassword() {
  if (!tempPassword.value) return;
  await navigator.clipboard.writeText(tempPassword.value);
  copied.value = true;
  setTimeout(() => (copied.value = false), 2000);
}
</script>

<template>
  <div class="site-admins">
    <UiButton variant="ghost" size="sm" :to="`/admin/sites/${route.params.id}`">
      <template #icon-left><ArrowLeft :size="14" /></template>
      Retour au site
    </UiButton>

    <header>
      <h1 class="heading-section">Admins distants</h1>
      <p class="text-muted">CRUD via l'API super-admin du clone. Le mot de passe temporaire n'est affiché qu'une fois — copie-le immédiatement.</p>
    </header>

    <section v-if="tempPassword" class="site-admins__temp-password">
      <KeyRound :size="14" />
      <div>
        <p>Mot de passe temporaire pour <strong>{{ tempPasswordEmail }}</strong></p>
        <code>{{ tempPassword }}</code>
      </div>
      <UiButton size="sm" variant="ghost" @click="copyPassword">
        <template #icon-left><Check v-if="copied" :size="14" /><Copy v-else :size="14" /></template>
        {{ copied ? 'Copié' : 'Copier' }}
      </UiButton>
    </section>

    <form class="site-admins__create" @submit.prevent="create">
      <UiField v-model="newEmail" label="Ajouter un admin (email)" type="email" required />
      <UiButton type="submit" variant="primary" :loading="creating">
        <template #icon-left><Plus :size="14" /></template>
        Créer
      </UiButton>
    </form>
    <p v-if="createError" class="site-admins__error">{{ createError }}</p>

    <section class="site-admins__panel">
      <h2>Admins enregistrés</h2>
      <p v-if="loading" class="text-muted">Chargement…</p>
      <p v-else-if="!admins.length" class="text-muted">Aucun admin sur le clone (ou opt-in désactivé côté clone).</p>
      <table v-else class="site-admins__table">
        <thead>
          <tr><th>Email</th><th>Statut</th><th>Dernière connexion</th><th>Créé</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="a in admins" :key="a.id">
            <td>{{ a.email }}</td>
            <td>
              <UiTag :variant="a.enabled ? 'success' : 'warn'">{{ a.enabled ? 'actif' : 'désactivé' }}</UiTag>
            </td>
            <td>{{ a.lastLoginAt ? new Date(a.lastLoginAt).toLocaleString('fr-CA') : '—' }}</td>
            <td>{{ new Date(a.createdAt).toLocaleDateString('fr-CA') }}</td>
            <td>
              <UiButton size="sm" :variant="a.enabled ? 'ghost' : 'primary'" @click="toggle(a)">
                <template #icon-left><Power :size="14" /></template>
                {{ a.enabled ? 'Désactiver' : 'Réactiver' }}
              </UiButton>
            </td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>

<style lang="scss" scoped>
.site-admins {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);

  &__temp-password {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-4);
    border-radius: var(--radius-md);
    background: rgba(43, 91, 215, 0.12);
    border: 1px solid var(--color-accent-2);

    p { margin: 0; font-size: var(--fs-sm); }
    code { display: inline-block; padding: var(--space-1) var(--space-2); background: rgba(0,0,0,0.25); border-radius: var(--radius-sm); font-family: var(--font-mono); }
  }

  &__create {
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: end;
    gap: var(--space-3);
    max-width: 640px;
  }

  &__panel {
    padding: var(--space-5);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-bg-elev);

    h2 {
      font-family: var(--font-display);
      font-size: var(--fs-md);
      margin: 0 0 var(--space-3);
    }
  }

  &__table {
    width: 100%;
    border-collapse: collapse;
    font-size: var(--fs-sm);

    th, td {
      padding: var(--space-2) var(--space-3);
      border-bottom: 1px solid var(--color-border);
      text-align: left;
    }

    th {
      font-family: var(--font-mono);
      font-size: var(--fs-xs);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--color-text-muted);
    }
  }

  &__error {
    margin: 0;
    color: var(--color-danger);
    font-size: var(--fs-sm);
  }
}
</style>
