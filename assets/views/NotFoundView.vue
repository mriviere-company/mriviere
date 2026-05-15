<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import UiButton from '@/components/atoms/UiButton.vue';
import { ArrowRight } from 'lucide-vue-next';
import { localePath } from '@/router';

const { t } = useI18n();
</script>

<template>
  <div class="not-found container container--narrow section">
    <span class="not-found__digits">404</span>
    <h1 class="heading-display">
      {{ t('notFound.title1') }}<br /><span class="text-accent">{{ t('notFound.title2') }}</span>
    </h1>
    <p class="text-soft">{{ t('notFound.lead') }}</p>
    <UiButton variant="glow" :to="localePath('home')">
      {{ t('notFound.back') }}
      <template #icon-right><ArrowRight :size="16" /></template>
    </UiButton>
  </div>
</template>

<style lang="scss" scoped>
.not-found {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
  align-items: flex-start;
  padding-block: var(--space-9);

  &__digits {
    font-family: var(--font-display);
    font-weight: 700;
    font-size: clamp(7rem, 22vw, 14rem);
    line-height: 1;
    letter-spacing: -0.06em;
    background: linear-gradient(120deg, var(--color-accent), var(--color-accent-2));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
  }
}
</style>
