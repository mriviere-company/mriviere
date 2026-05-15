<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowLeft, Save } from 'lucide-vue-next';
import UiButton from '@/components/atoms/UiButton.vue';
import UiField from '@/components/atoms/UiField.vue';
import { apiClient, call } from '@/api/client';

const router = useRouter();

const domain = ref('');
const label = ref('');
const publicKeyFingerprint = ref('');
const enabled = ref(true);

const submitting = ref(false);
const error = ref<string | null>(null);
const fieldErrors = ref<Record<string, string>>({});

async function submit() {
  if (submitting.value) return;
  submitting.value = true;
  error.value = null;
  fieldErrors.value = {};

  const res = await call<{ id: number }>(() =>
    apiClient.post('/admin/sites', {
      domain: domain.value.trim(),
      label: label.value.trim(),
      publicKeyFingerprint: publicKeyFingerprint.value.trim().toLowerCase(),
      enabled: enabled.value,
    }),
  );

  submitting.value = false;
  if (res.ok) {
    router.push(`/admin/sites/${res.data.id}`);
  } else {
    error.value = res.error;
    // axios error payload may include `fields` map
    const ax = (res as unknown as { fields?: Record<string, string> }).fields;
    if (ax) fieldErrors.value = ax;
  }
}
</script>

<template>
  <div class="site-create">
    <header class="site-create__head">
      <UiButton variant="ghost" size="sm" to="/admin/sites">
        <template #icon-left><ArrowLeft :size="14" /></template>
        Retour à la liste
      </UiButton>
      <h1 class="heading-section">Ajouter un site client</h1>
      <p class="text-muted">
        Le clone doit avoir <code>super_admin.enabled: true</code> dans son <code>config/super_admin.yaml</code>
        et la clé publique du central installée en <code>config/super_admin_public.pem</code>.
      </p>
    </header>

    <form class="site-create__form" @submit.prevent="submit">
      <UiField
        v-model="label"
        label="Label interne"
        required
        :error="fieldErrors.label"
        hint="Un nom court qui te parle (ex. « Showcase », « Salon Marie »)."
      />

      <UiField
        v-model="domain"
        label="Domaine"
        required
        :error="fieldErrors.domain"
        autocomplete="off"
        hint="Sans protocole, ex. exemple.rivierematthieu.com"
      />

      <UiField
        v-model="publicKeyFingerprint"
        label="Fingerprint SHA-256 de la clé publique distribuée au clone"
        required
        :error="fieldErrors.publicKeyFingerprint"
        hint="Hex 64 caractères. Demande-le à l'admin du clone (sha256sum sur la clé publique installée), ou compare avec celui du central affiché sur la liste."
      />

      <label class="site-create__toggle">
        <input v-model="enabled" type="checkbox" />
        <span>Activer immédiatement (le central enverra des requêtes à ce site)</span>
      </label>

      <p v-if="error" class="site-create__error">{{ error }}</p>

      <UiButton type="submit" variant="glow" :loading="submitting">
        Enregistrer le site
        <template #icon-right><Save :size="16" /></template>
      </UiButton>
    </form>
  </div>
</template>

<style lang="scss" scoped>
.site-create {
  max-width: 640px;
  display: flex;
  flex-direction: column;
  gap: var(--space-5);

  &__head {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
  }

  &__form {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__toggle {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--fs-sm);
    color: var(--color-text-soft);
    cursor: pointer;

    input {
      width: 18px;
      height: 18px;
      accent-color: var(--color-accent);
    }
  }

  &__error {
    color: var(--color-danger);
    font-size: var(--fs-sm);
    margin: 0;
  }

  code {
    font-family: var(--font-mono);
    background: var(--color-bg-soft);
    padding: 2px 6px;
    border-radius: var(--radius-xs);
    font-size: var(--fs-xs);
  }
}
</style>
