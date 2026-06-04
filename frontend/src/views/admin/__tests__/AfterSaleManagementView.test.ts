import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import AfterSaleManagementView from '../AfterSaleManagementView.vue'

const mockAfterSales = [
  { id: 1, after_sale_no: 'A202601010001', order_no: 'Y202601010001', type: 1, status: 11, reason: '质量问题', amount: 2000, created_at: '2026-01-01 10:00:00' },
  { id: 2, after_sale_no: 'A202601010002', order_no: 'Y202601010002', type: 2, status: 12, reason: '发错货', amount: 1000, created_at: '2026-01-02 14:30:00' },
]

let getAdminAfterSalesMock = vi.fn()
let auditAfterSaleMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminAfterSales: (...args: any[]) => getAdminAfterSalesMock(...args),
  auditAfterSale: (...args: any[]) => auditAfterSaleMock(...args),
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
  getAdminAfterSalesMock = vi.fn(() => Promise.resolve({ data: mockAfterSales, total: 2 }))
  auditAfterSaleMock = vi.fn(() => Promise.resolve({}))
})

describe('AfterSaleManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(AfterSaleManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders after-sale management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.aftersale-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('售后管理')
  })

  it('loads after-sales on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminAfterSalesMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).afterSales).toHaveLength(2)
    expect((wrapper.vm as any).afterSales[0].after_sale_no).toBe('A202601010001')
    expect((wrapper.vm as any).total).toBe(2)
  })

  it('filters by status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).statusFilter = '11'
    ;(wrapper.vm as any).onSearch()
    await flushPromises()
    expect(getAdminAfterSalesMock).toHaveBeenLastCalledWith(expect.objectContaining({ status: '11', page: 1, per_page: 10 }))
  })

  it('audits after-sale approve', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleAudit(mockAfterSales[0], 12)
    await flushPromises()
    expect(auditAfterSaleMock).toHaveBeenCalledWith(1, { status: 12 })
  })

  it('audits after-sale reject', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleAudit(mockAfterSales[0], 13)
    await flushPromises()
    expect(auditAfterSaleMock).toHaveBeenCalledWith(1, { status: 13 })
  })

  it('handles pagination change', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).currentPage = 2
    ;(wrapper.vm as any).loadAfterSales()
    await flushPromises()
    expect(getAdminAfterSalesMock).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2, per_page: 10 }))
  })
})
