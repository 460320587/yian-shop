import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import ProductListView from '../ProductListView.vue'

const mockProducts = [
  { id: 1, name: '名片', thumbnail: '', price_min: 1000, price_max: 5000, sales_count: 50 },
  { id: 2, name: '海报', thumbnail: '', price_min: 2000, price_max: 8000, sales_count: 20 },
]

let getProductsMock = vi.fn()

vi.mock('@/api/product', () => ({
  getProducts: (...args: any[]) => getProductsMock(...args),
}))

beforeEach(() => {
  getProductsMock = vi.fn(() => Promise.resolve({
    data: mockProducts,
    total: 2, current_page: 1, last_page: 1,
  }))
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

  it('loads products on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getProductsMock).toHaveBeenCalledTimes(1)
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
})
