import request from '@/utils/request'

export interface InvoiceTitle {
  id: number
  title_type: number
  invoice_category: number
  company_name: string
  tax_number: string | null
  register_address: string | null
  register_phone: string | null
  bank_name: string | null
  bank_account: string | null
  is_default: number
}

export interface CreateInvoiceTitleData {
  title_type: number
  invoice_category: number
  company_name: string
  tax_number?: string
  register_address?: string
  register_phone?: string
  bank_name?: string
  bank_account?: string
  is_default?: number
}

export function getInvoiceTitles() {
  return request.get<InvoiceTitle[]>('/invoice-titles')
}

export function createInvoiceTitle(data: CreateInvoiceTitleData) {
  return request.post<InvoiceTitle>('/invoice-titles', data)
}

export function updateInvoiceTitle(id: number, data: Partial<CreateInvoiceTitleData>) {
  return request.put<InvoiceTitle>(`/invoice-titles/${id}`, data)
}

export function deleteInvoiceTitle(id: number) {
  return request.delete(`/invoice-titles/${id}`)
}

export interface Invoice {
  id: number
  invoice_no: string
  status: number
  type: number
  title: string
  amount: number | { amount: number; currency: string }
  created_at: string
}

export interface InvoiceListResponse {
  data: Invoice[]
  meta: {
    total: number
    current_page: number
    last_page: number
    per_page: number
  }
}

export function getInvoices(params?: { status?: number; page?: number }) {
  return request.get<InvoiceListResponse>('/invoices', params)
}

export function cancelInvoice(id: number) {
  return request.put(`/invoices/${id}/cancel`)
}
