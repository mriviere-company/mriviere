<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { storeToRefs } from 'pinia';
import { ArrowLeft, ArrowRight, ShieldCheck } from 'lucide-vue-next';
import UiButton from '@/components/atoms/UiButton.vue';
import UiField from '@/components/atoms/UiField.vue';
import UiCard from '@/components/molecules/UiCard.vue';
import UiTag from '@/components/atoms/UiTag.vue';
import { usePackagesStore } from '@/stores/packages';
import { useQuoteStore } from '@/stores/quote';
import type { PackageSlug } from '@/types';
import { formatMoney } from '@/i18n';

const route = useRoute();
const { t } = useI18n();
const packagesStore = usePackagesStore();
const { items: packages } = storeToRefs(packagesStore);
const quote = useQuoteStore();

const initialPackage = (route.query.pkg as PackageSlug | undefined) ?? null;

// If we arrive with `?pkg=...`, the forfait is already chosen — start on step 2.
const step = ref(initialPackage ? 2 : 1);
const totalSteps = 4;

const draft = ref({
  package: initialPackage,
  clientName: '',
  clientCompany: '',
  clientEmail: '',
  clientPhone: '',
  clientAddress: '',
  projectDescription: '',
  honeypot: '',
});

const validationErrors = ref<Record<string, string>>({});

onMounted(async () => {
  await packagesStore.ensureLoaded();
  if (draft.value.package) quote.selectPackage(draft.value.package);
});

watch(() => draft.value.package, (slug) => {
  if (slug) quote.selectPackage(slug);
});

const selectedPackage = computed(() => packages.value.find((p) => p.slug === draft.value.package));

function selectPackage(slug: PackageSlug) {
  draft.value.package = slug;
  step.value = 2;
}

function next() {
  validationErrors.value = {};
  if (step.value === 1 && !draft.value.package) {
    validationErrors.value.package = t('quote.step1ErrorPackage');
    return;
  }
  if (step.value === 2) {
    if (!draft.value.clientName) validationErrors.value.clientName = t('quote.errorNameRequired');
    if (!draft.value.clientEmail) validationErrors.value.clientEmail = t('quote.errorEmailRequired');
    if (!draft.value.clientPhone) validationErrors.value.clientPhone = t('quote.errorPhoneRequired');
    if (Object.keys(validationErrors.value).length > 0) return;
  }
  if (step.value === 3 && !draft.value.projectDescription) {
    validationErrors.value.projectDescription = t('quote.errorBriefRequired');
    return;
  }
  step.value = Math.min(step.value + 1, totalSteps);
}

function prev() {
  step.value = Math.max(step.value - 1, 1);
}

const submitting = ref(false);
const submitError = ref<string | null>(null);

async function submit() {
  if (!draft.value.package) return;
  submitting.value = true;
  submitError.value = null;

  quote.update({
    package: draft.value.package,
    clientName: draft.value.clientName,
    clientCompany: draft.value.clientCompany || undefined,
    clientEmail: draft.value.clientEmail,
    clientPhone: draft.value.clientPhone,
    clientAddress: draft.value.clientAddress,
    projectDescription: draft.value.projectDescription,
    website: draft.value.honeypot,
  });

  const res = await quote.submit();
  submitting.value = false;
  if (res.ok && res.checkoutUrl) {
    window.location.href = res.checkoutUrl;
  } else {
    submitError.value = res.error ?? t('quote.submitError');
  }
}
</script>

