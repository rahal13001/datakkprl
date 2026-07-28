<script setup lang="ts">
import { computed } from 'vue'
import { IonIcon } from '@ionic/vue'
import {
  checkmarkCircleOutline,
  fileTrayOutline,
  lockClosedOutline,
  searchOutline,
} from 'ionicons/icons'

const props = withDefaults(defineProps<{
  title: string
  body?: string
  kind?: 'empty' | 'search' | 'success' | 'permission'
}>(), {
  kind: 'empty',
})

const icon = computed(() => ({
  empty: fileTrayOutline,
  search: searchOutline,
  success: checkmarkCircleOutline,
  permission: lockClosedOutline,
})[props.kind])
</script>

<template>
  <div class="empty-state surface" role="status">
    <IonIcon :icon="icon" aria-hidden="true" />
    <h3>{{ title }}</h3>
    <p v-if="body">{{ body }}</p>
    <div v-if="$slots.default" class="empty-state__actions">
      <slot />
    </div>
  </div>
</template>

<style scoped>
h3 {
  color: var(--app-color-text);
  font-size: var(--app-font-size-md);
  margin: var(--app-space-3) 0 var(--app-space-1);
}

p {
  line-height: var(--app-line-height-body);
  margin: 0 auto;
  max-width: 28rem;
}

.empty-state__actions {
  margin-top: var(--app-space-4);
}
</style>
