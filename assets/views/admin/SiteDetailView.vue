<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft, Activity, Trash2, ExternalLink, AlertTriangle, CircleCheck, Edit3, Save, X, Users } from 'lucide-vue-next';
import UiButton from '@/components/atoms/UiButton.vue';
import UiField from '@/components/atoms/UiField.vue';
import UiTag from '@/components/atoms/UiTag.vue';
import UiSparkline from '@/components/molecules/UiSparkline.vue';
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

interface CheckResult {
  status: string;
  httpStatus: number | null;
  latencyMs: number | null;
  appVersion: string | null;
  phpVersion: string | null;
  databaseOk: boolean | null;
  freeDiskBytes: number | null;
  observedAt: string | null;
  errorReason: string | null;
  alertSent?: boolean;
}

interface HistoryEntry {
  id: number;
  status: string;
  latencyMs: number | null;
  appVersion: string | null;
  error: string | null;
  checkedAt: string;
}

interface StatsPayload {
  totals: { pageViews: number; uniqueVisitors: number };
  timeseries: { day: string; pageViews: number; uniqueVisitors: number }[];
  topPaths: Record<string, number>;
}

const route = useRoute();
const router = useRouter();

const site = ref<SiteDto | null>(null);
const loading = ref(true);
const checking = ref(false);
const lastCheck = ref<CheckResult | null>(null);
const history = ref<HistoryEntry[]>([]);
const consecutiveFailures = ref(0);
const stats = ref<StatsPayload | null>(null);

const sparklineSeries = computed(() =>
  stats.value?.timeseries.map((p) => ({ label: p.day, value: p.pageViews })) ?? [],
);
const topPathEntries = computed(() => Object.entries(stats.value?.topPaths ?? {}));

const editing = ref(false);
const editLabel = ref('');
const editDomain = ref('');
const editFingerprint = ref('');
const editEnabled = ref(true);
const saveError = ref<string | null>(null);

async function load() {
  const id = route.params.id;
  const [siteRes, historyRes, statsRes] = await Promise.all([
    call<SiteDto>(() => apiClient.get(`/admin/sites/${id}`)),
    call<{ site: SiteDto; history: HistoryEntry[]; consecutiveFailures: number }>(() =>
      apiClient.get(`/admin/sites/${id}/health-history`),
    ),
    call<StatsPayload>(() => apiClient.get(`/admin/sites/${id}/stats`)),
  ]);
  if (siteRes.ok) site.value = siteRes.data;
  if (historyRes.ok) {
    history.value = historyRes.data.history;
    consecutiveFailures.value = historyRes.data.consecutiveFailures;
  }
  if (statsRes.ok) stats.value = statsRes.data;
  loading.value = false;
}

onMounted(load);

async function refreshHistory() {
  if (!site.value) return;
  const res = await call<{ history: HistoryEntry[]; consecutiveFailures: number }>(() =>
    apiClient.get(`/admin/sites/${site.value!.id}/health-history`),
  );
  if (res.ok) {
    history.value = res.data.history;
    consecutiveFailures.value = res.data.consecutiveFailures;
  }
}

async function check() {
  if (!site.value) return;
  checking.value = true;
  const res = await call<{ site: SiteDto; check: CheckResult }>(() =>
    apiClient.post(`/admin/sites/${site.value!.id}/check-health`, {}),
  );
  checking.value = false;
  if (res.ok) {
    site.value = res.data.site;
    lastCheck.value = res.data.check;
    await refreshHistory();
  }
}

function startEdit() {
  if (!site.value) return;
  editLabel.value = site.value.label;
  editDomain.value = site.value.domain;
  editFingerprint.value = site.value.publicKeyFingerprint;
  editEnabled.value = site.value.enabled;
  saveError.value = null;
  editing.value = true;
}

async function saveEdit() {
  if (!site.value) return;
  saveError.value = null;
  const res = await call<SiteDto>(() =>
    apiClient.patch(`/admin/sites/${site.value!.id}`, {
      label: editLabel.value.trim(),
      domain: editDomain.value.trim(),
      publicKeyFingerprint: editFingerprint.value.trim().toLowerCase(),
      enabled: editEnabled.value,
    }),
  );
  if (res.ok) {
    site.value = res.data;
    editing.value = false;
  } else {
    saveError.value = res.error;
  }
}

