import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import InkCoverageCheckManagementView from '../InkCoverageCheckManagementView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve()) },
    ElMessage: { success: vi.fn(), error: vi.fn() },
  }
})

const mockChecks = [
  { id: 1, order_id: 5, file_id: 10, check_type: 1, ink_type: 'CMYK', coverage_c: 12.5, coverage_m: 34.2, coverage_y: 8.1, coverage_k: 45.0, total_coverage: 99.8, check_result: 1, checked_at: '2026-01-01T10:00:00Z' },
  { id: 2, order_id: 6, file_id: 11, check_type: 2, ink_type: '专色', coverage_c: null, coverage_m: null, coverage_y: null, coverage_k: null, total_coverage: 80.0, check_result: 0, checked_at: '2026-01-02T10:00:00Z' },
]

let getAdminInkCoverageChecksMock = vi.fn()
let createAdminInkCoverageCheckMock = vi.fn()
let updateAdminInkCoverageCheckMock = vi.fn()
let deleteAdminInkCoverageCheckMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminInkCoverageChecks: (...args: any[]) => getAdminInkCoverageChecksMock(...args),
  createAdminInkCoverageCheck: (...args: any[]) => createAdminInkCoverageCheckMock(...args),
  updateAdminInkCoverageCheck: (...args: any[]) => updateAdminInkCoverageCheckMock(...args),
  deleteAdminInkCoverageCheck: (...args: any[]) => deleteAdminInkCoverageCheckMock(...args),
}))

beforeEach(() => {
  getAdminInkCoverageChecksMock = vi.fn(() => Promise.resolve({ data: mockChecks, total: 2, current_page: 1, last_page: 1 }))
  createAdminInkCoverageCheckMock = vi.fn(() => Promise.resolve({ id: 3 }))
  updateAdminInkCoverageCheckMock = vi.fn(() => Promise.resolve({ id: 1 }))
  deleteAdminInkCoverageCheckMock = vi.fn(() => Promise.resolve({}))
})

describe('InkCoverageCheckManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(InkCoverageCheckManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders ink check management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.ink-check-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('印前检查管理')
  })

  it('loads checks on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminInkCoverageChecksMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).checks).toHaveLength(2)
    expect((wrapper.vm as any).checks[0].ink_type).toBe('CMYK')
  })

  it('opens create dialog', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openCreate()
    expect((wrapper.vm as any).dialogVisible).toBe(true)
    expect((wrapper.vm as any).isEdit).toBe(false)
    expect((wrapper.vm as any).form.order_id).toBe(null)
  })

  it('opens edit dialog with row data', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openEdit(mockChecks[0])
    expect((wrapper.vm as any).dialogVisible).toBe(true)
    expect((wrapper.vm as any).isEdit).toBe(true)
    expect((wrapper.vm as any).form.id).toBe(1)
    expect((wrapper.vm as any).form.ink_type).toBe('CMYK')
  })

  it('creates new check', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openCreate()
    ;(wrapper.vm as any).form.order_id = 7
    ;(wrapper.vm as any).form.file_id = 12
    ;(wrapper.vm as any).form.check_type = 1
    ;(wrapper.vm as any).handleSave()
    await flushPromises()
    expect(createAdminInkCoverageCheckMock).toHaveBeenCalledWith(expect.objectContaining({ order_id: 7, file_id: 12, check_type: 1 }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
  })

  it('updates existing check', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openEdit(mockChecks[0])
    ;(wrapper.vm as any).form.coverage_c = 20.0
    ;(wrapper.vm as any).handleSave()
    await flushPromises()
    expect(updateAdminInkCoverageCheckMock).toHaveBeenCalledWith(1, expect.objectContaining({ coverage_c: 20.0 }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
  })

  it('deletes check', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleDelete(mockChecks[0])
    await flushPromises()
    expect(deleteAdminInkCoverageCheckMock).toHaveBeenCalledWith(1)
  })

  it('result text helper returns correct values', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.resultText(1)).toBe('通过')
    expect(vm.resultText(0)).toBe('未通过')
    expect(vm.resultText(null)).toBe('-')
  })
})
