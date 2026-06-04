import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import AuditLogView from '../AuditLogView.vue'

vi.mock('@/api/admin', () => ({
  getAdminAuditLogs: vi.fn(() => Promise.resolve({
    data: [{ id: 1, admin_id: 1, admin_name: '管理员', action: 'login', model_type: 'Admin', model_id: 1, ip: '127.0.0.1', result: 1, created_at: '2026-01-01' }],
    total: 1, current_page: 1, last_page: 1,
  })),
  getAdminAuditLogDetail: vi.fn(() => Promise.resolve({
    id: 1, admin_id: 1, admin_name: '管理员', action: 'login', model_type: 'Admin', model_id: 1,
    before_data: null, after_data: null, ip: '127.0.0.1', user_agent: 'Mozilla', result: 1, remark: '', created_at: '2026-01-01',
  })),
}))

describe('AuditLogView', () => {
  it('renders audit log list', async () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(AuditLogView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 200))
    expect(wrapper.find('.audit-log-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('审计日志')
  })
})
