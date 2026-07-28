<script setup lang="ts">
import {
  IonBackButton,
  IonButtons,
  IonHeader,
  IonToolbar,
} from '@ionic/vue'

withDefaults(defineProps<{
  title: string
  eyebrow?: string
  defaultHref?: string
}>(), {
  eyebrow: '',
  defaultHref: '',
})
</script>

<template>
  <IonHeader class="app-header">
    <IonToolbar>
      <IonButtons v-if="defaultHref" slot="start">
        <IonBackButton :default-href="defaultHref" text="" />
      </IonButtons>

      <div class="app-header__copy" :class="{ 'has-back': defaultHref }">
        <span v-if="eyebrow" class="eyebrow">{{ eyebrow }}</span>
        <h1>{{ title }}</h1>
      </div>

      <IonButtons v-if="$slots.actions" slot="end" class="app-header__actions">
        <slot name="actions" />
      </IonButtons>
    </IonToolbar>
  </IonHeader>
</template>

<style scoped>
.app-header {
  background:
    radial-gradient(circle at 86% -20%, rgba(63, 213, 201, 0.26), transparent 42%),
    linear-gradient(135deg, #082b45 0%, #0d4d68 100%);
  box-shadow: 0 10px 28px rgba(7, 38, 61, 0.18);
  overflow: hidden;
}

.app-header ion-toolbar {
  --background: transparent;
  --border-width: 0;
  --color: white;
  --min-height: 4.75rem;
}

:deep(.app-header ion-back-button) {
  --color: white;
}

.app-header__copy {
  display: grid;
  gap: 0.125rem;
  min-width: 0;
  padding: var(--app-space-3) var(--app-space-4);
}

.app-header__copy.has-back {
  padding-left: 0;
}

h1 {
  color: white;
  font-size: 1.15rem;
  font-weight: 850;
  letter-spacing: -0.025em;
  line-height: var(--app-line-height-tight);
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.app-header__actions {
  padding-right: var(--app-space-2);
}

.app-header :deep(.eyebrow),
.app-header__copy > .eyebrow {
  color: #7de4da;
  font-size: 0.68rem;
  letter-spacing: 0.13em;
}
</style>
