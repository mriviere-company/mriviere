<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import UiTag from '@/components/atoms/UiTag.vue';
import { apiClient, call } from '@/api/client';
import { formatMoney } from '@/i18n';
import type { QuoteSummary } from '@/types';

const items = ref<QuoteSummary[]>([]);
const loading = ref(true);
const filter = ref<'all' | QuoteSummary['status']>('all');
const search = ref('');

onMounted(async () => {
  const res = await call<{ quotes: QuoteSummary[] }>(() => apiClient.get('/admin/quotes'));
  loading.value = false;
  if (res.ok) items.value = res.data.quotes;
});

const filtered = computed(() => {
  return items.value.filter((q) => {
    if (filter.value !== 'all' && q.status !== filter.value) return false;
    if (search.value && !`${q.clientName} ${q.clientEmail}`.toLowerCase().includes(search.value.toLowerCase())) return false;
    return true;
  });
});

function fmt(cents: number) {
  return formatMoney(cents, 'fr');
}

function statusVariant(s: QuoteSummary['status']) {
  if (s === 'deposit_paid' || s === 'active') return 'success';
  if (s === 'declined' || s === 'cancelled') return 'danger';
  return 'warn';
}

function statusLabel(s: QuoteSummary['status']) {
  return ({
    pending: 'En attente',
    deposit_paid: 'Acompte payé',
    active: 'Actif',
    declined: 'Refusé',
    cancelled: 'Annulé',
  } as const)[s];
}
</script>

<template>
  <div class="quotes-admin">
    <header>
      <h1 class="heading-section">Devis</h1>
      <p class="text-muted">{{ items.length }} demande(s) au total.</p>
    </header>

    <div class="quotes-admin__filters">
      <input v-model="search" type="search" placeholder="Rechercher par nom ou email…" />
      <select v-model="filter">
        <option value="all">Tous les statuts</option>
        <option value="pending">En attente</option>
        <option value="deposit_paid">Acompte payé</option>
        <option value="active">Actif</option>
        <option value="declined">Refusé</option>
        <option value="cancelled">Annulé</option>
      </select>
    </div>

    <div v-if="loading" class="text-muted">Chargement…</div>
    <table v-else class="quotes-admin__table">
      <thead>
        <tr>
          <th>Client</th>
          <th>Forfait</th>
          <th>Acompte</th>
          <th>Mensuel</th>
          <th>Statut</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="q in filtered" :key="q.id">
          <td>
            <RouterLink :to="`/admin/quotes/${q.id}`">
              <strong>{{ q.clientName }}</strong>
              <span class="text-muted">{{ q.clientEmail }}</span>
            </RouterLink>
          </td>
          <td>{{ q.package }}</td>
          <td>{{ fmt(q.totalOneShot) }}</td>
          <td>{{ fmt(q.totalMonthly) }}/mois</td>
          <td><UiTag :variant="statusVariant(q.status)">{{ statusLabel(q.status) }}</UiTag></td>
          <td class="text-muted">{{ new Date(q.createdAt).toLocaleDateString('fr-CA') }}</td>
        </tr>
        <tr v-if="filtered.length === 0">
          <td colspan="6" class="text-muted">Aucun devis ne correspond.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style lang="scss" scoped>
.quotes-admin {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);

  &__filters {
    display: flex;
    gap: var(--space-3);
    flex-wrap: wrap;

    input,
    select {
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

    input {
      flex: 1;
      min-width: 240px;
    }
  }

  &__table {
    width: 100%;
    border-collapse: collapse;
    font-size: var(--fs-sm);

    th, td {
      padding: var(--space-3);
      text-align: left;
      border-bottom: 1px solid var(--color-border);
    }

    th {
      font-family: var(--font-mono);
      font-size: var(--fs-xs);
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--color-text-muted);
      font-weight: 500;
    }

    td a {
      display: flex;
      flex-direction: column;
      gap: 2px;

      &:hover strong {
        color: var(--color-accent);
      }
    }
  }
}
</style>
