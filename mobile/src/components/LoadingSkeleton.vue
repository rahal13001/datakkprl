<script setup lang="ts">
import { IonSkeletonText } from '@ionic/vue'

withDefaults(defineProps<{
  rows?: number
  compact?: boolean
  label?: string
}>(), {
  rows: 4,
  compact: false,
  label: 'Memuat data',
})
</script>

<template>
  <div class="loading-skeleton" :class="{ 'loading-skeleton--compact': compact }" role="status">
    <span class="app-visually-hidden">{{ label }}</span>
    <div v-for="row in rows" :key="row" class="loading-skeleton__card surface">
      <IonSkeletonText :animated="true" style="width: 34%; height: 12px" />
      <IonSkeletonText :animated="true" style="width: 72%; height: 22px" />
      <IonSkeletonText :animated="true" style="width: 54%; height: 14px" />
    </div>
  </div>
</template>

<style scoped>
.loading-skeleton {
  display: grid;
  gap: var(--app-space-3);
}

.loading-skeleton__card {
  display: grid;
  gap: var(--app-space-2);
  min-height: 8.5rem;
  padding: var(--app-space-4);
}

.loading-skeleton--compact .loading-skeleton__card {
  min-height: 6rem;
}
</style>
