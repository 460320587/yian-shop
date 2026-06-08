import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import ProductManagementView from '../ProductManagementView.vue'

const mockProducts = [
  { id: 1, name: '名片', code: 'CARD-001', price_min: 1000, price_max: 5000, status: 1, category: { id: 1, name: '名片' } },
  { id: 2, name: '海报', code: 'POSTER-001', price_min: 2000, price_max: 8000, status: 0, category: { id: 2, name: '海报' } },
]

let getAdminProductsMock = vi.fn()
let createAdminProductMock = vi.fn()
let updateAdminProductMock = vi.fn()
let getAdminProductDetailMock = vi.fn()
let toggleProductStatusMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminProducts: (...args: any[]) => getAdminProductsMock(...args),
  createAdminProduct: (...args: any[]) => createAdminProductMock(...args),
  updateAdminProduct: (...args: any[]) => updateAdminProductMock(...args),
  getAdminProductDetail: (...args: any[]) => getAdminProductDetailMock(...args),
  toggleProductStatus: (...args: any[]) => toggleProductStatusMock(...args),
}))

beforeEach(() => {
  getAdminProductsMock = vi.fn(() => Promise.resolve({ data: mockProducts, total: 2, current_page: 1, last_page: 1 }))
  createAdminProductMock = vi.fn(() => Promise.resolve({ id: 3, name: '新产品', code: 'NEW-001', price_min: 1000, price_max: 5000, status: 1 }))
  updateAdminProductMock = vi.fn(() => Promise.resolve({ id: 1, name: '名片-改', code: 'CARD-001', price_min: 1500, price_max: 5500, status: 1 }))
  getAdminProductDetailMock = vi.fn(() => Promise.resolve(mockProducts[0]))
  toggleProductStatusMock = vi.fn(() => Promise.resolve({ status: 0 }))
})

describe('ProductManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(ProductManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders product management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.product-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('商品管理')
  })

  it('loads products on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminProductsMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).products).toHaveLength(2)
    expect((wrapper.vm as any).products[0].name).toBe('名片')
    expect((wrapper.vm as any).total).toBe(2)
  })

  it('filters products by keyword', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).keyword = '名片'
    ;(wrapper.vm as any).onSearch()
    await flushPromises()
    expect(getAdminProductsMock).toHaveBeenLastCalledWith(expect.objectContaining({ keyword: '名片', page: 1, per_page: 10 }))
  })

  it('toggles product status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleToggle(mockProducts[0])
    await flushPromises()
    expect(toggleProductStatusMock).toHaveBeenCalledWith(1)
  })

  it('creates new product', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form.name = '新产品'
    ;(wrapper.vm as any).form.code = 'NEW-001'
    ;(wrapper.vm as any).form.price_min = 1000
    ;(wrapper.vm as any).form.price_max = 5000
    ;(wrapper.vm as any).handleSave()
    await flushPromises()
    expect(createAdminProductMock).toHaveBeenCalledWith(expect.objectContaining({ name: '新产品', code: 'NEW-001' }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
  })

  it('opens edit dialog and loads product detail', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openEdit(mockProducts[0])
    await flushPromises()
    expect(getAdminProductDetailMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).dialogVisible).toBe(true)
    expect((wrapper.vm as any).isEdit).toBe(true)
    expect((wrapper.vm as any).form.name).toBe('名片')
  })

  it('updates existing product', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openEdit(mockProducts[0])
    await flushPromises()
    ;(wrapper.vm as any).form.name = '名片-改'
    ;(wrapper.vm as any).form.price_min = 1500
    ;(wrapper.vm as any).handleSave()
    await flushPromises()
    expect(updateAdminProductMock).toHaveBeenCalledWith(1, expect.objectContaining({ name: '名片-改', price_min: 1500 }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
    expect(getAdminProductsMock).toHaveBeenCalledTimes(2)
  })
})
