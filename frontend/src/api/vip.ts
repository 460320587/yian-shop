import request from '@/utils/request'

export interface VipInfo {
  current_grow_value: number
  current_level: number
  current_level_name: string
  current_discount: number
  next_level: number | null
  next_level_name: string | null
  next_level_points: number | null
  progress_percent: number
}

export interface VipLevel {
  level: number
  name: string
  min_points: number
  discount: number
  icon: string
  privileges: Record<string, any>
}

export function getVipInfo() {
  return request.get<VipInfo>('/vip/info')
}

export function getVipLevels() {
  return request.get<VipLevel[]>('/vip/levels')
}
