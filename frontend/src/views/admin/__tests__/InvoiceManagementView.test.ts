import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import InvoiceManagementView from '../InvoiceManagementView.vue'

const mockInvoices = [
  { id: 1, order_no: 'Y202601010001', type: 1, title: '怡安公司', tax_no: '123456789012345', amount: 5000, status: 11, created_at: '2026-01-01 10:00:00' },
  { id: 2, order_no: 'Y202601010002', type: 2, title: '智印科技', tax_no: '987654321098765', amount: 12000, status: 12, created_at: '2026-01-02 14:30:00' },
]

let getAdminInvoicesMock = vi.fn()
let auditInvoiceMock = vi.fn()
let issueInvoiceMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminInvoices: (...args: any[]) => getAdminInvoicesMock(...args),
  auditInvoice: (...args: any[]) => auditInvoiceMock(...args),
  issueInvoice: (...args: any[]) => issueInvoiceMock(...args),
}))

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve()) },
    ElMessage: { success: vi.fn(), error: vi.fn() },
  }
})

beforeEach(() => {
  getAdminInvoicesMock = vi.fn(() => Promise.resolve({ data: mockInvoices, total: 2 }))
  auditInvoiceMock = vi.fn(() => Promise.resolve({}))
  issueInvoiceMock = vi.fn(() => Promise.resolve({}))
})

describe('InvoiceManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(InvoiceManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders invoice management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.invoice-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('发票管理')
  })

  it('loads invoices on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminInvoicesMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).invoices).toHaveLength(2)
    expect((wrapper.vm as any).invoices[0].title).toBe('怡安公司')
    expect((wrapper.vm as any).total).toBe(2)
  })

  it('filters by status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).statusFilter = '11'
    ;(wrapper.vm as any).onSearch()
    await flushPromises()
    expect(getAdminInvoicesMock).toHaveBeenLastCalledWith(expect.objectContaining({ status: '11', page: 1, per_page: 10 }))
  })

  it('audits invoice approve', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleAudit(mockInvoices[0], 12)
    await flushPromises()
    expect(auditInvoiceMock).toHaveBeenCalledWith(1, { status: 12 })
  })

  it('audits invoice reject', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleAudit(mockInvoices[0], 14)
    await flushPromises()
    expect(auditInvoiceMock).toHaveBeenCalledWith(1, { status: 14 })
  })

  it('issues invoice', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleIssue(mockInvoices[1])
    await flushPromises()
    expect(issueInvoiceMock).toHaveBeenCalledWith(2)
  })

  it('handles pagination change', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).currentPage = 2
    ;(wrapper.vm as any).loadInvoices()
    await flushPromises()
    expect(getAdminInvoicesMock).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2, per_page: 10 }))
  })
})
