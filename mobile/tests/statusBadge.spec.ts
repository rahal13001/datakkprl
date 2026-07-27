import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import StatusBadge from '@/components/StatusBadge.vue'

describe('StatusBadge', () => {
  it.each([
    ['waiting', 'Menunggu'],
    ['scheduled', 'Dijadwalkan'],
    ['completed', 'Selesai'],
    ['izin_mendadak', 'Izin mendadak'],
  ])('renders the established %s label', (status, label) => {
    const wrapper = mount(StatusBadge, { props: { status } })

    expect(wrapper.text()).toBe(label)
    expect(wrapper.classes()).toContain(`status-${status}`)
  })
})

