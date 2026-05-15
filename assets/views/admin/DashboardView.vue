<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { TrendingUp, Mail, FileText, CreditCard, Phone, ArrowRight } from 'lucide-vue-next';
import UiCard from '@/components/molecules/UiCard.vue';
import { apiClient, call } from '@/api/client';

interface DashboardStats {
  pendingQuotes: number;
  activeSubscriptions: number;
  monthlyRecurring: number;
  unreadMessages: number;
  pendingCallbacks: number;
}

interface RecentCallback {
  id: number;
  name: string;
  phone: string;
  preferredSlot: string;
  status: 'unread' | 'read' | 'archived';
  createdAt: string;
}

interface RecentQuote {
  id: string;
  package: string;
  clientName: string;
  status: string;
  createdAt: string;
}

interface RecentMessage {
  id: number;
  name: string;
  message: string;
  status: 'unread' | 'read' | 'archived';
  createdAt: string;
}

const stats = ref<DashboardStats | null>(null);
const recentCallbacks = ref<RecentCallback[]>([]);
const recentQuotes = ref<RecentQuote[]>([]);
const recentMessages = ref<RecentMessage[]>([]);
const loading = ref(true);

onMounted(async () => {
  const [statsRes, callbacksRes, quotesRes, messagesRes] = await Promise.all([
    call<DashboardStats>(() => apiClient.get('/admin/dashboard')),
    call<{ callbacks: RecentCallback[] }>(() => apiClient.get('/admin/callbacks')),
    call<{ quotes: RecentQuote[] }>(() => apiClient.get('/admin/quotes')),
    call<{ messages: RecentMessage[] }>(() => apiClient.get('/admin/messages')),
  ]);
  loading.value = false;
  if (statsRes.ok) stats.value = statsRes.data;
  if (callbacksRes.ok) recentCallbacks.value = callbacksRes.data.callbacks.slice(0, 4);
  if (quotesRes.ok) recentQuotes.value = quotesRes.data.quotes.slice(0, 4);
  if (messagesRes.ok) recentMessages.value = messagesRes.data.messages.slice(0, 4);
});

function formatPrice(cents: number): string {
  return new Intl.NumberFormat('fr-CA', { style: 'currency', currency: 'CAD', maximumFractionDigits: 0 }).format(cents / 100);
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('fr-CA', { day: '2-digit', month: 'short' });
}

function formatSlot(iso: string): string {
  return new Date(iso).toLocaleString('fr-CA', {
    weekday: 'short',
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
    timeZone: 'America/Toronto',
  });
}
</script>

