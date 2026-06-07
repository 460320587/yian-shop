import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import SampleOrderManagementView from '../SampleOrderManagementView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve('confirm')) },
    ElMessage: { success: vi.fn(), error: vi.fn(), warning: vi.fn() },
  }
})

let getAdminSampleOrdersMock = vi.fn()
let getAdminSampleOrderDetailMock = vi.fn()
let updateAdminSampleOrderStatusMock = vi.fn()
let deleteAdminSampleOrderMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminSampleOrders: (...args: any[]) => getAdminSampleOrdersMock(...args),
  getAdminSampleOrderDetail: (...args: any[]) => getAdminSampleOrderDetailMock(...args),
  updateAdminSampleOrderStatus: (...args: any[]) => updateAdminSampleOrderStatusMock(...args),
  deleteAdminSampleOrder: (...args: any[]) => deleteAdminSampleOrderMock(...args),
}))

beforeEach(() => {
  getAdminSampleOrdersMock = vi.fn(() => Promise.resolve({
    data: [
      { id: 1, order_no: 'S202601010001', customer_id: 10, product_id: 20, quantity: 2, status: 101, total_amount: 10000, customer: { id: 10, nickname: '张三', phone: '13800138000' }, product: { id: 20, name: '名片样品' }, created_at: '2026-01-01T10:00:00Z' },
      { id: 2, order_no: 'S202601010002', customer_id: 11, product_id: 21, quantity: 1, status: 103, total_amount: 8000, customer: { id: 11, nickname: '李四', phone: '13900139000' }, product: { id: 21, name: '宣传单样品' }, created_at: '2026-01-02T10:00:00Z' },
    ],
    total: 2, current_page: 1, last_page: 1,
  }))
  getAdminSampleOrderDetailMock = vi.fn(() => Promise.resolve({
    id: 1, order_no: 'S202601010001', customer_id: 10, product_id: 20, quantity: 2, status: 101, total_amount: 10000, unit_price: 5000, discount_amount: 0, customer: { id: 10, nickname: '张三', phone: '13800138000' }, product: { id: 20, name: '名片样品' }, remark: '加急', address_snapshot: null, created_at: '2026-01-01T10:00:00Z',
  }))
  updateAdminSampleOrderStatusMock = vi.fn(() => Promise.resolve({ id: 1, status: 103 }))
  deleteAdminSampleOrderMock = vi.fn(() => Promise.resolve({}))
})

describe('SampleOrderManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(SampleOrderManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders sample order management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.sample-order-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('样品订单管理')
  })

  it('loads sample orders on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminSampleOrdersMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).orders).toHaveLength(2)
  })

  it('filters by status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).statusFilter = 101
    ;(wrapper.vm as any).onFilter()
    await flushPromises()
    expect(getAdminSampleOrdersMock).toHaveBeenLastCalledWith(expect.objectContaining({ status: 101, page: 1, per_page: 10 }))
  })

  it('opens detail dialog', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).openDetail({ id: 1 })
    await flushPromises()
    expect(getAdminSampleOrderDetailMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).detailDialogVisible).toBe(true)
  })

  it('opens status dialog', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openStatusDialog({ id: 1, status: 101 })
    expect((wrapper.vm as any).statusDialogVisible).toBe(true)
    expect((wrapper.vm as any).statusForm.status).toBe(102)
  })

  it('updates status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openStatusDialog({ id: 1, status: 101 })
    ;(wrapper.vm as any).statusForm.status = 103
    ;(wrapper.vm as any).statusForm.remark = '已发货'
    ;(wrapper.vm as any).handleUpdateStatus()
    await flushPromises()
    expect(updateAdminSampleOrderStatusMock).toHaveBeenCalledWith(1, { status: 103, remark: '已发货' })
    expect((wrapper.vm as any).statusDialogVisible).toBe(false)
  })

  it('deletes order', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleDelete({ id: 1 })
    await flushPromises()
    expect(deleteAdminSampleOrderMock).toHaveBeenCalledWith(1)
  })

  it('status helper returns correct text', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.statusText(100)).toBe('待付款')
    expect(vm.statusText(101)).toBe('已付款')
    expect(vm.statusText(102)).toBe('待发货')
    expect(vm.statusText(103)).toBe('已发货')
    expect(vm.statusText(104)).toBe('已完成')
    expect(vm.statusText(105)).toBe('已取消')
  })

  it('computes valid next statuses', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.validNextStatuses(100)).toEqual([105])
    expect(vm.validNextStatuses(101)).toEqual([102, 103, 105])
    expect(vm.validNextStatuses(102)).toEqual([103, 105])
    expect(vm.validNextStatuses(103)).toEqual([104])
  })
})
