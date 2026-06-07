import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { nextTick } from 'vue'
import PointsView from '../PointsView.vue'

let getPointsMock = vi.fn()
let getPointsRecordsMock = vi.fn()

vi.mock('@/api/points', () => ({
  getPoints: (...args: any[]) => getPointsMock(...args),
  getPointsRecords: (...args: any[]) => getPointsRecordsMock(...args),
}))

describe('PointsView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    getPointsMock = vi.fn(() => Promise.resolve({ points: 2580, grow_value: 5000 }))
    getPointsRecordsMock = vi.fn(() => Promise.resolve({
      data: [
        { id: 1, type: 1, points: 100, balance_before: 2400, balance_after: 2500, remark: '签到奖励', created_at: '2026-01-01 10:00:00' },
        { id: 2, type: 2, points: -50, balance_before: 2500, balance_after: 2450, remark: '积分兑换', created_at: '2026-01-02 14:30:00' },
        { id: 3, type: 4, points: 200, balance_before: 2300, balance_after: 2500, remark: '订单返积分', created_at: '2026-01-03 09:00:00' },
      ],
      meta: { total: 3, per_page: 20, current_page: 1, last_page: 1 },
    }))
  })

  function createWrapper() {
    return mount(PointsView)
  }

  it('renders points page', async () => {
    const wrapper = createWrapper()
    await flushPromises()
    expect(wrapper.find('.points-view').exists()).toBe(true)
    expect(wrapper.find('.page-title').text()).toBe('我的积分')
  })

  it('fetches points info on mount', async () => {
    createWrapper()
    await flushPromises()
    expect(getPointsMock).toHaveBeenCalledTimes(1)
  })

  it('displays points balance', async () => {
    const wrapper = createWrapper()
    await flushPromises()
    expect(wrapper.find('.points-value').text()).toContain('2580')
  })

  it('displays grow value', async () => {
    const wrapper = createWrapper()
    await flushPromises()
    expect(wrapper.text()).toContain('成长值')
    expect(wrapper.text()).toContain('5000')
  })

  it('loads points records on mount', async () => {
    createWrapper()
    await flushPromises()
    expect(getPointsRecordsMock).toHaveBeenCalledTimes(1)
  })

  it('renders points record list', async () => {
    const wrapper = createWrapper()
    await flushPromises()
    const rows = wrapper.findAll('.record-row')
    expect(rows.length).toBe(3)
  })

  it('shows record type name', async () => {
    const wrapper = createWrapper()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.typeName(1)).toBe('获得')
    expect(vm.typeName(2)).toBe('消耗')
    expect(vm.typeName(4)).toBe('返积分')
  })

  it('shows positive points with plus sign', async () => {
    const wrapper = createWrapper()
    await flushPromises()
    expect(wrapper.text()).toContain('+100')
  })

  it('shows negative points with minus sign', async () => {
    const wrapper = createWrapper()
    await flushPromises()
    expect(wrapper.text()).toContain('-50')
  })

  it('shows remark for each record', async () => {
    const wrapper = createWrapper()
    await flushPromises()
    expect(wrapper.text()).toContain('签到奖励')
    expect(wrapper.text()).toContain('积分兑换')
    expect(wrapper.text()).toContain('订单返积分')
  })

  it('shows empty state when no records', async () => {
    getPointsRecordsMock = vi.fn(() => Promise.resolve({
      data: [],
      meta: { total: 0, per_page: 20, current_page: 1, last_page: 1 },
    }))
    const wrapper = createWrapper()
    await flushPromises()
    expect(wrapper.find('.record-empty').exists()).toBe(true)
    expect(wrapper.text()).toContain('暂无积分记录')
  })

  it('filters records by type', async () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any
    await flushPromises()

    vm.recordType = 1
    await nextTick()
    await vm.loadRecords()
    await flushPromises()

    expect(getPointsRecordsMock).toHaveBeenLastCalledWith(expect.objectContaining({ type: 1 }))
  })

  it('exposes refs and methods', () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any
    expect(typeof vm.loadRecords).toBe('function')
    expect(typeof vm.typeName).toBe('function')
  })
})
