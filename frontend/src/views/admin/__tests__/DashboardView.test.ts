import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import DashboardView from '../DashboardView.vue'

const mockStats = {
  today_orders: 8,
  today_sales: 85000,
  yesterday_orders: 5,
  yesterday_sales: 50000,
  total_customers: 120,
  total_products: 25,
  pending_after_sales: 3,
  pending_invoices: 2,
  order_status_counts: {
    pending_payment: 4,
    paid: 6,
    in_production: 2,
    pending_delivery: 3,
    shipped: 5,
    pending_receive: 1,
    completed: 45,
  },
  recent_orders: [
    { id: 1, order_no: 'Y202601010001', customer_name: '张三', total_amount: 5000, status: 11, customer_status: '待付款', created_at: '2026-01-01T10:00:00' },
    { id: 2, order_no: 'Y202601010002', customer_name: '李四', total_amount: 8000, status: 12, customer_status: '已付款', created_at: '2026-01-01T11:00:00' },
  ],
  sales_trend: [
    { date: '2026-05-26', amount: 30000, count: 3 },
    { date: '2026-05-27', amount: 45000, count: 4 },
    { date: '2026-05-28', amount: 20000, count: 2 },
    { date: '2026-05-29', amount: 60000, count: 6 },
    { date: '2026-05-30', amount: 50000, count: 5 },
    { date: '2026-05-31', amount: 75000, count: 7 },
    { date: '2026-06-01', amount: 85000, count: 8 },
  ],
}

const mockPush = vi.fn()
let getDashboardStatsMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getDashboardStats: (...args: any[]) => getDashboardStatsMock(...args),
}))

vi.mock('vue-router', async (importOriginal) => {
  const actual = await importOriginal() as any
  return {
    ...actual,
    useRoute: () => ({ params: {} }),
    useRouter: () => ({ push: mockPush }),
  }
})

vi.mock('element-plus', () => ({
  ElMessage: {
    success: vi.fn(),
    error: vi.fn(),
  },
}))

function mountComponent() {
  setActivePinia(createPinia())
  const router = createRouter({ history: createWebHistory(), routes: [] })
  return mount(DashboardView, {
    global: {
      plugins: [createPinia(), router],
      stubs: {
        'el-table': { template: '<div class="el-table"><slot /></div>' },
        'el-table-column': { template: '<div class="el-table-column"><slot :row="$attrs.data || {}" /></div>' },
        'el-tag': { template: '<span class="el-tag"><slot /></span>' },
        'el-button': { template: '<button class="el-button"><slot /></button>' },
        'el-icon': { template: '<i class="el-icon"><slot /></i>' },
      },
      directives: {
        loading: { mounted() {}, updated() {} } as any,
      },
    },
  })
}

describe('DashboardView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    getDashboardStatsMock = vi.fn(() => Promise.resolve(mockStats))
  })

  it('renders dashboard page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.dashboard-view').exists()).toBe(true)
    expect(wrapper.findAll('.stat-card').length).toBe(6)
  })

  it('loads stats on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getDashboardStatsMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).stats).toBeTruthy()
  })

  it('displays stat values with trend indicators', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const text = wrapper.text()
    expect(text).toContain('今日订单')
    expect(text).toContain('8')
    expect(text).toContain('今日销售额')
    expect(text).toContain('¥850.00')
    expect(text).toContain('注册用户')
    expect(text).toContain('120')
    expect(text).toContain('待处理售后')
    expect(text).toContain('3')
  })

  it('computes week-over-week diff correctly', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    const orderCard = vm.statCards.find((c: any) => c.key === 'today_orders')
    expect(orderCard.diff).toBeDefined()
    expect(orderCard.diff.pct).toBe(60)
    expect(orderCard.diff.up).toBe(true)
    const salesCard = vm.statCards.find((c: any) => c.key === 'today_sales')
    expect(salesCard.diff.pct).toBe(70)
    expect(salesCard.diff.up).toBe(true)
  })

  it('renders sales trend chart when data exists', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.trend-svg').exists()).toBe(true)
    expect((wrapper.vm as any).salesTrendData).toBeTruthy()
    expect((wrapper.vm as any).salesTrendData.points.length).toBe(7)
  })

  it('renders order status distribution bars', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const bars = wrapper.findAll('.status-row')
    expect(bars.length).toBe(7)
    expect(wrapper.text()).toContain('待付款')
    expect(wrapper.text()).toContain('已完成')
  })

  it('renders quick action cards', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const actions = wrapper.findAll('.quick-action-card')
    expect(actions.length).toBe(4)
    expect(wrapper.text()).toContain('待处理售后')
    expect(wrapper.text()).toContain('商品管理')
  })

  it('navigates on stat card click', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const cards = wrapper.findAll('.stat-card')
    await cards[0].trigger('click')
    expect(mockPush).toHaveBeenCalledWith('/admin/orders')
  })

  it('navigates on quick action click', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const actions = wrapper.findAll('.quick-action-card')
    await actions[2].trigger('click')
    expect(mockPush).toHaveBeenCalledWith('/admin/products')
  })

  it('shows empty chart state when no trend data', async () => {
    getDashboardStatsMock = vi.fn(() => Promise.resolve({ ...mockStats, sales_trend: [] }))
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).salesTrendData).toBeNull()
  })

  it('exposes refs and methods', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.stats).toBeDefined()
    expect(vm.loading).toBe(false)
    expect(typeof vm.loadStats).toBe('function')
  })
})
