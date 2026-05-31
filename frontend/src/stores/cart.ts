/**
 * 购物车状态管理 (Pinia)
 * - 支持本地缓存 + API 同步（骨架阶段以本地为主）
 */

import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import type { CartItem } from '@/types'

const CART_KEY = 'cart_items'

export const useCartStore = defineStore('cart', () => {
  // ========== State ==========
  const items = ref<CartItem[]>([])

  // 初始化时从 localStorage 恢复
  const cached = localStorage.getItem(CART_KEY)
  if (cached) {
    try {
      items.value = JSON.parse(cached)
    } catch {
      items.value = []
    }
  }

  // ========== Getters ==========
  /** 购物车商品总数 */
  const totalCount = computed(() =>
    items.value.reduce((sum, item) => sum + item.quantity, 0)
  )

  /** 已选商品总价（分） */
  const selectedTotalPrice = computed(() =>
    items.value
      .filter((item) => item.selected)
      .reduce((sum, item) => sum + item.price * item.quantity, 0)
  )

  /** 是否全选 */
  const isAllSelected = computed(
    () => items.value.length > 0 && items.value.every((item) => item.selected)
  )

  /** 已选数量 */
  const selectedCount = computed(
    () => items.value.filter((item) => item.selected).length
  )

  // ========== Actions ==========
  /** 持久化到 localStorage */
  function persist() {
    localStorage.setItem(CART_KEY, JSON.stringify(items.value))
  }

  /** 添加商品到购物车 */
  function addItem(item: CartItem) {
    const existing = items.value.find(
      (i) => i.product_id === item.product_id && i.spec_info === item.spec_info
    )
    if (existing) {
      existing.quantity += item.quantity
    } else {
      items.value.push(item)
    }
    persist()
  }

  /** 修改数量 */
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

  /** 切换选中状态 */
  function toggleSelect(itemId: number) {
    const target = items.value.find((i) => i.id === itemId)
    if (target) {
      target.selected = !target.selected
      persist()
    }
  }

  /** 全选 / 取消全选 */
  function toggleSelectAll(selected: boolean) {
    items.value.forEach((item) => (item.selected = selected))
    persist()
  }

  /** 删除商品 */
  function removeItem(itemId: number) {
    items.value = items.value.filter((i) => i.id !== itemId)
    persist()
  }

  /** 清空购物车 */
  function clearCart() {
    items.value = []
    persist()
  }

  return {
    items,
    totalCount,
    selectedTotalPrice,
    isAllSelected,
    selectedCount,
    addItem,
    updateQuantity,
    toggleSelect,
    toggleSelectAll,
    removeItem,
    clearCart,
  }
})
