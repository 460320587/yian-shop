import { get, post, put, del } from '@/utils/request'

function getAdmin<T>(url: string, params?: Record<string, unknown>): Promise<T> {
  return get<T>('/admin' + url, params) as Promise<T>
}
function postAdmin<T>(url: string, data?: unknown): Promise<T> {
  return post<T>('/admin' + url, data) as Promise<T>
}
function putAdmin<T>(url: string, data?: unknown): Promise<T> {
  return put<T>('/admin' + url, data) as Promise<T>
}
function delAdmin<T>(url: string, params?: Record<string, unknown>): Promise<T> {
  return del<T>('/admin' + url, params) as Promise<T>
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
  yesterday_orders: number
  yesterday_sales: number
  total_customers: number
  total_products: number
  pending_after_sales: number
  pending_invoices: number
  order_status_counts: Record<string, number>
  recent_orders: Array<{ id: number; order_no: string; customer_name: string; total_amount: number; status: number; customer_status: string; created_at: string }>
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
export function toggleAdminCustomerStatus(id: number) {
  return putAdmin<{ status: number }>(`/customers/${id}/toggle-status`)
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
  return delAdmin(`/banners/${id}`)
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
  return delAdmin(`/announcements/${id}`)
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

// ===== InkCoverageCheck =====
export interface AdminInkCoverageCheck {
  id: number
  order_id: number
  file_id: number
  check_type: number
  ink_type: string | null
  coverage_c: number | null
  coverage_m: number | null
  coverage_y: number | null
  coverage_k: number | null
  total_coverage: number | null
  check_result: number | null
  check_report: Record<string, any> | null
  checked_at: string | null
  created_at: string
}
export function getAdminInkCoverageChecks(params?: { order_id?: number; page?: number; per_page?: number }) {
  return getAdmin<{ data: AdminInkCoverageCheck[]; total: number; current_page: number; last_page: number }>('/ink-coverage-checks', params)
}
export function createAdminInkCoverageCheck(data: Partial<AdminInkCoverageCheck> & { order_id: number; file_id: number; check_type: number }) {
  return postAdmin<AdminInkCoverageCheck>('/ink-coverage-checks', data)
}
export function updateAdminInkCoverageCheck(id: number, data: Partial<AdminInkCoverageCheck>) {
  return putAdmin<AdminInkCoverageCheck>(`/ink-coverage-checks/${id}`, data)
}
export function deleteAdminInkCoverageCheck(id: number) {
  return delAdmin(`/ink-coverage-checks/${id}`)
}

// ===== Refund =====
export interface AdminRefund {
  id: number
  refund_no: string
  order_id: number
  customer_id: number
  amount: number
  reason: string
  status: number
  refund_path: string
  approved_by: number | null
  approved_at: string | null
  completed_at: string | null
  created_at: string
  customer?: { id: number; nickname: string; phone: string }
  order?: { id: number; order_no: string }
  approver?: { id: number; name: string }
}
export function getAdminRefunds(params?: { status?: number; page?: number; per_page?: number }) {
  return getAdmin<{ data: AdminRefund[]; total: number; current_page: number; last_page: number }>('/refunds', params)
}
export function getAdminRefundDetail(id: number) {
  return getAdmin<AdminRefund>(`/refunds/${id}`)
}
export function auditAdminRefund(id: number, data: { action: 'approve' | 'reject'; remark: string }) {
  return putAdmin<{ id: number; status: number; approved_by: number | null; approved_at: string | null }>(`/refunds/${id}/audit`, data)
}

// ===== Ticket =====
export interface Ticket {
  id: number
  ticket_no: string
  customer_id: number
  customer_name: string
  type: number
  status: number
  priority: number
  title: string
  content: string
  remark: string | null
  customer: { id: number; nickname: string | null; phone: string } | null
  order: { id: number; order_no: string } | null
  processed_at: string | null
  completed_at: string | null
  created_at: string
}
export function getAdminTickets(params?: { status?: number; type?: number; page?: number }) {
  return getAdmin<{ data: Ticket[]; total: number }>('/tickets', params)
}
export function getAdminTicketDetail(id: number) {
  return getAdmin<Ticket>(`/tickets/${id}`)
}
export function processTicket(id: number, data: { status: number; remark?: string; priority?: number }) {
  return putAdmin<{ id: number; status: number }>(`/tickets/${id}/process`, data)
}

// ===== Sample Order =====
export interface AdminSampleOrder {
  id: number
  order_no: string
  customer_id: number
  product_id: number
  quantity: number
  unit_price: number
  discount_amount: number
  total_amount: number
  status: number
  remark: string | null
  address_snapshot: Record<string, any> | null
  customer: { id: number; nickname: string | null; phone: string } | null
  product: { id: number; name: string } | null
  paid_at: string | null
  shipped_at: string | null
  completed_at: string | null
  cancelled_at: string | null
  created_at: string
}
export function getAdminSampleOrders(params?: { status?: number; keyword?: string; page?: number; per_page?: number; created_from?: string; created_to?: string }) {
  return getAdmin<{ data: AdminSampleOrder[]; total: number; current_page: number; last_page: number }>('/sample-orders', params)
}
export function getAdminSampleOrderDetail(id: number) {
  return getAdmin<AdminSampleOrder>(`/sample-orders/${id}`)
}
export function updateAdminSampleOrderStatus(id: number, data: { status: number; remark?: string }) {
  return putAdmin<{ id: number; status: number }>(`/sample-orders/${id}/status`, data)
}
export function deleteAdminSampleOrder(id: number) {
  return delAdmin(`/sample-orders/${id}`)
}

// ===== Review =====
export interface AdminReview {
  id: number
  customer_id: number
  product_id: number
  order_id: number | null
  rating: number
  content: string
  images: string[] | null
  reply: string | null
  reply_at: string | null
  is_show: boolean
  created_at: string
  customer?: { id: number; nickname: string | null }
  product?: { id: number; name: string; thumbnail: string | null }
}
export function getAdminReviews(params?: { product_id?: number; is_show?: boolean; keyword?: string; page?: number; per_page?: number }) {
  return getAdmin<{ data: AdminReview[]; total: number; current_page: number; last_page: number }>('/reviews', params)
}
export function getAdminReviewDetail(id: number) {
  return getAdmin<AdminReview>(`/reviews/${id}`)
}
export function replyAdminReview(id: number, data: { reply: string }) {
  return putAdmin<{ id: number; reply: string; reply_at: string }>(`/reviews/${id}/reply`, data)
}
export function toggleAdminReviewShow(id: number) {
  return putAdmin<{ is_show: boolean }>(`/reviews/${id}/toggle-show`)
}
export function deleteAdminReview(id: number) {
  return delAdmin(`/reviews/${id}`)
}

// ===== Order File =====
export interface AdminOrderFile {
  id: number
  order_id: number
  file_name: string
  file_url: string
  thumb_url: string | null
  file_size: number
  file_type: string
  page_count: number | null
  ink_coverage: number | null
  version: number
  status: number
  created_at: string
}
export function getAdminOrderFiles(orderId: number) {
  return getAdmin<{ data: AdminOrderFile[] }>(`/orders/${orderId}/files`)
}
export function deleteAdminOrderFile(id: number) {
  return delAdmin(`/order-files/${id}`)
}
export function confirmAdminOrderPayment(id: number) {
  return putAdmin(`/orders/${id}/confirm-payment`)
}
export function shipAdminOrder(id: number, data: { express_company: string; tracking_no?: string }) {
  return putAdmin(`/orders/${id}/ship`, data)
}
export function completeAdminOrder(id: number) {
  return putAdmin(`/orders/${id}/complete`)
}

// ===== Category =====
export interface AdminCategory {
  id: number
  name: string
  parent_id: number
  icon: string | null
  sort: number
  status: number
  level: number
  path: string
}
export function getAdminCategories() {
  return getAdmin<AdminCategory[]>('/categories')
}
export function createAdminCategory(data: { name: string; parent_id?: number; icon?: string; sort?: number }) {
  return postAdmin<AdminCategory>('/categories', data)
}
export function updateAdminCategory(id: number, data: { name?: string; icon?: string; sort?: number }) {
  return putAdmin(`/categories/${id}`, data)
}
export function toggleAdminCategoryStatus(id: number) {
  return putAdmin<{ status: number }>(`/categories/${id}/toggle-status`)
}
export function deleteAdminCategory(id: number) {
  return delAdmin(`/categories/${id}`)
}

// ===== Enterprise Auth =====
export interface AdminEnterpriseAuth {
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
  customer?: { id: number; nickname: string | null; phone: string }
}
export function getAdminEnterpriseAuths(params?: { auth_status?: number; keyword?: string; page?: number; per_page?: number }) {
  return getAdmin<{ data: AdminEnterpriseAuth[]; total: number; current_page: number; last_page: number }>('/enterprise-auths', params)
}
export function getAdminEnterpriseAuthDetail(id: number) {
  return getAdmin<AdminEnterpriseAuth>(`/enterprise-auths/${id}`)
}
export function auditAdminEnterpriseAuth(id: number, data: { auth_status: number; audit_remark: string }) {
  return putAdmin<{ id: number; auth_status: number; audit_remark: string }>(`/enterprise-auths/${id}/audit`, data)
}

// ===== Admin Account =====
export interface AdminAccount {
  id: number
  username: string
  real_name: string
  phone: string | null
  email: string | null
  role_id: number | null
  roleModel?: { id: number; name: string }
  status: number
  created_at: string
}
export interface AdminRole {
  id: number
  name: string
  code: string
  description: string | null
  status: number
}
export function getAdminAccounts(params?: { keyword?: string; page?: number; per_page?: number }) {
  return getAdmin<{ data: AdminAccount[]; total: number; current_page: number; last_page: number }>('/admins', params)
}
export function createAdminAccount(data: { username: string; password: string; password_confirmation: string; real_name: string; phone?: string; email?: string; role_id?: number }) {
  return postAdmin<AdminAccount>('/admins', data)
}
export function updateAdminAccount(id: number, data: { real_name?: string; phone?: string; email?: string; role_id?: number }) {
  return putAdmin<AdminAccount>(`/admins/${id}`, data)
}
export function toggleAdminAccountStatus(id: number) {
  return putAdmin<{ status: number }>(`/admins/${id}/toggle-status`)
}
export function deleteAdminAccount(id: number) {
  return delAdmin(`/admins/${id}`)
}
export function resetAdminAccountPassword(id: number, data: { password: string; password_confirmation: string }) {
  return putAdmin(`/admins/${id}/reset-password`, data)
}
export function getAdminRoles() {
  return getAdmin<AdminRole[]>('/roles')
}
export function createAdminRole(data: { name: string; code: string; description?: string }) {
  return postAdmin<AdminRole>('/roles', data)
}
export function updateAdminRole(id: number, data: { name?: string; code?: string; description?: string }) {
  return putAdmin<AdminRole>(`/roles/${id}`, data)
}
export function deleteAdminRole(id: number) {
  return delAdmin(`/roles/${id}`)
}
export function toggleAdminRoleStatus(id: number) {
  return putAdmin<{ status: number }>(`/roles/${id}/toggle-status`)
}
export interface AdminPermission {
  id: number
  name: string
  code: string
  group: string
  type: string
}
export function getAdminPermissions(group?: string) {
  const params: Record<string, unknown> = {}
  if (group) params.group = group
  return getAdmin<AdminPermission[]>('/permissions', params)
}
export function getRolePermissions(id: number) {
  return getAdmin<AdminPermission[]>(`/roles/${id}/permissions`)
}
export function assignRolePermissions(id: number, data: { permission_ids: number[] }) {
  return putAdmin(`/roles/${id}/permissions`, data)
}

// ===== Production Schedule =====
export interface AdminProductionSchedule {
  id: number
  order_id: number
  order?: { id: number; order_no: string }
  schedule_date: string
  start_time: string | null
  end_time: string | null
  process_name: string
  status: number
  priority: number
  estimated_hours: number
  actual_hours: number | null
  progress: number
  delay_reason: string | null
  created_at: string
}
export function getAdminProductionSchedules(params?: { status?: number; order_id?: number; process_name?: string; schedule_date_from?: string; schedule_date_to?: string; page?: number; per_page?: number }) {
  return getAdmin<{ data: AdminProductionSchedule[]; total: number; current_page: number; last_page: number }>('/production-schedules', params)
}
export function getAdminProductionScheduleDetail(id: number) {
  return getAdmin<AdminProductionSchedule>(`/production-schedules/${id}`)
}
export function createAdminProductionSchedule(data: { order_id: number; schedule_date: string; process_name: string; priority?: number; estimated_hours?: number }) {
  return postAdmin<AdminProductionSchedule>('/production-schedules', data)
}
export function updateAdminProductionSchedule(id: number, data: { schedule_date?: string; process_name?: string; priority?: number; estimated_hours?: number; actual_hours?: number; progress?: number; status?: number; delay_reason?: string }) {
  return putAdmin<AdminProductionSchedule>(`/production-schedules/${id}`, data)
}
export function updateAdminProductionScheduleProgress(id: number, data: { progress: number; actual_hours?: number }) {
  return putAdmin<AdminProductionSchedule>(`/production-schedules/${id}/progress`, data)
}
