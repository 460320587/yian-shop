import { get } from '@/utils/request'

export interface HomeData {
  banners: Array<{ id: number; title: string; image: string; image_mobile: string; link_type: number; link_target: string; sort: number }>
  announcements: Array<{ id: number; title: string; type: number; is_popup: number }>
  hot_products: Array<{ id: number; name: string; thumbnail: string | null; min_price: number | null; max_price: number | null; sales_count: number; is_hot: number; is_new: number; category: { id: number; name: string } }>
  new_arrivals: Array<{ id: number; name: string; thumbnail: string | null; min_price: number | null; max_price: number | null; sales_count: number; is_hot: number; is_new: number; category: { id: number; name: string } }>
}

export interface Category {
  id: number
  name: string
  children: Category[]
}

export function getHome() {
  return get<HomeData>('/portal/home')
}

export function getCategories() {
  return get<Category[]>('/portal/categories')
}
