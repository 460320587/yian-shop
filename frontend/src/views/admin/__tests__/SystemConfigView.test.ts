import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import SystemConfigView from '../SystemConfigView.vue'

vi.mock('@/api/admin', () => ({
  getSystemConfigs: vi.fn(() => Promise.resolve([
    { id: 1, config_key: 'site_name', config_value: '怡安印刷', type: 'string', description: '站点名称', group: 'basic' },
    { id: 2, config_key: 'maintenance_mode', config_value: '0', type: 'bool', description: '维护模式', group: 'system' },
  ])),
  updateSystemConfig: vi.fn(() => Promise.resolve({})),
  batchUpdateSystemConfigs: vi.fn(() => Promise.resolve({})),
}))

describe('SystemConfigView', () => {
  it('renders system config list', async () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(SystemConfigView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 200))
    expect(wrapper.find('.system-config-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('系统配置')
  })
})
