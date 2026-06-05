import { get, post, put } from '@/utils/request'

export interface AfterSaleItem {
  id: number
  order_item_id: number
  product_name: string
  quantity: number
  unit_refund: number
}

export interface AfterSale {
  id: number
  after_sale_no: string
  order_no: string
  type: number
  status: number
  reason: string
  description: string | null
  images: string[] | null
  refund_amount: number
  approved_amount: number
  created_at: string
  items: AfterSaleItem[]
}

export interface AfterSaleListResponse {
  data: AfterSale[]
  total: number
  current_page: number
  last_page: number
}

export function getAfterSales(params?: { page?: number; per_page?: number; status?: number }) {
  return get<AfterSaleListResponse>('/after-sales', params)
}

export function getAfterSaleDetail(id: number) {
  return get<AfterSale>('/after-sales/' + id)
}

export function cancelAfterSale(id: number) {
  return put('/after-sales/' + id + '/cancel')
}

export function createAfterSale(data: {
  order_no: string
  type: number
  reason: string
  description?: string
  images?: string[]
  items: { order_item_id: number; quantity: number }[]
}) {
  return post<AfterSale>('/after-sales', data)
}
