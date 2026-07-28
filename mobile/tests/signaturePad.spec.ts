import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import SignaturePad from '@/components/SignaturePad.vue'

describe('SignaturePad', () => {
  it('opens a dedicated canvas editor instead of a text input', async () => {
    const wrapper = mount(SignaturePad, {
      global: {
        stubs: {
          IonButton: {
            emits: ['click'],
            template: '<button type="button" @click="$emit(\'click\')"><slot /></button>',
          },
          IonContent: {
            template: '<div><slot /><slot name="fixed" /></div>',
          },
          IonIcon: true,
          IonModal: {
            props: ['isOpen'],
            template: '<section v-if="isOpen"><slot /></section>',
          },
        },
      },
    })

    await wrapper.get('.signature-field__preview').trigger('click')

    expect(wrapper.find('canvas').exists()).toBe(true)
    expect(wrapper.find('input').exists()).toBe(false)
    expect(wrapper.text()).toContain('Gunakan tanda tangan')
  })
})
