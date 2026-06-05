import request from '@/utils/request'

export interface AfterSale {
  id: number
  order_no: string
  type: number
  reason: string
  description?: string
  status: number
  images?: string[]
  created_at: string
}

export interface CreateAfterSaleData {
  order_no: string
  type: number
  reason: string
  description?: string
  images?: string[]
  items: { order_item_id: number; quantity: number }[]
}

export function getAfterSales(params?: { page?: number; per_page?: number; status?: number }) {
  return request.get<{ data: AfterSale[]; total: number; current_page: number; last_page: number }>('/after-sales', { params })
}

export function createAfterSale(data: CreateAfterSaleData) {
  return request.post('/after-sales', data)
}

export function cancelAfterSale(id: number) {
  return request.post(`/after-sales/${id}/cancel`)
}
