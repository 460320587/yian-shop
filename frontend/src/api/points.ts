import { get } from '@/utils/request'

export interface PointsInfo {
  points: number
  grow_value: number
}

export interface PointsRecord {
  id: number
  customer_id: number
  type: number
  points: number
  balance_before: number
  balance_after: number
  order_no: string | null
  remark: string | null
  expired_at: string | null
  created_at: string
}

export interface PointsRecordsResponse {
  data: PointsRecord[]
  meta: {
    total: number
    per_page: number
    current_page: number
    last_page: number
  }
}

export function getPoints() {
  return get<PointsInfo>('/points')
}

export function getPointsRecords(params?: { type?: number; page?: number; per_page?: number }) {
  return get<PointsRecordsResponse>('/points/records', params)
}
