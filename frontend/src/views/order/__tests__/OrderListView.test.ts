import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import OrderListView from '../OrderListView.vue'

vi.mock('@/api/order', () => ({
  getOrders: vi.fn(() => Promise.resolve({
    data: [{ id: 1, order_no: 'Y202601010001', status: 11, customer_status: '待付款', total_amount: 5000, created_at: '2026-01-01' }],
    total: 1, current_page: 1, per_page: 10, last_page: 1,
  })),
  cancelOrder: vi.fn(() => Promise.resolve(null)),
}))

describe('OrderListView', () => {
  it('renders order list', async () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(OrderListView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 100))
    expect(wrapper.find('.order-list-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('我的订单')
  })
})
