<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Check, Minus, ChevronDown } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import UiButton from '@/components/atoms/UiButton.vue';
import UiCard from '@/components/molecules/UiCard.vue';
import UiTag from '@/components/atoms/UiTag.vue';
import { usePackagesStore } from '@/stores/packages';
import { formatMoney } from '@/i18n';
import { localePath } from '@/router';

const { t } = useI18n();
const packagesStore = usePackagesStore();
const { items: packages } = storeToRefs(packagesStore);

onMounted(() => packagesStore.ensureLoaded());

const faqKeys = ['faq1', 'faq2', 'faq3', 'faq4', 'faq5'];

const openFaq = ref<number | null>(null);
function toggleFaq(idx: number) {
  openFaq.value = openFaq.value === idx ? null : idx;
}
</script>

<template>
  <div class="pricing">
    <section class="pricing__hero">
      <div class="container">
        <span class="eyebrow">{{ t('home.pricingEyebrow') }}</span>
        <h1 class="heading-display pricing__title">
          {{ t('pricing.title1') }}<br />
          <span class="text-accent">{{ t('pricing.title2') }}</span>
        </h1>
        <p class="text-soft pricing__lead">{{ t('pricing.lead') }}</p>
      </div>
    </section>

    <section class="pricing__cards container">
      <div v-if="packages.length === 0" class="pricing__empty text-muted">{{ t('common.loading') }}</div>
      <div v-else class="pricing__grid">
        <UiCard
          v-for="pkg in packages"
          :key="pkg.slug"
          padding="lg"
          :highlighted="pkg.highlighted"
          class="pricing__card"
        >
          <div class="pricing__card-head">
            <h2>{{ pkg.name }}</h2>
            <UiTag v-if="pkg.highlighted" variant="accent">{{ t('pricing.recommended') }}</UiTag>
          </div>
          <p class="text-muted">{{ t('pricing.pagesIncluded', { n: pkg.maxPages }) }}</p>
          <div class="pricing__price">
            <span class="pricing__price-main">{{ formatMoney(pkg.oneShotPrice) }}</span>
            <span class="text-muted">{{ t('pricing.create') }}</span>
          </div>
          <div class="pricing__monthly text-soft">
            + <strong>{{ formatMoney(pkg.monthlyPrice) }}</strong> {{ t('pricing.perMonth') }}
          </div>
          <ul class="pricing__features">
            <li v-for="f in pkg.features" :key="f.label" :class="{ 'is-out': !f.included }">
              <Check v-if="f.included" :size="16" />
              <Minus v-else :size="16" />
              <span>{{ f.label }}</span>
            </li>
          </ul>
          <UiButton :variant="pkg.highlighted ? 'glow' : 'primary'" :to="`${localePath('quote')}?pkg=${pkg.slug}`" size="lg">
            {{ t('pricing.choose', { name: pkg.name }) }}
          </UiButton>
        </UiCard>
      </div>
    </section>

    <section class="section pricing__faq container container--narrow">
      <span class="eyebrow">{{ t('pricing.faqEyebrow') }}</span>
      <h2 class="heading-section">{{ t('pricing.faqTitle') }}</h2>
      <ul class="faq">
        <li v-for="(key, i) in faqKeys" :key="key" :class="{ 'is-open': openFaq === i }">
          <button type="button" class="faq__btn" @click="toggleFaq(i)" :aria-expanded="openFaq === i">
            <span>{{ t(`pricing.${key}Q`) }}</span>
            <ChevronDown :size="18" class="faq__icon" />
          </button>
          <div class="faq__body" :hidden="openFaq !== i">
            <p>{{ t(`pricing.${key}A`) }}</p>
          </div>
        </li>
      </ul>
    </section>
  </div>
</template>

<style lang="scss" scoped>
.pricing {
  &__hero {
    padding-block: var(--space-8) var(--space-7);
  }

  &__title {
    margin-block: var(--space-3) var(--space-4);
  }

  &__lead {
    max-width: 60ch;
    font-size: var(--fs-md);
  }

  &__cards {
    margin-bottom: var(--space-9);
  }

  &__empty {
    padding: var(--space-7);
    text-align: center;
  }

  &__grid {
    display: grid;
    gap: var(--space-5);

    @media (min-width: 880px) {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  &__card {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
  }

  &__card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;

    h2 {
      font-size: var(--fs-xl);
    }
  }

  &__price {
    display: flex;
    align-items: baseline;
    gap: var(--space-2);
    margin-top: var(--space-3);
  }

  &__price-main {
    font-family: var(--font-display);
    font-size: var(--fs-3xl);
    font-weight: 600;
    letter-spacing: -0.04em;
  }

  &__monthly {
    margin-bottom: var(--space-3);
  }

  &__features {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    margin-block: var(--space-3) var(--space-5);

    li {
      display: flex;
      align-items: center;
      gap: var(--space-2);
      color: var(--color-text-soft);

      svg {
        color: var(--color-accent);
        flex-shrink: 0;
      }

      &.is-out {
        color: var(--color-text-muted);
        text-decoration: line-through;
        text-decoration-color: var(--color-border-strong);

        svg {
          color: var(--color-text-muted);
        }
      }
    }
  }
}

.faq {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  margin-top: var(--space-5);

  li {
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    overflow: hidden;
    transition: border-color var(--transition-base);

    &.is-open {
      border-color: var(--color-text);
    }
  }

  &__btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    padding: var(--space-4) var(--space-5);
    text-align: left;
    font-family: var(--font-display);
    font-size: var(--fs-md);
    color: var(--color-text);
  }

  &__icon {
    transition: transform var(--transition-base);
    color: var(--color-text-muted);
  }

  li.is-open &__icon {
    transform: rotate(-180deg);
    color: var(--color-accent);
  }

  &__body {
    padding: 0 var(--space-5) var(--space-5);
    color: var(--color-text-soft);
    line-height: var(--lh-relaxed);
  }
}
</style>
