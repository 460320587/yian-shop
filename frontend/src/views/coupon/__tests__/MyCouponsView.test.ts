import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import MyCouponsView from '../MyCouponsView.vue'

let getMyCouponsMock = vi.fn()

vi.mock('@/api/coupon', () => ({
  getMyCoupons: (status?: number) => getMyCouponsMock(status),
}))

vi.mock('element-plus', () => ({
  ElMessage: { success: vi.fn(), error: vi.fn(), warning: vi.fn() },
}))

beforeEach(() => {
  getMyCouponsMock = vi.fn(() =>
    Promise.resolve([
      {
        id: 1,
        code: 'COUPON001',
        coupon_id: 1,
        status: 1,
        claimed_at: '2026-01-01T10:00:00+08:00',
        used_at: null,
        expired_at: '2026-12-31T23:59:59+08:00',
        coupon: {
          id: 1,
          name: '满减券',
          type: 1,
          value: 2000,
          min_amount: 10000,
          max_discount: null,
          start_at: '2026-01-01',
          end_at: '2026-12-31',
          total_count: 100,
          claimed_count: 10,
          per_customer_limit: 1,
          status: 1,
        },
      },
    ])
  )
})

describe('MyCouponsView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(MyCouponsView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders my coupons page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.my-coupons-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('我的优惠券')
  })

  it('loads coupons on mount with default tab', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getMyCouponsMock).toHaveBeenCalledWith(1)
    const vm = wrapper.vm as any
    expect(vm.coupons.length).toBe(1)
  })

  it('displays coupon code and status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('COUPON001')
    expect(wrapper.text()).toContain('未使用')
  })

  it('switches tab and reloads', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.activeTab = 2
    await vm.onTabChange()
    await flushPromises()
    expect(getMyCouponsMock).toHaveBeenCalledWith(2)
  })

  it('shows empty state when no coupons', async () => {
    getMyCouponsMock = vi.fn(() => Promise.resolve([]))
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.coupons.length).toBe(0)
  })
})