<template>
  <div class="dashboard">
    <header class="dashboard__head">
      <h1 class="heading-section">Vue d'ensemble</h1>
      <p class="text-muted">Tout ce qui demande ton attention en un coup d'œil.</p>
    </header>

    <div v-if="loading" class="text-muted">Chargement…</div>

    <template v-else-if="stats">
      <div class="dashboard__grid">
        <UiCard padding="md">
          <FileText :size="20" />
          <p class="dashboard__metric">{{ stats.pendingQuotes }}</p>
          <p class="text-muted">Devis en attente</p>
        </UiCard>
        <UiCard padding="md" :highlighted="stats.pendingCallbacks > 0">
          <Phone :size="20" />
          <p class="dashboard__metric">{{ stats.pendingCallbacks }}</p>
          <p class="text-muted">Rappels à faire</p>
        </UiCard>
        <UiCard padding="md">
          <CreditCard :size="20" />
          <p class="dashboard__metric">{{ stats.activeSubscriptions }}</p>
          <p class="text-muted">Abonnements actifs</p>
        </UiCard>
        <UiCard padding="md">
          <TrendingUp :size="20" />
          <p class="dashboard__metric">{{ formatPrice(stats.monthlyRecurring) }}</p>
          <p class="text-muted">MRR (revenu mensuel)</p>
        </UiCard>
        <UiCard padding="md">
          <Mail :size="20" />
          <p class="dashboard__metric">{{ stats.unreadMessages }}</p>
          <p class="text-muted">Messages non lus</p>
        </UiCard>
      </div>

      <section class="dashboard__recent">
        <article class="dashboard__panel">
          <header>
            <h2><Phone :size="16" /> Derniers rappels demandés</h2>
            <RouterLink to="/admin/callbacks">Tout voir <ArrowRight :size="14" /></RouterLink>
          </header>
          <ul v-if="recentCallbacks.length > 0">
            <li v-for="cb in recentCallbacks" :key="cb.id" :class="{ 'is-unread': cb.status === 'unread' }">
              <span class="dashboard__panel-name">{{ cb.name }}</span>
              <span class="dashboard__panel-meta">{{ cb.phone }} · {{ formatSlot(cb.preferredSlot) }}</span>
              <span class="dashboard__panel-date">{{ formatDate(cb.createdAt) }}</span>
            </li>
          </ul>
          <p v-else class="text-muted">Aucune demande pour l'instant.</p>
        </article>

        <article class="dashboard__panel">
          <header>
            <h2><FileText :size="16" /> Derniers devis</h2>
            <RouterLink to="/admin/quotes">Tout voir <ArrowRight :size="14" /></RouterLink>
          </header>
          <ul v-if="recentQuotes.length > 0">
            <li v-for="q in recentQuotes" :key="q.id">
              <RouterLink :to="`/admin/quotes/${q.id}`" class="dashboard__panel-name">{{ q.clientName }}</RouterLink>
              <span class="dashboard__panel-meta">{{ q.package }} · {{ q.status }}</span>
              <span class="dashboard__panel-date">{{ formatDate(q.createdAt) }}</span>
            </li>
          </ul>
          <p v-else class="text-muted">Aucun devis encore.</p>
        </article>

        <article class="dashboard__panel">
          <header>
            <h2><Mail :size="16" /> Derniers messages</h2>
            <RouterLink to="/admin/messages">Tout voir <ArrowRight :size="14" /></RouterLink>
          </header>
          <ul v-if="recentMessages.length > 0">
            <li v-for="m in recentMessages" :key="m.id" :class="{ 'is-unread': m.status === 'unread' }">
              <span class="dashboard__panel-name">{{ m.name }}</span>
              <span class="dashboard__panel-meta">{{ m.message.slice(0, 60) }}…</span>
              <span class="dashboard__panel-date">{{ formatDate(m.createdAt) }}</span>
            </li>
          </ul>
          <p v-else class="text-muted">Aucun message reçu.</p>
        </article>
      </section>
    </template>
  </div>
</template>

<style lang="scss" scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);

  &__head {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
  }

  &__grid {
    display: grid;
    gap: var(--space-4);
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  }

  &__metric {
    font-family: var(--font-display);
    font-size: var(--fs-2xl);
    font-weight: 600;
    margin-block: var(--space-2) var(--space-1);
  }

  &__recent {
    display: grid;
    gap: var(--space-4);
    grid-template-columns: 1fr;

    @media (min-width: 1080px) {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  &__panel {
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-bg-elev);
    padding: var(--space-4);
    display: flex;
    flex-direction: column;
    gap: var(--space-3);

    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: var(--space-2);

      h2 {
        display: inline-flex;
        align-items: center;
        gap: var(--space-2);
        font-size: var(--fs-sm);
        font-family: var(--font-mono);
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--color-text-muted);
        font-weight: 500;
      }

      a {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-family: var(--font-mono);
        font-size: var(--fs-xs);
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--color-accent);

        &:hover {
          text-decoration: underline;
        }
      }
    }

    ul {
      display: flex;
      flex-direction: column;
      gap: var(--space-2);
    }

    li {
      display: grid;
      grid-template-columns: 1fr auto;
      grid-template-areas: 'name date' 'meta meta';
      gap: var(--space-1) var(--space-3);
      padding: var(--space-2) var(--space-3);
      border-radius: var(--radius-sm);
      background: var(--color-bg);
      font-size: var(--fs-sm);

      &.is-unread {
        border-left: 3px solid var(--color-accent);
      }
    }
  }

  &__panel-name {
    grid-area: name;
    font-weight: 600;
    color: var(--color-text);

    &:where(a):hover {
      color: var(--color-accent);
    }
  }

  &__panel-meta {
    grid-area: meta;
    font-size: var(--fs-xs);
    color: var(--color-text-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__panel-date {
    grid-area: date;
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    color: var(--color-text-muted);
  }
}
</style>
