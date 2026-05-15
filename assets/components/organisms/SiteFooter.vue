<script setup lang="ts">
import { RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { Github, Linkedin, Instagram, Mail } from 'lucide-vue-next';
import { localePath } from '@/router';
import { useTheme } from '@/composables/useTheme';

const { t } = useI18n();
const { theme } = useTheme();
const year = new Date().getFullYear();
</script>

<template>
  <footer class="site-footer">
    <div class="site-footer__inner container">
      <div class="site-footer__brand">
        <img
          class="site-footer__mark"
          :src="theme === 'dark' ? '/img/mriviere-mark-inverse.svg' : '/img/mriviere-mark.svg'"
          alt="Studio de développement web — Matthieu Rivière"
          width="48"
          height="48"
          decoding="async"
        />
        <p class="site-footer__tagline">{{ t('footer.tagline') }}</p>
      </div>

      <nav class="site-footer__cols" :aria-label="t('footer.navigate')">
        <div>
          <h3 class="site-footer__col-title">{{ t('footer.navigate') }}</h3>
          <ul>
            <li><RouterLink :to="localePath('home')">{{ t('nav.home') }}</RouterLink></li>
            <li><RouterLink :to="localePath('pricing')">{{ t('nav.pricing') }}</RouterLink></li>
            <li><RouterLink :to="localePath('profile')">{{ t('nav.profile') }}</RouterLink></li>
            <li><RouterLink :to="localePath('contact')">{{ t('nav.contact') }}</RouterLink></li>
          </ul>
        </div>
        <div>
          <h3 class="site-footer__col-title">{{ t('footer.legal') }}</h3>
          <ul>
            <li><RouterLink :to="localePath('legals')">{{ t('nav.legals') }}</RouterLink></li>
            <li><RouterLink :to="localePath('terms')">{{ t('nav.terms') }}</RouterLink></li>
          </ul>
        </div>
        <div>
          <h3 class="site-footer__col-title">{{ t('footer.follow') }}</h3>
          <div class="site-footer__social">
            <a href="https://github.com/mriviere-company" target="_blank" rel="noopener noreferrer" aria-label="GitHub">
              <Github :size="18" />
            </a>
            <a href="https://www.linkedin.com/in/rivierematthieu/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
              <Linkedin :size="18" />
            </a>
            <a href="https://www.instagram.com/matthieu_rvr_/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
              <Instagram :size="18" />
            </a>
            <a href="mailto:contact@rivierematthieu.com" aria-label="Email">
              <Mail :size="18" />
            </a>
          </div>
        </div>
      </nav>
    </div>

    <div class="site-footer__bottom container">
      <span>© {{ year }} Matthieu Rivière. {{ t('footer.rights') }}</span>
      <span class="site-footer__build">{{ t('footer.build') }}</span>
    </div>
  </footer>
</template>

<style lang="scss" scoped>
.site-footer {
  margin-top: var(--space-10);
  border-top: 1px solid var(--color-border);
  background: var(--color-bg-soft);

  &__inner {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-7);
    padding-block: var(--space-8);

    @media (min-width: 768px) {
      grid-template-columns: 1.2fr 2fr;
    }
  }

  &__brand {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__mark {
    display: block;
    width: 48px;
    height: 48px;
    object-fit: contain;
  }

  &__tagline {
    color: var(--color-text-soft);
    max-width: 28ch;
  }

  &__cols {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-6);

    @media (max-width: 540px) {
      grid-template-columns: 1fr 1fr;
    }
  }

  &__col-title {
    font-size: var(--fs-sm);
    font-family: var(--font-mono);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--color-text-muted);
    margin-bottom: var(--space-3);
  }

  ul {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
  }

  ul a {
    color: var(--color-text-soft);
    transition: color var(--transition-base);

    &:hover {
      color: var(--color-accent);
    }
  }

  &__social {
    display: inline-flex;
    gap: var(--space-3);

    a {
      display: grid;
      place-items: center;
      width: 36px;
      height: 36px;
      border: 1px solid var(--color-border);
      border-radius: var(--radius-pill);
      color: var(--color-text-soft);
      transition: color var(--transition-base), border-color var(--transition-base), transform var(--transition-base);

      &:hover {
        color: var(--color-accent);
        border-color: var(--color-accent);
        transform: translateY(-2px);
      }
    }
  }

  &__bottom {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: var(--space-3);
    padding-block: var(--space-5);
    border-top: 1px solid var(--color-border);
    font-size: var(--fs-xs);
    color: var(--color-text-muted);
    font-family: var(--font-mono);
  }
}
</style>
