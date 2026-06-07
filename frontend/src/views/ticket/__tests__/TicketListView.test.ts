import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import TicketListView from '../TicketListView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve('confirm')) },
    ElMessage: { success: vi.fn(), error: vi.fn(), warning: vi.fn() },
  }
})

let getTicketsMock = vi.fn()
let cancelTicketMock = vi.fn()

vi.mock('@/api/ticket', () => ({
  getTickets: (...args: any[]) => getTicketsMock(...args),
  cancelTicket: (...args: any[]) => cancelTicketMock(...args),
}))

beforeEach(() => {
  getTicketsMock = vi.fn(() => Promise.resolve({
    data: [
      { id: 1, ticket_no: 'TK202601010001', type: 2, status: 1, priority: 3, title: '印刷质量问题', content: '颜色不对', order: { id: 5, order_no: 'Y202601010005' }, remark: null, created_at: '2026-01-01T10:00:00Z' },
      { id: 2, ticket_no: 'TK202601010002', type: 1, status: 4, priority: 2, title: '咨询运费', content: '运费怎么算', order: null, remark: '已回复', created_at: '2026-01-02T10:00:00Z' },
    ],
    total: 2, current_page: 1, last_page: 1,
  }))
  cancelTicketMock = vi.fn(() => Promise.resolve({}))
})

describe('TicketListView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/tickets', name: 'TicketList', component: TicketListView },
        { path: '/ticket-create', name: 'TicketCreate', component: { template: '<div>Create</div>' } },
      ],
    })
    return mount(TicketListView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders ticket list page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.ticket-list-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('我的工单')
  })

  it('loads tickets on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getTicketsMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).tickets).toHaveLength(2)
  })

  it('displays type name', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.typeName(1)).toBe('咨询')
    expect(vm.typeName(2)).toBe('投诉')
    expect(vm.typeName(3)).toBe('建议')
    expect(vm.typeName(4)).toBe('售后')
    expect(vm.typeName(5)).toBe('其他')
  })

  it('displays status name', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.statusName(0)).toBe('已取消')
    expect(vm.statusName(1)).toBe('待处理')
    expect(vm.statusName(2)).toBe('处理中')
    expect(vm.statusName(4)).toBe('已完成')
    expect(vm.statusName(99)).toBe('未知')
  })

  it('cancels pending ticket', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleCancel({ id: 1, status: 1 })
    await flushPromises()
    expect(cancelTicketMock).toHaveBeenCalledWith(1)
  })

  it('does not cancel completed ticket', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleCancel({ id: 2, status: 4 })
    expect(cancelTicketMock).not.toHaveBeenCalled()
  })

  it('filters by status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).statusFilter = 1
    ;(wrapper.vm as any).onFilter()
    await flushPromises()
    expect(getTicketsMock).toHaveBeenLastCalledWith(expect.objectContaining({ status: 1, page: 1 }))
  })

  it('navigates to create page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const router = (wrapper.vm as any).$router
    const pushSpy = vi.spyOn(router, 'push')
    await (wrapper.vm as any).goToCreate()
    expect(pushSpy).toHaveBeenCalledWith('/ticket-create')
  })
})
