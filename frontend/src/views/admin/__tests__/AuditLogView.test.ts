import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import AuditLogView from '../AuditLogView.vue'

const mockLogs = [
  { id: 1, admin_id: 1, admin_name: '管理员', action: 'login', model_type: 'Admin', model_id: 1, ip: '127.0.0.1', result: 1, created_at: '2026-01-01 10:00:00' },
  { id: 2, admin_id: 1, admin_name: '管理员', action: 'create', model_type: 'Product', model_id: 5, ip: '127.0.0.1', result: 1, created_at: '2026-01-02 14:30:00' },
]

let getAdminAuditLogsMock = vi.fn()
let getAdminAuditLogDetailMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminAuditLogs: (...args: any[]) => getAdminAuditLogsMock(...args),
  getAdminAuditLogDetail: (...args: any[]) => getAdminAuditLogDetailMock(...args),
}))

beforeEach(() => {
  getAdminAuditLogsMock = vi.fn(() => Promise.resolve({ data: mockLogs, total: 2, current_page: 1, last_page: 1 }))
  getAdminAuditLogDetailMock = vi.fn(() => Promise.resolve({
    id: 1, admin_id: 1, admin_name: '管理员', action: 'login', model_type: 'Admin', model_id: 1,
    before_data: null, after_data: null, ip: '127.0.0.1', user_agent: 'Mozilla/5.0', result: 1, remark: '', created_at: '2026-01-01 10:00:00',
  }))
})

describe('AuditLogView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(AuditLogView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders audit log page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.audit-log-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('审计日志')
  })

  it('loads audit logs on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminAuditLogsMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).logs).toHaveLength(2)
    expect((wrapper.vm as any).logs[0].action).toBe('login')
    expect((wrapper.vm as any).total).toBe(2)
  })

  it('filters by action type', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).actionFilter = 'create'
    ;(wrapper.vm as any).loadLogs()
    await flushPromises()
    expect(getAdminAuditLogsMock).toHaveBeenLastCalledWith(expect.objectContaining({ action: 'create', page: 1, per_page: 10 }))
  })

  it('shows log detail', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).showDetail(mockLogs[0])
    await flushPromises()
    expect(getAdminAuditLogDetailMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).detailVisible).toBe(true)
    expect((wrapper.vm as any).detail?.admin_name).toBe('管理员')
  })

  it('handles pagination change', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).currentPage = 2
    ;(wrapper.vm as any).loadLogs()
    await flushPromises()
    expect(getAdminAuditLogsMock).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2, per_page: 10 }))
  })
})
