import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import PaginationControl from '@/components/PaginationControl.vue'

function wrapper(options: {
  page?: number
  itemCount?: number
  hasPrevious?: boolean
  hasNext?: boolean
  loading?: boolean
} = {}) {
  return mount(PaginationControl, {
    props: {
      page: options.page ?? 1,
      itemCount: options.itemCount ?? 20,
      hasPrevious: options.hasPrevious ?? false,
      hasNext: options.hasNext ?? false,
      loading: options.loading ?? false,
    },
    global: {
      stubs: {
        IonButton: {
          props: ['disabled'],
          emits: ['click'],
          template: '<button :disabled="disabled" @click="$emit(\'click\')"><slot /></button>',
        },
        IonIcon: true,
      },
    },
  })
}

describe('PaginationControl', () => {
  it('describes the current cursor page without claiming a global total', () => {
    const control = wrapper({ page: 3, itemCount: 7 })

    expect(control.text()).toContain('Halaman 3')
    expect(control.text()).toContain('7 data ditampilkan')
    expect(control.text()).not.toContain('total')
  })

  it('enables cursor actions only when the API provides a cursor', async () => {
    const control = wrapper({ hasPrevious: true, hasNext: true })
    const buttons = control.findAll('button')

    await buttons[0].trigger('click')
    await buttons[1].trigger('click')

    expect(control.emitted('previous')).toHaveLength(1)
    expect(control.emitted('next')).toHaveLength(1)
  })

  it('disables both actions while a page request is loading', () => {
    const control = wrapper({ hasPrevious: true, hasNext: true, loading: true })

    expect(control.findAll('button').every((button) => button.attributes('disabled') !== undefined))
      .toBe(true)
  })
})
