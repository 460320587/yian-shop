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
let updateCartItemMock = vi.fn()
let removeCartItemMock = vi.fn()
let clearCartMock = vi.fn()

vi.mock('@/api/cart', () => ({
  getCart: () => getCartMock(),
  updateCartItem: (...args: any[]) => updateCartItemMock(...args),
  removeCartItem: (...args: any[]) => removeCartItemMock(...args),
  clearCart: () => clearCartMock(),
}))

vi.stubGlobal('confirm', () => true)

beforeEach(() => {
  getCartMock = vi.fn(() => Promise.resolve(mockCart))
  updateCartItemMock = vi.fn(() => Promise.resolve({}))
  removeCartItemMock = vi.fn(() => Promise.resolve({}))
  clearCartMock = vi.fn(() => Promise.resolve({}))
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
    expect(wrapper.find('.empty-state').exists()).toBe(true)
  })

  it('updates item quantity', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.updateQuantity(mockCart.items[0], 3)
    await flushPromises()

    expect(updateCartItemMock).toHaveBeenCalledWith(1, { quantity: 3 })
  })

  it('toggles item selection', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.toggleSelection(mockCart.items[0], false)
    await flushPromises()

    expect(updateCartItemMock).toHaveBeenCalledWith(1, { selected: false })
  })

  it('removes item', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.removeItem(mockCart.items[0])
    await flushPromises()

    expect(removeCartItemMock).toHaveBeenCalledWith(1)
  })

  it('selects all items', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.toggleSelectAll(true)
    await flushPromises()

    expect(updateCartItemMock).toHaveBeenCalledTimes(2)
  })

  it('shows summary and checkout', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('.cart-summary').exists()).toBe(true)
    expect(wrapper.find('.cart-summary').text()).toContain('20.00')
    expect(wrapper.find('[data-testid="checkout-btn"]').exists()).toBe(true)
  })

  it('clears cart', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.clearCart()
    await flushPromises()

    expect(clearCartMock).toHaveBeenCalled()
  })

  it('exposes refs and methods', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    expect(vm.cartItems).toBeDefined()
    expect(vm.summary).toBeDefined()
    expect(typeof vm.updateQuantity).toBe('function')
    expect(typeof vm.removeItem).toBe('function')
    expect(typeof vm.toggleSelectAll).toBe('function')
    expect(typeof vm.goCheckout).toBe('function')
  })
})
