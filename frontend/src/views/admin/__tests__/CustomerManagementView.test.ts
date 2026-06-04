import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import CustomerManagementView from '../CustomerManagementView.vue'

vi.mock('@/api/admin', () => ({
  getAdminCustomers: vi.fn(() => Promise.resolve({ data: [{ id: 1, phone: '13800138000', name: '张三', company_name: '怡安公司', status: 1, created_at: '2026-01-01' }], total: 1, current_page: 1, last_page: 1 })),
  getAdminCustomerDetail: vi.fn(() => Promise.resolve({ id: 1, phone: '13800138000', name: '张三', company_name: '怡安公司', status: 1, created_at: '2026-01-01' })),
}))

describe('CustomerManagementView', () => {
  it('renders customer management', async () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(CustomerManagementView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 100))
    expect(wrapper.find('.customer-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('客户管理')
  })
})
