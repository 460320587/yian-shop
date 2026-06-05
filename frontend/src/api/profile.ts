import request from '@/utils/request'

export interface ProfileData {
  nickname?: string
  avatar?: string
  link_person?: string
  qq?: string
}

export interface UserProfile {
  id: number
  phone: string
  nickname: string | null
  avatar: string | null
  type: number
  auth_status: number
  vip_level: number
  balance: number
  link_person?: string | null
  qq?: string | null
}

export function updateProfile(data: ProfileData) {
  return request.put<UserProfile>('/user/profile', data)
}
