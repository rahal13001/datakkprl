import { mount } from '@vue/test-utils'
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'
import { describe, expect, it, vi } from 'vitest'
import EmployeeSearchSelector from '@/components/EmployeeSearchSelector.vue'

vi.mock('@/api/client', () => ({
  api: {
    get: vi.fn().mockResolvedValue({ data: { data: [] } }),
  },
  apiError: () => 'Pencarian gagal.',
}))

describe('EmployeeSearchSelector', () => {
  it('keeps the confirmation action in the fixed modal area', async () => {
    const wrapper = mount(EmployeeSearchSelector, {
      props: {
        modelValue: [],
        'onUpdate:modelValue': () => undefined,
      },
      global: {
        plugins: [[VueQueryPlugin, { queryClient: new QueryClient() }]],
        stubs: {
          IonButton: {
            props: ['disabled'],
            emits: ['click'],
            template: '<button type="button" :disabled="disabled" @click="$emit(\'click\')"><slot /></button>',
          },
          IonCheckbox: true,
          IonChip: true,
          IonContent: {
            template: '<div><slot /><slot name="fixed" /></div>',
          },
          IonIcon: true,
          IonModal: {
            props: ['isOpen'],
            template: '<section v-if="isOpen"><slot /></section>',
          },
          IonProgressBar: true,
          IonSearchbar: true,
        },
      },
    })

    await wrapper.get('.employee-selector > button').trigger('click')

    const actions = wrapper.get('.employee-sheet__actions')
    expect(actions.attributes('slot')).toBe('fixed')
    expect(actions.text()).toContain('Gunakan')
    expect(actions.text()).toContain('Batal')
  })
})
