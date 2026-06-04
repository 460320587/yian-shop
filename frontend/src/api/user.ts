import { get, post } from '@/utils/request'

export interface UserInfo {
  id: number
  phone: string
  nickname: string | null
  avatar: string | null
  type: number
  auth_status: number
  vip_level: number
  balance: number
}

export interface LoginResult {
  token: string
  user: {
    id: number
    phone: string
    nickname: string | null
  }
}

/** 登录 */
export function login(data: { phone: string; password: string }) {
  return post<LoginResult>('/auth/login', data)
}

/** 注册 */
export function register(data: { phone: string; password: string; password_confirmation: string; nickname?: string }) {
  return post<LoginResult>('/auth/register', data)
}

/** 退出登录 */
export function logout() {
  return post<null>('/auth/logout')
}

/** 获取当前用户信息 */
export function getUserInfo() {
  return get<UserInfo>('/user/profile')
}
