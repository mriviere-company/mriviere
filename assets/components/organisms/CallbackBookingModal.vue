<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { Phone, X, CheckCircle2, ArrowRight } from 'lucide-vue-next';
import UiButton from '@/components/atoms/UiButton.vue';
import UiField from '@/components/atoms/UiField.vue';
import { apiClient, call } from '@/api/client';

const props = defineProps<{ open: boolean }>();
const emit = defineEmits<{ close: [] }>();

const { t } = useI18n();

const dialogRef = ref<HTMLDialogElement | null>(null);

const name = ref('');
const email = ref('');
const phone = ref('');
const slot = ref('');
const message = ref('');
const honeypot = ref('');

const submitting = ref(false);
const error = ref<string | null>(null);
const success = ref(false);

// Bornes du créneau : maintenant + 30 min, jusqu'à 30 jours dans le futur.
function pad(n: number): string { return String(n).padStart(2, '0'); }
function formatDatetimeLocal(d: Date): string {
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}
function nextHalfHour(d: Date): Date {
  const r = new Date(d);
  r.setSeconds(0, 0);
  r.setMinutes(r.getMinutes() + (30 - (r.getMinutes() % 30)));
  return r;
}
const slotMin = computed(() => formatDatetimeLocal(nextHalfHour(new Date())));
const slotMax = computed(() => {
  const d = new Date();
  d.setDate(d.getDate() + 30);
  return formatDatetimeLocal(d);
});

watch(
  () => props.open,
  async (isOpen) => {
    await nextTick();
    if (!dialogRef.value) return;
    if (isOpen) {
      if (!dialogRef.value.open) dialogRef.value.showModal();
      success.value = false;
      error.value = null;
    } else if (dialogRef.value.open) {
      dialogRef.value.close();
    }
  },
);

function reset() {
  name.value = '';
  email.value = '';
  phone.value = '';
  slot.value = '';
  message.value = '';
  honeypot.value = '';
  error.value = null;
  success.value = false;
}

function onClose() {
  reset();
  emit('close');
}

async function submit() {
  if (submitting.value) return;
  submitting.value = true;
  error.value = null;
  const res = await call<{ ok: true }>(() =>
    apiClient.post('/callbacks', {
      name: name.value,
      email: email.value,
      phone: phone.value,
      slot: slot.value,
      message: message.value || null,
      website: honeypot.value,
    }),
  );
  submitting.value = false;
  if (res.ok) {
    success.value = true;
  } else {
    error.value = res.error;
  }
}
</script>

<template>
  <dialog ref="dialogRef" class="callback-modal" @close="onClose">
    <button type="button" class="callback-modal__close" :aria-label="t('callbackModal.close')" @click="onClose">
      <X :size="18" />
    </button>

    <div v-if="!success" class="callback-modal__body">
      <header class="callback-modal__head">
        <Phone :size="22" class="callback-modal__icon" />
        <h2>{{ t('callbackModal.title') }}</h2>
        <p class="text-soft">{{ t('callbackModal.lead') }}</p>
      </header>

      <form class="callback-modal__form" @submit.prevent="submit" novalidate>
        <UiField v-model="name" :label="t('callbackModal.fieldName')" required autocomplete="name" />
        <UiField
          v-model="email"
          :label="t('callbackModal.fieldEmail')"
          type="email"
          autocomplete="email"
          :hint="t('callbackModal.fieldEmailHelp')"
        />
        <UiField
          v-model="phone"
          :label="t('callbackModal.fieldPhone')"
          type="tel"
          required
          autocomplete="tel"
          :hint="t('callbackModal.fieldPhoneHelp')"
        />

        <div class="ui-field">
          <label class="ui-field__label" for="callback-slot">
            {{ t('callbackModal.fieldSlot') }}<span class="ui-field__req" aria-hidden="true">*</span>
          </label>
          <input
            id="callback-slot"
            v-model="slot"
            class="ui-field__input"
            type="datetime-local"
            :min="slotMin"
            :max="slotMax"
            step="1800"
            required
          />
          <p class="ui-field__hint">{{ t('callbackModal.fieldSlotHelp') }}</p>
        </div>

        <UiField
          v-model="message"
          :label="t('callbackModal.fieldMessage')"
          type="textarea"
          :rows="3"
          :placeholder="t('callbackModal.fieldMessagePlaceholder')"
        />

        <div class="callback-modal__honey" aria-hidden="true">
          <label>
            {{ t('contact.honeypotLabel') }}
            <input v-model="honeypot" type="text" tabindex="-1" autocomplete="off" />
          </label>
        </div>

        <p v-if="error" class="callback-modal__error">{{ error }}</p>

        <UiButton type="submit" variant="glow" size="lg" :loading="submitting">
          {{ t('callbackModal.submit') }}
          <template #icon-right><ArrowRight :size="16" /></template>
        </UiButton>
      </form>
    </div>

    <div v-else class="callback-modal__success">
      <CheckCircle2 :size="40" class="callback-modal__success-icon" />
      <h2>{{ t('callbackModal.successTitle') }}</h2>
      <p class="text-soft">{{ t('callbackModal.successText') }}</p>
      <UiButton variant="ghost" @click="onClose">{{ t('callbackModal.close') }}</UiButton>
    </div>
  </dialog>
</template>

<style lang="scss" scoped>
.callback-modal {
  --modal-padding: var(--space-6);

  width: min(540px, 92vw);
  max-height: 92dvh;
  margin: auto;
  padding: var(--modal-padding);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--color-bg-elev);
  color: var(--color-text);
  box-shadow: var(--shadow-lg);
  overflow-y: auto;

  &::backdrop {
    background: var(--color-overlay);
    backdrop-filter: blur(4px);
  }

  &__close {
    position: absolute;
    top: var(--space-3);
    right: var(--space-3);
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    border-radius: var(--radius-pill);
    color: var(--color-text-muted);
    transition: background var(--transition-base), color var(--transition-base);

    &:hover {
      background: var(--color-bg-soft);
      color: var(--color-text);
    }
  }

  &__icon {
    color: var(--color-accent);
  }

  &__head {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    margin-bottom: var(--space-5);

    h2 {
      font-size: var(--fs-xl);
      font-family: var(--font-display);
    }
  }

  &__form {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__honey {
    position: absolute;
    left: -9999px;
    width: 1px;
    height: 1px;
    overflow: hidden;
  }

  &__error {
    color: var(--color-danger);
    font-size: var(--fs-sm);
    margin: 0;
  }

  &__success {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: var(--space-3);
    padding-block: var(--space-6);

    h2 {
      font-size: var(--fs-xl);
      font-family: var(--font-display);
    }
  }

  &__success-icon {
    color: var(--color-success);
  }
}

// Reuse the native datetime-local field styling consistent with UiField
.ui-field {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);

  &__label {
    font-family: var(--font-display);
    font-size: var(--fs-sm);
    font-weight: 500;
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
    font-family: var(--font-body);
    color-scheme: dark light;

    &:focus {
      outline: 0;
      border-color: var(--color-accent);
    }
  }

  &__hint {
    font-size: var(--fs-xs);
    color: var(--color-text-muted);
    margin: 0;
  }
}
</style>
