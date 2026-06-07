import { get, post } from '@/utils/request'

export interface SampleOrder {
  id: number
  order_no: string
  product_id: number
  quantity: number
  unit_price: number
  discount_amount: number
  total_amount: number
  status: number
  remark: string | null
  address_snapshot: Record<string, any> | null
  product: { id: number; name: string } | null
  paid_at: string | null
  shipped_at: string | null
  completed_at: string | null
  cancelled_at: string | null
  created_at: string
}

export interface CreateSampleOrderData {
  product_id: number
  quantity: number
  address_snapshot?: Record<string, any>
  remark?: string
}

export function getSampleOrders(params?: { status?: number; page?: number; per_page?: number }) {
  return get<{ data: SampleOrder[]; total: number; current_page: number; last_page: number }>('/samples', params)
}

export function getSampleOrderDetail(id: number) {
  return get<SampleOrder>(`/samples/orders/${id}`)
}

export function createSampleOrder(data: CreateSampleOrderData) {
  return post<SampleOrder>('/samples/orders', data)
}
