import { get, post } from '@/utils/request'

export interface PaymentCredential {
  type: string
  qrcode_url?: string
  redirect_url?: string
}

export interface PaymentData {
  payment_id: number
  payment_no: string
  order_no: string
  amount: number
  gateway: string
  status: number
  credential?: PaymentCredential
  paid_at: string | null
  expire_at: string | null
}

export interface PaymentStatusResponse {
  payment_id: number
  payment_no: string
  order_no: string
  amount: number
  gateway: string
  status: number
  credential: PaymentCredential | null
  paid_at: string | null
  expire_at: string | null
}

export function createPayment(data: { order_no: string; gateway: string; pay_password?: string }) {
  return post<PaymentData>('/payments/create', data)
}

export function getPaymentStatus(id: number) {
  return get<PaymentStatusResponse>('/payments/' + id + '/status')
}

export function mockPaymentCallback(id: number, data: { status: 'success' | 'failed' }) {
  return post('/payments/' + id + '/mock-callback', data)
}

export function walletRecharge(data: { amount: number; gateway: string }) {
  return post<PaymentData>('/payments/wallet/recharge', data)
}

export function walletWithdraw(data: { amount: number }) {
  return post<PaymentData>('/payments/wallet/withdraw', data)
}
