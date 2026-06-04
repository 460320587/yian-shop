import { describe, it, expect, vi } from 'vitest'
import {
  adminLogin, adminLogout, adminProfile,
  getDashboardStats,
  getAdminProducts, getAdminProductDetail, createAdminProduct, toggleProductStatus,
  getAdminAuditLogs, getAdminAuditLogDetail,
  getSystemConfigs, updateSystemConfig, batchUpdateSystemConfigs,
} from '../admin'

vi.mock('@/utils/request', () => ({
  post: vi.fn((url: string) => {
    if (url === '/auth/login') {
      return Promise.resolve({ admin: { id: 1, username: 'admin', real_name: '管理员', role: 'super' }, token: 'admin-token' })
    }
    if (url === '/auth/logout') {
      return Promise.resolve({})
    }
    if (url === '/products') {
      return Promise.resolve({ id: 2, name: '新产品', code: 'NEW-001', price_min: 1000, price_max: 5000, status: 1 })
    }
    if (url === '/system-configs/batch') {
      return Promise.resolve({})
    }
    return Promise.resolve({})
  }),
  get: vi.fn((url: string) => {
    if (url === '/auth/profile') {
      return Promise.resolve({ id: 1, username: 'admin', real_name: '管理员', phone: null, email: null, role: 'super', last_login_at: null })
    }
    if (url === '/dashboard') {
      return Promise.resolve({
        today_orders: 5, today_sales: 50000, total_customers: 100, total_products: 20,
        pending_after_sales: 2, pending_invoices: 3,
        recent_orders: [{ order_no: 'Y202601010001', customer_name: '测试', total_amount: 5000, status: 11, customer_status: '待付款', created_at: '2026-01-01' }],
        sales_trend: [{ date: '2026-01-01', amount: 50000, count: 5 }],
      })
    }
    if (url === '/products') {
      return Promise.resolve({
        data: [{ id: 1, name: '名片', code: 'CARD-001', price_min: 1000, price_max: 5000, status: 1, category: { id: 1, name: '名片' } }],
        total: 1, current_page: 1, last_page: 1,
      })
    }
    if (url.startsWith('/products/')) {
      return Promise.resolve({ id: 1, name: '名片', code: 'CARD-001', price_min: 1000, price_max: 5000, status: 1, category: { id: 1, name: '名片' } })
    }
    if (url === '/audit-logs') {
      return Promise.resolve({
        data: [{ id: 1, admin_id: 1, admin_name: '管理员', action: 'login', model_type: 'Admin', model_id: 1, ip: '127.0.0.1', result: 1, created_at: '2026-01-01' }],
        total: 1, current_page: 1, last_page: 1,
      })
    }
    if (url.startsWith('/audit-logs/')) {
      return Promise.resolve({ id: 1, admin_id: 1, admin_name: '管理员', action: 'login', model_type: 'Admin', model_id: 1, before_data: null, after_data: null, ip: '127.0.0.1', user_agent: 'Mozilla', result: 1, remark: '', created_at: '2026-01-01' })
    }
    if (url === '/system-configs') {
      return Promise.resolve([
        { id: 1, config_key: 'site_name', config_value: '怡安印刷', type: 'string', description: '站点名称', group: 'basic' },
        { id: 2, config_key: 'maintenance_mode', config_value: '0', type: 'bool', description: '维护模式', group: 'system' },
      ])
    }
    if (url.startsWith('/system-configs/') && !url.includes('/batch')) {
      return Promise.resolve({ id: 1, config_key: 'site_name', config_value: '怡安印刷', type: 'string', description: '站点名称', group: 'basic' })
    }
    return Promise.resolve({})
  }),
  put: vi.fn((url: string) => {
    if (url.startsWith('/products/')) {
      return Promise.resolve({ status: 0 })
    }
    if (url.startsWith('/system-configs/')) {
      return Promise.resolve({})
    }
    return Promise.resolve({})
  }),
}))

describe('Admin API', () => {
  it('adminLogin returns token and admin', async () => {
    const res = await adminLogin({ username: 'admin', password: 'password' })
    expect(res.token).toBe('admin-token')
    expect(res.admin.username).toBe('admin')
  })

  it('adminLogout calls logout endpoint', async () => {
    const res = await adminLogout()
    expect(res).toEqual({})
  })

  it('adminProfile returns admin profile', async () => {
    const profile = await adminProfile()
    expect(profile.username).toBe('admin')
  })

  it('getDashboardStats returns dashboard data', async () => {
    const data = await getDashboardStats()
    expect(data.today_orders).toBe(5)
    expect(data.total_customers).toBe(100)
  })

  it('getAdminProducts returns paginated products', async () => {
    const res = await getAdminProducts()
    expect(res.data).toHaveLength(1)
    expect(res.data[0].name).toBe('名片')
  })

  it('getAdminProductDetail returns product info', async () => {
    const product = await getAdminProductDetail(1)
    expect(product.name).toBe('名片')
  })

  it('createAdminProduct creates product', async () => {
    const res = await createAdminProduct({ name: '新产品', code: 'NEW-001', category_id: 1, price_min: 1000, price_max: 5000 })
    expect(res.name).toBe('新产品')
  })

  it('toggleProductStatus toggles status', async () => {
    const res = await toggleProductStatus(1)
    expect(res.status).toBe(0)
  })

  it('getAdminAuditLogs returns audit logs', async () => {
    const res = await getAdminAuditLogs()
    expect(res.data).toHaveLength(1)
    expect(res.data[0].action).toBe('login')
  })

  it('getAdminAuditLogDetail returns log detail', async () => {
    const log = await getAdminAuditLogDetail(1)
    expect(log.action).toBe('login')
    expect(log.ip).toBe('127.0.0.1')
  })

  it('getSystemConfigs returns config list', async () => {
    const res = await getSystemConfigs()
    expect(res).toHaveLength(2)
    expect(res[0].config_key).toBe('site_name')
    expect(res[1].group).toBe('system')
  })

  it('updateSystemConfig updates config', async () => {
    const res = await updateSystemConfig(1, { config_value: '新名称' })
    expect(res).toEqual({})
  })

  it('batchUpdateSystemConfigs batch updates', async () => {
    const res = await batchUpdateSystemConfigs([{ id: 1, config_value: '新名称' }, { id: 2, config_value: '1' }])
    expect(res).toEqual({})
  })
})
