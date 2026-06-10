<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import UiCard from '@/components/molecules/UiCard.vue';
import UiTag from '@/components/atoms/UiTag.vue';
import { apiClient, call } from '@/api/client';
import { formatMoney } from '@/i18n';

interface QuoteDetail {
  id: string;
  package: string;
  clientName: string;
  clientCompany?: string;
  clientEmail: string;
  clientPhone: string;
  clientAddress?: string;
  projectDescription: string;
  totalOneShot: number;
  totalMonthly: number;
  status: 'pending' | 'deposit_paid' | 'active' | 'declined' | 'cancelled';
  createdAt: string;
  stripeCheckoutSessionId?: string;
  stripeSubscriptionId?: string;
}

const route = useRoute();
const detail = ref<QuoteDetail | null>(null);
const loading = ref(true);

onMounted(async () => {
  const res = await call<QuoteDetail>(() => apiClient.get(`/admin/quotes/${route.params.id}`));
  loading.value = false;
  if (res.ok) detail.value = res.data;
});

function fmt(cents: number) {
  return formatMoney(cents, 'fr');
}
</script>

<template>
  <div class="quote-admin-detail">
    <div v-if="loading" class="text-muted">Chargement…</div>
    <template v-else-if="detail">
      <header>
        <h1 class="heading-section">Devis {{ detail.id.slice(0, 8) }}</h1>
        <UiTag>{{ detail.status }}</UiTag>
      </header>

      <UiCard padding="md">
        <h2>Client</h2>
        <p>
          <strong>{{ detail.clientName }}</strong>
          <span v-if="detail.clientCompany"> — {{ detail.clientCompany }}</span><br />
          {{ detail.clientEmail }} · {{ detail.clientPhone }}<br />
          <span v-if="detail.clientAddress" class="text-muted">{{ detail.clientAddress }}</span>
        </p>
      </UiCard>

      <UiCard padding="md">
        <h2>Forfait & montants</h2>
        <p>
          <strong>{{ detail.package }}</strong><br />
          {{ fmt(detail.totalOneShot) }} création — {{ fmt(detail.totalMonthly) }}/mois
        </p>
      </UiCard>

      <UiCard padding="md">
        <h2>Brief projet</h2>
        <pre class="quote-admin-detail__brief">{{ detail.projectDescription }}</pre>
      </UiCard>

      <UiCard v-if="detail.stripeCheckoutSessionId || detail.stripeSubscriptionId" padding="md">
        <h2>Stripe</h2>
        <p v-if="detail.stripeCheckoutSessionId">
          Checkout&nbsp;: <code>{{ detail.stripeCheckoutSessionId }}</code>
        </p>
        <p v-if="detail.stripeSubscriptionId">
          Subscription&nbsp;: <code>{{ detail.stripeSubscriptionId }}</code>
        </p>
      </UiCard>
    </template>
    <p v-else class="text-muted">Devis introuvable.</p>
  </div>
</template>

<style lang="scss" scoped>
.quote-admin-detail {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);

  header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
  }

  h2 {
    font-size: var(--fs-sm);
    font-family: var(--font-mono);
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--color-text-muted);
    margin-bottom: var(--space-2);
  }

  &__brief {
    white-space: pre-wrap;
    font-family: var(--font-body);
    font-size: var(--fs-base);
    color: var(--color-text-soft);
  }

  code {
    font-family: var(--font-mono);
    background: var(--color-bg-soft);
    padding: 2px 6px;
    border-radius: var(--radius-xs);
    font-size: var(--fs-xs);
  }
}
</style>
