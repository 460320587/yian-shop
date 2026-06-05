import { get, post } from '@/utils/request'

export interface EnterpriseAuthInfo {
  id: number
  customer_id: number
  company_name: string
  credit_code: string
  legal_person: string
  legal_person_id_card: string
  business_license_img: string
  contact_name: string
  contact_phone: string
  register_address: string | null
  office_address: string | null
  valid_date: string | null
  auth_status: number
  audit_remark: string | null
  created_at: string
  updated_at: string
}

export interface AuthStatusResponse {
  auth_status: number
  auth_status_name: string
}

export interface EnterpriseApplyData {
  company_name: string
  credit_code: string
  legal_person: string
  legal_person_id_card: string
  business_license_img: string
  contact_name: string
  contact_phone: string
  register_address?: string
  office_address?: string
  valid_date?: string
}

export function getAuthStatus() {
  return get<AuthStatusResponse>('/enterprise/auth-status')
}

export function applyEnterpriseAuth(data: EnterpriseApplyData) {
  return post<EnterpriseAuthInfo>('/enterprise/apply', data)
}

export function getEnterpriseInfo() {
  return get<EnterpriseAuthInfo>('/enterprise/info')
}
