import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import OrderCreateView from '../OrderCreateView.vue'

vi.mock('@/api/order', () => ({
  createOrder: vi.fn(() => Promise.resolve({ order_no: 'Y202601010002', total_amount: 3000, status: 11 })),
}))

describe('OrderCreateView', () => {
  it('renders order creation page', () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(OrderCreateView, {
      global: { plugins: [createPinia(), router] },
    })
    expect(wrapper.find('.order-create-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('确认订单')
  })
})
