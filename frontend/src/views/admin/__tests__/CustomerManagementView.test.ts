import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import CustomerManagementView from '../CustomerManagementView.vue'

const mockCustomers = [
  { id: 1, phone: '13800138000', nickname: '张三', real_name: '张三丰', company_name: '怡安公司', status: 1, vip_level: 3, created_at: '2026-01-01 10:00:00' },
  { id: 2, phone: '13900139000', nickname: '李四', real_name: '李四', company_name: '智印科技', status: 1, vip_level: 5, created_at: '2026-01-02 14:30:00' },
]

let getAdminCustomersMock = vi.fn()
let getAdminCustomerDetailMock = vi.fn()
let toggleAdminCustomerStatusMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminCustomers: (...args: any[]) => getAdminCustomersMock(...args),
  getAdminCustomerDetail: (...args: any[]) => getAdminCustomerDetailMock(...args),
  toggleAdminCustomerStatus: (...args: any[]) => toggleAdminCustomerStatusMock(...args),
}))

beforeEach(() => {
  getAdminCustomersMock = vi.fn(() => Promise.resolve({ data: mockCustomers, total: 2, current_page: 1, last_page: 1 }))
  getAdminCustomerDetailMock = vi.fn(() => Promise.resolve({
    id: 1, phone: '13800138000', nickname: '张三', real_name: '张三丰', company_name: '怡安公司',
    status: 1, vip_level: 3, balance: 50000, created_at: '2026-01-01 10:00:00',
    addresses: [{ id: 1, contact_name: '张三', phone: '13800138000', address: '河南省郑州市' }],
  }))
  toggleAdminCustomerStatusMock = vi.fn(() => Promise.resolve({ status: 0 }))
})

describe('CustomerManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(CustomerManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders customer management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.customer-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('客户管理')
  })

  it('loads customers on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminCustomersMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).customers).toHaveLength(2)
    expect((wrapper.vm as any).customers[0].phone).toBe('13800138000')
    expect((wrapper.vm as any).total).toBe(2)
  })

  it('filters customers by keyword', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).keyword = '13800138000'
    ;(wrapper.vm as any).onSearch()
    await flushPromises()
    expect(getAdminCustomersMock).toHaveBeenLastCalledWith(expect.objectContaining({ keyword: '13800138000', page: 1, per_page: 10 }))
  })

  it('filters customers by vip level', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).vipFilter = '3'
    ;(wrapper.vm as any).onSearch()
    await flushPromises()
    expect(getAdminCustomersMock).toHaveBeenLastCalledWith(expect.objectContaining({ vip_level: '3', page: 1 }))
  })

  it('opens detail dialog', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openDetail(mockCustomers[0])
    await flushPromises()
    expect(getAdminCustomerDetailMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).detailVisible).toBe(true)
    expect((wrapper.vm as any).detail?.phone).toBe('13800138000')
  })

  it('handles pagination change', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).currentPage = 2
    ;(wrapper.vm as any).loadCustomers()
    await flushPromises()
    expect(getAdminCustomersMock).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2, per_page: 10 }))
  })

  it('toggles customer status from enabled to disabled', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleToggleStatus(mockCustomers[0])
    await flushPromises()
    expect(toggleAdminCustomerStatusMock).toHaveBeenCalledWith(1)
    expect(getAdminCustomersMock).toHaveBeenCalledTimes(2)
  })

  it('toggles customer status from disabled to enabled', async () => {
    const disabledCustomer = { ...mockCustomers[0], status: 0 }
    getAdminCustomersMock = vi.fn(() => Promise.resolve({ data: [disabledCustomer, mockCustomers[1]], total: 2, current_page: 1, last_page: 1 }))
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleToggleStatus(disabledCustomer)
    await flushPromises()
    expect(toggleAdminCustomerStatusMock).toHaveBeenCalledWith(1)
    expect(getAdminCustomersMock).toHaveBeenCalledTimes(2)
  })
})
