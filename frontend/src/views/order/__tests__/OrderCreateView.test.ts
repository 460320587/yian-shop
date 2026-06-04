import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import OrderCreateView from '../OrderCreateView.vue'

let createOrderMock = vi.fn()

vi.mock('@/api/order', () => ({
  createOrder: (...args: any[]) => createOrderMock(...args),
}))

beforeEach(() => {
  createOrderMock = vi.fn(() => Promise.resolve({ id: 1, order_no: 'Y202601010001' }))
})

describe('OrderCreateView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(OrderCreateView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders order create page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.order-create-view').exists() || wrapper.find('.order-create-page').exists()).toBe(true)
    expect(wrapper.text()).toContain('确认订单')
  })

  it('submits order', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    if (vm.submitOrder) vm.submitOrder()
    await flushPromises()
    expect(createOrderMock).toHaveBeenCalledTimes(1)
  })
})
