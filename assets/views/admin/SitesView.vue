<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Plus, Activity, AlertTriangle, CircleCheck, ExternalLink, Server } from 'lucide-vue-next';
import UiButton from '@/components/atoms/UiButton.vue';
import UiTag from '@/components/atoms/UiTag.vue';
import { apiClient, call } from '@/api/client';

interface SiteDto {
  id: number;
  domain: string;
  label: string;
  publicKeyFingerprint: string;
  enabled: boolean;
  addedAt: string;
  lastSeenAt: string | null;
  lastHealthStatus: string | null;
  lastHealthError: string | null;
  lastAppVersion: string | null;
  baseUrl: string;
}

const sites = ref<SiteDto[]>([]);
const centralFingerprint = ref('');
const loading = ref(true);
const checkingId = ref<number | null>(null);

async function loadSites() {
  const res = await call<{ sites: SiteDto[]; centralFingerprint: string }>(() => apiClient.get('/admin/sites'));
  if (res.ok) {
    sites.value = res.data.sites;
    centralFingerprint.value = res.data.centralFingerprint;
  }
}

onMounted(async () => {
  await loadSites();
  loading.value = false;
});

async function checkHealth(siteId: number) {
  checkingId.value = siteId;
  const res = await call<{ site: SiteDto }>(() => apiClient.post(`/admin/sites/${siteId}/check-health`, {}));
  checkingId.value = null;
  if (res.ok) {
    const idx = sites.value.findIndex((s) => s.id === siteId);
    if (idx >= 0) sites.value[idx] = res.data.site;
  }
}

function statusVariant(status: string | null): 'success' | 'warn' | 'danger' | 'default' {
  if (status === 'ok') return 'success';
  if (status === 'opt_in_off') return 'warn';
  if (status === null) return 'default';
  return 'danger';
}

function statusLabel(status: string | null): string {
  if (status === null) return 'Jamais vérifié';
  return ({
    ok: 'En ligne',
    unreachable: 'Injoignable',
    unauthorized: 'Token rejeté',
    forbidden: 'Scope manquant',
    opt_in_off: 'Opt-in désactivé',
    http_error: 'Erreur HTTP',
    invalid_body: 'Réponse invalide',
  } as Record<string, string>)[status] ?? status;
}

const fingerprintShort = computed(() =>
  centralFingerprint.value
    ? `${centralFingerprint.value.slice(0, 8)}…${centralFingerprint.value.slice(-8)}`
    : '',
);

async function copyFingerprint() {
  await navigator.clipboard.writeText(centralFingerprint.value);
}
</script>

