import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { nextTick } from 'vue'
import VipCenterView from '../VipCenterView.vue'

const mockVipInfo = {
  current_grow_value: 3250,
  current_level: 2,
  current_level_name: '银牌会员',
  current_discount: 0.95,
  next_level: 3,
  next_level_name: '金牌会员',
  next_level_points: 5000,
  progress_percent: 65,
}

const mockVipLevels = [
  { level: 1, name: '普通会员', min_points: 0, discount: 1.00, icon: '', privileges: { sample_discount: 1.0 } },
  { level: 2, name: '银牌会员', min_points: 1000, discount: 0.95, icon: '', privileges: { sample_discount: 0.9 } },
  { level: 3, name: '金牌会员', min_points: 5000, discount: 0.90, icon: '', privileges: { sample_discount: 0.8 } },
  { level: 4, name: '钻石会员', min_points: 10000, discount: 0.80, icon: '', privileges: { sample_discount: 0.5 } },
]

let getVipInfoMock = vi.fn()
let getVipLevelsMock = vi.fn()

vi.mock('@/api/vip', () => ({
  getVipInfo: (...args: any[]) => getVipInfoMock(...args),
  getVipLevels: (...args: any[]) => getVipLevelsMock(...args),
}))

vi.mock('element-plus', () => ({
  ElMessage: {
    error: vi.fn(),
  },
}))

function createWrapper() {
  return mount(VipCenterView)
}

describe('VipCenterView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    getVipInfoMock = vi.fn(() => Promise.resolve(mockVipInfo))
    getVipLevelsMock = vi.fn(() => Promise.resolve(mockVipLevels))
  })

  it('renders loading state initially', async () => {
    getVipInfoMock = vi.fn(() => new Promise(() => {}))
    getVipLevelsMock = vi.fn(() => new Promise(() => {}))
    const wrapper = createWrapper()
    await nextTick()
    expect(wrapper.find('.vip-center-view').exists()).toBe(true)
    expect(wrapper.find('.page-title').text()).toBe('VIP中心')
    expect(wrapper.find('.loading-text').exists()).toBe(true)
  })

  it('fetches vip info and levels on mount', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    expect(getVipInfoMock).toHaveBeenCalled()
    expect(getVipLevelsMock).toHaveBeenCalled()
  })

  it('renders current vip level and progress', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('.current-level').text()).toContain('银牌会员')
    expect(wrapper.find('.grow-value').text()).toContain('3250')
    expect(wrapper.find('.progress-percent').text()).toContain('65')
    expect(wrapper.find('.next-level-name').text()).toContain('金牌会员')
  })

  it('renders level comparison table', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const rows = wrapper.findAll('.level-row')
    expect(rows.length).toBe(4)
    expect(rows[0].text()).toContain('普通会员')
    expect(rows[1].text()).toContain('银牌会员')
    expect(rows[2].text()).toContain('金牌会员')
    expect(rows[3].text()).toContain('钻石会员')
  })

  it('shows max level state when no next level', async () => {
    getVipInfoMock = vi.fn(() => Promise.resolve({
      current_grow_value: 15000,
      current_level: 4,
      current_level_name: '钻石会员',
      current_discount: 0.80,
      next_level: null,
      next_level_name: null,
      next_level_points: null,
      progress_percent: 100,
    }))
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('.current-level').text()).toContain('钻石会员')
    expect(wrapper.find('.max-level-badge').exists()).toBe(true)
  })

  it('exposes refs and methods', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const vm = wrapper.vm as any
    expect(vm.vipInfo).toBeDefined()
    expect(vm.vipLevels).toBeDefined()
    expect(vm.loading).toBe(false)
    expect(typeof vm.loadData).toBe('function')
  })
})