async function deleteSite() {
  if (!site.value) return;
  if (!window.confirm(`Supprimer ${site.value.label} du registre central ? Les clés sur le clone restent en place.`)) return;
  const res = await call<{ ok: true }>(() => apiClient.delete(`/admin/sites/${site.value!.id}`));
  if (res.ok) router.push('/admin/sites');
}

function formatBytes(bytes: number | null): string {
  if (!bytes) return '—';
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  let i = 0;
  let n = bytes;
  while (n >= 1024 && i < units.length - 1) {
    n /= 1024;
    i++;
  }
  return `${n.toFixed(n >= 100 ? 0 : 1)} ${units[i]}`;
}

const statusVariant = computed<'success' | 'warn' | 'danger' | 'default'>(() => {
  const s = site.value?.lastHealthStatus ?? null;
  if (s === 'ok') return 'success';
  if (s === 'opt_in_off') return 'warn';
  if (s === null) return 'default';
  return 'danger';
});
</script>

<template>
  <div class="site-detail">
    <UiButton variant="ghost" size="sm" to="/admin/sites">
      <template #icon-left><ArrowLeft :size="14" /></template>
      Retour à la liste
    </UiButton>

    <div v-if="loading" class="text-muted">Chargement…</div>
    <p v-else-if="!site" class="text-muted">Site introuvable.</p>

    <template v-else>
      <header class="site-detail__head">
        <div>
          <h1 class="heading-section">{{ site.label }}</h1>
          <p class="site-detail__domain">{{ site.domain }}</p>
        </div>
        <div class="site-detail__head-actions">
          <UiTag :variant="statusVariant">
            <CircleCheck v-if="site.lastHealthStatus === 'ok'" :size="12" />
            <AlertTriangle v-else-if="site.lastHealthStatus" :size="12" />
            {{ site.lastHealthStatus ?? 'Jamais vérifié' }}
          </UiTag>
          <UiButton variant="primary" size="sm" :loading="checking" @click="check">
            <template #icon-left><Activity :size="14" /></template>
            Vérifier maintenant
          </UiButton>
        </div>
      </header>

      <section class="site-detail__panel" v-if="lastCheck">
        <h2>Dernière vérification</h2>
        <dl class="site-detail__check">
          <div><dt>Status</dt><dd>{{ lastCheck.status }}</dd></div>
          <div><dt>HTTP</dt><dd>{{ lastCheck.httpStatus ?? '—' }}</dd></div>
          <div><dt>Latence</dt><dd>{{ lastCheck.latencyMs ?? '—' }} ms</dd></div>
          <div><dt>App version</dt><dd>{{ lastCheck.appVersion ?? '—' }}</dd></div>
          <div><dt>PHP</dt><dd>{{ lastCheck.phpVersion ?? '—' }}</dd></div>
          <div><dt>Base de données</dt><dd>{{ lastCheck.databaseOk === true ? 'OK' : lastCheck.databaseOk === false ? 'KO' : '—' }}</dd></div>
          <div><dt>Espace libre</dt><dd>{{ formatBytes(lastCheck.freeDiskBytes) }}</dd></div>
          <div v-if="lastCheck.observedAt"><dt>Observé à</dt><dd>{{ new Date(lastCheck.observedAt).toLocaleString('fr-CA') }}</dd></div>
        </dl>
        <p v-if="lastCheck.errorReason" class="site-detail__error">{{ lastCheck.errorReason }}</p>
        <p v-if="lastCheck.alertSent" class="site-detail__error">⚠️ Alerte e-mail envoyée (3 checks consécutifs KO).</p>
      </section>

      <section class="site-detail__panel" v-if="stats && stats.timeseries.length">
        <header class="site-detail__panel-head">
          <h2>Trafic 30 jours</h2>
          <div class="site-detail__totals">
            <span><strong>{{ stats.totals.pageViews.toLocaleString('fr-CA') }}</strong> vues</span>
            <span><strong>{{ stats.totals.uniqueVisitors.toLocaleString('fr-CA') }}</strong> visiteurs uniques</span>
          </div>
        </header>
        <UiSparkline :series="sparklineSeries" :height="160" aria-label="Pages vues sur 30 jours" />
        <div v-if="topPathEntries.length" class="site-detail__top-paths">
          <h3>Top 10 pages</h3>
          <ol>
            <li v-for="[path, count] in topPathEntries" :key="path">
              <code>{{ path }}</code>
              <span>{{ count.toLocaleString('fr-CA') }}</span>
            </li>
          </ol>
        </div>
      </section>
      <section class="site-detail__panel" v-else-if="stats">
        <h2>Trafic 30 jours</h2>
        <p class="text-muted">Aucune donnée encore — le clone n'a pas encore renvoyé de stats. La sync tourne chaque jour à 04h00 (cron <code>app:stats:sync-yesterday</code>).</p>
      </section>

      <section class="site-detail__panel" v-if="history.length">
        <header class="site-detail__panel-head">
          <h2>Historique des vérifications</h2>
          <UiTag :variant="consecutiveFailures > 0 ? 'danger' : 'default'">
            {{ consecutiveFailures > 0 ? `${consecutiveFailures} échec(s) consécutif(s)` : 'Stable' }}
          </UiTag>
        </header>
        <ul class="site-detail__history">
          <li v-for="entry in history" :key="entry.id" :class="{ 'is-failed': entry.status !== 'ok' }">
            <span class="site-detail__history-status">
              <CircleCheck v-if="entry.status === 'ok'" :size="12" />
              <AlertTriangle v-else :size="12" />
              {{ entry.status }}
            </span>
            <span class="site-detail__history-time">{{ new Date(entry.checkedAt).toLocaleString('fr-CA') }}</span>
            <span class="site-detail__history-latency">{{ entry.latencyMs ?? '—' }} ms</span>
            <span v-if="entry.appVersion" class="site-detail__history-version">v{{ entry.appVersion }}</span>
            <span v-if="entry.error" class="site-detail__history-error" :title="entry.error">{{ entry.error.slice(0, 60) }}</span>
          </li>
        </ul>
      </section>

      <section class="site-detail__panel">
        <header class="site-detail__panel-head">
          <h2>Configuration</h2>
          <button v-if="!editing" type="button" class="site-detail__edit-btn" @click="startEdit">
            <Edit3 :size="14" /> Modifier
          </button>
        </header>

        <dl v-if="!editing" class="site-detail__config">
          <div><dt>Domaine</dt><dd>{{ site.domain }}</dd></div>
          <div><dt>Label</dt><dd>{{ site.label }}</dd></div>
          <div><dt>État</dt><dd>{{ site.enabled ? 'Activé' : 'Désactivé' }}</dd></div>
          <div>
            <dt>Fingerprint clé publique</dt>
            <dd><code>{{ site.publicKeyFingerprint }}</code></dd>
          </div>
          <div><dt>Ajouté le</dt><dd>{{ new Date(site.addedAt).toLocaleString('fr-CA') }}</dd></div>
          <div v-if="site.lastSeenAt"><dt>Dernier check OK</dt><dd>{{ new Date(site.lastSeenAt).toLocaleString('fr-CA') }}</dd></div>
        </dl>

        <form v-else class="site-detail__edit" @submit.prevent="saveEdit">
          <UiField v-model="editLabel" label="Label" required />
          <UiField v-model="editDomain" label="Domaine" required />
          <UiField v-model="editFingerprint" label="Fingerprint clé publique (SHA-256 hex)" required />
          <label class="site-detail__toggle">
            <input v-model="editEnabled" type="checkbox" /> Activé
          </label>
          <p v-if="saveError" class="site-detail__error">{{ saveError }}</p>
          <div class="site-detail__edit-actions">
            <UiButton type="submit" variant="primary" size="sm">
              <template #icon-left><Save :size="14" /></template>
              Enregistrer
            </UiButton>
            <UiButton variant="ghost" size="sm" @click="editing = false">
              <template #icon-left><X :size="14" /></template>
              Annuler
            </UiButton>
          </div>
        </form>
      </section>

      <section class="site-detail__panel">
        <h2>Liens rapides</h2>
        <div class="site-detail__links">
          <RouterLink :to="`/admin/sites/${site.id}/admins`">
            <Users :size="14" /> Gérer les admins du clone
          </RouterLink>
          <a :href="site.baseUrl" target="_blank" rel="noopener noreferrer">
            <ExternalLink :size="14" /> Visiter {{ site.domain }}
          </a>
          <a :href="`${site.baseUrl}/admin/login`" target="_blank" rel="noopener noreferrer">
            <ExternalLink :size="14" /> Login admin du clone
          </a>
        </div>
      </section>

      <section class="site-detail__panel site-detail__panel--danger">
        <h2>Zone dangereuse</h2>
        <p class="text-muted">Retire ce site du registre central. Le clone reste en ligne — seules les requêtes de monitoring depuis ce dashboard cessent.</p>
        <UiButton variant="ghost" size="sm" @click="deleteSite">
          <template #icon-left><Trash2 :size="14" /></template>
          Supprimer du registre
        </UiButton>
      </section>
    </template>
  </div>
