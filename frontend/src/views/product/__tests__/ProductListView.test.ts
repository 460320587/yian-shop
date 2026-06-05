import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import ProductListView from '../ProductListView.vue'

const mockProducts = [
  { id: 1, name: '名片', thumbnail: '', price_min: 1000, price_max: 5000, sales_count: 50, category: { id: 1, name: '名片印刷' } },
  { id: 2, name: '海报', thumbnail: '', price_min: 2000, price_max: 8000, sales_count: 20, category: { id: 2, name: '海报印刷' } },
]

const mockCategories = [
  { id: 1, name: '名片印刷', children: [] },
  { id: 2, name: '海报印刷', children: [] },
]

let getProductsMock = vi.fn()
let getCategoriesMock = vi.fn()

vi.mock('@/api/product', () => ({
  getProducts: (...args: any[]) => getProductsMock(...args),
}))

vi.mock('@/api/portal', () => ({
  getCategories: (...args: any[]) => getCategoriesMock(...args),
}))

beforeEach(() => {
  getProductsMock = vi.fn(() => Promise.resolve({
    data: mockProducts,
    total: 2, current_page: 1, last_page: 1,
  }))
  getCategoriesMock = vi.fn(() => Promise.resolve(mockCategories))
})

describe('ProductListView', () => {
  function mountComponent() {
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(ProductListView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders product list page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.product-list-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('全部商品')
  })

  it('loads products and categories on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getProductsMock).toHaveBeenCalledTimes(1)
    expect(getCategoriesMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).products).toHaveLength(2)
    expect((wrapper.vm as any).products[0].name).toBe('名片')
  })

  it('passes page parameter', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getProductsMock).toHaveBeenCalledWith(expect.objectContaining({ page: 1 }))
  })

  it('handles empty product list', async () => {
    getProductsMock = vi.fn(() => Promise.resolve({ data: [], total: 0, current_page: 1, last_page: 1 }))
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).products).toHaveLength(0)
  })

  it('searches by keyword', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.filters.keyword = '名片'
    await vm.handleSearch()
    await flushPromises()

    expect(getProductsMock).toHaveBeenLastCalledWith(expect.objectContaining({ keyword: '名片', page: 1 }))
  })

  it('filters by category', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.filters.category_id = 1
    await vm.handleSearch()
    await flushPromises()

    expect(getProductsMock).toHaveBeenLastCalledWith(expect.objectContaining({ category_id: 1, page: 1 }))
  })

  it('filters by price range', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.filters.min_price = 100
    vm.filters.max_price = 500
    await vm.handleSearch()
    await flushPromises()

    expect(getProductsMock).toHaveBeenLastCalledWith(expect.objectContaining({ min_price: 100, max_price: 500, page: 1 }))
  })

  it('sorts products', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.filters.sort = 'price_asc'
    await vm.handleSearch()
    await flushPromises()

    expect(getProductsMock).toHaveBeenLastCalledWith(expect.objectContaining({ sort: 'price_asc', page: 1 }))
  })

  it('resets filters', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.filters.keyword = '测试'
    vm.filters.category_id = 1
    vm.filters.min_price = 100
    vm.filters.max_price = 500
    vm.filters.sort = 'price_desc'
    await vm.resetFilters()
    await flushPromises()

    expect(vm.filters.keyword).toBe('')
    expect(vm.filters.category_id).toBeNull()
    expect(vm.filters.min_price).toBeNull()
    expect(vm.filters.max_price).toBeNull()
    expect(vm.filters.sort).toBe('default')
    expect(getProductsMock).toHaveBeenLastCalledWith(expect.objectContaining({ page: 1 }))
  })

  it('paginates results', async () => {
    getProductsMock = vi.fn(() => Promise.resolve({
      data: mockProducts,
      total: 20, current_page: 1, last_page: 2,
    }))
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.handlePageChange(2)
    await flushPromises()

    expect(getProductsMock).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2 }))
  })
})
