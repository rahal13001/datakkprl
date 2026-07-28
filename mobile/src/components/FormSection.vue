<script setup lang="ts">
withDefaults(defineProps<{
  title: string
  description?: string
  step?: number | string
  error?: string
}>(), {
  description: '',
  step: '',
  error: '',
})
</script>

<template>
  <section class="form-section">
    <header>
      <span v-if="step" class="form-section__step">{{ step }}</span>
      <div>
        <h3>{{ title }}</h3>
        <p v-if="description">{{ description }}</p>
      </div>
      <slot name="action" />
    </header>
    <div class="form-section__body">
      <slot />
    </div>
    <p v-if="error" class="form-section__error" role="alert">{{ error }}</p>
  </section>
</template>

<style scoped>
.form-section {
  display: grid;
  gap: var(--app-space-3);
}

header {
  align-items: start;
  display: grid;
  gap: var(--app-space-3);
  grid-template-columns: auto 1fr auto;
}

.form-section__step {
  align-items: center;
  background: var(--app-color-info-soft);
  border-radius: var(--app-radius-pill);
  color: var(--app-color-info);
  display: flex;
  font-size: var(--app-font-size-xs);
  font-weight: 850;
  height: 1.75rem;
  justify-content: center;
  width: 1.75rem;
}

h3 {
  color: var(--app-color-text);
  font-size: var(--app-font-size-md);
  margin: 0;
}

header p {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-xs);
  line-height: var(--app-line-height-body);
  margin: var(--app-space-1) 0 0;
}

.form-section__body {
  display: grid;
  gap: var(--app-space-3);
}

.form-section__error {
  color: var(--app-color-danger);
  font-size: var(--app-font-size-xs);
  margin: 0;
}
</style>
