import { get, post, del } from '@/utils/request'

export interface FavoriteProduct {
  id: number
  name: string
  thumbnail: string | null
  price_min: number | null
  price_max: number | null
  category?: { id: number; name: string }
}

export interface FavoriteItem {
  id: number
  customer_id: number
  product_id: number
  remark: string | null
  status: number
  product: FavoriteProduct
  created_at: string
}

export interface FavoriteListResponse {
  data: FavoriteItem[]
  total: number
  current_page: number
  last_page: number
}

export function getFavorites(params?: { page?: number; per_page?: number }) {
  return get<FavoriteListResponse>('/favorites', params)
}

export function addFavorite(data: { product_id: number; remark?: string }) {
  return post<FavoriteItem>('/favorites', data)
}

export function removeFavorite(id: number) {
  return del('/favorites/' + id)
}
