<script setup lang="ts">
import { onMounted, onBeforeUnmount, nextTick, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import { ArrowRight, Sparkles, Layers, Zap, Lock, Mail, Phone } from 'lucide-vue-next';
import UiButton from '@/components/atoms/UiButton.vue';
import UiCard from '@/components/molecules/UiCard.vue';
import UiTag from '@/components/atoms/UiTag.vue';
import CallbackBookingModal from '@/components/organisms/CallbackBookingModal.vue';
import { usePackagesStore } from '@/stores/packages';
import { storeToRefs } from 'pinia';
import { formatMoney } from '@/i18n';
import { localePath } from '@/router';

const { t } = useI18n();
const packagesStore = usePackagesStore();
const { items: packages } = storeToRefs(packagesStore);

const callbackOpen = ref(false);

const STAGGER_MS = 110;
const REVEAL_TARGETS_SELECTOR = [
  '.hero__inner > *',
  '.container > *:not(.features__grid):not(.pricing-preview__grid)',
  '.container > .container--narrow > *',
  '.features__grid > *',
  '.pricing-preview__grid > *',
].join(', ');

let observer: IntersectionObserver | null = null;

function applyStaggerDelays(section: Element): void {
  const items = section.querySelectorAll<HTMLElement>(REVEAL_TARGETS_SELECTOR);
  items.forEach((el, idx) => {
    el.style.setProperty('--reveal-delay', `${idx * STAGGER_MS}ms`);
  });
}

function setupRevealObserver(): void {
  if (typeof IntersectionObserver === 'undefined') return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.querySelectorAll('.home > section').forEach((s) => s.setAttribute('data-revealed', ''));
    return;
  }

  const sections = document.querySelectorAll('.home > section');
  sections.forEach(applyStaggerDelays);

  observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting && entry.intersectionRatio >= 0.2) {
          entry.target.setAttribute('data-revealed', '');
          observer?.unobserve(entry.target);
        }
      }
    },
    { threshold: [0, 0.2, 0.5], rootMargin: '0px 0px -10% 0px' },
  );

  sections.forEach((section) => observer!.observe(section));
}

onMounted(async () => {
  await packagesStore.ensureLoaded();
  // Wait for the DOM (including the dynamically rendered package cards) to be
  // committed before observing — otherwise the cards are missing from the
  // initial query and never get a stagger delay.
  await nextTick();
  setupRevealObserver();
});

onBeforeUnmount(() => {
  observer?.disconnect();
  observer = null;
});

const features = [
  { icon: Layers, titleKey: 'home.feature1Title', textKey: 'home.feature1Text' },
  { icon: Zap, titleKey: 'home.feature2Title', textKey: 'home.feature2Text' },
  { icon: Lock, titleKey: 'home.feature3Title', textKey: 'home.feature3Text' },
  { icon: Sparkles, titleKey: 'home.feature4Title', textKey: 'home.feature4Text' },
];
</script>

