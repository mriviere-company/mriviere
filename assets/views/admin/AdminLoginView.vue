<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { LogIn } from 'lucide-vue-next';
import UiButton from '@/components/atoms/UiButton.vue';
import UiField from '@/components/atoms/UiField.vue';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const email = ref('');
const password = ref('');
const submitting = ref(false);
const error = ref<string | null>(null);

async function submit() {
  if (submitting.value) return;
  submitting.value = true;
  error.value = null;
  const res = await auth.login(email.value, password.value);
  submitting.value = false;
  if (res.ok) {
    const target = (route.query.redirect as string | undefined) ?? '/admin/dashboard';
    router.replace(target);
  } else {
    error.value = res.error ?? 'Identifiants incorrects.';
  }
}
</script>

<template>
  <div class="admin-login">
    <form class="admin-login__card" @submit.prevent="submit">
      <h1 class="admin-login__title">Back-office</h1>
      <p class="text-muted">Connexion administrateur.</p>
      <UiField v-model="email" label="Email" type="email" required autocomplete="username" />
      <UiField v-model="password" label="Mot de passe" type="text" required autocomplete="current-password" />
      <p v-if="error" class="admin-login__error">{{ error }}</p>
      <UiButton type="submit" variant="glow" :loading="submitting">
        Se connecter
        <template #icon-right><LogIn :size="16" /></template>
      </UiButton>
    </form>
  </div>
</template>

<style lang="scss" scoped>
.admin-login {
  display: grid;
  place-items: center;
  min-height: 100vh;
  padding: var(--space-5);

  &__card {
    width: 100%;
    max-width: 400px;
    padding: var(--space-7);
    border-radius: var(--radius-md);
    border: 1px solid var(--color-border);
    background: var(--color-bg-elev);
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    box-shadow: var(--shadow-md);
  }

  &__title {
    font-size: var(--fs-2xl);
    font-family: var(--font-display);
  }

  &__error {
    color: var(--color-danger);
    font-size: var(--fs-sm);
  }
}
</style>
