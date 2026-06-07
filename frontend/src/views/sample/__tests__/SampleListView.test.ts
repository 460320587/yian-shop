import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import SampleListView from '../SampleListView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve('confirm')) },
    ElMessage: { success: vi.fn(), error: vi.fn(), warning: vi.fn() },
  }
})

let getSampleOrdersMock = vi.fn()
let getSampleOrderDetailMock = vi.fn()

vi.mock('@/api/sample', () => ({
  getSampleOrders: (...args: any[]) => getSampleOrdersMock(...args),
  getSampleOrderDetail: (...args: any[]) => getSampleOrderDetailMock(...args),
}))

beforeEach(() => {
  getSampleOrdersMock = vi.fn(() => Promise.resolve({
    data: [
      { id: 1, order_no: 'S202601010001', product_id: 10, quantity: 2, status: 100, total_amount: 10000, unit_price: 5000, product: { id: 10, name: '名片样品' }, created_at: '2026-01-01T10:00:00Z' },
      { id: 2, order_no: 'S202601010002', product_id: 11, quantity: 1, status: 103, total_amount: 8000, unit_price: 8000, product: { id: 11, name: '宣传单样品' }, created_at: '2026-01-02T10:00:00Z' },
    ],
    total: 2, current_page: 1, last_page: 1,
  }))
  getSampleOrderDetailMock = vi.fn(() => Promise.resolve({
    id: 1, order_no: 'S202601010001', product_id: 10, quantity: 2, status: 100, total_amount: 10000, unit_price: 5000, discount_amount: 0, product: { id: 10, name: '名片样品' }, remark: null, address_snapshot: null, created_at: '2026-01-01T10:00:00Z',
  }))
})

describe('SampleListView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(SampleListView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders sample order list page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.sample-list-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('我的样品订单')
  })

  it('loads sample orders on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getSampleOrdersMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).orders).toHaveLength(2)
  })

  it('displays status name', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.statusName(100)).toBe('待付款')
    expect(vm.statusName(101)).toBe('已付款')
    expect(vm.statusName(102)).toBe('待发货')
    expect(vm.statusName(103)).toBe('已发货')
    expect(vm.statusName(104)).toBe('已完成')
    expect(vm.statusName(105)).toBe('已取消')
    expect(vm.statusName(99)).toBe('未知')
  })

  it('filters by status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).statusFilter = 100
    ;(wrapper.vm as any).onFilter()
    await flushPromises()
    expect(getSampleOrdersMock).toHaveBeenLastCalledWith(expect.objectContaining({ status: 100, page: 1 }))
  })

  it('opens detail dialog', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).openDetail({ id: 1 })
    await flushPromises()
    expect(getSampleOrderDetailMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).detailDialogVisible).toBe(true)
  })

  it('formats amount correctly', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).formatAmount(10000)).toBe('¥100.00')
    expect((wrapper.vm as any).formatAmount(500)).toBe('¥5.00')
  })
})
