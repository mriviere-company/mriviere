<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { Sun, Moon, Menu, X, Globe, ExternalLink } from 'lucide-vue-next';
import UiButton from '@/components/atoms/UiButton.vue';
import { useTheme } from '@/composables/useTheme';
import { localePath, switchLocale } from '@/router';
import type { AppLocale } from '@/i18n';

const EXAMPLE_SITE_URL = 'https://exemple.rivierematthieu.com';

const route = useRoute();
const { t, locale } = useI18n();
const { theme, toggle } = useTheme();
const scrolled = ref(false);
const mobileOpen = ref(false);

function onScroll() {
  scrolled.value = window.scrollY > 24;
}

onMounted(() => {
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll);
});

const navItems = computed(() => [
  { name: 'home' as const, label: t('nav.home') },
  { name: 'pricing' as const, label: t('nav.pricing') },
  { name: 'profile' as const, label: t('nav.profile') },
  { name: 'contact' as const, label: t('nav.contact') },
]);

function isActive(name: 'home' | 'pricing' | 'profile' | 'contact'): boolean {
  if (name === 'home') {
    // /, /fr, /en all map to the home page.
    return route.path === '/' || route.path === '/fr' || route.path === '/en';
  }
  return route.path === localePath(name) || route.path.startsWith(localePath(name) + '/');
}

function toggleLocale() {
  const target: AppLocale = locale.value === 'fr' ? 'en' : 'fr';
  switchLocale(target);
  mobileOpen.value = false;
}
</script>

<template>
  <header class="site-header" :class="{ 'site-header--scrolled': scrolled }">
    <div class="site-header__inner container">
      <RouterLink :to="localePath('home')" class="site-header__brand" aria-label="Studio de développement web — Matthieu Rivière">
        <img
          class="site-header__mark"
          :src="theme === 'dark' ? '/img/mriviere-mark-inverse.svg' : '/img/mriviere-mark.svg'"
          alt=""
          width="36"
          height="36"
          decoding="async"
        />
        <span class="site-header__name">Matthieu Rivière</span>
      </RouterLink>

      <nav class="site-header__nav" :class="{ 'site-header__nav--open': mobileOpen }" :aria-label="t('nav.home')">
        <RouterLink
          v-for="item in navItems"
          :key="item.name"
          :to="localePath(item.name)"
          class="site-header__link"
          :class="{ 'site-header__link--active': isActive(item.name) }"
          @click="mobileOpen = false"
        >
          {{ item.label }}
        </RouterLink>
        <a
          class="site-header__link site-header__link--external"
          :href="EXAMPLE_SITE_URL"
          target="_blank"
          rel="noopener noreferrer"
          @click="mobileOpen = false"
        >
          {{ t('nav.exampleSite') }}
          <ExternalLink :size="14" aria-hidden="true" />
        </a>
        <UiButton variant="glow" size="sm" :to="localePath('quote')" @click="mobileOpen = false">
          {{ t('nav.quote') }}
        </UiButton>
      </nav>

      <div class="site-header__actions">
        <button
          type="button"
          class="site-header__icon site-header__locale"
          :aria-label="t('common.language')"
          @click="toggleLocale"
        >
          <Globe :size="16" />
          <span>{{ t('nav.switchToEnglish') }}</span>
        </button>
        <button
          type="button"
          class="site-header__icon"
          :aria-label="theme === 'dark' ? t('nav.themeLight') : t('nav.themeDark')"
          @click="toggle"
        >
          <Sun v-if="theme === 'dark'" :size="18" />
          <Moon v-else :size="18" />
        </button>
        <button
          type="button"
          class="site-header__icon site-header__burger"
          :aria-label="mobileOpen ? t('nav.menuClose') : t('nav.menuOpen')"
          :aria-expanded="mobileOpen"
          @click="mobileOpen = !mobileOpen"
        >
          <X v-if="mobileOpen" :size="20" />
          <Menu v-else :size="20" />
        </button>
      </div>
    </div>
  </header>
</template>

<style lang="scss" scoped>
.site-header {
  position: sticky;
  top: 0;
  z-index: var(--z-header);
  border-bottom: 1px solid transparent;
  transition: border-color var(--transition-base);

  // The blur lives on a pseudo-element so the header itself doesn't establish
  // a containing block for fixed descendants (otherwise the mobile menu
  // collapses inside the header bounds).
  &::before {
    content: '';
    position: absolute;
    inset: 0;
    background: color-mix(in srgb, var(--color-bg) 80%, transparent);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    z-index: -1;
    pointer-events: none;
  }

  &--scrolled {
    border-bottom-color: var(--color-border);
  }

  &__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-5);
    padding-block: var(--space-4);
  }

  &__brand {
    display: inline-flex;
    align-items: center;
    gap: var(--space-3);
    font-family: var(--font-display);
    font-weight: 600;
  }

  &__mark {
    display: block;
    width: 36px;
    height: 36px;
    object-fit: contain;
    transition: transform var(--transition-base), filter var(--transition-base);
  }

  &__brand:hover &__mark {
    transform: rotate(-6deg) scale(1.04);
  }

  &__name {
    display: none;

    @media (min-width: 541px) {
      display: inline;
    }
  }

  &__nav {
    position: fixed;
    inset: 64px 0 0 0;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: var(--space-5);
    padding: var(--space-7) var(--space-5);
    background: var(--color-bg);
    border-top: 1px solid var(--color-border);
    transform: translateX(100%);
    transition: transform var(--transition-base);

    &--open {
      transform: translateX(0);
    }

    @media (min-width: 881px) {
      position: static;
      inset: auto;
      flex-direction: row;
      align-items: center;
      padding: 0;
      background: none;
      border-top: 0;
      transform: none;
      transition: none;
    }
  }

  &__link {
    position: relative;
    font-family: var(--font-display);
    font-size: var(--fs-lg);
    color: var(--color-text-soft);
    transition: color var(--transition-base);

    @media (min-width: 881px) {
      font-size: var(--fs-sm);
    }

    &::after {
      content: '';
      position: absolute;
      bottom: -6px;
      left: 0;
      width: 100%;
      height: 1px;
      background: var(--color-accent);
      transform: scaleX(0);
      transform-origin: right;
      transition: transform var(--transition-base);
    }

    &:hover {
      color: var(--color-text);

      &::after {
        transform: scaleX(1);
        transform-origin: left;
      }
    }

    &--active {
      color: var(--color-text);

      &::after {
        transform: scaleX(1);
      }
    }

    &--external {
      display: inline-flex;
      align-items: center;
      gap: var(--space-1);

      svg {
        opacity: 0.7;
        transition: transform var(--transition-base), opacity var(--transition-base);
      }

      &:hover svg {
        opacity: 1;
        transform: translate(2px, -2px);
      }
    }
  }

  &__actions {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
  }

  &__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    height: 36px;
    padding: 0 var(--space-3);
    border-radius: var(--radius-pill);
    color: var(--color-text);
    font-size: var(--fs-sm);
    font-family: var(--font-mono);
    transition: background var(--transition-base);

    &:hover {
      background: var(--color-bg-soft);
    }
  }

  &__locale {
    span {
      display: none;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      font-size: var(--fs-xs);

      @media (min-width: 541px) {
        display: inline;
      }
    }
  }

  &__burger {
    @media (min-width: 881px) {
      display: none;
    }
  }
}
</style>
