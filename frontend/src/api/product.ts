/**
 * 商品相关 API 接口
 * （骨架阶段预留，后续对接真实接口）
 */
import { get } from '@/utils/request'
import type { ApiResponse, Product, PaginationData, Category } from '@/types'

/** 获取商品分类列表 */
export function getCategories() {
  return get<ApiResponse<Category[]>>('/categories')
}

/** 获取商品列表 */
export function getProducts(params?: Record<string, unknown>) {
  return get<ApiResponse<PaginationData<Product>>>('/products', params)
}

/** 获取商品详情 */
export function getProductDetail(id: number) {
  return get<ApiResponse<Product>>(`/products/${id}`)
}
