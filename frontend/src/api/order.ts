import { get, post, put } from '@/utils/request'
import type { PaginationData } from '@/types'

export interface OrderItem {
  id?: number
  product_id: number
  product_name: string
  quantity: number
  unit_price: number
  subtotal: number
}

export interface Order {
  id: number
  order_no: string
  status: number
  customer_status: string
  total_amount: number
  discount_sum?: number
  created_at: string
  items: OrderItem[]
}

/** 创建订单 */
export function createOrder(data: { address_id?: number; coupon_code?: string }) {
  return post<Order>('/orders', data)
}

/** 获取订单列表 */
export function getOrders(params?: { status?: number; page?: number; per_page?: number }) {
  return get<PaginationData<Order>>('/orders', params as Record<string, unknown>)
}

/** 获取订单详情 */
export function getOrderDetail(id: number) {
  return get<Order>(`/orders/${id}`)
}

/** 取消订单 */
export function cancelOrder(id: number) {
  return put<null>(`/orders/${id}/cancel`)
}
