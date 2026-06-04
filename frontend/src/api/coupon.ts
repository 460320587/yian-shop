import { get, post } from '@/utils/request'
import type { PaginationData } from '@/types'

export interface Coupon {
  id: number
  code: string
  name: string
  type: number
  value: number
  min_amount: number
  start_at: string
  end_at: string
  claimed_count: number
  used_count: number
  status: number
}

export interface CustomerCoupon {
  id: number
  code: string
  status: number
  claimed_at: string
  used_at: string | null
  expired_at: string | null
  coupon: Coupon
}

/** 获取可领券列表 */
export function getCoupons(params?: { page?: number; per_page?: number }) {
  return get<PaginationData<Coupon>>('/coupons', params as Record<string, unknown>)
}

/** 领取优惠券 */
export function claimCoupon(id: number) {
  return post<{ id: number; code: string; status: number }>(`/coupons/${id}/claim`)
}

/** 获取我的优惠券 */
export function getMyCoupons(params?: { status?: number; page?: number; per_page?: number }) {
  return get<PaginationData<CustomerCoupon>>('/my-coupons', params as Record<string, unknown>)
}
