import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import CartView from '../CartView.vue'

vi.mock('@/api/cart', () => ({
  getCart: vi.fn(() => Promise.resolve({
    items: [{ id: 1, product_id: 1, product_name: '名片', thumbnail: '', quantity: 2, unit_price: 1000, subtotal: 2000, selected: true }],
    summary: { total_count: 1, selected_count: 1, selected_subtotal: 2000 },
  })),
  updateCartItem: vi.fn(() => Promise.resolve({})),
  removeCartItem: vi.fn(() => Promise.resolve(null)),
  clearCart: vi.fn(() => Promise.resolve(null)),
}))

describe('CartView', () => {
  it('renders cart items', async () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(CartView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 100))
    expect(wrapper.find('.cart-view').exists()).toBe(true)
  })
})
