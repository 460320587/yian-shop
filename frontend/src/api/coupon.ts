import { get, post } from '@/utils/request'

export interface Coupon {
  id: number
  name: string
  description: string | null
  type: number
  value: number
  min_amount: number
  max_discount: number | null
  start_at: string
  end_at: string
  total_count: number
  claimed_count: number
  per_customer_limit: number
  status: number
}

export interface CustomerCoupon {
  id: number
  code: string
  coupon_id: number
  status: number
  claimed_at: string
  used_at: string | null
  expired_at: string | null
  coupon: Coupon
}

export function getCoupons() {
  return get<{ data: Coupon[]; total: number; current_page: number; last_page: number }>('/coupons')
}

export function claimCoupon(id: number) {
  return post<{ id: number; code: string; coupon: Coupon; status: number; expired_at: string }>(`/coupons/${id}/claim`)
}

export function getMyCoupons(status?: number) {
  return get<{ data: CustomerCoupon[]; total: number; current_page: number; last_page: number }>('/my-coupons', status !== undefined ? { status } : undefined)
}
