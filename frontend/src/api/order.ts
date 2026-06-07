import request, { get, post, put, del } from '@/utils/request'

export interface Order {
  id: number
  order_no: string
  status: number
  customer_status: string
  total_amount: number
  created_at: string
  items?: any[]
}

export interface ProductionSchedule {
  id: number
  order_id: number
  schedule_date: string
  process_name: string
  status: number
  progress: number
  priority: number
  estimated_hours: number | null
  actual_hours: number | null
  created_at: string
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

export interface OrderPricingResponse {
  freight_amount: number
  goods_amount: number
  free_threshold: number | null
  carrier_name: string | null
  calculation_type: number | null
}

export function createOrder(data: CreateOrderData) {
  return post<CreateOrderResponse>('/orders', data)
}

export function calculateOrderPricing(data: { address_id: number; items: Array<{ product_id: number; quantity: number }> }) {
  return post<OrderPricingResponse>('/orders/pricing', data)
}

export function cancelOrder(orderNo: string) {
  return put(`/orders/${orderNo}/cancel`)
}

export function reorder(orderId: number) {
  return post(`/orders/${orderId}/reorder`)
}

export interface OrderFile {
  id: number
  file_name: string
  file_url: string
  thumb_url: string | null
  file_size: number
  file_type: string
  page_count: number
  version: number
  created_at: string
}

export function getOrderProductionSchedule(orderId: number) {
  return get<{ data: ProductionSchedule[] }>(`/orders/${orderId}/production-schedule`)
}

export function getOrderFiles(orderId: number) {
  return get<{ data: OrderFile[] }>(`/orders/${orderId}/files`)
}

export interface InkCoverageCheck {
  id: number
  order_id: number
  file_id: number
  file_name: string | null
  check_type: number
  ink_type: string
  coverage_c: number
  coverage_m: number
  coverage_y: number
  coverage_k: number
  total_coverage: number
  check_result: number
  check_report: Record<string, any> | null
  checked_at: string | null
}

export function getOrderInkChecks(orderId: number) {
  return get<{ data: InkCoverageCheck[] }>(`/orders/${orderId}/ink-coverage-checks`)
}

export interface OrderStatusLog {
  id: number
  from_status: number
  to_status: number
  remark: string | null
  operator_type: string
  created_at: string
}

export function getOrderStatusLogs(orderId: number) {
  return get<{ data: OrderStatusLog[] }>(`/orders/${orderId}/status-logs`)
}

export function deleteOrderFile(orderId: number, fileId: number) {
  return del(`/orders/${orderId}/files/${fileId}`)
}

export function uploadOrderFile(orderId: number, data: FormData) {
  return request.post(`/orders/${orderId}/files`, data, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}
