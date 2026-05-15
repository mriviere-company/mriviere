<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Activity, Globe, TrendingUp } from 'lucide-vue-next';
import UiSparkline from '@/components/molecules/UiSparkline.vue';
import UiTag from '@/components/atoms/UiTag.vue';
import { apiClient, call } from '@/api/client';

interface AggregatedDto {
  totals: {
    sites: number;
    enabledSites: number;
    pageViews30d: number;
    uniqueVisitors30d: number;
  };
  timeseries: { day: string; pageViews: number }[];
  topPaths: Record<string, number>;
  perSite: {
    id: number;
    label: string;
    domain: string;
    enabled: boolean;
    lastHealthStatus: string | null;
    pageViews30d: number;
    uniqueVisitors30d: number;
  }[];
}

const data = ref<AggregatedDto | null>(null);
const loading = ref(true);

onMounted(async () => {
  const res = await call<AggregatedDto>(() => apiClient.get('/admin/aggregated'));
  loading.value = false;
  if (res.ok) data.value = res.data;
});

const sparklineSeries = computed(() =>
  data.value?.timeseries.map((p) => ({ label: p.day, value: p.pageViews })) ?? [],
);
const topPathEntries = computed(() => Object.entries(data.value?.topPaths ?? {}));
</script>

<template>
  <div class="aggregated">
    <header>
      <h1 class="heading-section">Vue agrégée</h1>
      <p class="text-muted">Trafic et santé consolidés sur l'ensemble des clones supervisés (fenêtre glissante 30 jours).</p>
    </header>

    <p v-if="loading" class="text-muted">Chargement…</p>

    <template v-else-if="data">
      <section class="aggregated__kpis">
        <article>
          <span class="aggregated__kpi-eyebrow"><Globe :size="14" /> Sites</span>
          <strong>{{ data.totals.enabledSites }}<span> / {{ data.totals.sites }}</span></strong>
          <em>actifs / total</em>
        </article>
        <article>
          <span class="aggregated__kpi-eyebrow"><TrendingUp :size="14" /> Pages vues</span>
          <strong>{{ data.totals.pageViews30d.toLocaleString('fr-CA') }}</strong>
          <em>30 jours</em>
        </article>
        <article>
          <span class="aggregated__kpi-eyebrow"><Activity :size="14" /> Visiteurs uniques</span>
          <strong>{{ data.totals.uniqueVisitors30d.toLocaleString('fr-CA') }}</strong>
          <em>30 jours</em>
        </article>
      </section>

      <section class="aggregated__panel" v-if="data.timeseries.length">
        <h2>Pages vues — tous sites</h2>
        <UiSparkline :series="sparklineSeries" :height="180" aria-label="Pages vues agrégées sur 30 jours" />
      </section>
      <section class="aggregated__panel" v-else>
        <h2>Pages vues — tous sites</h2>
        <p class="text-muted">Aucune donnée encore. La sync quotidienne (cron <code>app:stats:sync-yesterday</code>) alimentera ce graphique.</p>
      </section>

      <section class="aggregated__panel" v-if="topPathEntries.length">
        <h2>Top 20 pages tous sites confondus</h2>
        <ol class="aggregated__paths">
          <li v-for="[path, count] in topPathEntries" :key="path">
            <code>{{ path }}</code>
            <span>{{ count.toLocaleString('fr-CA') }}</span>
          </li>
        </ol>
      </section>

      <section class="aggregated__panel">
        <h2>Détail par site</h2>
        <table v-if="data.perSite.length" class="aggregated__sites">
          <thead>
            <tr><th>Site</th><th>Statut</th><th>Pages</th><th>Visiteurs</th></tr>
          </thead>
          <tbody>
            <tr v-for="row in data.perSite" :key="row.id">
              <td>
                <RouterLink :to="`/admin/sites/${row.id}`">{{ row.label }}</RouterLink>
                <small>{{ row.domain }}</small>
              </td>
              <td>
                <UiTag v-if="row.lastHealthStatus === 'ok'" variant="success">ok</UiTag>
                <UiTag v-else-if="row.lastHealthStatus" variant="danger">{{ row.lastHealthStatus }}</UiTag>
                <UiTag v-else>jamais vérifié</UiTag>
                <UiTag v-if="!row.enabled" variant="warn">désactivé</UiTag>
              </td>
              <td class="aggregated__num">{{ row.pageViews30d.toLocaleString('fr-CA') }}</td>
              <td class="aggregated__num">{{ row.uniqueVisitors30d.toLocaleString('fr-CA') }}</td>
            </tr>
          </tbody>
        </table>
        <p v-else class="text-muted">Aucun site enregistré.</p>
      </section>
    </template>

    <p v-else class="text-muted">Données indisponibles.</p>
  </div>
</template>

<style lang="scss" scoped>
.aggregated {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);

  &__kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: var(--space-3);

    article {
      padding: var(--space-4);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-md);
      background: var(--color-bg-elev);
      display: flex;
      flex-direction: column;
      gap: var(--space-1);
    }
  }

  &__kpi-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--color-text-muted);
  }

  article strong {
    font-family: var(--font-display);
    font-size: 2.4rem;
    font-variant-numeric: tabular-nums;
    color: var(--color-accent-2);

    span {
      color: var(--color-text-muted);
      font-size: 1.4rem;
    }
  }

  article em {
    font-style: normal;
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--color-text-muted);
  }

  &__panel {
    padding: var(--space-5);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-bg-elev);

    h2 {
      font-family: var(--font-display);
      font-size: var(--fs-md);
      margin: 0 0 var(--space-3);
    }
  }

  &__paths {
    list-style: none;
    counter-reset: agg-path;
    margin: 0;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 4px var(--space-4);

    li {
      counter-increment: agg-path;
      display: grid;
      grid-template-columns: 28px 1fr auto;
      align-items: baseline;
      gap: var(--space-2);

      &::before {
        content: counter(agg-path, decimal-leading-zero);
        font-family: var(--font-mono);
        font-size: var(--fs-xs);
        color: var(--color-text-muted);
      }

      code { font-family: var(--font-mono); font-size: var(--fs-xs); word-break: break-all; }
      span { font-family: var(--font-mono); font-variant-numeric: tabular-nums; color: var(--color-accent-2); }
    }
  }

  &__sites {
    width: 100%;
    border-collapse: collapse;
    font-size: var(--fs-sm);

    th, td {
      padding: var(--space-2) var(--space-3);
      border-bottom: 1px solid var(--color-border);
      text-align: left;
    }

    th {
      font-family: var(--font-mono);
      font-size: var(--fs-xs);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--color-text-muted);
    }

    td small {
      display: block;
      color: var(--color-text-muted);
      font-family: var(--font-mono);
      font-size: var(--fs-xs);
    }

    a {
      color: var(--color-text);
      font-weight: 500;

      &:hover { color: var(--color-accent-2); }
    }
  }

  &__num {
    font-family: var(--font-mono);
    font-variant-numeric: tabular-nums;
    text-align: right;
  }
}
</style>
