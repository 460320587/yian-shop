import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import CartView from '../CartView.vue'

const mockCart = {
  items: [
    { id: 1, product_id: 1, product_name: '名片', thumbnail: '', quantity: 2, unit_price: 1000, subtotal: 2000, selected: true },
    { id: 2, product_id: 2, product_name: '海报', thumbnail: '', quantity: 1, unit_price: 2000, subtotal: 2000, selected: false },
  ],
  summary: { total_count: 2, selected_count: 1, selected_subtotal: 2000 },
}

let getCartMock = vi.fn()

vi.mock('@/api/cart', () => ({
  getCart: () => getCartMock(),
}))

beforeEach(() => {
  getCartMock = vi.fn(() => Promise.resolve(mockCart))
})

describe('CartView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(CartView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders cart page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.cart-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('购物车')
  })

  it('loads cart items on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getCartMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).cartItems).toHaveLength(2)
    expect((wrapper.vm as any).cartItems[0].product_name).toBe('名片')
  })

  it('handles empty cart', async () => {
    getCartMock = vi.fn(() => Promise.resolve({ items: [], summary: { total_count: 0, selected_count: 0, selected_subtotal: 0 } }))
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).cartItems).toHaveLength(0)
  })
})
