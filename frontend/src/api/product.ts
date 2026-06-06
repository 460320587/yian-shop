import { get, post } from '@/utils/request'

export interface Product {
  id: number
  name: string
  thumbnail: string | null
  price_min: number | null
  price_max: number | null
  sales_count: number
  category?: { id: number; name: string }
}

export interface PricingParams {
  base_price: number
  unit: string
  price_tiers: Array<{ min_qty: number; price: number }>
  paper_options: Array<{ id: number; name: string; price_factor: number }>
  color_options: Array<{ id: number; name: string; price_factor: number }>
  process_options: Array<{ id: number; name: string; price: number; unit: string }>
}

export interface ProductDetail extends Product {
  description: string
  pricing_params: PricingParams | null
}

export interface PriceResult {
  product_id: number
  quantity: number
  unit_price: number
  breakdown: {
    base_amount: number
    process_amount: number
    total_amount: number
  }
}

export function getProducts(params?: { page?: number; category_id?: number; keyword?: string; min_price?: number; max_price?: number; sort?: string }) {
  return get<{ data: Product[]; total: number; current_page: number; last_page: number }>('/products', params)
}

export function getProductDetail(id: number) {
  return get<ProductDetail>(`/products/${id}`)
}

export function calculatePrice(id: number, data: {
  quantity: number
  paper_id: number
  color_id: number
  process_ids?: number[]
}) {
  return post<PriceResult>(`/products/${id}/price`, data)
}
