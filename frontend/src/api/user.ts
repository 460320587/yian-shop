import { get, post } from '@/utils/request'

export interface LoginData {
  phone: string
  password: string
}

export interface RegisterData {
  phone: string
  password: string
  password_confirmation: string
}

export interface User {
  id: number
  phone: string
  nickname: string | null
  avatar: string | null
  type: number
  auth_status: number
  vip_level: number
  balance: number
  points?: number
  link_person?: string | null
  qq?: string | null
}

export function login(data: LoginData) {
  return post<{ token: string; user: User }>('/auth/login', data)
}

export function register(data: RegisterData) {
  return post<{ token: string; user: User }>('/auth/register', data)
}

export function getUserInfo() {
  return get<User>('/user/profile')
}

export function logout() {
  return post('/auth/logout')
}

export function forgotPassword(data: { phone: string }) {
  return post<{ token: string }>('/auth/forgot-password', data)
}

export function resetPassword(data: { phone: string; token: string; password: string; password_confirmation: string }) {
  return post('/auth/reset-password', data)
}

export function sendSmsCode(data: { phone: string; captcha_key: string; captcha_code: string }) {
  return post('/auth/sms-code', data)
}

export function loginBySms(data: { phone: string; sms_code: string }) {
  return post<{ token: string; user: User }>('/auth/login-sms', data)
}

export function checkPhone(phone: string) {
  return get<{ available: boolean }>('/auth/check-phone', { phone })
}

export interface UserDashboardOrder {
  id: number
  order_no: string
  status: number
  customer_status: string
  total_amount: number
  created_at: string
}

export interface UserDashboard {
  order_status_counts: {
    pending_payment: number
    in_progress: number
    pending_delivery: number
    pending_receive: number
    pending_review: number
    completed: number
  }
  unread_notification_count: number
  available_coupon_count: number
  recent_orders: UserDashboardOrder[]
}

export function getUserDashboard() {
  return get<UserDashboard>('/user/dashboard')
}
