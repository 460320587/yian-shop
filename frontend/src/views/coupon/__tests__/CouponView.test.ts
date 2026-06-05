import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import CouponView from '../CouponView.vue'

let getCouponsMock = vi.fn()
let claimCouponMock = vi.fn()

vi.mock('@/api/coupon', () => ({
  getCoupons: () => getCouponsMock(),
  claimCoupon: (...args: any[]) => claimCouponMock(...args),
}))

vi.mock('element-plus', () => ({
  ElMessage: { success: vi.fn(), error: vi.fn(), warning: vi.fn() },
}))

beforeEach(() => {
  getCouponsMock = vi.fn(() =>
    Promise.resolve([
      {
        id: 1,
        name: '满100减20',
        type: 1,
        value: 2000,
        min_amount: 10000,
        max_discount: null,
        start_at: '2026-01-01T00:00:00+08:00',
        end_at: '2026-12-31T23:59:59+08:00',
        total_count: 100,
        claimed_count: 10,
        per_customer_limit: 1,
        status: 1,
      },
      {
        id: 2,
        name: '9折券',
        type: 2,
        value: 90,
        min_amount: 5000,
        max_discount: 5000,
        start_at: '2026-01-01T00:00:00+08:00',
        end_at: '2026-12-31T23:59:59+08:00',
        total_count: 50,
        claimed_count: 50,
        per_customer_limit: 1,
        status: 1,
      },
    ])
  )
  claimCouponMock = vi.fn(() => Promise.resolve({}))
})

describe('CouponView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(CouponView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders coupon page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.coupon-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('领券中心')
  })

  it('loads coupons on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getCouponsMock).toHaveBeenCalledTimes(1)
    const vm = wrapper.vm as any
    expect(vm.coupons.length).toBe(2)
  })

  it('displays coupon value and condition', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('¥20.00')
    expect(wrapper.text()).toContain('满¥100.00可用')
    expect(wrapper.text()).toContain('9折券')
  })

  it('disables claim button for claimed out coupons', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('已领完')
  })

  it('claims a coupon', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    await vm.handleClaim(vm.coupons[0])
    await flushPromises()
    expect(claimCouponMock).toHaveBeenCalledWith(1)
    expect(getCouponsMock).toHaveBeenCalledTimes(2)
  })

  it('shows empty state when no coupons', async () => {
    getCouponsMock = vi.fn(() => Promise.resolve([]))
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.coupons.length).toBe(0)
  })
})
