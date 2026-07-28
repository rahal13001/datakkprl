<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ status: string }>()

const labels: Record<string, string> = {
  waiting: 'Menunggu',
  scheduled: 'Dijadwalkan',
  completed: 'Selesai',
  hadir: 'Hadir',
  izin_mendadak: 'Izin mendadak',
  draft: 'Draft',
}

const label = computed(() => labels[props.status] ?? props.status.replaceAll('_', ' '))
const visualStatus = computed(() => props.status in labels ? props.status : 'unknown')
</script>

<template>
  <span
    class="status-pill"
    :class="[`status-${status}`, `status-${visualStatus}`]"
    role="status"
    :aria-label="`Status: ${label}`"
  >
    {{ label }}
  </span>
</template>
