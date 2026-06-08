import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import ProductionScheduleManagementView from '../ProductionScheduleManagementView.vue'

const mockSchedules = [
  { id: 1, order_id: 100, order: { id: 100, order_no: 'Y202601010001' }, schedule_date: '2026-01-05', process_name: '印刷', status: 2, priority: 3, estimated_hours: 4, actual_hours: 3, progress: 50, delay_reason: null, created_at: '2026-01-01 10:00:00' },
  { id: 2, order_id: 101, order: { id: 101, order_no: 'Y202601010002' }, schedule_date: '2026-01-06', process_name: '覆膜', status: 0, priority: 2, estimated_hours: 2, actual_hours: null, progress: 0, delay_reason: null, created_at: '2026-01-02 14:30:00' },
]

let getAdminProductionSchedulesMock = vi.fn()
let getAdminProductionScheduleDetailMock = vi.fn()
let createAdminProductionScheduleMock = vi.fn()
let updateAdminProductionScheduleMock = vi.fn()
let updateAdminProductionScheduleProgressMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminProductionSchedules: (...args: any[]) => getAdminProductionSchedulesMock(...args),
  getAdminProductionScheduleDetail: (...args: any[]) => getAdminProductionScheduleDetailMock(...args),
  createAdminProductionSchedule: (...args: any[]) => createAdminProductionScheduleMock(...args),
  updateAdminProductionSchedule: (...args: any[]) => updateAdminProductionScheduleMock(...args),
  updateAdminProductionScheduleProgress: (...args: any[]) => updateAdminProductionScheduleProgressMock(...args),
}))

beforeEach(() => {
  getAdminProductionSchedulesMock = vi.fn(() => Promise.resolve({ data: mockSchedules, total: 2, current_page: 1, last_page: 1 }))
  getAdminProductionScheduleDetailMock = vi.fn(() => Promise.resolve(mockSchedules[0]))
  createAdminProductionScheduleMock = vi.fn(() => Promise.resolve({ id: 3, order_id: 102, schedule_date: '2026-01-07', process_name: '裁切', status: 0, priority: 1, estimated_hours: 1, progress: 0 }))
  updateAdminProductionScheduleMock = vi.fn(() => Promise.resolve({ ...mockSchedules[0], process_name: '裁切', priority: 1 }))
  updateAdminProductionScheduleProgressMock = vi.fn(() => Promise.resolve({ ...mockSchedules[0], progress: 75, actual_hours: 3.5 }))
})

describe('ProductionScheduleManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(ProductionScheduleManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders production schedule management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.schedule-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('生产排期')
  })

  it('loads schedules on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminProductionSchedulesMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).schedules).toHaveLength(2)
  })

  it('filters schedules by status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).statusFilter = 2
    ;(wrapper.vm as any).onSearch()
    await flushPromises()
    expect(getAdminProductionSchedulesMock).toHaveBeenLastCalledWith(expect.objectContaining({ status: 2, page: 1, per_page: 10 }))
  })

  it('filters schedules by process name', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).keyword = '印刷'
    ;(wrapper.vm as any).onSearch()
    await flushPromises()
    expect(getAdminProductionSchedulesMock).toHaveBeenLastCalledWith(expect.objectContaining({ process_name: '印刷', page: 1 }))
  })

  it('opens detail drawer', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openDetail(mockSchedules[0])
    await flushPromises()
    expect(getAdminProductionScheduleDetailMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).detailVisible).toBe(true)
  })

  it('creates new schedule', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openCreate()
    ;(wrapper.vm as any).form.order_id = 102
    ;(wrapper.vm as any).form.schedule_date = '2026-01-07'
    ;(wrapper.vm as any).form.process_name = '裁切'
    ;(wrapper.vm as any).form.priority = 1
    ;(wrapper.vm as any).form.estimated_hours = 1
    await (wrapper.vm as any).handleSave()
    await flushPromises()
    expect(createAdminProductionScheduleMock).toHaveBeenCalledWith(expect.objectContaining({ order_id: 102, process_name: '裁切' }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
  })

  it('opens edit dialog and loads schedule data', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openEdit(mockSchedules[0])
    expect((wrapper.vm as any).isEdit).toBe(true)
    expect((wrapper.vm as any).form.process_name).toBe('印刷')
    expect((wrapper.vm as any).dialogVisible).toBe(true)
  })

  it('updates existing schedule', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openEdit(mockSchedules[0])
    ;(wrapper.vm as any).form.process_name = '裁切'
    await (wrapper.vm as any).handleSave()
    await flushPromises()
    expect(updateAdminProductionScheduleMock).toHaveBeenCalledWith(1, expect.objectContaining({ process_name: '裁切' }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
    expect(getAdminProductionSchedulesMock).toHaveBeenCalledTimes(2)
  })

  it('updates schedule progress', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openProgressDialog(mockSchedules[0])
    ;(wrapper.vm as any).progressForm.progress = 75
    ;(wrapper.vm as any).progressForm.actual_hours = 3.5
    await (wrapper.vm as any).handleSaveProgress()
    await flushPromises()
    expect(updateAdminProductionScheduleProgressMock).toHaveBeenCalledWith(1, { progress: 75, actual_hours: 3.5 })
    expect((wrapper.vm as any).progressDialogVisible).toBe(false)
    expect(getAdminProductionSchedulesMock).toHaveBeenCalledTimes(2)
  })

  it('handles pagination change', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).currentPage = 2
    ;(wrapper.vm as any).loadList()
    await flushPromises()
    expect(getAdminProductionSchedulesMock).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2, per_page: 10 }))
  })
})
