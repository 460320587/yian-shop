import request from '@/utils/request'

export interface CreateAfterSaleData {
  order_no: string
  type: number
  reason: string
  description?: string
  images?: string[]
  items: { order_item_id: number; quantity: number }[]
}

export function createAfterSale(data: CreateAfterSaleData) {
  return request.post('/after-sales', data)
}
