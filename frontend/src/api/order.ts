/**
 * 订单相关 API 接口
 * （骨架阶段预留，后续对接真实接口）
 */
import { get, post } from '@/utils/request'
import type { ApiResponse, Order, PaginationData } from '@/types'

/** 创建订单 */
export function createOrder(data: { cart_item_ids: number[]; address_id: number }) {
  return post<ApiResponse<Order>>('/orders', data)
}

/** 获取订单列表 */
export function getOrders(params?: Record<string, unknown>) {
  return get<ApiResponse<PaginationData<Order>>>('/orders', params)
}

/** 获取订单详情 */
export function getOrderDetail(id: number) {
  return get<ApiResponse<Order>>(`/orders/${id}`)
}

/** 取消订单 */
export function cancelOrder(id: number) {
  return post<ApiResponse<Order>>(`/orders/${id}/cancel`)
}
