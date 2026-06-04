import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import DashboardView from '../DashboardView.vue'

vi.mock('@/api/admin', () => ({
  getAdminOrders: vi.fn(() => Promise.resolve({ data: [{ order_no: 'Y202601010001', customer_name: '测试', total_amount: 5000, status: 11, customer_status: '待付款', created_at: '2026-01-01' }], total: 1 })),
  getAdminCustomers: vi.fn(() => Promise.resolve({ data: [], total: 10 })),
}))

describe('DashboardView', () => {
  it('renders dashboard', async () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(DashboardView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 100))
    expect(wrapper.find('.dashboard-view').exists()).toBe(true)
  })
})
