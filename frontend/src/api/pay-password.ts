import { get, post, put } from '@/utils/request'

export interface PayPasswordStatus {
  has_pay_password: boolean
  locked: boolean
  locked_until: string | null
}

export interface SetPayPasswordData {
  pay_password: string
  pay_password_confirmation: string
}

export interface UpdatePayPasswordData {
  old_pay_password: string
  pay_password: string
  pay_password_confirmation: string
}

export function getPayPasswordStatus() {
  return get<PayPasswordStatus>('/pay-password/status')
}

export function setPayPassword(data: SetPayPasswordData) {
  return post<{ has_pay_password: boolean }>('/pay-password', data)
}

export function updatePayPassword(data: UpdatePayPasswordData) {
  return put<{ has_pay_password: boolean }>('/pay-password', data)
}
