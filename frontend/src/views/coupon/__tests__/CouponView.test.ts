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

beforeEach(() => {
  getCouponsMock = vi.fn(() => Promise.resolve([
    { id: 1, name: '满减券', type: 1, value: 2000, min_amount: 10000, status: 1 },
  ]))
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
    expect(wrapper.find('.coupon-view').exists() || wrapper.find('.coupon-page').exists()).toBe(true)
  })

  it('loads coupons on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getCouponsMock).toHaveBeenCalledTimes(1)
  })
})
