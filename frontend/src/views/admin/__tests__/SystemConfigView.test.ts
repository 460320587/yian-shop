import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import SystemConfigView from '../SystemConfigView.vue'

const mockConfigs = [
  { id: 1, config_key: 'site_name', config_value: '怡安印刷', type: 'string', description: '站点名称', group: 'basic' },
  { id: 2, config_key: 'maintenance_mode', config_value: '0', type: 'bool', description: '维护模式', group: 'system' },
]

let getSystemConfigsMock = vi.fn()
let updateSystemConfigMock = vi.fn()
let batchUpdateSystemConfigsMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getSystemConfigs: (...args: any[]) => getSystemConfigsMock(...args),
  updateSystemConfig: (...args: any[]) => updateSystemConfigMock(...args),
  batchUpdateSystemConfigs: (...args: any[]) => batchUpdateSystemConfigsMock(...args),
}))

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessage: { success: vi.fn(), warning: vi.fn(), error: vi.fn() },
  }
})

beforeEach(() => {
  getSystemConfigsMock = vi.fn(() => Promise.resolve(mockConfigs))
  updateSystemConfigMock = vi.fn(() => Promise.resolve({}))
  batchUpdateSystemConfigsMock = vi.fn(() => Promise.resolve({}))
})

describe('SystemConfigView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(SystemConfigView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders system config page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.system-config-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('系统配置')
  })

  it('loads configs on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getSystemConfigsMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).configs).toHaveLength(2)
    expect((wrapper.vm as any).configs[0].config_key).toBe('site_name')
  })

  it('filters by group', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).groupFilter = 'basic'
    ;(wrapper.vm as any).loadConfigs()
    await flushPromises()
    expect(getSystemConfigsMock).toHaveBeenLastCalledWith(expect.objectContaining({ group: 'basic' }))
  })

  it('saves single config', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).changedConfigs[1] = '新名称'
    ;(wrapper.vm as any).handleSave(mockConfigs[0])
    await flushPromises()
    expect(updateSystemConfigMock).toHaveBeenCalledWith(1, { config_value: '新名称' })
    expect((wrapper.vm as any).changedConfigs[1]).toBeUndefined()
  })

  it('batch saves modified configs', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).changedConfigs[1] = '新名称'
    ;(wrapper.vm as any).changedConfigs[2] = '1'
    ;(wrapper.vm as any).handleBatchSave()
    await flushPromises()
    expect(batchUpdateSystemConfigsMock).toHaveBeenCalledWith([
      { id: 1, config_value: '新名称' },
      { id: 2, config_value: '1' },
    ])
    expect(Object.keys((wrapper.vm as any).changedConfigs)).toHaveLength(0)
  })

  it('tracks value changes', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).onValueChange(mockConfigs[0], '新名称')
    expect((wrapper.vm as any).changedConfigs[1]).toBe('新名称')
  })
})
