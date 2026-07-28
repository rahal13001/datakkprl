<script setup lang="ts">
import { IonButton, IonSpinner } from '@ionic/vue'

withDefaults(defineProps<{
  primaryLabel: string
  secondaryLabel?: string
  loading?: boolean
  disabled?: boolean
  dirty?: boolean
}>(), {
  secondaryLabel: '',
  loading: false,
  disabled: false,
  dirty: false,
})

defineEmits<{ primary: []; secondary: [] }>()
</script>

<template>
  <footer class="sticky-form-actions">
    <span v-if="dirty" class="sticky-form-actions__dirty" role="status">
      Ada perubahan yang belum disimpan
    </span>
    <div>
      <IonButton
        v-if="secondaryLabel"
        fill="outline"
        type="button"
        :disabled="loading"
        @click="$emit('secondary')"
      >
        {{ secondaryLabel }}
      </IonButton>
      <IonButton
        type="button"
        :disabled="disabled || loading"
        @click="$emit('primary')"
      >
        <IonSpinner v-if="loading" slot="start" name="crescent" />
        {{ loading ? 'Menyimpan…' : primaryLabel }}
      </IonButton>
    </div>
  </footer>
</template>

<style scoped>
.sticky-form-actions {
  background: color-mix(in srgb, var(--app-color-surface) 94%, transparent);
  border: 1px solid var(--app-color-border);
  border-radius: var(--app-radius-lg);
  bottom: var(--app-safe-bottom);
  box-shadow: var(--app-shadow-raised);
  display: grid;
  gap: var(--app-space-2);
  padding: var(--app-space-3);
  position: sticky;
  z-index: 20;
}

.sticky-form-actions__dirty {
  color: var(--app-color-warning);
  font-size: var(--app-font-size-xs);
  font-weight: 700;
}

.sticky-form-actions > div {
  display: grid;
  gap: var(--app-space-2);
  grid-template-columns: repeat(auto-fit, minmax(8rem, 1fr));
}

ion-button {
  margin: 0;
}
</style>
