<script setup lang="ts">
import { IonButton, IonIcon } from '@ionic/vue'
import { alertCircleOutline, refreshOutline } from 'ionicons/icons'

withDefaults(defineProps<{
  title?: string
  message: string
  retryLabel?: string
  compact?: boolean
}>(), {
  title: 'Data belum dapat dimuat',
  retryLabel: 'Coba lagi',
  compact: false,
})

defineEmits<{ retry: [] }>()
</script>

<template>
  <section
    class="error-state surface"
    :class="{ 'error-state--compact': compact }"
    role="alert"
  >
    <IonIcon :icon="alertCircleOutline" aria-hidden="true" />
    <div>
      <h2>{{ title }}</h2>
      <p>{{ message }}</p>
    </div>
    <IonButton fill="outline" color="danger" @click="$emit('retry')">
      <IonIcon slot="start" :icon="refreshOutline" aria-hidden="true" />
      {{ retryLabel }}
    </IonButton>
  </section>
</template>

<style scoped>
.error-state {
  align-items: center;
  display: grid;
  gap: var(--app-space-3);
  justify-items: center;
  padding: var(--app-space-8) var(--app-space-6);
  text-align: center;
}

.error-state--compact {
  grid-template-columns: auto 1fr;
  justify-items: start;
  padding: var(--app-space-4);
  text-align: left;
}

.error-state > ion-icon {
  color: var(--app-color-danger);
  font-size: 2.5rem;
}

.error-state--compact > ion-icon {
  font-size: var(--app-icon-lg);
}

h2 {
  color: var(--app-color-text);
  font-size: var(--app-font-size-md);
  margin: 0 0 var(--app-space-1);
}

p {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-sm);
  line-height: var(--app-line-height-body);
  margin: 0;
}

ion-button {
  margin-top: var(--app-space-1);
}

.error-state--compact ion-button {
  grid-column: 2;
}
</style>
