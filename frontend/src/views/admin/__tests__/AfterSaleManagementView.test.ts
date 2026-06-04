import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import AfterSaleManagementView from '../AfterSaleManagementView.vue'

vi.mock('@/api/admin', () => ({
  getAdminAfterSales: vi.fn(() => Promise.resolve({ data: [{ id: 1, order_no: 'Y202601010001', type: 1, status: 11, reason: '质量问题', amount: 2000, created_at: '2026-01-01' }], total: 1 })),
  auditAfterSale: vi.fn(() => Promise.resolve({})),
}))

describe('AfterSaleManagementView', () => {
  it('renders after-sale management', async () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(AfterSaleManagementView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 100))
    expect(wrapper.find('.aftersale-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('售后管理')
  })
})
