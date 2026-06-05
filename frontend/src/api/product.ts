import { get } from '@/utils/request'

export interface Product {
  id: number
  name: string
  thumbnail: string | null
  price_min: number | null
  price_max: number | null
  sales_count: number
  category?: { id: number; name: string }
}

export interface ProductDetail extends Product {
  description: string
  specs: any[]
  prices: any[]
}

export function getProducts(params?: { page?: number; category_id?: number; keyword?: string; min_price?: number; max_price?: number; sort?: string }) {
  return get<{ data: Product[]; total: number; current_page: number; last_page: number }>('/products', params)
}

export function getProductDetail(id: number) {
  return get<ProductDetail>(`/products/${id}`)
}
