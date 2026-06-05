import request from '@/utils/request'

export interface LogisticsTrack {
  time: string
  location: string
  description: string
}

export interface LogisticsData {
  carrier_name: string
  tracking_no: string
  status: number
  shipped_at: string
  delivered_at: string | null
  tracks: LogisticsTrack[]
}

export function getLogisticsTracks(orderId: number) {
  return request.get<LogisticsData>(`/logistics/${orderId}/tracks`)
}
