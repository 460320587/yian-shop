import { ref, computed } from 'vue'
import { defineStore } from 'pinia'

export interface CartItem {
  id: number
  product_id: number
  product_name: string
  thumbnail: string
  price: number
  quantity: number
  selected: boolean
  spec_info?: string | null
}

const CART_KEY = 'cart_items'

export const useCartStore = defineStore('cart', () => {
  const items = ref<CartItem[]>([])

  const cached = localStorage.getItem(CART_KEY)
  if (cached) {
    try { items.value = JSON.parse(cached) } catch { items.value = [] }
  }

  const totalCount = computed(() => items.value.reduce((sum, item) => sum + item.quantity, 0))
  const selectedTotalPrice = computed(() => items.value.filter((item) => item.selected).reduce((sum, item) => sum + item.price * item.quantity, 0))
  const isAllSelected = computed(() => items.value.length > 0 && items.value.every((item) => item.selected))
  const selectedCount = computed(() => items.value.filter((item) => item.selected).length)

  function persist() {
    localStorage.setItem(CART_KEY, JSON.stringify(items.value))
  }

  function addItem(item: CartItem) {
    const existing = items.value.find((i) => i.product_id === item.product_id && i.spec_info === item.spec_info)
    if (existing) {
      existing.quantity += item.quantity
    } else {
      items.value.push(item)
    }
    persist()
  }

  function updateQuantity(itemId: number, quantity: number) {
    const target = items.value.find((i) => i.id === itemId)
    if (target) {
      if (quantity <= 0) {
        removeItem(itemId)
      } else {
        target.quantity = quantity
        persist()
      }
    }
  }

  function toggleSelect(itemId: number) {
    const target = items.value.find((i) => i.id === itemId)
    if (target) {
      target.selected = !target.selected
      persist()
    }
  }

  function toggleSelectAll(selected: boolean) {
    items.value.forEach((item) => (item.selected = selected))
    persist()
  }

  function removeItem(itemId: number) {
    items.value = items.value.filter((i) => i.id !== itemId)
    persist()
  }

  function clearCart() {
    items.value = []
    persist()
  }

  return { items, totalCount, selectedTotalPrice, isAllSelected, selectedCount, addItem, updateQuantity, toggleSelect, toggleSelectAll, removeItem, clearCart }
})
