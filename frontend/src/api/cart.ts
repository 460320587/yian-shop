import { get, post, put, del } from '@/utils/request'

export interface CartItem {
  id: number
  product_id: number
  product_name: string
  thumbnail: string
  quantity: number
  unit_price: number
  subtotal: number
  selected: boolean
  spec_info: string | null
}

export interface CartSummary {
  total_count: number
  selected_count: number
  selected_subtotal: number
}

export interface CartData {
  items: CartItem[]
  summary: CartSummary
}

/** 获取购物车 */
export function getCart() {
  return get<CartData>('/cart')
}

/** 添加商品到购物车 */
export function addToCart(data: { product_id: number; quantity: number }) {
  return post<{ id: number; product_id: number; quantity: number; unit_price: number; subtotal: number; selected: boolean }>('/cart/items', data)
}

/** 更新购物车商品 */
export function updateCartItem(id: number, data: { quantity?: number; selected?: boolean }) {
  return put<{ id: number; quantity: number; unit_price: number; subtotal: number; selected: boolean }>(`/cart/items/${id}`, data)
}

/** 删除购物车商品 */
export function removeCartItem(id: number) {
  return del<null>(`/cart/items/${id}`)
}

/** 清空购物车 */
export function clearCart() {
  return del<null>('/cart')
}
