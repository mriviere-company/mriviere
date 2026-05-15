<script setup lang="ts">
import { useI18n } from 'vue-i18n';
const { t } = useI18n();
</script>

<template>
  <article class="legals container container--narrow section">
    <span class="eyebrow">{{ t('legals.eyebrow') }}</span>
    <h1 class="heading-display legals__title">
      {{ t('legals.title1') }}<br /><span class="text-accent">{{ t('legals.title2') }}</span>
    </h1>

    <section><h2>{{ t('legals.h1') }}</h2><p>{{ t('legals.p1') }}</p></section>
    <section><h2>{{ t('legals.h2') }}</h2><p>{{ t('legals.p2') }}</p></section>
    <section><h2>{{ t('legals.h3') }}</h2><p>{{ t('legals.p3') }}</p></section>
    <section><h2>{{ t('legals.h4') }}</h2><p>{{ t('legals.p4') }}</p><p>{{ t('legals.p4b') }}</p></section>
    <section><h2>{{ t('legals.h5') }}</h2><p>{{ t('legals.p5') }}</p></section>
    <section><h2>{{ t('legals.h6') }}</h2><p>{{ t('legals.p6') }}</p></section>
    <section><h2>{{ t('legals.h7') }}</h2><p>{{ t('legals.p7') }}</p></section>
  </article>
</template>

<style lang="scss" scoped>
.legals {
  &__title {
    margin-block: var(--space-3) var(--space-7);
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

    a {
      color: var(--color-accent);
      text-decoration: underline;
      text-underline-offset: 4px;

      &:hover {
        text-decoration-thickness: 2px;
      }
    }
  }
}
</style>
