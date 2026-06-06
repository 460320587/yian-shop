import request from '@/utils/request'

export interface RechargeData {
  amount: number
  gateway: string
}

export interface WithdrawData {
  amount: number
}

export interface WalletTransaction {
  payment_no: string
  amount: number
  status: number
  gateway: string
}

export interface WalletBalance {
  balance: number
  customer_id: number
}

export function getWalletBalance() {
  return request.get<WalletBalance>('/wallet/balance')
}

export function recharge(data: RechargeData) {
  return request.post<WalletTransaction>('/payments/wallet/recharge', data)
}

export function withdraw(data: WithdrawData) {
  return request.post<WalletTransaction>('/payments/wallet/withdraw', data)
}