<template>
  <div class="quote container">
    <header class="quote__head">
      <span class="eyebrow">{{ t('quote.eyebrow') }}</span>
      <h1 class="heading-display quote__title">
        {{ t('quote.title1') }}<br />
        <span class="text-accent">{{ t('quote.title2') }}</span>
      </h1>
      <ol class="quote__steps" :data-step="step">
        <li :class="{ 'is-active': step >= 1, 'is-current': step === 1 }">{{ t('quote.step1') }}</li>
        <li :class="{ 'is-active': step >= 2, 'is-current': step === 2 }">{{ t('quote.step2') }}</li>
        <li :class="{ 'is-active': step >= 3, 'is-current': step === 3 }">{{ t('quote.step3') }}</li>
        <li :class="{ 'is-active': step >= 4, 'is-current': step === 4 }">{{ t('quote.step4') }}</li>
      </ol>
    </header>

    <section v-if="step === 1" class="quote__step">
      <h2 class="heading-section">{{ t('quote.step1Title') }}</h2>
      <p v-if="validationErrors.package" class="quote__error">{{ validationErrors.package }}</p>
      <div class="quote__packages">
        <UiCard
          v-for="pkg in packages"
          :key="pkg.slug"
          padding="md"
          hoverable
          :highlighted="draft.package ? pkg.slug === draft.package : pkg.highlighted"
          @click="() => selectPackage(pkg.slug)"
          class="quote__package-card"
        >
          <div class="quote__package-head">
            <span class="quote__package-name">{{ pkg.name }}</span>
            <UiTag v-if="pkg.highlighted">{{ t('pricing.recommended') }}</UiTag>
          </div>
          <p class="text-muted">{{ t('quote.pagesIncluded', { n: pkg.maxPages }) }}</p>
          <div class="quote__package-price">
            {{ formatMoney(pkg.oneShotPrice) }}
            <small>+ {{ formatMoney(pkg.monthlyPrice) }}{{ t('quote.monthlyShort') }}</small>
          </div>
        </UiCard>
      </div>
    </section>

    <section v-if="step === 2" class="quote__step">
      <div v-if="selectedPackage" class="quote__chosen">
        <div>
          <span class="quote__chosen-label text-muted">{{ t('quote.summaryPackage') }}</span>
          <strong class="quote__chosen-name">{{ selectedPackage.name }}</strong>
          <span class="quote__chosen-price text-muted">
            {{ formatMoney(selectedPackage.oneShotPrice) }} +
            {{ formatMoney(selectedPackage.monthlyPrice) }}{{ t('quote.monthlyShort') }}
          </span>
        </div>
        <button type="button" class="quote__chosen-change" @click="step = 1">
          {{ t('common.edit') }}
        </button>
      </div>
      <h2 class="heading-section">{{ t('quote.step2Title') }}</h2>
      <p class="text-soft">{{ t('quote.step2Lead') }}</p>
      <div class="quote__form">
        <UiField v-model="draft.clientName" :label="t('quote.fieldFullName')" required :error="validationErrors.clientName" autocomplete="name" />
        <UiField v-model="draft.clientCompany" :label="t('quote.fieldCompany')" autocomplete="organization" />
        <UiField v-model="draft.clientEmail" :label="t('quote.fieldEmail')" type="email" required :error="validationErrors.clientEmail" autocomplete="email" />
        <UiField v-model="draft.clientPhone" :label="t('quote.fieldPhone')" type="tel" required :error="validationErrors.clientPhone" autocomplete="tel" />
        <UiField v-model="draft.clientAddress" :label="t('quote.fieldAddress')" :placeholder="t('quote.fieldAddressPlaceholder')" />
      </div>
    </section>

    <section v-if="step === 3" class="quote__step">
      <h2 class="heading-section">{{ t('quote.step3Title') }}</h2>
      <p class="text-soft">{{ t('quote.step3Lead') }}</p>
      <UiField
        v-model="draft.projectDescription"
        :label="t('quote.fieldBrief')"
        type="textarea"
        :rows="9"
        required
        :error="validationErrors.projectDescription"
        :placeholder="t('quote.fieldBriefPlaceholder')"
      />
      <div class="quote__honeypot" aria-hidden="true">
        <label>
          {{ t('contact.honeypotLabel') }}
          <input v-model="draft.honeypot" type="text" tabindex="-1" autocomplete="off" />
        </label>
      </div>
    </section>

    <section v-if="step === 4" class="quote__step">
      <h2 class="heading-section">{{ t('quote.step4Title') }}</h2>
      <UiCard padding="lg" class="quote__summary">
        <h3>{{ t('quote.summaryPackage') }}</h3>
        <p v-if="selectedPackage">
          <i18n-t keypath="quote.summaryPackageDetail" tag="span">
            <template #name><strong>{{ selectedPackage.name }}</strong></template>
            <template #pages>{{ selectedPackage.maxPages }}</template>
            <template #oneShot>{{ formatMoney(selectedPackage.oneShotPrice) }}</template>
            <template #monthly>{{ formatMoney(selectedPackage.monthlyPrice) }}</template>
          </i18n-t>
        </p>

        <h3>{{ t('quote.summaryClient') }}</h3>
        <p>
          {{ draft.clientName }}<span v-if="draft.clientCompany"> — {{ draft.clientCompany }}</span><br />
          {{ draft.clientEmail }} · {{ draft.clientPhone }}<br />
          <span class="text-muted" v-if="draft.clientAddress">{{ draft.clientAddress }}</span>
        </p>

        <h3>{{ t('quote.summaryProject') }}</h3>
        <p class="quote__brief">{{ draft.projectDescription }}</p>

        <div class="quote__total" v-if="selectedPackage">
          <span class="text-muted">{{ t('quote.depositLabel') }}</span>
          <strong class="quote__total-amount">
            {{ formatMoney(selectedPackage.oneShotPrice / 2 + selectedPackage.monthlyPrice) }}
          </strong>
          <span class="text-muted quote__total-detail">
            <i18n-t keypath="quote.depositDetail" tag="span">
              <template #deposit>{{ formatMoney(selectedPackage.oneShotPrice / 2) }}</template>
              <template #monthly>{{ formatMoney(selectedPackage.monthlyPrice) }}</template>
            </i18n-t>
          </span>
        </div>

        <p class="text-muted quote__notice">
          <ShieldCheck :size="16" /> {{ t('quote.noticeStripe') }}
        </p>
      </UiCard>

      <p v-if="submitError" class="quote__error">{{ submitError }}</p>
    </section>

    <footer class="quote__nav">
      <UiButton v-if="step > 1" variant="ghost" @click="prev">
        <template #icon-left><ArrowLeft :size="16" /></template>
        {{ t('common.back') }}
      </UiButton>
      <span v-else />
      <UiButton v-if="step < totalSteps" variant="primary" @click="next">
        {{ t('common.continue') }}
        <template #icon-right><ArrowRight :size="16" /></template>
      </UiButton>
      <UiButton v-else variant="glow" :loading="submitting" @click="submit">
        {{ t('quote.submit') }}
        <template #icon-right><ArrowRight :size="16" /></template>
      </UiButton>
    </footer>
  </div>
