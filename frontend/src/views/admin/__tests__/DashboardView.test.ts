import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import DashboardView from '../DashboardView.vue'

const mockStats = {
  today_orders: 5,
  today_sales: 50000,
  total_customers: 100,
  total_products: 20,
  pending_after_sales: 2,
  pending_invoices: 3,
  recent_orders: [
    { order_no: 'Y202601010001', customer_name: '测试', total_amount: 5000, status: 11, customer_status: '待付款', created_at: '2026-01-01' },
  ],
  sales_trend: [{ date: '2026-01-01', amount: 50000, count: 5 }],
}

let getDashboardStatsMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getDashboardStats: (...args: any[]) => getDashboardStatsMock(...args),
}))

beforeEach(() => {
  getDashboardStatsMock = vi.fn(() => Promise.resolve(mockStats))
})

describe('DashboardView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(DashboardView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders dashboard page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.dashboard-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('今日订单')
  })

  it('loads stats on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getDashboardStatsMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).stats[0].value).toBe(5)
    expect((wrapper.vm as any).stats[1].value).toBe('¥500.00')
    expect((wrapper.vm as any).stats[2].value).toBe(100)
  })

  it('displays recent orders', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).recentOrders).toHaveLength(1)
    expect((wrapper.vm as any).recentOrders[0].order_no).toBe('Y202601010001')
  })

  it('formats sales amount correctly', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).stats[1].value).toBe('¥500.00')
  })
})
