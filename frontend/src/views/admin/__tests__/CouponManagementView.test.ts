import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import CouponManagementView from '../CouponManagementView.vue'

vi.mock('@/api/admin', () => ({
  getAdminCoupons: vi.fn(() => Promise.resolve({ data: [{ id: 1, name: '满减券', type: 1, value: 2000, min_amount: 10000, total_count: 100, claimed_count: 5, status: 1 }], total: 1, current_page: 1, last_page: 1 })),
  createAdminCoupon: vi.fn(() => Promise.resolve({ id: 2, name: '新券', type: 1, value: 1000, min_amount: 5000, total_count: 50, status: 1 })),
  updateAdminCoupon: vi.fn(() => Promise.resolve({ id: 1, name: '满减券', type: 1, value: 2000, status: 1 })),
  toggleCouponStatus: vi.fn(() => Promise.resolve({ status: 0 })),
}))

describe('CouponManagementView', () => {
  it('renders coupon management', async () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(CouponManagementView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 100))
    expect(wrapper.find('.coupon-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('优惠券管理')
  })
})
