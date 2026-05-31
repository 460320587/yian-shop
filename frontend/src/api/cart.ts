/**
 * 购物车相关 API 接口
 * （骨架阶段预留，后续对接真实接口）
 */
import { get, post, put, del } from '@/utils/request'
import type { ApiResponse, CartItem } from '@/types'

/** 获取购物车列表 */
export function getCartItems() {
  return get<ApiResponse<CartItem[]>>('/cart')
}

/** 添加商品到购物车 */
export function addToCart(data: { product_id: number; quantity: number; spec_info?: string }) {
  return post<ApiResponse<CartItem>>('/cart', data)
}

/** 更新购物车商品数量 */
export function updateCartItem(id: number, data: { quantity: number }) {
  return put<ApiResponse<CartItem>>(`/cart/${id}`, data)
}

/** 删除购物车商品 */
export function removeCartItem(id: number) {
  return del<ApiResponse<null>>(`/cart/${id}`)
}

/** 清空购物车 */
export function clearCart() {
  return del<ApiResponse<null>>('/cart')
}
