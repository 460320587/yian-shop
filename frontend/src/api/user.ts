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
}

export function login(data: LoginData) {
  return post<{ token: string; user: User }>('/auth/login', data)
}

export function register(data: RegisterData) {
  return post<{ token: string; user: User }>('/auth/register', data)
}

export function getUserInfo() {
  return get<User>('/auth/me')
}

export function logout() {
  return post('/auth/logout')
}
