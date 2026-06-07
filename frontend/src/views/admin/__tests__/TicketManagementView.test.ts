import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import TicketManagementView from '../TicketManagementView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessage: { success: vi.fn(), error: vi.fn() },
  }
})

const mockTickets = [
  { id: 1, ticket_no: 'T202601010001', customer_id: 10, type: 1, status: 1, priority: 2, title: '咨询运费', content: '运费怎么算', customer: { id: 10, nickname: '张三', phone: '13800138000' }, order: null, remark: null, created_at: '2026-01-01T10:00:00Z' },
  { id: 2, ticket_no: 'T202601010002', customer_id: 11, type: 2, status: 4, priority: 3, title: '投诉印刷质量', content: '颜色不对', customer: { id: 11, nickname: '李四', phone: '13900139000' }, order: { id: 5, order_no: 'Y202601010005' }, remark: '已补印', created_at: '2026-01-02T10:00:00Z' },
]

let getAdminTicketsMock = vi.fn()
let getAdminTicketDetailMock = vi.fn()
let processTicketMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminTickets: (...args: any[]) => getAdminTicketsMock(...args),
  getAdminTicketDetail: (...args: any[]) => getAdminTicketDetailMock(...args),
  processTicket: (...args: any[]) => processTicketMock(...args),
}))

beforeEach(() => {
  getAdminTicketsMock = vi.fn(() => Promise.resolve({ data: mockTickets, total: 2, current_page: 1, last_page: 1 }))
  getAdminTicketDetailMock = vi.fn(() => Promise.resolve(mockTickets[0]))
  processTicketMock = vi.fn(() => Promise.resolve({ id: 1, status: 2 }))
})

describe('TicketManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(TicketManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders ticket management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.ticket-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('工单管理')
  })

  it('loads tickets on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminTicketsMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).tickets).toHaveLength(2)
    expect((wrapper.vm as any).tickets[0].ticket_no).toBe('T202601010001')
  })

  it('filters by status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).statusFilter = 1
    ;(wrapper.vm as any).onFilter()
    await flushPromises()
    expect(getAdminTicketsMock).toHaveBeenLastCalledWith(expect.objectContaining({ status: 1, page: 1, per_page: 10 }))
  })

  it('filters by type', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).typeFilter = 2
    ;(wrapper.vm as any).onFilter()
    await flushPromises()
    expect(getAdminTicketsMock).toHaveBeenLastCalledWith(expect.objectContaining({ type: 2, page: 1, per_page: 10 }))
  })

  it('opens detail dialog', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).openDetail(mockTickets[0])
    await flushPromises()
    expect(getAdminTicketDetailMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).detailDialogVisible).toBe(true)
  })

  it('opens process dialog for pending ticket', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openProcess(mockTickets[0])
    expect((wrapper.vm as any).processDialogVisible).toBe(true)
    expect((wrapper.vm as any).processForm.status).toBe(2)
  })

  it('processes ticket', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openProcess(mockTickets[0])
    ;(wrapper.vm as any).processForm.status = 2
    ;(wrapper.vm as any).processForm.remark = '正在处理'
    ;(wrapper.vm as any).processForm.priority = 3
    ;(wrapper.vm as any).handleProcess()
    await flushPromises()
    expect(processTicketMock).toHaveBeenCalledWith(1, { status: 2, remark: '正在处理', priority: 3 })
    expect((wrapper.vm as any).processDialogVisible).toBe(false)
  })

  it('status helper returns correct text', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.statusText(1)).toBe('待处理')
    expect(vm.statusText(2)).toBe('处理中')
    expect(vm.statusText(4)).toBe('已完成')
    expect(vm.statusText(99)).toBe('未知')
  })

  it('type helper returns correct text', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.typeText(1)).toBe('咨询')
    expect(vm.typeText(2)).toBe('投诉')
    expect(vm.typeText(3)).toBe('建议')
    expect(vm.typeText(4)).toBe('售后')
  })

  it('priority helper returns correct text', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.priorityText(1)).toBe('低')
    expect(vm.priorityText(2)).toBe('中')
    expect(vm.priorityText(3)).toBe('高')
    expect(vm.priorityText(4)).toBe('紧急')
  })
})
