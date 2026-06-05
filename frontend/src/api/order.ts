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

export interface CreateOrderData {
  address_id: number
  items?: Array<{ product_id: number; quantity: number }>
  cart_item_ids?: number[]
  coupon_code?: string | null
  invoice_type?: number
  invoice_title?: string
  invoice_tax_no?: string
  invoice_email?: string
  remark?: string
  use_balance?: number
  delivery_date?: string
}

export interface CreateOrderResponse {
  order_no: string
  total_amount: number
  status: number
  discount_sum?: number
  items?: any[]
}

export function createOrder(data: CreateOrderData) {
  return post<CreateOrderResponse>('/orders', data)
}

export function cancelOrder(orderNo: string) {
  return put(`/orders/${orderNo}/cancel`)
}

export function reorder(orderId: number) {
  return post(`/orders/${orderId}/reorder`)
}
