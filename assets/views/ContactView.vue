<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Send, MapPin, Mail, Phone, CheckCircle2 } from 'lucide-vue-next';
import UiButton from '@/components/atoms/UiButton.vue';
import UiField from '@/components/atoms/UiField.vue';
import { apiClient, call } from '@/api/client';

const { t } = useI18n();
const name = ref('');
const email = ref('');
const message = ref('');
const honeypot = ref('');

const submitting = ref(false);
const success = ref(false);
const error = ref<string | null>(null);

async function submit() {
  if (submitting.value) return;
  submitting.value = true;
  error.value = null;
  const res = await call<{ ok: true }>(() => apiClient.post('/contact', {
    name: name.value,
    email: email.value,
    message: message.value,
    website: honeypot.value,
  }));
  submitting.value = false;
  if (res.ok) {
    success.value = true;
    name.value = email.value = message.value = '';
  } else {
    error.value = res.error;
  }
}
</script>

<template>
  <div class="contact container">
    <section class="contact__head">
      <span class="eyebrow">{{ t('contact.eyebrow') }}</span>
      <h1 class="heading-display contact__title">
        {{ t('contact.title1') }}<br />
        <span class="text-accent">{{ t('contact.title2') }}</span>
      </h1>
      <p class="text-soft contact__lead">{{ t('contact.lead') }}</p>
    </section>

    <div class="contact__grid">
      <form class="contact__form" @submit.prevent="submit" novalidate>
        <UiField v-model="name" :label="t('contact.fieldName')" required autocomplete="name" />
        <UiField v-model="email" :label="t('contact.fieldEmail')" type="email" required autocomplete="email" />
        <UiField
          v-model="message"
          :label="t('contact.fieldMessage')"
          type="textarea"
          required
          :rows="7"
          :placeholder="t('contact.fieldMessagePlaceholder')"
        />
        <div class="contact__honey" aria-hidden="true">
          <label>
            {{ t('contact.honeypotLabel') }}
            <input v-model="honeypot" type="text" tabindex="-1" autocomplete="off" />
          </label>
        </div>

        <p v-if="error" class="contact__error">{{ error }}</p>

        <UiButton v-if="!success" type="submit" variant="glow" size="lg" :loading="submitting">
          {{ t('contact.submit') }}
          <template #icon-right><Send :size="16" /></template>
        </UiButton>

        <div v-else class="contact__success">
          <CheckCircle2 :size="20" />
          <p>{{ t('contact.successText') }}</p>
        </div>
      </form>

      <aside class="contact__aside">
        <h2 class="contact__aside-title">{{ t('contact.asideTitle') }}</h2>
        <ul>
          <li>
            <Mail :size="18" />
            <a href="mailto:contact@rivierematthieu.com">contact@rivierematthieu.com</a>
          </li>
          <li>
            <Phone :size="18" />
            <a :href="`tel:${t('common.phoneNumberHref')}`">{{ t('common.phoneNumber') }}</a>
          </li>
          <li>
            <MapPin :size="18" />
            <span>{{ t('contact.addressLine1') }}<br />{{ t('contact.addressLine2') }}</span>
          </li>
        </ul>
        <p class="contact__aside-note text-muted">{{ t('contact.addressNote') }}</p>
      </aside>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.contact {
  padding-block: var(--space-8) var(--space-10);

  &__head {
    max-width: 720px;
    margin-bottom: var(--space-8);
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__title {
    margin-block: var(--space-2);
  }

  &__lead {
    font-size: var(--fs-md);
  }

  &__grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-7);

    @media (min-width: 880px) {
      grid-template-columns: 1.4fr 1fr;
    }
  }

  &__form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
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
  }

  &__success {
    display: inline-flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-4) var(--space-5);
    border-radius: var(--radius-md);
    background: rgba(22, 163, 74, 0.12);
    color: var(--color-success);
    font-family: var(--font-display);
  }

  &__aside {
    padding: var(--space-5);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-bg-soft);
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    height: fit-content;
  }

  &__aside-title {
    font-size: var(--fs-md);
    font-family: var(--font-display);
    font-weight: 600;
  }

  ul {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
  }

  ul li {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
    color: var(--color-text-soft);

    svg {
      color: var(--color-accent);
      margin-top: 2px;
    }

    a:hover {
      color: var(--color-accent);
    }
  }

  &__aside-note {
    font-size: var(--fs-xs);
    border-top: 1px solid var(--color-border);
    padding-top: var(--space-3);
  }
}
</style>
