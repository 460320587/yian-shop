import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import MyCouponsView from '../MyCouponsView.vue'

let getMyCouponsMock = vi.fn()

vi.mock('@/api/coupon', () => ({
  getMyCoupons: () => getMyCouponsMock(),
}))

beforeEach(() => {
  getMyCouponsMock = vi.fn(() => Promise.resolve([
    { id: 1, name: '满减券', type: 1, value: 2000, status: 1, used_at: null },
  ]))
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
    expect(wrapper.find('.my-coupons-view').exists() || wrapper.find('.my-coupons-page').exists()).toBe(true)
  })

  it('loads my coupons on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getMyCouponsMock).toHaveBeenCalledTimes(1)
  })
})
