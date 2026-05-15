<script setup lang="ts">
import { onMounted, ref } from 'vue';
import UiCard from '@/components/molecules/UiCard.vue';
import UiTag from '@/components/atoms/UiTag.vue';
import { apiClient, call } from '@/api/client';
import type { ContactMessageDto } from '@/types';

const items = ref<ContactMessageDto[]>([]);
const loading = ref(true);

onMounted(async () => {
  const res = await call<{ messages: ContactMessageDto[] }>(() => apiClient.get('/admin/messages'));
  loading.value = false;
  if (res.ok) items.value = res.data.messages;
});

async function markAsRead(id: number) {
  const res = await call<{ ok: true }>(() => apiClient.post(`/admin/messages/${id}/read`, {}));
  if (res.ok) {
    const item = items.value.find((m) => m.id === id);
    if (item) item.status = 'read';
  }
}
</script>

<template>
  <div class="messages-admin">
    <header>
      <h1 class="heading-section">Messages</h1>
      <p class="text-muted">{{ items.length }} message(s) au total.</p>
    </header>

    <div v-if="loading" class="text-muted">Chargement…</div>
    <ul v-else class="messages-admin__list">
      <li v-for="msg in items" :key="msg.id">
        <UiCard padding="md">
          <header class="messages-admin__head">
            <div>
              <strong>{{ msg.name }}</strong>
              <span class="text-muted"> · {{ msg.email }}</span>
            </div>
            <UiTag :variant="msg.status === 'unread' ? 'accent' : 'default'">{{ msg.status }}</UiTag>
          </header>
          <p class="messages-admin__body">{{ msg.message }}</p>
          <footer class="messages-admin__foot text-muted">
            <span>{{ new Date(msg.createdAt).toLocaleString('fr-FR') }}</span>
            <button v-if="msg.status === 'unread'" type="button" @click="markAsRead(msg.id)">
              Marquer comme lu
            </button>
          </footer>
        </UiCard>
      </li>
      <li v-if="items.length === 0" class="text-muted">Aucun message pour le moment.</li>
    </ul>
  </div>
</template>

<style lang="scss" scoped>
.messages-admin {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);

  &__list {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
  }

  &__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-2);
  }

  &__body {
    color: var(--color-text-soft);
    line-height: var(--lh-relaxed);
    white-space: pre-wrap;
  }

  &__foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: var(--space-3);
    padding-top: var(--space-3);
    border-top: 1px solid var(--color-border);
    font-size: var(--fs-xs);
    font-family: var(--font-mono);

    button {
      color: var(--color-accent);
      text-transform: uppercase;
      letter-spacing: 0.1em;

      &:hover {
        text-decoration: underline;
      }
    }
  }
}
</style>
