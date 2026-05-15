<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

type Variant = 'primary' | 'ghost' | 'outline' | 'glow';
type Size = 'sm' | 'md' | 'lg';

const props = withDefaults(defineProps<{
  variant?: Variant;
  size?: Size;
  to?: string;
  href?: string;
  target?: string;
  type?: 'button' | 'submit' | 'reset';
  disabled?: boolean;
  loading?: boolean;
}>(), {
  variant: 'primary',
  size: 'md',
  type: 'button',
});

const emit = defineEmits<{ click: [event: MouseEvent] }>();

const classes = computed(() => [
  'ui-button',
  `ui-button--${props.variant}`,
  `ui-button--${props.size}`,
  { 'ui-button--loading': props.loading, 'ui-button--disabled': props.disabled },
]);

const tag = computed(() => {
  if (props.to) return RouterLink;
  if (props.href) return 'a';
  return 'button';
});

const bindings = computed(() => {
  if (props.to) return { to: props.to };
  if (props.href) return { href: props.href, target: props.target, rel: props.target === '_blank' ? 'noopener noreferrer' : undefined };
  return { type: props.type, disabled: props.disabled || props.loading };
});
</script>

<template>
  <component :is="tag" :class="classes" v-bind="bindings" @click="(e: MouseEvent) => emit('click', e)">
    <span class="ui-button__inner">
      <slot name="icon-left" />
      <span class="ui-button__label"><slot /></span>
      <slot name="icon-right" />
    </span>
  </component>
</template>

<style lang="scss" scoped>
.ui-button {
  --btn-bg: var(--color-text);
  --btn-color: var(--color-bg);
  --btn-border: transparent;
  --btn-hover-bg: var(--color-accent);
  --btn-hover-color: #fff;

  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  padding: var(--space-3) var(--space-5);
  border: 1px solid var(--btn-border);
  border-radius: var(--radius-pill);
  background: var(--btn-bg);
  color: var(--btn-color);
  font-family: var(--font-display);
  font-size: var(--fs-sm);
  font-weight: 500;
  letter-spacing: 0.02em;
  cursor: pointer;
  user-select: none;
  white-space: nowrap;
  transition: background var(--transition-base), color var(--transition-base),
    border-color var(--transition-base), transform var(--transition-fast),
    box-shadow var(--transition-base);

  &:hover:not(.ui-button--disabled, .ui-button--loading) {
    background: var(--btn-hover-bg);
    color: var(--btn-hover-color);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
  }

  &:active {
    transform: translateY(0);
  }

  &--ghost {
    --btn-bg: transparent;
    --btn-color: var(--color-text);
    --btn-border: var(--color-border-strong);
    --btn-hover-bg: var(--color-text);
    --btn-hover-color: var(--color-bg);
  }

  &--outline {
    --btn-bg: transparent;
    --btn-color: var(--color-text);
    --btn-border: var(--color-text);
    --btn-hover-bg: var(--color-text);
    --btn-hover-color: var(--color-bg);
  }

  &--glow {
    --btn-bg: var(--color-accent);
    --btn-color: #fff;
    --btn-hover-bg: var(--color-accent-hover);
    box-shadow: 0 12px 30px -12px var(--color-accent);

    &:hover:not(.ui-button--disabled) {
      box-shadow: 0 18px 40px -10px var(--color-accent);
    }
  }

  &--sm {
    padding: var(--space-2) var(--space-4);
    font-size: var(--fs-xs);
  }

  &--lg {
    padding: var(--space-4) var(--space-6);
    font-size: var(--fs-base);
  }

  &--disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  &--loading {
    cursor: wait;

    .ui-button__inner {
      opacity: 0.4;
    }

    &::after {
      content: '';
      position: absolute;
      inset: 0;
      margin: auto;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      border: 2px solid currentColor;
      border-top-color: transparent;
      animation: spin 0.8s linear infinite;
    }
  }

  &__inner {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
  }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
