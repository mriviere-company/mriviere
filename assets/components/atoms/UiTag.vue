<script setup lang="ts">
withDefaults(defineProps<{
  variant?: 'default' | 'accent' | 'success' | 'warn' | 'danger';
}>(), {
  variant: 'default',
});
</script>

<template>
  <span class="ui-tag" :class="`ui-tag--${variant}`">
    <slot />
  </span>
</template>

<style lang="scss" scoped>
.ui-tag {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  padding: 2px var(--space-3);
  border-radius: var(--radius-pill);
  font-family: var(--font-mono);
  font-size: var(--fs-xs);
  font-weight: 500;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  background: var(--color-bg-soft);
  color: var(--color-text-soft);
  border: 1px solid var(--color-border);

  &--accent {
    background: var(--color-accent-soft);
    color: var(--color-accent);
    border-color: transparent;
  }

  &--success {
    background: rgba(22, 163, 74, 0.12);
    color: var(--color-success);
    border-color: transparent;
  }

  &--warn {
    background: rgba(245, 158, 11, 0.12);
    color: var(--color-warn);
    border-color: transparent;
  }

  &--danger {
    background: rgba(220, 38, 38, 0.12);
    color: var(--color-danger);
    border-color: transparent;
  }
}
</style>
