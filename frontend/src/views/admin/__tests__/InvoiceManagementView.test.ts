import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import InvoiceManagementView from '../InvoiceManagementView.vue'

vi.mock('@/api/admin', () => ({
  getAdminInvoices: vi.fn(() => Promise.resolve({ data: [{ id: 1, order_no: 'Y202601010001', type: 1, title: '怡安公司', tax_no: '123456', amount: 5000, status: 11, created_at: '2026-01-01' }], total: 1 })),
  auditInvoice: vi.fn(() => Promise.resolve({})),
  issueInvoice: vi.fn(() => Promise.resolve({})),
}))

describe('InvoiceManagementView', () => {
  it('renders invoice management', async () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(InvoiceManagementView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 100))
    expect(wrapper.find('.invoice-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('发票管理')
  })
})
