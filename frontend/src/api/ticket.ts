import { get, post, put } from '@/utils/request'

export interface Ticket {
  id: number
  ticket_no: string
  type: number
  status: number
  priority: number
  title: string
  content: string
  expected_resolution: string | null
  remark: string | null
  images: string[]
  order: { id: number; order_no: string } | null
  processed_at: string | null
  completed_at: string | null
  created_at: string
}

export interface CreateTicketData {
  order_id?: number
  type: number
  title: string
  content: string
  images?: string[]
  expected_resolution?: string
}

export function getTickets(params?: { status?: number; type?: number; page?: number; per_page?: number }) {
  return get<{ data: Ticket[]; total: number; current_page: number; last_page: number }>('/tickets', params)
}

export function getTicketDetail(id: number) {
  return get<Ticket>(`/tickets/${id}`)
}

export function createTicket(data: CreateTicketData) {
  return post<Ticket>('/tickets', data)
}

export function cancelTicket(id: number) {
  return put(`/tickets/${id}/cancel`)
}
