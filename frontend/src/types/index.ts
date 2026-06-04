/**
 * 怡安印刷商城 - 全局类型定义
 */

/** 通用API响应结构 */
export interface ApiResponse<T = unknown> {
  code: number
  message: string
  data: T
}

/** 分页请求参数 */
export interface PaginationParams {
  page: number
  per_page: number
}

/** 分页响应结构 */
export interface PaginationData<T> {
  data: T[]
  total: number
  current_page: number
  per_page: number
  last_page: number
}

/** 用户 */
export interface User {
  id: number
  name: string
  email: string
  phone: string
  avatar?: string
  created_at: string
}

/** 商品 */
export interface Product {
  id: number
  name: string
  description: string
  price: number
  original_price?: number
  cover_image: string
  images?: string[]
  category_id: number
  category_name?: string
  stock: number
  sold_count: number
  rating?: number
  specs?: ProductSpec[]
  created_at: string
}

/** 商品规格 */
export interface ProductSpec {
  id: number
  name: string
  values: string[]
}

/** 购物车项 */
export interface CartItem {
  id: number
  product_id: number
  product_name: string
  product_image: string
  spec_info?: string
  price: number
  quantity: number
  selected: boolean
}

/** 订单 */
export interface Order {
  id: number
  order_no: string
  status: OrderStatus
  total_amount: number
  items: OrderItem[]
  created_at: string
}

/** 订单状态（与后端枚举对应） */
export enum OrderStatus {
  PENDING_PAYMENT = 11,
  PAID = 12,
  IN_PRODUCTION = 13,
  SHIPPED = 20,
  PENDING_RECEIVE = 54,
  RECEIVED = 55,
  COMPLETED = 60,
  CANCELLED = 61,
}

export const ORDER_STATUS_MAP: Record<number, { label: string; type: string }> = {
  11: { label: '待付款', type: 'warning' },
  12: { label: '已付款', type: 'primary' },
  13: { label: '生产中', type: 'info' },
  20: { label: '已发货', type: 'success' },
  54: { label: '待收货', type: 'success' },
  55: { label: '已收货', type: 'success' },
  60: { label: '已完成', type: 'info' },
  61: { label: '已取消', type: 'info' },
}

/** 订单项 */
export interface OrderItem {
  id: number
  product_id: number
  product_name: string
  product_image: string
  price: number
  quantity: number
  spec_info?: string
}

/** 商品分类 */
export interface Category {
  id: number
  name: string
  icon?: string
  children?: Category[]
}