</template>

<style lang="scss" scoped>
.quote {
  padding-block: var(--space-7) var(--space-9);
  max-width: 880px;

  &__head {
    margin-bottom: var(--space-7);
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__title {
    margin-block: var(--space-2);
  }

  &__steps {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
    list-style: none;
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--color-text-muted);

    li {
      padding: var(--space-2) var(--space-3);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-pill);
      transition: border-color var(--transition-base), color var(--transition-base);

      &.is-active {
        color: var(--color-text);
      }

      &.is-current {
        border-color: var(--color-accent);
        color: var(--color-accent);
      }
    }
  }

  &__step {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
  }

  &__chosen {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    border: 1px solid var(--color-accent);
    border-radius: var(--radius-md);
    background: var(--color-accent-soft);

    > div {
      display: flex;
      flex-wrap: wrap;
      align-items: baseline;
      gap: var(--space-2) var(--space-3);
    }
  }

  &__chosen-label {
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    text-transform: uppercase;
    letter-spacing: 0.12em;
  }

  &__chosen-name {
    font-family: var(--font-display);
    font-size: var(--fs-md);
    color: var(--color-accent);
  }

  &__chosen-price {
    font-size: var(--fs-sm);
  }

  &__chosen-change {
    flex-shrink: 0;
    padding: var(--space-2) var(--space-3);
    border: 1px solid transparent;
    border-radius: var(--radius-pill);
    color: var(--color-accent);
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    text-transform: uppercase;
    letter-spacing: 0.12em;
    transition: background var(--transition-base), border-color var(--transition-base);

    &:hover {
      background: var(--color-bg);
      border-color: var(--color-accent);
    }
  }

  &__error {
    color: var(--color-danger);
    font-size: var(--fs-sm);
  }

  &__packages {
    display: grid;
    gap: var(--space-4);

    @media (min-width: 720px) {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  &__package-card {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
  }

  &__package-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__package-name {
    font-family: var(--font-display);
    font-size: var(--fs-lg);
    font-weight: 600;
  }

  &__package-price {
    font-family: var(--font-display);
    font-size: var(--fs-2xl);
    font-weight: 600;
    margin-top: var(--space-2);

    small {
      display: block;
      font-size: var(--fs-sm);
      color: var(--color-text-muted);
      font-weight: 400;
    }
  }

  &__form {
    display: grid;
    gap: var(--space-4);

    @media (min-width: 640px) {
      grid-template-columns: 1fr 1fr;
    }
  }

  &__honeypot {
    position: absolute;
    left: -9999px;
    width: 1px;
    height: 1px;
    overflow: hidden;
  }

  &__summary {
    h3 {
      font-size: var(--fs-sm);
      font-family: var(--font-mono);
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--color-text-muted);
      margin-top: var(--space-4);
      margin-bottom: var(--space-2);

      &:first-child {
        margin-top: 0;
      }
    }
  }

  &__brief {
    white-space: pre-wrap;
  }

  &__total {
    margin-top: var(--space-5);
    padding: var(--space-4) var(--space-5);
    border-radius: var(--radius-md);
    background: var(--color-accent-soft);
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
  }

  &__total-amount {
    font-family: var(--font-display);
    font-size: var(--fs-2xl);
    color: var(--color-accent);
  }

  &__total-detail {
    font-size: var(--fs-xs);
  }

  &__notice {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    margin-top: var(--space-4);
    font-size: var(--fs-sm);
  }

  &__nav {
    display: flex;
    justify-content: space-between;
    margin-top: var(--space-7);
    padding-top: var(--space-5);
    border-top: 1px solid var(--color-border);
  }
}
</style>
