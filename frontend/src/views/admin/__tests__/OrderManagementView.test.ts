import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import OrderManagementView from '../OrderManagementView.vue'

vi.mock('@/api/admin', () => ({
  getAdminOrders: vi.fn(() => Promise.resolve({ data: [{ id: 1, order_no: 'Y202601010001', customer_name: '测试', total_amount: 5000, status: 11, customer_status: '待付款', created_at: '2026-01-01' }], total: 1, current_page: 1, last_page: 1 })),
  getAdminOrderDetail: vi.fn(() => Promise.resolve({ id: 1, order_no: 'Y202601010001', customer_name: '测试', total_amount: 5000, status: 11, customer_status: '待付款', created_at: '2026-01-01' })),
}))

describe('OrderManagementView', () => {
  it('renders order management', async () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(OrderManagementView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 100))
    expect(wrapper.find('.order-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('订单管理')
  })
})
