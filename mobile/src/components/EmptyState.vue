<script setup lang="ts">
import { IonIcon } from '@ionic/vue'
import { fileTrayOutline } from 'ionicons/icons'

defineProps<{ title: string; body?: string }>()
</script>

<template>
  <div class="empty-state surface">
    <IonIcon :icon="fileTrayOutline" />
    <h3>{{ title }}</h3>
    <p v-if="body">{{ body }}</p>
  </div>
</template>
