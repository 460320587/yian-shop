import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import CouponView from '../CouponView.vue'

vi.mock('@/api/coupon', () => ({
  getCoupons: vi.fn(() => Promise.resolve({
    data: [{ id: 1, code: 'SAVE20', name: '满减券', type: 1, value: 2000, min_amount: 10000, status: 1 }],
    total: 1, current_page: 1, per_page: 10, last_page: 1,
  })),
  claimCoupon: vi.fn(() => Promise.resolve({ id: 2, code: 'NEW', status: 1 })),
}))

describe('CouponView', () => {
  it('renders coupon list', async () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(CouponView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 100))
    expect(wrapper.find('.coupon-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('领券中心')
  })
})
