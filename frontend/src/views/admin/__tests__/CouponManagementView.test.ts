import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import CouponManagementView from '../CouponManagementView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve()) },
    ElMessage: { success: vi.fn(), error: vi.fn() },
  }
})

const mockCoupons = [
  { id: 1, name: '满减券', type: 1, value: 2000, min_amount: 10000, total_count: 100, claimed_count: 5, status: 1 },
  { id: 2, name: '折扣券', type: 2, value: 800, min_amount: 5000, total_count: 50, claimed_count: 2, status: 0 },
]

let getAdminCouponsMock = vi.fn()
let createAdminCouponMock = vi.fn()
let updateAdminCouponMock = vi.fn()
let toggleCouponStatusMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminCoupons: (...args: any[]) => getAdminCouponsMock(...args),
  createAdminCoupon: (...args: any[]) => createAdminCouponMock(...args),
  updateAdminCoupon: (...args: any[]) => updateAdminCouponMock(...args),
  toggleCouponStatus: (...args: any[]) => toggleCouponStatusMock(...args),
}))

beforeEach(() => {
  getAdminCouponsMock = vi.fn(() => Promise.resolve({ data: mockCoupons, total: 2, current_page: 1, last_page: 1 }))
  createAdminCouponMock = vi.fn(() => Promise.resolve({ id: 3, name: '新券', type: 1, value: 1000, min_amount: 5000, total_count: 50, status: 1 }))
  updateAdminCouponMock = vi.fn(() => Promise.resolve({ id: 1, name: '满减券', type: 1, value: 2000, status: 1 }))
  toggleCouponStatusMock = vi.fn(() => Promise.resolve({ status: 0 }))
})

describe('CouponManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(CouponManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders coupon management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.coupon-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('优惠券管理')
  })

  it('loads coupons on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminCouponsMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).coupons).toHaveLength(2)
    expect((wrapper.vm as any).coupons[0].name).toBe('满减券')
    expect((wrapper.vm as any).total).toBe(2)
  })

  it('opens create dialog', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openCreate()
    expect((wrapper.vm as any).dialogVisible).toBe(true)
    expect((wrapper.vm as any).isEdit).toBe(false)
    expect((wrapper.vm as any).form.name).toBe('')
  })

  it('opens edit dialog with row data', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openEdit(mockCoupons[0])
    expect((wrapper.vm as any).dialogVisible).toBe(true)
    expect((wrapper.vm as any).isEdit).toBe(true)
    expect((wrapper.vm as any).form.name).toBe('满减券')
    expect((wrapper.vm as any).form.id).toBe(1)
  })

  it('creates new coupon', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openCreate()
    ;(wrapper.vm as any).form.name = '新券'
    ;(wrapper.vm as any).form.type = 1
    ;(wrapper.vm as any).form.value = 1000
    ;(wrapper.vm as any).handleSave()
    await flushPromises()
    expect(createAdminCouponMock).toHaveBeenCalledWith(expect.objectContaining({ name: '新券', type: 1, value: 1000 }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
  })

  it('updates existing coupon', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openEdit(mockCoupons[0])
    ;(wrapper.vm as any).form.name = '更新的券'
    ;(wrapper.vm as any).handleSave()
    await flushPromises()
    expect(updateAdminCouponMock).toHaveBeenCalledWith(1, expect.objectContaining({ name: '更新的券' }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
  })

  it('toggles coupon status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleToggle(mockCoupons[0])
    await flushPromises()
    expect(toggleCouponStatusMock).toHaveBeenCalledWith(1)
  })
})
