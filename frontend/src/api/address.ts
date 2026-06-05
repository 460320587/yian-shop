import { get, post, put, del } from '@/utils/request'

export interface Address {
  id: number
  contact_name: string
  contact_phone: string
  province_name: string
  city_name: string
  county_name: string
  detail_address: string
  full_address: string
  zip_code: string | null
  is_default: boolean
  tag: string | null
  created_at: string
}

export interface AddressListResponse {
  data: Address[]
  total: number
  current_page: number
  last_page: number
}

export interface AddressFormData {
  contact_name: string
  contact_phone: string
  province_name: string
  city_name: string
  county_name: string
  detail_address: string
  zip_code?: string
  is_default?: boolean
  tag?: string
}

export function getAddresses(params?: { page?: number; per_page?: number }) {
  return get<AddressListResponse>('/addresses', params)
}

export function createAddress(data: AddressFormData) {
  return post<Address>('/addresses', data)
}

export function updateAddress(id: number, data: AddressFormData) {
  return put<Address>('/addresses/' + id, data)
}

export function deleteAddress(id: number) {
  return del('/addresses/' + id)
}

export function setDefaultAddress(id: number) {
  return put<Address>('/addresses/' + id + '/default')
}
