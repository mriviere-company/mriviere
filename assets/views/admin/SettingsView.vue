<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Save, CheckCircle2, Upload, Trash2 } from 'lucide-vue-next';
import UiButton from '@/components/atoms/UiButton.vue';
import UiField from '@/components/atoms/UiField.vue';
import { apiClient, call } from '@/api/client';
import type { ProfileDto } from '@/types';

const bio = ref('');
const stack = ref('');
const photoUrl = ref('');
const loading = ref(true);
const saving = ref(false);
const saved = ref(false);
const error = ref<string | null>(null);
const uploading = ref(false);
const uploadError = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

onMounted(async () => {
  const res = await call<ProfileDto>(() => apiClient.get('/profile'));
  loading.value = false;
  if (res.ok) {
    bio.value = res.data.bio;
    stack.value = res.data.stack.join(', ');
    photoUrl.value = res.data.photoUrl ?? '';
  }
});

async function save() {
  saving.value = true;
  error.value = null;
  saved.value = false;
  const res = await call<{ ok: true }>(() => apiClient.put('/admin/profile', {
    bio: bio.value,
    stack: stack.value.split(',').map((s) => s.trim()).filter(Boolean),
    photoUrl: photoUrl.value.trim() || null,
  }));
  saving.value = false;
  if (res.ok) {
    saved.value = true;
    setTimeout(() => (saved.value = false), 3000);
  } else {
    error.value = res.error;
  }
}

async function onFilePicked(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;
  uploadError.value = null;
  uploading.value = true;

  const form = new FormData();
  form.append('file', file);
  form.append('kind', 'profile');

  const res = await call<{ url: string }>(() =>
    apiClient.post('/admin/upload', form, { headers: { 'Content-Type': 'multipart/form-data' } }),
  );
  uploading.value = false;
  input.value = '';

  if (res.ok) {
    photoUrl.value = res.data.url;
  } else {
    uploadError.value = res.error;
  }
}

function clearPhoto() {
  photoUrl.value = '';
}
</script>

<template>
  <div class="settings-admin">
    <header>
      <h1 class="heading-section">Profil public</h1>
      <p class="text-muted">Édité ici, affiché sur la page <code>/profil</code>.</p>
    </header>

    <form v-if="!loading" class="settings-admin__form" @submit.prevent="save">
      <UiField v-model="bio" label="Biographie" type="textarea" :rows="8" required />
      <UiField v-model="stack" label="Stack technique (séparés par virgule)" type="text" hint="Ex. PHP 8.4, Symfony 8, Vue 3, Stripe" />
      <div class="settings-admin__photo">
        <UiField v-model="photoUrl" label="Photo de profil — URL ou chemin local" type="text" hint="Upload (JPG/PNG/WebP ≤ 2 Mo) ou colle un chemin. Vide = monogramme SVG en fallback." />
        <div class="settings-admin__photo-actions">
          <UiButton type="button" variant="ghost" size="sm" :loading="uploading" @click="fileInput?.click()">
            <template #icon-left><Upload :size="14" /></template>
            Choisir un fichier
          </UiButton>
          <UiButton v-if="photoUrl" type="button" variant="ghost" size="sm" @click="clearPhoto">
            <template #icon-left><Trash2 :size="14" /></template>
            Retirer
          </UiButton>
          <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="settings-admin__file-input"
            @change="onFilePicked"
          />
        </div>
        <p v-if="uploadError" class="settings-admin__error">{{ uploadError }}</p>
        <figure v-if="photoUrl" class="settings-admin__preview">
          <img :src="photoUrl" alt="Aperçu de la photo de profil" />
        </figure>
      </div>
      <p v-if="error" class="settings-admin__error">{{ error }}</p>
      <div class="settings-admin__actions">
        <UiButton type="submit" variant="primary" :loading="saving">
          Enregistrer
          <template #icon-right><Save :size="16" /></template>
        </UiButton>
        <span v-if="saved" class="settings-admin__saved"><CheckCircle2 :size="14" /> Enregistré</span>
      </div>
    </form>
    <p v-else class="text-muted">Chargement…</p>
  </div>
</template>

<style lang="scss" scoped>
.settings-admin {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);

  &__form {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    max-width: 720px;
  }

  &__error {
    color: var(--color-danger);
    font-size: var(--fs-sm);
  }

  &__actions {
    display: inline-flex;
    align-items: center;
    gap: var(--space-3);
  }

  &__saved {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    color: var(--color-success);
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    text-transform: uppercase;
    letter-spacing: 0.1em;
  }

  &__photo {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
  }

  &__photo-actions {
    display: inline-flex;
    flex-wrap: wrap;
    gap: var(--space-2);
  }

  &__file-input {
    display: none;
  }

  &__preview {
    margin: var(--space-2) 0 0;
    width: 160px;
    aspect-ratio: 4 / 5;
    border-radius: var(--radius-sm);
    overflow: hidden;
    border: 1px solid var(--color-border);

    img { width: 100%; height: 100%; object-fit: cover; display: block; }
  }
}
</style>
