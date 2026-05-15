<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { Check, ArrowRight } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import UiButton from '@/components/atoms/UiButton.vue';
import { usePackagesStore } from '@/stores/packages';
import { formatMoney } from '@/i18n';
import { localePath } from '@/router';

const route = useRoute();
const { t } = useI18n();
const packagesStore = usePackagesStore();
const { items: packages } = storeToRefs(packagesStore);

onMounted(() => packagesStore.ensureLoaded());

const pkg = computed(() => packages.value.find((p) => p.slug === route.params.slug));
</script>

<template>
  <div class="package container container--narrow section">
    <template v-if="pkg">
      <span class="eyebrow">{{ t('package.eyebrow') }}</span>
      <h1 class="heading-display package__title">{{ pkg.name }}</h1>
      <p class="text-soft package__lead">{{ t('package.lead', { n: pkg.maxPages }) }}</p>

      <div class="package__pricing">
        <div>
          <span class="text-muted">{{ t('package.creationLabel') }}</span>
          <p class="package__price">{{ formatMoney(pkg.oneShotPrice) }}</p>
        </div>
        <div>
          <span class="text-muted">{{ t('package.subscriptionLabel') }}</span>
          <p class="package__price">{{ formatMoney(pkg.monthlyPrice) }}<small>{{ t('package.perMonthShort') }}</small></p>
        </div>
      </div>

      <h2 class="heading-section package__h2">{{ t('package.includedTitle') }}</h2>
      <ul class="package__features">
        <li v-for="f in pkg.features.filter(f => f.included)" :key="f.label">
          <Check :size="18" /> {{ f.label }}
        </li>
      </ul>

      <UiButton variant="glow" size="lg" :to="`${localePath('quote')}?pkg=${pkg.slug}`">
        {{ t('package.ctaQuote', { name: pkg.name }) }}
        <template #icon-right><ArrowRight :size="18" /></template>
      </UiButton>
    </template>
    <p v-else class="text-muted">{{ t('package.notFound') }}</p>
  </div>
</template>

<style lang="scss" scoped>
.package {
  &__title {
    margin-block: var(--space-3) var(--space-4);
  }

  &__lead {
    font-size: var(--fs-md);
    max-width: 60ch;
  }

  &__pricing {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-5);
    padding: var(--space-5);
    margin-block: var(--space-7);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-bg-soft);
  }

  &__price {
    font-family: var(--font-display);
    font-size: var(--fs-2xl);
    font-weight: 600;
    margin-top: var(--space-1);

    small {
      font-size: var(--fs-md);
      color: var(--color-text-muted);
      font-weight: 400;
    }
  }

  &__h2 {
    margin-bottom: var(--space-5);
  }

  &__features {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-3);
    margin-bottom: var(--space-7);

    @media (min-width: 640px) {
      grid-template-columns: 1fr 1fr;
    }

    li {
      display: flex;
      align-items: center;
      gap: var(--space-3);
      color: var(--color-text-soft);

      svg {
        color: var(--color-accent);
      }
    }
  }
}
</style>
