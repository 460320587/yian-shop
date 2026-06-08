import { get, post } from '@/utils/request'

export interface ReviewItem {
  id: number
  customer_id: number
  product_id: number
  order_id: number | null
  rating: number
  content: string
  images: string[] | null
  reply: string | null
  reply_at: string | null
  is_show: boolean
  created_at: string
  customer?: { id: number; nickname: string | null }
  product?: { id: number; name: string; thumbnail: string | null }
}

export interface ReviewListResponse {
  data: ReviewItem[]
  total: number
  current_page: number
  last_page: number
}

export function getProductReviews(productId: number, params?: { page?: number; per_page?: number }) {
  return get<ReviewListResponse>('/products/' + productId + '/reviews', params)
}

export function submitReview(orderId: number, data: {
  product_id: number
  rating: number
  content: string
  images?: string[]
}) {
  return post<ReviewItem>('/orders/' + orderId + '/reviews', data)
}

export function getMyReviews(params?: { page?: number; per_page?: number }) {
  return get<ReviewListResponse>('/my-reviews', params)
}

export function uploadReviewImages(formData: FormData) {
  return post<{ urls: string[] }>('/upload/review-images', formData)
}
