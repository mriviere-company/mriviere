<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { LayoutDashboard, FileText, Mail, CreditCard, Settings, LogOut, Phone, ExternalLink, Server, Globe } from 'lucide-vue-next';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const { locale } = useI18n();

onMounted(async () => {
  if (!auth.user) await auth.fetchMe();
  if (!auth.user) router.replace({ name: 'admin-login' });
});

const links = [
  { to: '/admin/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { to: '/admin/quotes', label: 'Devis', icon: FileText },
  { to: '/admin/callbacks', label: 'Rappels', icon: Phone },
  { to: '/admin/messages', label: 'Messages', icon: Mail },
  { to: '/admin/subscriptions', label: 'Abonnements', icon: CreditCard },
  { to: '/admin/sites', label: 'Sites clients', icon: Server },
  { to: '/admin/aggregated', label: 'Vue agrégée', icon: Globe },
  { to: '/admin/settings', label: 'Profil', icon: Settings },
];

const publicSiteUrl = computed(() => `/${locale.value}`);

async function logout() {
  await auth.logout();
  router.replace({ name: 'admin-login' });
}
</script>

<template>
  <div class="admin-shell">
    <aside class="admin-shell__sidebar">
      <div class="admin-shell__brand">
        <span class="admin-shell__mark">MR</span>
        <span class="admin-shell__title">Back-office</span>
      </div>
      <nav>
        <RouterLink
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="admin-shell__link"
          :class="{ 'is-active': route.path.startsWith(link.to) }"
        >
          <component :is="link.icon" :size="16" />
          <span>{{ link.label }}</span>
        </RouterLink>
      </nav>
      <div class="admin-shell__bottom">
        <a class="admin-shell__public-link" :href="publicSiteUrl" target="_blank" rel="noopener">
          <ExternalLink :size="14" /> Voir le site
        </a>
        <p class="text-muted">{{ auth.user?.email }}</p>
        <button type="button" class="admin-shell__logout" @click="logout">
          <LogOut :size="14" /> Déconnexion
        </button>
      </div>
    </aside>
    <section class="admin-shell__content">
      <RouterView />
    </section>
  </div>
</template>

<style lang="scss" scoped>
.admin-shell {
  display: grid;
  grid-template-columns: 240px 1fr;
  min-height: 100vh;

  @media (max-width: 720px) {
    grid-template-columns: 1fr;
  }

  &__sidebar {
    border-right: 1px solid var(--color-border);
    padding: var(--space-5);
    background: var(--color-bg-soft);
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
  }

  &__brand {
    display: inline-flex;
    align-items: center;
    gap: var(--space-3);
  }

  &__mark {
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    border-radius: var(--radius-sm);
    background: var(--color-text);
    color: var(--color-bg);
    font-family: var(--font-display);
    font-weight: 600;
    font-size: var(--fs-xs);
  }

  &__title {
    font-family: var(--font-display);
    font-weight: 600;
    font-size: var(--fs-sm);
  }

  nav {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    flex: 1;
  }

  &__link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-sm);
    color: var(--color-text-soft);
    font-size: var(--fs-sm);
    transition: background var(--transition-base), color var(--transition-base);

    &:hover {
      background: var(--color-bg-elev);
      color: var(--color-text);
    }

    &.is-active {
      background: var(--color-text);
      color: var(--color-bg);
    }
  }

  &__bottom {
    border-top: 1px solid var(--color-border);
    padding-top: var(--space-3);
    font-size: var(--fs-xs);
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
  }

  &__public-link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-3);
    border: 1px solid var(--color-border-strong);
    border-radius: var(--radius-pill);
    color: var(--color-text);
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    width: fit-content;
    transition: background var(--transition-base), border-color var(--transition-base);

    &:hover {
      background: var(--color-accent-soft);
      border-color: var(--color-accent);
      color: var(--color-accent);
    }
  }

  &__logout {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    color: var(--color-text-soft);
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    text-transform: uppercase;
    letter-spacing: 0.1em;

    &:hover {
      color: var(--color-danger);
    }
  }

  &__content {
    padding: var(--space-6) var(--space-7);
    overflow-x: hidden;
  }
}
</style>
