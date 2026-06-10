<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { ExternalLink } from 'lucide-vue-next';
import UiCard from '@/components/molecules/UiCard.vue';
import UiTag from '@/components/atoms/UiTag.vue';
import { apiClient, call } from '@/api/client';

interface SubscriptionDto {
  id: string;
  customerEmail: string;
  packageName: string;
  amount: number;
  currency: string;
  status: string;
  currentPeriodEnd: string;
  stripeUrl: string;
}

const items = ref<SubscriptionDto[]>([]);
const loading = ref(true);

onMounted(async () => {
  const res = await call<{ subscriptions: SubscriptionDto[] }>(() => apiClient.get('/admin/subscriptions'));
  loading.value = false;
  if (res.ok) items.value = res.data.subscriptions;
});

function fmt(cents: number, currency: string) {
  return new Intl.NumberFormat('fr-CA', { style: 'currency', currency: currency.toUpperCase(), currencyDisplay: 'narrowSymbol' }).format(cents / 100);
}
</script>

<template>
  <div class="subs-admin">
    <header>
      <h1 class="heading-section">Abonnements</h1>
      <p class="text-muted">Lecture seule. Toute modification doit passer par le dashboard Stripe.</p>
    </header>

    <div v-if="loading" class="text-muted">Chargement…</div>
    <ul v-else class="subs-admin__list">
      <li v-for="sub in items" :key="sub.id">
        <UiCard padding="md">
          <div class="subs-admin__row">
            <div>
              <strong>{{ sub.customerEmail }}</strong>
              <p class="text-muted">{{ sub.packageName }} — {{ fmt(sub.amount, sub.currency) }}/mois</p>
            </div>
            <div class="subs-admin__meta">
              <UiTag :variant="sub.status === 'active' ? 'success' : 'warn'">{{ sub.status }}</UiTag>
              <a :href="sub.stripeUrl" target="_blank" rel="noopener noreferrer" class="subs-admin__link">
                Stripe <ExternalLink :size="14" />
              </a>
            </div>
          </div>
          <p class="text-muted subs-admin__footer">
            Prochain renouvellement&nbsp;: {{ new Date(sub.currentPeriodEnd).toLocaleDateString('fr-CA') }}
          </p>
        </UiCard>
      </li>
      <li v-if="items.length === 0" class="text-muted">Aucun abonnement actif pour le moment.</li>
    </ul>
  </div>
</template>

<style lang="scss" scoped>
.subs-admin {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);

  &__list {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
  }

  &__row {
    display: flex;
    justify-content: space-between;
    gap: var(--space-3);
  }

  &__meta {
    display: inline-flex;
    align-items: center;
    gap: var(--space-3);
  }

  &__link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    color: var(--color-accent);
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    text-transform: uppercase;
    letter-spacing: 0.1em;

    &:hover {
      text-decoration: underline;
    }
  }

  &__footer {
    margin-top: var(--space-3);
    font-size: var(--fs-xs);
  }
}
</style>
