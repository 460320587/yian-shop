import { describe, it, expect, vi } from 'vitest'
import { getCart, addToCart, updateCartItem, removeCartItem, clearCart } from '../cart'

vi.mock('@/utils/request', () => ({
  get: vi.fn((url: string) => {
    if (url === '/cart') {
      return Promise.resolve({ items: [{ id: 1, product_id: 1, product_name: '名片', thumbnail: '', quantity: 2, unit_price: 1000, subtotal: 2000, selected: true }], summary: { total_count: 1, selected_count: 1, selected_subtotal: 2000 } })
    }
    return Promise.resolve({})
  }),
  post: vi.fn((url: string) => {
    if (url === '/cart') {
      return Promise.resolve({ id: 2, product_id: 2, product_name: '传单', quantity: 1, unit_price: 500, subtotal: 500 })
    }
    if (url === '/cart/clear') {
      return Promise.resolve(null)
    }
    return Promise.resolve({})
  }),
  put: vi.fn(() => Promise.resolve({})),
  del: vi.fn(() => Promise.resolve(null)),
}))

describe('Cart API', () => {
  it('getCart returns cart data', async () => {
    const res = await getCart()
    expect(res.items).toHaveLength(1)
    expect(res.items[0].product_name).toBe('名片')
    expect(res.summary.selected_subtotal).toBe(2000)
  })

  it('addToCart adds item', async () => {
    const res = await addToCart({ product_id: 2, quantity: 1, spec_id: null })
    expect(res.product_name).toBe('传单')
  })

  it('updateCartItem updates quantity', async () => {
    const res = await updateCartItem(1, { quantity: 3 })
    expect(res).toEqual({})
  })

  it('removeCartItem removes item', async () => {
    const res = await removeCartItem(1)
    expect(res).toBeNull()
  })

  it('clearCart clears all', async () => {
    const res = await clearCart()
    expect(res).toBeNull()
  })
})
