<script setup lang="ts">
import { computed, useId } from 'vue';

const props = withDefaults(defineProps<{
  modelValue: string;
  label: string;
  type?: 'text' | 'email' | 'tel' | 'url' | 'textarea';
  placeholder?: string;
  required?: boolean;
  error?: string;
  hint?: string;
  rows?: number;
  autocomplete?: string;
  disabled?: boolean;
}>(), {
  type: 'text',
  rows: 5,
});

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const id = useId();
const errorId = computed(() => props.error ? `${id}-error` : undefined);
const hintId = computed(() => props.hint && !props.error ? `${id}-hint` : undefined);

function onInput(e: Event) {
  emit('update:modelValue', (e.target as HTMLInputElement | HTMLTextAreaElement).value);
}
</script>

<template>
  <div class="ui-field" :class="{ 'ui-field--error': !!error }">
    <label :for="id" class="ui-field__label">
      {{ label }}<span v-if="required" class="ui-field__req" aria-hidden="true">*</span>
    </label>
    <textarea
      v-if="type === 'textarea'"
      :id="id"
      class="ui-field__input ui-field__input--textarea"
      :value="modelValue"
      :placeholder="placeholder"
      :rows="rows"
      :required="required"
      :disabled="disabled"
      :aria-describedby="errorId ?? hintId"
      :aria-invalid="!!error"
      @input="onInput"
    />
    <input
      v-else
      :id="id"
      class="ui-field__input"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :autocomplete="autocomplete"
      :aria-describedby="errorId ?? hintId"
      :aria-invalid="!!error"
      @input="onInput"
    />
    <p v-if="error" :id="errorId" class="ui-field__error">{{ error }}</p>
    <p v-else-if="hint" :id="hintId" class="ui-field__hint">{{ hint }}</p>
  </div>
</template>

<style lang="scss" scoped>
.ui-field {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);

  &__label {
    font-family: var(--font-display);
    font-size: var(--fs-sm);
    font-weight: 500;
    color: var(--color-text);
  }

  &__req {
    color: var(--color-accent);
    margin-left: 2px;
  }

  &__input {
    background: transparent;
    border: 0;
    border-bottom: 1px solid var(--color-border-strong);
    padding: var(--space-3) 0;
    color: var(--color-text);
    font-size: var(--fs-base);
    transition: border-color var(--transition-base), padding var(--transition-base);

    &::placeholder {
      color: var(--color-text-muted);
    }

    &:focus {
      outline: 0;
      border-color: var(--color-accent);
    }

    &--textarea {
      resize: vertical;
      min-height: 120px;
      border: 1px solid var(--color-border-strong);
      border-radius: var(--radius-sm);
      padding: var(--space-3) var(--space-4);

      &:focus {
        border-color: var(--color-accent);
      }
    }

    &:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
  }

  &__error {
    font-size: var(--fs-xs);
    color: var(--color-danger);
  }

  &__hint {
    font-size: var(--fs-xs);
    color: var(--color-text-muted);
  }

  &--error .ui-field__input {
    border-color: var(--color-danger);
  }
}
</style>