<template>
  <div class="home">
    <section class="hero">
      <div class="hero__bg" aria-hidden="true">
        <div class="hero__orb hero__orb--1" />
        <div class="hero__orb hero__orb--2" />
        <div class="hero__grid grid-bg" />
      </div>
      <div class="container hero__inner">
        <div class="hero__eyebrow eyebrow">{{ t('home.eyebrow') }}</div>
        <h1 class="hero__title heading-display">
          {{ t('home.title1') }}<br />
          <span class="hero__title-accent">{{ t('home.title2') }}</span>
        </h1>
        <p class="hero__lead">
          <i18n-t keypath="home.lead" tag="span">
            <template #symfony><strong>Symfony 7</strong></template>
            <template #vue><strong>Vue 3</strong></template>
            <template #price><strong>{{ formatMoney(39900) }}</strong></template>
            <template #monthly><strong>{{ formatMoney(2900) }}</strong></template>
          </i18n-t>
        </p>
        <div class="hero__actions">
          <UiButton variant="glow" size="lg" :to="localePath('pricing')">
            {{ t('home.ctaPricing') }}
            <template #icon-right><ArrowRight :size="18" /></template>
          </UiButton>
          <UiButton variant="ghost" size="lg" :to="localePath('contact')">
            {{ t('home.ctaTalk') }}
          </UiButton>
        </div>
        <div class="hero__meta">
          <span><strong>{{ t('home.metaDelay', { days: '4-10' }) }}</strong></span>
          <span class="hero__sep" aria-hidden="true">·</span>
          <span>{{ t('home.metaDeposit') }}</span>
          <span class="hero__sep" aria-hidden="true">·</span>
          <span>{{ t('home.metaTax') }}</span>
        </div>
      </div>
    </section>

    <section class="section features">
      <div class="container">
        <div class="features__head">
          <span class="eyebrow">{{ t('home.featuresEyebrow') }}</span>
          <h2 class="heading-section">{{ t('home.featuresTitle') }}</h2>
        </div>
        <div class="features__grid">
          <UiCard v-for="f in features" :key="f.titleKey" padding="md" class="features__card">
            <component :is="f.icon" :size="22" class="features__icon" />
            <h3>{{ t(f.titleKey) }}</h3>
            <p class="text-soft">{{ t(f.textKey) }}</p>
          </UiCard>
        </div>
      </div>
    </section>

    <section class="section pricing-preview">
      <div class="container">
        <div class="pricing-preview__head">
          <span class="eyebrow">{{ t('home.pricingEyebrow') }}</span>
          <h2 class="heading-section">
            {{ t('home.pricingTitle1') }}<br />
            <span class="text-accent">{{ t('home.pricingTitle2') }}</span>
          </h2>
          <p class="text-soft">{{ t('home.pricingLead') }}</p>
        </div>

        <div v-if="packages.length === 0" class="pricing-preview__empty text-muted">
          {{ t('common.loading') }}
        </div>

        <div v-else class="pricing-preview__grid">
          <UiCard
            v-for="pkg in packages"
            :key="pkg.slug"
            padding="lg"
            :highlighted="pkg.highlighted"
            hoverable
            class="pricing-preview__card"
          >
            <div class="pricing-preview__name">
              <span>{{ pkg.name }}</span>
              <UiTag v-if="pkg.highlighted" variant="accent">{{ t('pricing.recommended') }}</UiTag>
            </div>
            <div class="pricing-preview__price">
              <span class="pricing-preview__price-main">{{ formatMoney(pkg.oneShotPrice) }}</span>
              <span class="text-muted">{{ t('home.pricingCreate') }}</span>
            </div>
            <div class="pricing-preview__sub text-soft">
              + <strong>{{ formatMoney(pkg.monthlyPrice) }}</strong>{{ t('home.pricingMonthlyShort') }} {{ t('home.pricingMonthlySuffix') }}
            </div>
            <ul class="pricing-preview__features">
              <li v-for="f in pkg.features" :key="f.label" :class="{ 'is-out': !f.included }">
                <span aria-hidden="true">{{ f.included ? '✓' : '·' }}</span>
                {{ f.label }}
              </li>
            </ul>
            <UiButton :variant="pkg.highlighted ? 'glow' : 'outline'" :to="`${localePath('quote')}?pkg=${pkg.slug}`">
              {{ t('home.pricingChoose', { name: pkg.name }) }}
            </UiButton>
          </UiCard>
        </div>

        <div class="pricing-preview__cta">
          <UiButton variant="ghost" :to="localePath('pricing')">
            {{ t('home.pricingCompare') }}
            <template #icon-right><ArrowRight :size="16" /></template>
          </UiButton>
        </div>
      </div>
    </section>

    <section class="section cta-block">
      <div class="container">
        <header class="cta-block__head">
          <h2 class="heading-section">{{ t('home.ctaTitle') }}</h2>
          <p class="text-soft cta-block__lead">{{ t('home.ctaText') }}</p>
        </header>

        <div class="cta-block__grid">
          <RouterLink class="cta-block__card" :to="localePath('contact')">
            <Mail :size="22" class="cta-block__icon" />
            <h3>{{ t('home.ctaCardEmailTitle') }}</h3>
            <p class="cta-block__email">contact@rivierematthieu.com</p>
            <span class="cta-block__delay">{{ t('home.ctaCardEmailDelay') }}</span>
            <ArrowRight :size="16" class="cta-block__arrow" />
          </RouterLink>

          <button type="button" class="cta-block__card cta-block__card--featured" @click="callbackOpen = true">
            <Phone :size="22" class="cta-block__icon" />
            <h3>{{ t('home.ctaCardCallbackTitle') }}</h3>
            <p class="cta-block__delay">{{ t('home.ctaCardCallbackDelay') }}</p>
            <span class="cta-block__action">
              {{ t('home.ctaCardCallbackAction') }}
              <ArrowRight :size="14" />
            </span>
          </button>

          <RouterLink class="cta-block__card" :to="localePath('quote')">
            <Zap :size="22" class="cta-block__icon" />
            <h3>{{ t('home.ctaCardQuoteTitle') }}</h3>
            <p class="cta-block__delay">{{ t('home.ctaCardQuoteDelay') }}</p>
            <span class="cta-block__action">
              {{ t('home.ctaCardQuoteAction') }}
              <ArrowRight :size="14" />
            </span>
          </RouterLink>
        </div>

        <p class="cta-block__footer text-muted">{{ t('home.ctaFooter') }}</p>
      </div>
    </section>

    <CallbackBookingModal :open="callbackOpen" @close="callbackOpen = false" />
  </div>
