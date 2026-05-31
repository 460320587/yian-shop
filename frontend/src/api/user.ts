/**
 * 用户相关 API 接口
 * （骨架阶段预留，后续对接真实接口）
 */
import { get, post } from '@/utils/request'
import type { ApiResponse, User } from '@/types'

/** 登录 */
export function login(data: { phone: string; password: string }) {
  return post<ApiResponse<{ token: string; user: User }>>('/auth/login', data)
}

/** 注册 */
export function register(data: { phone: string; password: string; password_confirmation: string }) {
  return post<ApiResponse<{ token: string; user: User }>>('/auth/register', data)
}

/** 获取当前用户信息 */
export function getUserInfo() {
  return get<ApiResponse<User>>('/user')
}

/** 退出登录 */
export function logout() {
  return post<ApiResponse<null>>('/auth/logout')
}
