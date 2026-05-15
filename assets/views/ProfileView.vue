<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Github, Linkedin, Instagram, Mail, ArrowRight } from 'lucide-vue-next';
import UiButton from '@/components/atoms/UiButton.vue';
import UiTag from '@/components/atoms/UiTag.vue';
import UiMonogram from '@/components/atoms/UiMonogram.vue';
import { apiClient, call } from '@/api/client';
import type { ProfileDto } from '@/types';
import { localePath } from '@/router';

const { t } = useI18n();
const profile = ref<ProfileDto | null>(null);

onMounted(async () => {
  const res = await call<ProfileDto>(() => apiClient.get('/profile'));
  if (res.ok) profile.value = res.data;
});
</script>

<template>
  <div class="profile container">
    <section class="profile__intro">
      <div class="profile__text">
        <span class="eyebrow">{{ t('profile.eyebrow') }}</span>
        <h1 class="heading-display profile__title">
          {{ t('profile.firstName') }}<br />
          <span class="text-accent">{{ t('profile.lastName') }}</span>
        </h1>
        <p class="profile__lead text-soft">{{ profile?.bio ?? t('profile.fallbackBio') }}</p>

        <div class="profile__socials">
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

      <div class="profile__photo">
        <img
          v-if="profile?.photoUrl"
          :src="profile.photoUrl"
          :alt="`${t('profile.firstName')} ${t('profile.lastName')}`"
          loading="lazy"
        />
        <UiMonogram v-else aria-label="Matthieu Rivière" />
      </div>
    </section>

    <section class="profile__stack section">
      <span class="eyebrow">{{ t('profile.stackEyebrow') }}</span>
      <h2 class="heading-section">{{ t('profile.stackTitle') }}</h2>
      <div class="profile__tags">
        <UiTag v-for="tech in profile?.stack ?? ['PHP 8.4', 'Symfony 8', 'Doctrine ORM', 'MariaDB', 'Vue 3', 'TypeScript', 'Vite', 'Pinia', 'SCSS', 'Stripe', 'Hostinger', 'GitHub Actions']" :key="tech">
          {{ tech }}
        </UiTag>
      </div>
    </section>

    <section class="profile__cta section">
      <h2 class="heading-section">{{ t('profile.ctaTitle') }}</h2>
      <p class="text-soft">{{ t('profile.ctaText') }}</p>
      <UiButton variant="glow" size="lg" :to="localePath('contact')">
        {{ t('profile.ctaButton') }}
        <template #icon-right><ArrowRight :size="18" /></template>
      </UiButton>
    </section>
  </div>
</template>

<style lang="scss" scoped>
.profile {
  &__intro {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-7);
    padding-block: var(--space-8);

    @media (min-width: 880px) {
      grid-template-columns: 1.4fr 1fr;
      align-items: center;
    }
  }

  &__text {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__title {
    margin-block: var(--space-2);
  }

  &__lead {
    font-size: var(--fs-md);
    max-width: 60ch;
  }

  &__socials {
    display: inline-flex;
    gap: var(--space-3);
    margin-top: var(--space-3);

    a {
      display: grid;
      place-items: center;
      width: 44px;
      height: 44px;
      border: 1px solid var(--color-border-strong);
      border-radius: var(--radius-pill);
      color: var(--color-text);
      transition: color var(--transition-base), border-color var(--transition-base), transform var(--transition-base);

      &:hover {
        color: var(--color-accent);
        border-color: var(--color-accent);
        transform: translateY(-2px);
      }
    }
  }

  &__photo {
    aspect-ratio: 4 / 5;
    border-radius: var(--radius-md);
    overflow: hidden;
    box-shadow: var(--shadow-lg);

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
  }

  &__stack {
    h2 {
      margin-block: var(--space-3) var(--space-5);
    }
  }

  &__tags {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
  }

  &__cta {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: var(--space-4);
    padding-block: var(--space-7) var(--space-9);
  }
}
</style>
