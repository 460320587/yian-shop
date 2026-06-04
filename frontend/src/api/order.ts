import { get, post, put } from '@/utils/request'

export interface Order {
  id: number
  order_no: string
  status: number
  customer_status: string
  total_amount: number
  created_at: string
  items?: any[]
}

export function getOrders(params?: { page?: number; status?: number }) {
  return get<{ data: Order[]; total: number; current_page: number; last_page: number }>('/orders', params)
}

export function getOrderDetail(orderNo: string) {
  return get<Order>(`/orders/${orderNo}`)
}

export function createOrder(data: { address_id: number; items: Array<{ product_id: number; quantity: number }>; coupon_id?: number }) {
  return post<{ order_no: string; total_amount: number; status: number }>('/orders', data)
}

export function cancelOrder(orderNo: string) {
  return put(`/orders/${orderNo}/cancel`)
}