<template>
  <div class="sites-admin">
    <header class="sites-admin__head">
      <div>
        <h1 class="heading-section">Sites clients</h1>
        <p class="text-muted">Registre des clones WebBase consommés par ce dashboard central.</p>
      </div>
      <UiButton variant="primary" to="/admin/sites/new">
        <template #icon-left><Plus :size="16" /></template>
        Ajouter un site
      </UiButton>
    </header>

    <aside class="sites-admin__fingerprint" v-if="centralFingerprint">
      <Server :size="18" />
      <div>
        <strong>Fingerprint de la clé publique du central</strong>
        <p class="text-muted">À distribuer (par SCP ou autre canal sûr) à chaque clone, qui doit installer la clé correspondante en <code>config/super_admin_public.pem</code>.</p>
      </div>
      <button type="button" class="sites-admin__copy" @click="copyFingerprint" :title="centralFingerprint">
        <code>{{ fingerprintShort }}</code>
        <span>Copier</span>
      </button>
    </aside>

    <div v-if="loading" class="text-muted">Chargement…</div>
    <div v-else-if="sites.length === 0" class="sites-admin__empty">
      <p>Aucun site enregistré pour l'instant.</p>
      <UiButton variant="glow" to="/admin/sites/new">
        <template #icon-left><Plus :size="16" /></template>
        Enregistrer le premier
      </UiButton>
    </div>
    <ul v-else class="sites-admin__list">
      <li v-for="site in sites" :key="site.id">
        <RouterLink :to="`/admin/sites/${site.id}`" class="sites-admin__card">
          <header class="sites-admin__card-head">
            <div>
              <strong>{{ site.label }}</strong>
              <span class="sites-admin__domain">{{ site.domain }}</span>
            </div>
            <UiTag :variant="statusVariant(site.lastHealthStatus)">
              <CircleCheck v-if="site.lastHealthStatus === 'ok'" :size="12" />
              <AlertTriangle v-else-if="site.lastHealthStatus" :size="12" />
              {{ statusLabel(site.lastHealthStatus) }}
            </UiTag>
          </header>

          <dl class="sites-admin__meta">
            <div>
              <dt>Dernier check</dt>
              <dd>{{ site.lastSeenAt ? new Date(site.lastSeenAt).toLocaleString('fr-CA') : '—' }}</dd>
            </div>
            <div>
              <dt>Version</dt>
              <dd>{{ site.lastAppVersion ?? '—' }}</dd>
            </div>
            <div>
              <dt>Statut</dt>
              <dd>{{ site.enabled ? 'Activé' : 'Désactivé' }}</dd>
            </div>
          </dl>

          <p v-if="site.lastHealthError" class="sites-admin__error">{{ site.lastHealthError }}</p>

          <footer class="sites-admin__card-foot">
            <button type="button" class="sites-admin__check" :disabled="checkingId === site.id" @click.prevent.stop="checkHealth(site.id)">
              <Activity :size="14" />
              {{ checkingId === site.id ? 'Vérification…' : 'Vérifier maintenant' }}
            </button>
            <a :href="site.baseUrl" target="_blank" rel="noopener noreferrer" class="sites-admin__visit" @click.stop>
              <ExternalLink :size="14" /> Visiter
            </a>
          </footer>
        </RouterLink>
      </li>
    </ul>
  </div>
</template>

<style lang="scss" scoped>
.sites-admin {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);

  &__head {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: var(--space-3);
    flex-wrap: wrap;
  }

  &__fingerprint {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
    padding: var(--space-4);
    border: 1px solid var(--color-accent-2);
    border-radius: var(--radius-md);
    background: var(--color-accent-2-soft);

    > div {
      flex: 1;
      min-width: 0;
    }

    code {
      font-family: var(--font-mono);
    }
  }

  &__copy {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-3);
    border: 1px solid var(--color-border-strong);
    border-radius: var(--radius-pill);
    background: var(--color-bg);
    cursor: pointer;
    transition: border-color var(--transition-base);

    &:hover {
      border-color: var(--color-accent-2);
    }

    span {
      font-family: var(--font-mono);
      font-size: var(--fs-xs);
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--color-text-muted);
    }
  }

  &__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-8) var(--space-5);
    border: 1px dashed var(--color-border-strong);
    border-radius: var(--radius-md);
    text-align: center;
  }

  &__list {
    display: grid;
    gap: var(--space-3);
    grid-template-columns: 1fr;

    @media (min-width: 1080px) {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  &__card {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    padding: var(--space-4);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-bg-elev);
    color: var(--color-text);
    transition: border-color var(--transition-base), transform var(--transition-base);

    &:hover {
      border-color: var(--color-accent);
      transform: translateY(-2px);
    }
  }

  &__card-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--space-3);

    strong {
      display: block;
      font-family: var(--font-display);
      font-size: var(--fs-lg);
    }
  }

  &__domain {
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    color: var(--color-text-muted);
  }

  &__meta {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-2);
    margin: 0;
    padding: var(--space-3);
    background: var(--color-bg-soft);
    border-radius: var(--radius-sm);
    font-size: var(--fs-sm);

    dt {
      font-family: var(--font-mono);
      font-size: var(--fs-xs);
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--color-text-muted);
      margin-bottom: 2px;
    }

    dd {
      margin: 0;
      font-weight: 500;
    }
  }

  &__error {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-left: 3px solid var(--color-danger);
    background: rgba(220, 38, 38, 0.08);
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    color: var(--color-danger);
    word-break: break-word;
  }

  &__card-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--space-3);
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    text-transform: uppercase;
    letter-spacing: 0.1em;
  }

  &__check,
  &__visit {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    color: var(--color-accent);

    &:hover {
      text-decoration: underline;
    }

    &:disabled {
      opacity: 0.5;
      cursor: wait;
    }
  }
}
</style>
