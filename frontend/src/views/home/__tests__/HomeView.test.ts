import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../HomeView.vue'

const mockHomeData = {
  banners: [{ id: 1, title: 'Banner1', image: 'img.jpg', image_mobile: '', link_type: 1, link_target: '', sort: 1 }],
  announcements: [{ id: 1, title: '公告', type: 1, is_popup: 0 }],
  hot_products: [
    { id: 1, name: '名片', thumbnail: '', min_price: 1000, max_price: 2000, sales_count: 50, is_hot: 1, is_new: 0, category: { id: 1, name: '名片' } },
  ],
  new_arrivals: [
    { id: 2, name: '海报', thumbnail: '', min_price: 500, max_price: 1500, sales_count: 10, is_hot: 0, is_new: 1, category: { id: 2, name: '海报' } },
  ],
}

const mockCategories = [{ id: 1, name: '名片', children: [] }]

let getHomeMock = vi.fn()
let getCategoriesMock = vi.fn()

vi.mock('@/api/portal', () => ({
  getHome: () => getHomeMock(),
  getCategories: () => getCategoriesMock(),
}))

beforeEach(() => {
  getHomeMock = vi.fn(() => Promise.resolve(mockHomeData))
  getCategoriesMock = vi.fn(() => Promise.resolve(mockCategories))
})

describe('HomeView', () => {
  function mountComponent() {
    const router = createRouter({ history: createWebHistory(), routes: [{ path: '/', component: { template: '<div />' } }] })
    return mount(HomeView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders home page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.home-view').exists()).toBe(true)
  })

  it('loads banners on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getHomeMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).banners).toHaveLength(1)
    expect((wrapper.vm as any).banners[0].title).toBe('Banner1')
  })

  it('loads categories on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getCategoriesMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).categories).toHaveLength(1)
    expect((wrapper.vm as any).categories[0].name).toBe('名片')
  })

  it('loads hot products', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).hotProducts).toHaveLength(1)
    expect((wrapper.vm as any).hotProducts[0].name).toBe('名片')
  })

  it('loads new arrivals', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).newArrivals).toHaveLength(1)
    expect((wrapper.vm as any).newArrivals[0].name).toBe('海报')
  })

  it('handles empty data gracefully', async () => {
    getHomeMock = vi.fn(() => Promise.resolve({ banners: [], hot_products: [], new_arrivals: [] }))
    getCategoriesMock = vi.fn(() => Promise.resolve([]))
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).banners).toHaveLength(0)
    expect((wrapper.vm as any).categories).toHaveLength(0)
    expect((wrapper.vm as any).hotProducts).toHaveLength(0)
  })
})
