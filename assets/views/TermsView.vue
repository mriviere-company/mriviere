<script setup lang="ts">
import { useI18n } from 'vue-i18n';
const { t } = useI18n();
</script>

<template>
  <article class="terms container container--narrow section">
    <span class="eyebrow">{{ t('terms.eyebrow') }}</span>
    <h1 class="heading-display terms__title">
      {{ t('terms.title1') }}<br /><span class="text-accent">{{ t('terms.title2') }}</span>
    </h1>
    <p class="text-soft terms__intro">{{ t('terms.intro') }}</p>

    <section><h2>{{ t('terms.h1') }}</h2><p>{{ t('terms.p1') }}</p></section>
    <section><h2>{{ t('terms.h2') }}</h2><p>{{ t('terms.p2') }}</p></section>
    <section><h2>{{ t('terms.h3') }}</h2><p>{{ t('terms.p3') }}</p></section>
    <section><h2>{{ t('terms.h4') }}</h2><p>{{ t('terms.p4') }}</p></section>
    <section><h2>{{ t('terms.h5') }}</h2><p>{{ t('terms.p5') }}</p></section>
    <section><h2>{{ t('terms.h6') }}</h2><p>{{ t('terms.p6') }}</p></section>
    <section><h2>{{ t('terms.h7') }}</h2><p>{{ t('terms.p7') }}</p></section>
    <section><h2>{{ t('terms.h8') }}</h2><p>{{ t('terms.p8') }}</p></section>
    <section><h2>{{ t('terms.h9') }}</h2><p>{{ t('terms.p9') }}</p></section>
  </article>
</template>

<style lang="scss" scoped>
.terms {
  &__title {
    margin-block: var(--space-3) var(--space-4);
  }

  &__intro {
    margin-bottom: var(--space-7);
    font-size: var(--fs-md);
  }

  section {
    margin-bottom: var(--space-6);

    h2 {
      font-size: var(--fs-lg);
      margin-bottom: var(--space-3);
    }

    p {
      color: var(--color-text-soft);
      margin-bottom: var(--space-3);
    }
  }
}
</style>
