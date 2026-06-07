import request from '@/utils/request'

export interface RechargeData {
  amount: number
  gateway: string
}

export interface WithdrawData {
  amount: number
  pay_password: string
}

export interface WalletTransaction {
  id: number
  customer_id: number
  type: number
  amount: number
  balance_before: number
  balance_after: number
  order_no: string | null
  payment_no: string | null
  remark: string | null
  status: number
  created_at: string
}

export interface WalletBalance {
  balance: number
  customer_id: number
}

export interface PaymentCredential {
  type: string
  qrcode_url?: string
  redirect_url?: string
}

export interface RechargePaymentData {
  payment_id: number
  payment_no: string
  amount: number
  gateway: string
  status: number
  credential?: PaymentCredential
  expire_at: string | null
}

export function getWalletBalance() {
  return request.get<WalletBalance>('/wallet/balance')
}

export function recharge(data: RechargeData) {
  return request.post<RechargePaymentData>('/payments/wallet/recharge', data)
}

export function withdraw(data: WithdrawData) {
  return request.post<WalletTransaction>('/payments/wallet/withdraw', data)
}

export function getWalletTransactions(params?: { type?: number; page?: number; per_page?: number }) {
  return request.get<{ list: WalletTransaction[]; total: number }>('/wallet/transactions', { params })
}
