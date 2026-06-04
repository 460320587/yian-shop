import { get, post } from '@/utils/request'

export interface AdminLoginData {
  username: string
  password: string
}

export interface AdminInfo {
  id: number
  username: string
  real_name: string | null
  role: string
}

export function adminLogin(data: AdminLoginData) {
  return post<{ admin: AdminInfo; token: string }>('/auth/login', data)
}

export function adminLogout() {
  return post('/auth/logout')
}

export function adminProfile() {
  return get<{ id: number; username: string; real_name: string | null; phone: string | null; email: string | null; role: string; last_login_at: string | null }>('/auth/profile')
}
