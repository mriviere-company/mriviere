<script setup lang="ts">
withDefaults(defineProps<{
  hoverable?: boolean;
  highlighted?: boolean;
  padding?: 'sm' | 'md' | 'lg';
}>(), {
  padding: 'md',
});
</script>

<template>
  <article
    class="ui-card"
    :class="[
      `ui-card--p-${padding}`,
      { 'ui-card--hoverable': hoverable, 'ui-card--highlighted': highlighted },
    ]"
  >
    <slot />
  </article>
</template>

<style lang="scss" scoped>
.ui-card {
  position: relative;
  background: var(--color-bg-elev);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  overflow: hidden;
  transition: border-color var(--transition-base), transform var(--transition-base),
    box-shadow var(--transition-base);

  &--p-sm { padding: var(--space-4); }
  &--p-md { padding: var(--space-5); }
  &--p-lg { padding: var(--space-6); }

  &--hoverable {
    cursor: pointer;

    &:hover {
      border-color: var(--color-text);
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }
  }

  &--highlighted {
    border-color: var(--color-accent);
    box-shadow: 0 0 0 1px var(--color-accent);

    &::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(
        circle at top right,
        var(--color-accent-soft),
        transparent 60%
      );
      pointer-events: none;
    }
  }
}
</style>