</template>

<style lang="scss" scoped>
.hero {
  position: relative;
  isolation: isolate;
  padding-block: var(--space-9) var(--space-10);
  overflow: hidden;

  &__bg {
    position: absolute;
    inset: 0;
    z-index: -1;
  }

  &__grid {
    position: absolute;
    inset: 0;
    opacity: 0.4;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 70%);
  }

  &__orb {
    position: absolute;
    width: 480px;
    height: 480px;
    border-radius: 50%;
    filter: blur(90px);
    // Plus discret en light mode (sinon les textes du hero passent dans
    // un voile bleu et perdent en lisibilité). Plus marqué en dark mode
    // pour conserver l'effet visuel.
    opacity: 0.32;
    animation: float-glow 18s ease-in-out infinite;

    [data-theme='dark'] & {
      opacity: 0.55;
    }

    &--1 {
      top: -120px;
      left: -160px;
      background: var(--color-accent);
    }

    &--2 {
      bottom: -180px;
      right: -120px;
      background: var(--color-accent-2);
      animation-delay: -8s;
    }
  }

  &__inner {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: var(--space-5);
    max-width: 980px;
  }

  &__title {
    margin-block: var(--space-3) var(--space-2);
  }

  &__title-accent {
    background: linear-gradient(120deg, var(--color-accent), var(--color-accent-2));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
  }

  &__lead {
    font-size: var(--fs-md);
    max-width: 60ch;
    color: var(--color-text-soft);

    @media (min-width: 768px) {
      font-size: var(--fs-lg);
    }

    strong {
      color: var(--color-text);
    }
  }

  &__actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
    margin-block: var(--space-3);
  }

  // Override de l'eyebrow utilitaire dans le contexte du hero : sur fond
  // tinté par les orbes, le gris muted devient illisible. On passe au gris
  // foncé "soft" pour ce composant uniquement.
  &__eyebrow {
    color: var(--color-text-soft);
  }

  &__meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
    color: var(--color-text-soft);
    font-family: var(--font-mono);
    font-size: var(--fs-sm);
  }

  &__sep {
    color: var(--color-border-strong);
  }
}

