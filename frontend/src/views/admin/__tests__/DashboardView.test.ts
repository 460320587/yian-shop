import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import DashboardView from '../DashboardView.vue'

vi.mock('@/api/admin', () => ({
  getDashboardStats: vi.fn(() => Promise.resolve({
    today_orders: 5,
    today_sales: 50000,
    total_customers: 100,
    total_products: 20,
    pending_after_sales: 2,
    pending_invoices: 3,
    recent_orders: [{ order_no: 'Y202601010001', customer_name: '测试', total_amount: 5000, status: 11, customer_status: '待付款', created_at: '2026-01-01' }],
    sales_trend: [{ date: '2026-01-01', amount: 50000, count: 5 }],
  })),
}))

describe('DashboardView', () => {
  it('renders dashboard with stats', async () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(DashboardView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 100))
    expect(wrapper.find('.dashboard-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('今日订单')
    expect(wrapper.text()).toContain('100')
  })
})
