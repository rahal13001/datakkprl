<script setup lang="ts">
defineProps<{ status: string }>()

const labels: Record<string, string> = {
  waiting: 'Menunggu',
  scheduled: 'Dijadwalkan',
  completed: 'Selesai',
  hadir: 'Hadir',
  izin_mendadak: 'Izin mendadak',
  draft: 'Draft',
}
</script>

<template>
  <span class="status-pill" :class="`status-${status}`">
    {{ labels[status] ?? status }}
  </span>
</template>
