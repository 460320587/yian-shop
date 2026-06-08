import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import CategoryManagementView from '../CategoryManagementView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve('confirm')) },
    ElMessage: { success: vi.fn(), error: vi.fn(), warning: vi.fn() },
  }
})

const mockCategories = [
  { id: 1, name: '名片', parent_id: 0, icon: '/img/card.jpg', sort: 0, status: 1, level: 1, path: '' },
  { id: 2, name: '宣传单页', parent_id: 0, icon: null, sort: 1, status: 1, level: 1, path: '' },
  { id: 3, name: '铜版纸名片', parent_id: 1, icon: null, sort: 0, status: 1, level: 2, path: '1' },
  { id: 4, name: '海报', parent_id: 0, icon: null, sort: 2, status: 0, level: 1, path: '' },
]

let getAdminCategoriesMock = vi.fn()
let createAdminCategoryMock = vi.fn()
let updateAdminCategoryMock = vi.fn()
let toggleAdminCategoryStatusMock = vi.fn()
let deleteAdminCategoryMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminCategories: (...args: any[]) => getAdminCategoriesMock(...args),
  createAdminCategory: (...args: any[]) => createAdminCategoryMock(...args),
  updateAdminCategory: (...args: any[]) => updateAdminCategoryMock(...args),
  toggleAdminCategoryStatus: (...args: any[]) => toggleAdminCategoryStatusMock(...args),
  deleteAdminCategory: (...args: any[]) => deleteAdminCategoryMock(...args),
}))

beforeEach(() => {
  getAdminCategoriesMock = vi.fn(() => Promise.resolve(mockCategories))
  createAdminCategoryMock = vi.fn((data) => Promise.resolve({
    id: 5, name: data.name, parent_id: data.parent_id ?? 0, icon: data.icon ?? null,
    sort: data.sort ?? 0, status: 1, level: data.parent_id ? 2 : 1, path: data.parent_id ? String(data.parent_id) : '',
  }))
  updateAdminCategoryMock = vi.fn(() => Promise.resolve({}))
  toggleAdminCategoryStatusMock = vi.fn(() => Promise.resolve({ status: 0 }))
  deleteAdminCategoryMock = vi.fn(() => Promise.resolve({}))
})

describe('CategoryManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(CategoryManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders category management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.category-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('商品分类管理')
  })

  it('loads categories on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminCategoriesMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).categories).toHaveLength(4)
  })

  it('filters by keyword', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).keyword = '名片'
    await flushPromises()
    const filtered = (wrapper.vm as any).filteredCategories
    expect(filtered.length).toBe(2)
    expect(filtered[0].name).toContain('名片')
  })

  it('filters by status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).statusFilter = 1
    await flushPromises()
    const filtered = (wrapper.vm as any).filteredCategories
    expect(filtered.every((c: any) => c.status === 1)).toBe(true)
  })

  it('opens create dialog', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openDialog()
    expect((wrapper.vm as any).dialogVisible).toBe(true)
    expect((wrapper.vm as any).isEdit).toBe(false)
    expect((wrapper.vm as any).form.name).toBe('')
  })

  it('creates new category', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openDialog()
    ;(wrapper.vm as any).form.name = '画册'
    ;(wrapper.vm as any).form.parent_id = 0
    ;(wrapper.vm as any).form.sort = 5
    await (wrapper.vm as any).submitForm()
    await flushPromises()
    expect(createAdminCategoryMock).toHaveBeenCalledWith(expect.objectContaining({ name: '画册', sort: 5 }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
  })

  it('opens edit dialog with category data', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openDialog(mockCategories[0])
    expect((wrapper.vm as any).dialogVisible).toBe(true)
    expect((wrapper.vm as any).isEdit).toBe(true)
    expect((wrapper.vm as any).form.name).toBe('名片')
  })

  it('updates category', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openDialog(mockCategories[0])
    ;(wrapper.vm as any).form.name = '名片定制'
    await (wrapper.vm as any).submitForm()
    await flushPromises()
    expect(updateAdminCategoryMock).toHaveBeenCalledWith(1, expect.objectContaining({ name: '名片定制' }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
  })

  it('toggles status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleToggleStatus(mockCategories[0])
    await flushPromises()
    expect(toggleAdminCategoryStatusMock).toHaveBeenCalledWith(1)
    expect(getAdminCategoriesMock).toHaveBeenCalledTimes(2)
  })

  it('deletes category', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleDelete(mockCategories[3])
    await flushPromises()
    expect(deleteAdminCategoryMock).toHaveBeenCalledWith(4)
    expect(getAdminCategoriesMock).toHaveBeenCalledTimes(2)
  })
})
