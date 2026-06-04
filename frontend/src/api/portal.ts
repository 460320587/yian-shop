import { get } from '@/utils/request'
import type { ApiResponse } from '@/types'

export interface Banner {
  id: number
  title: string
  image: string
  image_mobile: string
  link_type: number
  link_target: string
  sort: number
}

export interface Announcement {
  id: number
  title: string
  type: number
  is_popup: boolean
}

export interface HomeProduct {
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

export interface HomeData {
  banners: Banner[]
  announcements: Announcement[]
  hot_products: HomeProduct[]
  new_arrivals: HomeProduct[]
}

/** 获取首页聚合数据 */
export function getHomeData() {
  return get<HomeData>('/portal/home')
}

/** 获取分类树 */
export function getCategories() {
  return get<{ id: number; name: string; children?: { id: number; name: string }[] }[]>('/categories')
}
