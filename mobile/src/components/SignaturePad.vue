<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { IonButton } from '@ionic/vue'

const emit = defineEmits<{ change: [value: string | null] }>()
const canvas = ref<HTMLCanvasElement>()
let drawing = false

function context() {
  const value = canvas.value
  if (!value) return null
  const ctx = value.getContext('2d')
  if (!ctx) return null
  ctx.lineCap = 'round'
  ctx.lineJoin = 'round'
  ctx.lineWidth = 2.4
  ctx.strokeStyle = '#0d3150'
  return ctx
}

function point(event: PointerEvent) {
  const bounds = canvas.value!.getBoundingClientRect()
  return {
    x: (event.clientX - bounds.left) * (canvas.value!.width / bounds.width),
    y: (event.clientY - bounds.top) * (canvas.value!.height / bounds.height),
  }
}

function start(event: PointerEvent) {
  drawing = true
  canvas.value?.setPointerCapture(event.pointerId)
  const ctx = context()
  const current = point(event)
  ctx?.beginPath()
  ctx?.moveTo(current.x, current.y)
}

function move(event: PointerEvent) {
  if (!drawing) return
  const ctx = context()
  const current = point(event)
  ctx?.lineTo(current.x, current.y)
  ctx?.stroke()
}

function end() {
  if (!drawing || !canvas.value) return
  drawing = false
  emit('change', canvas.value.toDataURL('image/png'))
}

function clear() {
  const value = canvas.value
  if (value) context()?.clearRect(0, 0, value.width, value.height)
  emit('change', null)
}

onMounted(() => {
  const value = canvas.value
  if (!value) return
  const ratio = Math.max(1, window.devicePixelRatio || 1)
  value.width = Math.round(value.clientWidth * ratio)
  value.height = Math.round(150 * ratio)
})
</script>

<template>
  <div class="signature-field">
    <canvas
      ref="canvas"
      aria-label="Area tanda tangan"
      @pointerdown.prevent="start"
      @pointermove.prevent="move"
      @pointerup.prevent="end"
      @pointercancel.prevent="end"
    />
    <IonButton size="small" fill="clear" color="medium" @click="clear">
      Bersihkan tanda tangan
    </IonButton>
  </div>
</template>

<style scoped>
.signature-field {
  display: grid;
  gap: 4px;
}

canvas {
  background: #fff;
  border: 1px dashed #8da5ad;
  border-radius: 12px;
  height: 150px;
  touch-action: none;
  width: 100%;
}

ion-button {
  justify-self: start;
}
</style>
