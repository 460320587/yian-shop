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
}

export interface CartData {
  items: CartItem[]
  summary: { total_count: number; selected_count: number; selected_subtotal: number }
}

export function getCart() {
  return get<CartData>('/cart')
}

export function addToCart(data: { product_id: number; quantity: number; spec_id: number | null }) {
  return post<CartItem>('/cart', data)
}

export function updateCartItem(id: number, data: { quantity?: number; selected?: boolean }) {
  return put(`/cart/items/${id}`, data)
}

export function removeCartItem(id: number) {
  return del(`/cart/items/${id}`)
}

export function clearCart() {
  return post('/cart/clear')
}
