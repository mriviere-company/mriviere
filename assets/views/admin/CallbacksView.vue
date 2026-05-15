<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Phone, Calendar, Mail, Archive } from 'lucide-vue-next';
import UiCard from '@/components/molecules/UiCard.vue';
import UiTag from '@/components/atoms/UiTag.vue';
import { apiClient, call } from '@/api/client';

interface CallbackDto {
  id: number;
  name: string;
  email: string;
  phone: string;
  preferredSlot: string;
  message: string | null;
  status: 'unread' | 'read' | 'archived';
  createdAt: string;
}

const items = ref<CallbackDto[]>([]);
const loading = ref(true);
const filter = ref<'all' | 'unread' | 'read' | 'archived'>('all');

onMounted(async () => {
  const res = await call<{ callbacks: CallbackDto[] }>(() => apiClient.get('/admin/callbacks'));
  loading.value = false;
  if (res.ok) items.value = res.data.callbacks;
});

const filtered = computed(() =>
  filter.value === 'all' ? items.value : items.value.filter((c) => c.status === filter.value),
);

async function markAsRead(id: number) {
  const res = await call<{ ok: true }>(() => apiClient.post(`/admin/callbacks/${id}/read`, {}));
  if (res.ok) {
    const item = items.value.find((c) => c.id === id);
    if (item) item.status = 'read';
  }
}

async function archive(id: number) {
  const res = await call<{ ok: true }>(() => apiClient.post(`/admin/callbacks/${id}/archive`, {}));
  if (res.ok) {
    const item = items.value.find((c) => c.id === id);
    if (item) item.status = 'archived';
  }
}

function formatSlot(iso: string): string {
  const d = new Date(iso);
  return d.toLocaleString('fr-CA', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    timeZone: 'America/Toronto',
  });
}

function formatPhoneHref(phone: string): string {
  return phone.replace(/[\s\-.()]/g, '');
}

function statusVariant(s: CallbackDto['status']) {
  if (s === 'unread') return 'accent';
  if (s === 'archived') return 'default';
  return 'success';
}

function statusLabel(s: CallbackDto['status']) {
  return ({ unread: 'Non lu', read: 'Rappelé', archived: 'Archivé' } as const)[s];
}
</script>

<template>
  <div class="callbacks-admin">
    <header class="callbacks-admin__head">
      <div>
        <h1 class="heading-section">Demandes de rappel</h1>
        <p class="text-muted">{{ items.length }} demande(s) au total — clic sur le numéro pour appeler.</p>
      </div>
      <select v-model="filter" class="callbacks-admin__filter">
        <option value="all">Tous les statuts</option>
        <option value="unread">Non lus</option>
        <option value="read">Rappelés</option>
        <option value="archived">Archivés</option>
      </select>
    </header>

    <div v-if="loading" class="text-muted">Chargement…</div>
    <ul v-else class="callbacks-admin__list">
      <li v-for="cb in filtered" :key="cb.id">
        <UiCard padding="md">
          <header class="callbacks-admin__row-head">
            <div>
              <strong>{{ cb.name }}</strong>
              <span v-if="cb.email" class="text-muted"> · {{ cb.email }}</span>
            </div>
            <UiTag :variant="statusVariant(cb.status)">{{ statusLabel(cb.status) }}</UiTag>
          </header>

          <div class="callbacks-admin__highlights">
            <a class="callbacks-admin__phone" :href="`tel:${formatPhoneHref(cb.phone)}`">
              <Phone :size="16" /> {{ cb.phone }}
            </a>
            <span class="callbacks-admin__slot">
              <Calendar :size="16" /> {{ formatSlot(cb.preferredSlot) }} <em>(heure du Québec)</em>
            </span>
            <a v-if="cb.email" class="callbacks-admin__email" :href="`mailto:${cb.email}`">
              <Mail :size="16" /> {{ cb.email }}
            </a>
          </div>

          <p v-if="cb.message" class="callbacks-admin__message">{{ cb.message }}</p>

          <footer class="callbacks-admin__foot text-muted">
            <span>Reçue le {{ new Date(cb.createdAt).toLocaleString('fr-CA') }}</span>
            <div class="callbacks-admin__actions">
              <button v-if="cb.status === 'unread'" type="button" @click="markAsRead(cb.id)">Marquer comme rappelé</button>
              <button v-if="cb.status !== 'archived'" type="button" @click="archive(cb.id)">
                <Archive :size="14" /> Archiver
              </button>
            </div>
          </footer>
        </UiCard>
      </li>
      <li v-if="filtered.length === 0" class="text-muted">Aucune demande à afficher.</li>
    </ul>
  </div>
</template>

<style lang="scss" scoped>
.callbacks-admin {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);

  &__head {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: var(--space-3);
    flex-wrap: wrap;
  }

  &__filter {
    padding: var(--space-2) var(--space-3);
    border: 1px solid var(--color-border-strong);
    border-radius: var(--radius-sm);
    background: var(--color-bg);
    color: var(--color-text);

    &:focus {
      outline: none;
      border-color: var(--color-accent);
    }
  }

  &__list {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
  }

  &__row-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--space-3);
    margin-bottom: var(--space-3);
  }

  &__highlights {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3) var(--space-5);
    padding: var(--space-3) var(--space-4);
    background: var(--color-accent-soft);
    border-left: 3px solid var(--color-accent);
    border-radius: var(--radius-sm);
    font-size: var(--fs-sm);

    a, span {
      display: inline-flex;
      align-items: center;
      gap: var(--space-2);
      color: var(--color-accent);
    }

    a:hover {
      text-decoration: underline;
    }

    em {
      font-style: normal;
      color: var(--color-text-muted);
      font-size: var(--fs-xs);
    }
  }

  &__phone {
    font-family: var(--font-mono);
    font-weight: 600;
  }

  &__slot {
    font-family: var(--font-display);
  }

  &__email {
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    word-break: break-all;
  }

  &__message {
    margin-top: var(--space-3);
    color: var(--color-text-soft);
    line-height: var(--lh-relaxed);
    white-space: pre-wrap;
  }

  &__foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: var(--space-3);
    padding-top: var(--space-3);
    border-top: 1px solid var(--color-border);
    font-size: var(--fs-xs);
    font-family: var(--font-mono);
    flex-wrap: wrap;
    gap: var(--space-2);
  }

  &__actions {
    display: inline-flex;
    gap: var(--space-3);

    button {
      display: inline-flex;
      align-items: center;
      gap: var(--space-1);
      color: var(--color-accent);
      text-transform: uppercase;
      letter-spacing: 0.1em;

      &:hover {
        text-decoration: underline;
      }
    }
  }
}
</style>
