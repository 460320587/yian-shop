import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import RefundManagementView from '../RefundManagementView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve()) },
    ElMessage: { success: vi.fn(), error: vi.fn() },
  }
})

const mockRefunds = [
  { id: 1, refund_no: 'R202601010001', order_id: 5, customer_id: 10, amount: 50.00, reason: '商品缺货', status: 0, refund_path: 'wallet', customer: { id: 10, nickname: '张三', phone: '13800138000' }, order: { id: 5, order_no: 'Y202601010005' }, created_at: '2026-01-01T10:00:00Z' },
  { id: 2, refund_no: 'R202601010002', order_id: 6, customer_id: 11, amount: 100.00, reason: '重复支付', status: 4, refund_path: 'original', customer: { id: 11, nickname: '李四', phone: '13900139000' }, order: { id: 6, order_no: 'Y202601010006' }, created_at: '2026-01-02T10:00:00Z' },
]

let getAdminRefundsMock = vi.fn()
let getAdminRefundDetailMock = vi.fn()
let auditAdminRefundMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminRefunds: (...args: any[]) => getAdminRefundsMock(...args),
  getAdminRefundDetail: (...args: any[]) => getAdminRefundDetailMock(...args),
  auditAdminRefund: (...args: any[]) => auditAdminRefundMock(...args),
}))

beforeEach(() => {
  getAdminRefundsMock = vi.fn(() => Promise.resolve({ data: mockRefunds, total: 2, current_page: 1, last_page: 1 }))
  getAdminRefundDetailMock = vi.fn(() => Promise.resolve(mockRefunds[0]))
  auditAdminRefundMock = vi.fn(() => Promise.resolve({ id: 1, status: 4 }))
})

describe('RefundManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(RefundManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders refund management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.refund-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('退款管理')
  })

  it('loads refunds on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminRefundsMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).refunds).toHaveLength(2)
    expect((wrapper.vm as any).refunds[0].refund_no).toBe('R202601010001')
  })

  it('filters by status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).statusFilter = 0
    ;(wrapper.vm as any).onFilter()
    await flushPromises()
    expect(getAdminRefundsMock).toHaveBeenLastCalledWith(expect.objectContaining({ status: 0, page: 1, per_page: 10 }))
  })

  it('opens detail dialog', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).openDetail(mockRefunds[0])
    await flushPromises()
    expect(getAdminRefundDetailMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).detailDialogVisible).toBe(true)
  })

  it('opens audit dialog for pending refund', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openAudit(mockRefunds[0])
    expect((wrapper.vm as any).auditDialogVisible).toBe(true)
    expect((wrapper.vm as any).auditForm.action).toBe('approve')
  })

  it('approves refund', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openAudit(mockRefunds[0])
    ;(wrapper.vm as any).auditForm.remark = '同意退款'
    ;(wrapper.vm as any).handleAudit()
    await flushPromises()
    expect(auditAdminRefundMock).toHaveBeenCalledWith(1, { action: 'approve', remark: '同意退款' })
    expect((wrapper.vm as any).auditDialogVisible).toBe(false)
  })

  it('rejects refund', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openAudit(mockRefunds[0])
    ;(wrapper.vm as any).auditForm.action = 'reject'
    ;(wrapper.vm as any).auditForm.remark = '不符合退款条件'
    ;(wrapper.vm as any).handleAudit()
    await flushPromises()
    expect(auditAdminRefundMock).toHaveBeenCalledWith(1, { action: 'reject', remark: '不符合退款条件' })
  })

  it('status helper returns correct text', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.statusText(0)).toBe('待处理')
    expect(vm.statusText(1)).toBe('审核通过')
    expect(vm.statusText(2)).toBe('审核拒绝')
    expect(vm.statusText(4)).toBe('已完成')
    expect(vm.statusText(99)).toBe('未知')
  })

  it('refund path helper returns correct text', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.refundPathText('original')).toBe('原路返回')
    expect(vm.refundPathText('wallet')).toBe('退回钱包')
    expect(vm.refundPathText('bank_card')).toBe('银行卡')
  })
})