.features {
  &__head {
    margin-bottom: var(--space-7);
    max-width: 720px;
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__grid {
    display: grid;
    gap: var(--space-4);
    grid-template-columns: 1fr;

    @media (min-width: 640px) {
      grid-template-columns: 1fr 1fr;
    }

    @media (min-width: 1080px) {
      grid-template-columns: repeat(4, 1fr);
    }
  }

  &__card {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    min-height: 220px;
  }

  &__icon {
    color: var(--color-accent);
  }

  h3 {
    font-size: var(--fs-md);
  }
}

.pricing-preview {
  background: var(--color-bg-soft);
  position: relative;

  &__head {
    margin-bottom: var(--space-7);
    max-width: 720px;
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__empty {
    padding: var(--space-7);
    text-align: center;
  }

  &__grid {
    display: grid;
    gap: var(--space-5);
    grid-template-columns: 1fr;
    align-items: stretch;

    @media (min-width: 880px) {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  &__card {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__name {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-family: var(--font-display);
    font-weight: 600;
    font-size: var(--fs-lg);
  }

  &__price {
    display: flex;
    align-items: baseline;
    gap: var(--space-2);
  }

  &__price-main {
    font-family: var(--font-display);
    font-size: var(--fs-3xl);
    font-weight: 600;
    letter-spacing: -0.04em;
  }

  &__sub {
    font-size: var(--fs-sm);
  }

  &__features {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    flex: 1;

    li {
      display: flex;
      gap: var(--space-2);
      font-size: var(--fs-sm);
      color: var(--color-text-soft);

      span {
        color: var(--color-accent);
        font-family: var(--font-mono);
      }

      &.is-out {
        color: var(--color-text-muted);
        text-decoration: line-through;
        text-decoration-color: var(--color-border-strong);

        span {
          color: var(--color-text-muted);
        }
      }
    }
  }

  &__cta {
    display: flex;
    justify-content: center;
    margin-top: var(--space-7);
  }
}

.cta-block {
  &__head {
    text-align: center;
    margin-bottom: var(--space-7);

    h2 {
      margin-bottom: var(--space-3);
    }
  }

  &__lead {
    max-width: 56ch;
    margin: 0 auto;
  }

  &__grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-4);

    @media (min-width: 720px) {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  &__card {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    padding: var(--space-5);
    background: var(--color-bg-elev);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    color: var(--color-text);
    text-align: left;
    cursor: pointer;
    transition: border-color var(--transition-base), transform var(--transition-base),
      box-shadow var(--transition-base);

    h3 {
      font-size: var(--fs-md);
      font-family: var(--font-display);
      font-weight: 600;
    }

    &:hover {
      border-color: var(--color-accent);
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    &--featured {
      background: linear-gradient(135deg, var(--color-accent), var(--color-accent-2));
      border-color: transparent;
      color: #fff;

      h3,
      .cta-block__delay {
        color: #fff;
      }

      .cta-block__icon {
        color: #fff;
      }

      &:hover {
        border-color: transparent;
        box-shadow: 0 18px 40px -12px var(--color-accent);
      }
    }
  }

  &__icon {
    color: var(--color-accent);
  }

  &__email {
    font-family: var(--font-mono);
    font-size: var(--fs-sm);
    color: var(--color-text);
    word-break: break-all;
    margin: 0;
  }

  &__delay {
    color: var(--color-text-muted);
    font-size: var(--fs-sm);
    margin: 0;
  }

  &__action {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    margin-top: var(--space-2);
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: inherit;
  }

  &__arrow {
    position: absolute;
    top: var(--space-5);
    right: var(--space-5);
    color: var(--color-text-muted);
    transition: color var(--transition-base), transform var(--transition-base);
  }

  &__card:hover &__arrow {
    color: var(--color-accent);
    transform: translateX(3px);
  }

  &__footer {
    margin-top: var(--space-7);
    text-align: center;
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    letter-spacing: 0.06em;
  }
}
</style>
