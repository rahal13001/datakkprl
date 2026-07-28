<script setup lang="ts">
withDefaults(defineProps<{
  compact?: boolean
  reserveActions?: boolean
}>(), {
  compact: false,
  reserveActions: false,
})
</script>

<template>
  <main
    class="page-container"
    :class="{
      'page-container--compact': compact,
      'page-container--actions': reserveActions,
    }"
  >
    <slot />
  </main>
</template>

<style scoped>
.page-container {
  margin: 0 auto;
  max-width: var(--app-content-max);
  padding: var(--app-space-5) var(--app-space-4)
    calc(6.25rem + env(safe-area-inset-bottom));
}

.page-container--compact {
  padding-top: var(--app-space-3);
}

.page-container--actions {
  padding-bottom: calc(9rem + env(safe-area-inset-bottom));
}

@media (max-width: 380px) {
  .page-container {
    padding-left: var(--app-space-3);
    padding-right: var(--app-space-3);
  }
}
</style>
