import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AttachmentUploader from '@/components/AttachmentUploader.vue'

function mountUploader(maxFiles = 3) {
  return mount(AttachmentUploader, {
    props: {
      modelValue: [],
      label: 'Dokumentasi',
      multiple: true,
      maxFiles,
      'onUpdate:modelValue': () => undefined,
    },
    global: {
      stubs: {
        IonButton: {
          props: ['disabled'],
          template: '<button :disabled="disabled"><slot /></button>',
        },
        IonIcon: true,
        IonProgressBar: true,
      },
    },
  })
}

describe('AttachmentUploader', () => {
  it('returns selected files through the existing v-model contract', async () => {
    const uploader = mountUploader()
    const photo = new File(['photo'], 'lapangan.jpg', { type: 'image/jpeg' })
    const input = uploader.find('input:not([capture])')
    Object.defineProperty(input.element, 'files', { value: [photo] })

    await input.trigger('change')

    expect(uploader.emitted('update:modelValue')?.[0]).toEqual([[photo]])
  })

  it('enforces the configured client-side file count', async () => {
    const uploader = mountUploader(1)
    const first = new File(['one'], 'satu.jpg', { type: 'image/jpeg' })
    const second = new File(['two'], 'dua.jpg', { type: 'image/jpeg' })
    const input = uploader.find('input:not([capture])')
    Object.defineProperty(input.element, 'files', { value: [first, second] })

    await input.trigger('change')

    expect(uploader.emitted('update:modelValue')?.[0]).toEqual([[first]])
    expect(uploader.text()).toContain('Maksimal 1 file')
  })
})
