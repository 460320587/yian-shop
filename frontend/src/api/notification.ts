import { get, put, del } from '@/utils/request'

export interface NotificationItem {
  id: number
  type: string
  title: string
  content: string
  is_read: number
  action_url: string | null
  action_text: string | null
  created_at: string
}

export interface NotificationListResponse {
  data: NotificationItem[]
  total: number
  current_page: number
  last_page: number
}

export interface UnreadCountResponse {
  unread_count: number
}

export function getNotifications(params?: { page?: number; per_page?: number; is_read?: number }) {
  return get<NotificationListResponse>('/notifications', params)
}

export function markNotificationRead(id: number) {
  return put('/notifications/' + id + '/read')
}

export function markAllNotificationsRead() {
  return put('/notifications/read-all')
}

export function getUnreadCount() {
  return get<UnreadCountResponse>('/notifications/unread-count')
}

export function deleteNotification(id: number) {
  return del('/notifications/' + id)
}
