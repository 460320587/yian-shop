import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import MyCouponsView from '../MyCouponsView.vue'

vi.mock('@/api/coupon', () => ({
  getMyCoupons: vi.fn(() => Promise.resolve({
    data: [{ id: 1, code: 'SAVE20', coupon_id: 1, status: 1, coupon: { name: '满减券', type: 1, value: 2000 } }],
    total: 1, current_page: 1, last_page: 1,
  })),
}))

describe('MyCouponsView', () => {
  it('renders my coupons', async () => {
    setActivePinia(createPinia())
    const wrapper = mount(MyCouponsView, {
      global: { plugins: [createPinia()] },
    })
    await new Promise((r) => setTimeout(r, 100))
    expect(wrapper.find('.my-coupons-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('我的优惠券')
  })
})
