import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useCartStore } from '../cart'

describe('Cart Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('initial state is empty', () => {
    const store = useCartStore()
    expect(store.totalCount).toBe(0)
    expect(store.selectedTotalPrice).toBe(0)
    expect(store.isAllSelected).toBe(false)
  })

  it('addItem adds new item', () => {
    const store = useCartStore()
    store.addItem({ id: 1, product_id: 1, product_name: '商品1', thumbnail: '', price: 1000, quantity: 2, selected: true })
    expect(store.totalCount).toBe(2)
    expect(store.items).toHaveLength(1)
  })

  it('addItem increments quantity for existing item', () => {
    const store = useCartStore()
    store.addItem({ id: 1, product_id: 1, product_name: '商品1', thumbnail: '', price: 1000, quantity: 1, selected: true })
    store.addItem({ id: 2, product_id: 1, product_name: '商品1', thumbnail: '', price: 1000, quantity: 2, selected: true })
    expect(store.items[0].quantity).toBe(3)
  })

  it('removeItem removes item', () => {
    const store = useCartStore()
    store.addItem({ id: 1, product_id: 1, product_name: '商品1', thumbnail: '', price: 1000, quantity: 1, selected: true })
    store.removeItem(1)
    expect(store.items).toHaveLength(0)
  })

  it('clearCart empties cart', () => {
    const store = useCartStore()
    store.addItem({ id: 1, product_id: 1, product_name: '商品1', thumbnail: '', price: 1000, quantity: 1, selected: true })
    store.clearCart()
    expect(store.items).toHaveLength(0)
    expect(store.totalCount).toBe(0)
  })

  it('selectedTotalPrice calculates correctly', () => {
    const store = useCartStore()
    store.addItem({ id: 1, product_id: 1, product_name: '商品1', thumbnail: '', price: 1000, quantity: 2, selected: true })
    store.addItem({ id: 2, product_id: 2, product_name: '商品2', thumbnail: '', price: 500, quantity: 1, selected: false })
    expect(store.selectedTotalPrice).toBe(2000)
  })
})
