import { get } from '@/utils/request'
import type { PaginationData } from '@/types'

export interface ProductItem {
  id: number
  name: string
  thumbnail: string
  min_price: number
  max_price: number
  sales_count: number
  is_hot: number
  is_new: number
  category: { id: number; name: string }
}

export interface ProductDetail {
  id: number
  name: string
  code: string
  cover_image: string
  price_min: number
  price_max: number
  status: number
  sort: number
  category: { id: number; name: string } | null
}

/** 获取商品列表 */
export function getProducts(params?: {
  category_id?: number
  keyword?: string
  sort?: string
  per_page?: number
  page?: number
}) {
  return get<PaginationData<ProductItem>>('/products', params as Record<string, unknown>)
}

/** 获取商品详情 */
export function getProductDetail(id: number) {
  return get<ProductDetail>(`/products/${id}`)
}
