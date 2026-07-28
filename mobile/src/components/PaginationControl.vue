<script setup lang="ts">
import { IonButton, IonIcon } from '@ionic/vue'
import { arrowBackOutline, arrowForwardOutline } from 'ionicons/icons'

withDefaults(defineProps<{
  page: number
  itemCount: number
  hasPrevious?: boolean
  hasNext?: boolean
  loading?: boolean
}>(), {
  hasPrevious: false,
  hasNext: false,
  loading: false,
})

defineEmits<{ previous: []; next: [] }>()
</script>

<template>
  <nav class="pagination" aria-label="Navigasi halaman">
    <div class="pagination__summary" aria-live="polite">
      <strong>Halaman {{ page }}</strong>
      <span>{{ itemCount }} data ditampilkan</span>
    </div>
    <div class="pagination__actions">
      <IonButton
        fill="outline"
        :disabled="!hasPrevious || loading"
        @click="$emit('previous')"
      >
        <IonIcon slot="start" :icon="arrowBackOutline" aria-hidden="true" />
        Sebelumnya
      </IonButton>
      <IonButton
        :disabled="!hasNext || loading"
        @click="$emit('next')"
      >
        Berikutnya
        <IonIcon slot="end" :icon="arrowForwardOutline" aria-hidden="true" />
      </IonButton>
    </div>
  </nav>
</template>

<style scoped>
.pagination {
  align-items: center;
  background: var(--app-color-surface);
  border: 1px solid var(--app-color-border);
  border-radius: var(--app-radius-lg);
  display: grid;
  gap: var(--app-space-3);
  margin-top: var(--app-space-4);
  padding: var(--app-space-3);
}

.pagination__summary {
  display: grid;
  gap: 0.125rem;
}

.pagination__summary strong {
  color: var(--app-color-text);
  font-size: var(--app-font-size-sm);
}

.pagination__summary span {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-xs);
}

.pagination__actions {
  display: grid;
  gap: var(--app-space-2);
  grid-template-columns: 1fr 1fr;
}

.pagination__actions ion-button {
  margin: 0;
}

@media (min-width: 560px) {
  .pagination {
    grid-template-columns: 1fr auto;
  }
}
</style>
