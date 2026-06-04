import { get, post, put, del } from '@/utils/request'

function getAdmin<T>(url: string, params?: Record<string, unknown>): Promise<T> {
  return get<T>(url, params) as Promise<T>
}
function postAdmin<T>(url: string, data?: unknown): Promise<T> {
  return post<T>(url, data) as Promise<T>
}
function putAdmin<T>(url: string, data?: unknown): Promise<T> {
  return put<T>(url, data) as Promise<T>
}

// ===== Auth =====
export interface AdminLoginData {
  username: string
  password: string
}
export interface AdminInfo {
  id: number
  username: string
  real_name: string | null
  role: string
}
export function adminLogin(data: AdminLoginData) {
  return postAdmin<{ admin: AdminInfo; token: string }>('/auth/login', data)
}
export function adminLogout() {
  return postAdmin('/auth/logout')
}
export function adminProfile() {
  return getAdmin<{ id: number; username: string; real_name: string | null; phone: string | null; email: string | null; role: string; last_login_at: string | null }>('/auth/profile')
}

// ===== Dashboard =====
export interface DashboardStats {
  today_orders: number
  today_sales: number
  total_customers: number
  total_products: number
  pending_after_sales: number
  pending_invoices: number
  recent_orders: Array<{ order_no: string; customer_name: string; total_amount: number; status: number; customer_status: string; created_at: string }>
  sales_trend: Array<{ date: string; amount: number; count: number }>
}
export function getDashboardStats() {
  return getAdmin<DashboardStats>('/dashboard')
}

// ===== Product =====
export interface AdminProduct {
  id: number
  name: string
  code: string
  thumbnail: string | null
  price_min: number
  price_max: number
  status: number
  category: { id: number; name: string }
}
export function getAdminProducts(params?: { keyword?: string; page?: number; per_page?: number }) {
  return getAdmin<{ data: AdminProduct[]; total: number; current_page: number; last_page: number }>('/products', params)
}
export function getAdminProductDetail(id: number) {
  return getAdmin<AdminProduct>(`/products/${id}`)
}
export function createAdminProduct(data: Partial<AdminProduct> & { category_id: number; code: string; price_min: number; price_max: number }) {
  return postAdmin<AdminProduct>('/products', data)
}
export function updateAdminProduct(id: number, data: Partial<AdminProduct>) {
  return putAdmin<AdminProduct>(`/products/${id}`, data)
}
export function toggleProductStatus(id: number) {
  return putAdmin<{ status: number }>(`/products/${id}/toggle-status`)
}

// ===== Customer =====
export interface AdminCustomer {
  id: number
  phone: string
  name: string | null
  company_name: string | null
  status: number
  created_at: string
}
export function getAdminCustomers(params?: { keyword?: string; page?: number; per_page?: number }) {
  return getAdmin<{ data: AdminCustomer[]; total: number; current_page: number; last_page: number }>('/customers', params)
}
export function getAdminCustomerDetail(id: number) {
  return getAdmin<AdminCustomer & { orders_count?: number; total_spent?: number }>(`/customers/${id}`)
}

// ===== Order =====
export interface AdminOrder {
  id: number
  order_no: string
  customer_id: number
  customer_name: string
  total_amount: number
  status: number
  customer_status: string
  created_at: string
}
export function getAdminOrders(params?: { status?: number; keyword?: string; page?: number; per_page?: number }) {
  return getAdmin<{ data: AdminOrder[]; total: number; current_page: number; last_page: number }>('/orders', params)
}
export function getAdminOrderDetail(id: number) {
  return getAdmin<AdminOrder & { items: any[]; address?: any; logs?: any[] }>(`/orders/${id}`)
}

// ===== Banner =====
export interface Banner {
  id: number
  title: string
  image: string
  image_mobile: string
  link_type: number
  link_target: string
  sort: number
  status: number
  start_at: string | null
  end_at: string | null
}
export function getAdminBanners() {
  return getAdmin<Banner[]>('/banners')
}
export function createBanner(data: Partial<Banner>) {
  return postAdmin<Banner>('/banners', data)
}
export function updateBanner(id: number, data: Partial<Banner>) {
  return putAdmin<Banner>(`/banners/${id}`, data)
}
export function deleteBanner(id: number) {
  return del(`/banners/${id}`)
}

