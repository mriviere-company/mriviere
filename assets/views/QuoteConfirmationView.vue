<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { CheckCircle2, Clock, ArrowRight } from 'lucide-vue-next';
import UiButton from '@/components/atoms/UiButton.vue';
import { apiClient, call } from '@/api/client';
import type { QuoteSummary } from '@/types';
import { localePath } from '@/router';

const { t } = useI18n();
const route = useRoute();
const quote = ref<QuoteSummary | null>(null);
const loading = ref(true);

onMounted(async () => {
  const res = await call<QuoteSummary>(() => apiClient.get(`/quotes/${route.params.id}`));
  loading.value = false;
  if (res.ok) quote.value = res.data;
});
</script>

<template>
  <div class="confirm container container--narrow section">
    <div v-if="loading" class="confirm__loading text-muted">{{ t('confirm.loading') }}</div>
    <div v-else-if="!quote" class="confirm__empty text-muted">{{ t('confirm.notFound') }}</div>
    <div v-else class="confirm__inner">
      <div class="confirm__icon">
        <CheckCircle2 v-if="quote.status === 'deposit_paid' || quote.status === 'active'" :size="40" />
        <Clock v-else :size="40" />
      </div>

      <h1 class="heading-display">
        <template v-if="quote.status === 'deposit_paid' || quote.status === 'active'">{{ t('confirm.titlePaid') }}</template>
        <template v-else>{{ t('confirm.titlePending') }}</template>
      </h1>

      <p class="text-soft confirm__lead">
        <i18n-t keypath="confirm.leadRef" tag="span">
          <template #id><code>{{ quote.id }}</code></template>
        </i18n-t><br />
        <i18n-t keypath="confirm.leadEmail" tag="span">
          <template #email><strong>{{ quote.clientEmail }}</strong></template>
        </i18n-t>
      </p>

      <p class="text-soft">{{ t('confirm.next', { hours: 24 }) }}</p>

      <UiButton variant="ghost" :to="localePath('home')" size="lg">
        {{ t('confirm.back') }}
        <template #icon-right><ArrowRight :size="18" /></template>
      </UiButton>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.confirm {
  &__inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-4);
    text-align: center;
    padding-block: var(--space-7);
  }

  &__icon {
    color: var(--color-accent);
    margin-bottom: var(--space-3);
  }

  &__lead {
    font-size: var(--fs-md);

    code {
      font-family: var(--font-mono);
      background: var(--color-bg-soft);
      padding: 2px 6px;
      border-radius: var(--radius-xs);
    }
  }
}
</style>