</template>

<style lang="scss" scoped>
.site-detail {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);

  &__head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--space-3);
    flex-wrap: wrap;
  }

  &__head-actions {
    display: inline-flex;
    align-items: center;
    gap: var(--space-3);
  }

  &__domain {
    font-family: var(--font-mono);
    color: var(--color-text-muted);
  }

  &__panel {
    padding: var(--space-5);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-bg-elev);

    h2 {
      font-size: var(--fs-md);
      font-family: var(--font-display);
      margin-bottom: var(--space-3);
    }

    &--danger {
      border-color: rgba(220, 38, 38, 0.3);
    }
  }

  &__panel-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  &__edit-btn {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--color-accent);

    &:hover {
      text-decoration: underline;
    }
  }

  &__check,
  &__config {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: var(--space-3);
    margin: 0;

    div {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    dt {
      font-family: var(--font-mono);
      font-size: var(--fs-xs);
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--color-text-muted);
    }

    dd {
      margin: 0;
      font-weight: 500;
    }

    code {
      font-family: var(--font-mono);
      font-size: var(--fs-xs);
      word-break: break-all;
    }
  }

  &__error {
    margin: var(--space-3) 0 0;
    padding: var(--space-3);
    border-left: 3px solid var(--color-danger);
    background: rgba(220, 38, 38, 0.08);
    color: var(--color-danger);
    font-family: var(--font-mono);
    font-size: var(--fs-sm);
    word-break: break-word;
  }

  &__edit {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
  }

  &__edit-actions {
    display: flex;
    gap: var(--space-3);
  }

  &__toggle {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--fs-sm);

    input {
      width: 18px;
      height: 18px;
      accent-color: var(--color-accent);
    }
  }

  &__totals {
    display: inline-flex;
    gap: var(--space-4);
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--color-text-muted);

    strong {
      color: var(--color-accent-2);
      font-variant-numeric: tabular-nums;
      margin-right: 4px;
    }
  }

  &__top-paths {
    margin-top: var(--space-4);

    h3 {
      font-family: var(--font-mono);
      font-size: var(--fs-xs);
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--color-text-muted);
      margin: 0 0 var(--space-2);
    }

    ol {
      list-style: none;
      counter-reset: top-path;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    li {
      counter-increment: top-path;
      display: grid;
      grid-template-columns: 28px 1fr auto;
      align-items: baseline;
      gap: var(--space-3);
      padding: var(--space-1) var(--space-2);
      border-radius: var(--radius-sm);

      &::before {
        content: counter(top-path, decimal-leading-zero);
        font-family: var(--font-mono);
        font-size: var(--fs-xs);
        color: var(--color-text-muted);
      }

      code {
        font-family: var(--font-mono);
        font-size: var(--fs-xs);
        word-break: break-all;
      }

      span {
        font-family: var(--font-mono);
        font-variant-numeric: tabular-nums;
        color: var(--color-accent-2);
      }
    }
  }

  &__history {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
    max-height: 320px;
    overflow-y: auto;

    li {
      display: grid;
      grid-template-columns: minmax(110px, auto) minmax(160px, auto) 60px auto 1fr;
      align-items: center;
      gap: var(--space-3);
      padding: var(--space-2) var(--space-3);
      border-radius: var(--radius-sm);
      font-family: var(--font-mono);
      font-size: var(--fs-xs);
      background: rgba(255, 255, 255, 0.02);

      &.is-failed {
        background: rgba(220, 38, 38, 0.08);
        color: var(--color-danger);
      }
    }
  }

  &__history-status {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    text-transform: uppercase;
    letter-spacing: 0.1em;
  }

  &__history-time { color: var(--color-text-muted); }
  &__history-latency { font-variant-numeric: tabular-nums; text-align: right; }
  &__history-version { color: var(--color-accent-2); }
  &__history-error { color: var(--color-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

  &__links {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);

    a {
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
      transition: border-color var(--transition-base), color var(--transition-base);

      &:hover {
        border-color: var(--color-accent);
        color: var(--color-accent);
      }
    }
  }
}
</style>
