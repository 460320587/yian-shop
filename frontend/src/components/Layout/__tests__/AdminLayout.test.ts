import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import AdminLayout from '../AdminLayout.vue'

const mockPush = vi.fn()

vi.mock('vue-router', async () => {
  const actual = await vi.importActual('vue-router')
  return {
    ...actual as any,
    useRoute: () => ({ path: '/admin', meta: { title: '数据看板' } }),
    useRouter: () => ({ push: mockPush }),
  }
})

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => ({
    adminInfo: { real_name: 'Admin', username: 'admin' },
    logoutApi: vi.fn(() => Promise.resolve({})),
  }),
}))

describe('AdminLayout', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  function mountComponent() {
    const router = createRouter({
      history: createWebHistory(),
      routes: [{ path: '/admin', component: AdminLayout }],
    })
    return mount(AdminLayout, {
      global: { plugins: [router] },
    })
  }

  it('renders admin layout structure', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.admin-layout').exists()).toBe(true)
    expect(wrapper.find('.sidebar').exists()).toBe(true)
    expect(wrapper.find('.admin-header').exists()).toBe(true)
    expect(wrapper.find('.admin-content').exists()).toBe(true)
  })

  it('shows admin brand name', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('怡安后台')
  })

  it('has all required menu items', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    const labels = vm.menuItems.map((item: any) => item.label)

    expect(labels).toContain('数据看板')
    expect(labels).toContain('订单管理')
    expect(labels).toContain('客户管理')
    expect(labels).toContain('商品管理')
    expect(labels).toContain('优惠券')
    expect(labels).toContain('Banner/公告')
    expect(labels).toContain('售后管理')
    expect(labels).toContain('退款管理')
    expect(labels).toContain('发票管理')
    expect(labels).toContain('审计日志')
    expect(labels).toContain('系统配置')
    expect(labels).toContain('印前检查')
  })

  it('has correct paths for all menu items', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    const paths = vm.menuItems.map((item: any) => item.index)

    expect(paths).toContain('/admin')
    expect(paths).toContain('/admin/orders')
    expect(paths).toContain('/admin/customers')
    expect(paths).toContain('/admin/products')
    expect(paths).toContain('/admin/coupons')
    expect(paths).toContain('/admin/banners')
    expect(paths).toContain('/admin/after-sales')
    expect(paths).toContain('/admin/refunds')
    expect(paths).toContain('/admin/invoices')
    expect(paths).toContain('/admin/audit-logs')
    expect(paths).toContain('/admin/system-configs')
    expect(paths).toContain('/admin/ink-coverage-checks')
  })

  it('shows admin user name in header', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('Admin')
  })
})