// ===== Announcement =====
export interface Announcement {
  id: number
  title: string
  content: string
  type: number
  is_popup: number
  sort: number
  status: number
  publish_at: string | null
}
export function getAdminAnnouncements() {
  return getAdmin<Announcement[]>('/announcements')
}
export function createAnnouncement(data: Partial<Announcement>) {
  return postAdmin<Announcement>('/announcements', data)
}
export function updateAnnouncement(id: number, data: Partial<Announcement>) {
  return putAdmin<Announcement>(`/announcements/${id}`, data)
}
export function deleteAnnouncement(id: number) {
  return del(`/announcements/${id}`)
}

// ===== Coupon =====
export interface AdminCoupon {
  id: number
  name: string
  description: string | null
  type: number
  value: number
  min_amount: number
  max_discount: number | null
  start_at: string
  end_at: string
  total_count: number
  claimed_count: number
  per_customer_limit: number
  status: number
}
export function getAdminCoupons(params?: { page?: number; per_page?: number }) {
  return getAdmin<{ data: AdminCoupon[]; total: number; current_page: number; last_page: number }>('/coupons', params)
}
export function createAdminCoupon(data: Partial<AdminCoupon>) {
  return postAdmin<AdminCoupon>('/coupons', data)
}
export function updateAdminCoupon(id: number, data: Partial<AdminCoupon>) {
  return putAdmin<AdminCoupon>(`/coupons/${id}`, data)
}
export function toggleCouponStatus(id: number) {
  return putAdmin<{ status: number }>(`/coupons/${id}/toggle-status`)
}

// ===== AfterSale =====
export interface AfterSale {
  id: number
  order_id: number
  order_no: string
  type: number
  status: number
  reason: string
  amount: number
  created_at: string
}
export function getAdminAfterSales(params?: { status?: number; page?: number }) {
  return getAdmin<{ data: AfterSale[]; total: number }>('/after-sales', params)
}
export function getAdminAfterSaleDetail(id: number) {
  return getAdmin<AfterSale>(`/after-sales/${id}`)
}
export function auditAfterSale(id: number, data: { status: number; remark?: string }) {
  return putAdmin(`/after-sales/${id}/audit`, data)
}

// ===== Invoice =====
export interface Invoice {
  id: number
  order_id: number
  order_no: string
  type: number
  title: string
  tax_no: string
  amount: number
  status: number
  created_at: string
}
export function getAdminInvoices(params?: { status?: number; page?: number }) {
  return getAdmin<{ data: Invoice[]; total: number }>('/invoices', params)
}
export function getAdminInvoiceDetail(id: number) {
  return getAdmin<Invoice>(`/invoices/${id}`)
}
export function auditInvoice(id: number, data: { status: number; remark?: string }) {
  return putAdmin(`/invoices/${id}/audit`, data)
}
export function issueInvoice(id: number) {
  return putAdmin(`/invoices/${id}/issue`)
}

// ===== AuditLog =====
export interface AuditLog {
  id: number
  admin_id: number | null
  admin_name: string | null
  action: string
  model_type: string | null
  model_id: number | null
  ip: string | null
  result: number
  created_at: string
}
export interface AuditLogDetail extends AuditLog {
  before_data: any
  after_data: any
  user_agent: string | null
  remark: string | null
}
export function getAdminAuditLogs(params?: { action?: string; admin_id?: number; page?: number; per_page?: number }) {
  return getAdmin<{ data: AuditLog[]; total: number; current_page: number; last_page: number }>('/audit-logs', params)
}
export function getAdminAuditLogDetail(id: number) {
  return getAdmin<AuditLogDetail>(`/audit-logs/${id}`)
}

// ===== SystemConfig =====
export interface SystemConfig {
  id: number
  config_key: string
  config_value: string
  type: string
  description: string | null
  group: string
}
export function getSystemConfigs(params?: { group?: string; keyword?: string }) {
  return getAdmin<SystemConfig[]>('/system-configs', params)
}
export function updateSystemConfig(id: number, data: { config_value: string }) {
  return putAdmin(`/system-configs/${id}`, data)
}
export function batchUpdateSystemConfigs(configs: Array<{ id: number; config_value: string }>) {
  return putAdmin('/system-configs/batch', { configs })
}

// ===== Ticket =====
export interface Ticket {
  id: number
  customer_id: number
  customer_name: string
  type: number
  status: number
  title: string
  created_at: string
}
export function getAdminTickets(params?: { status?: number; page?: number }) {
  return getAdmin<{ data: Ticket[]; total: number }>('/tickets', params)
}
export function getAdminTicketDetail(id: number) {
  return getAdmin<Ticket>(`/tickets/${id}`)
}
export function processTicket(id: number, data: { status: number; reply?: string }) {
  return putAdmin(`/tickets/${id}/process`, data)
}
