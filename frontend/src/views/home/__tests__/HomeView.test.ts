import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../HomeView.vue'

vi.mock('@/api/portal', () => ({
  getHome: vi.fn(() => Promise.resolve({
    banners: [{ id: 1, title: 'Banner1', image: 'img.jpg', image_mobile: '', link_type: 1, link_target: '', sort: 1 }],
    announcements: [{ id: 1, title: '公告', type: 1, is_popup: 0 }],
    hot_products: [{ id: 1, name: '名片', thumbnail: '', min_price: 1000, max_price: 2000, sales_count: 50, is_hot: 1, is_new: 0, category: { id: 1, name: '名片' } }],
    new_arrivals: [{ id: 2, name: '海报', thumbnail: '', min_price: 500, max_price: 1500, sales_count: 10, is_hot: 0, is_new: 1, category: { id: 2, name: '海报' } }],
  })),
  getCategories: vi.fn(() => Promise.resolve([{ id: 1, name: '名片', children: [] }])),
}))

describe('HomeView', () => {
  it('renders home page and loads data', async () => {
    const router = createRouter({ history: createWebHistory(), routes: [{ path: '/', component: { template: '<div />' } }] })
    const wrapper = mount(HomeView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 100))
    expect(wrapper.find('.home-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('Banner1')
    expect(wrapper.text()).toContain('名片')
  })
})
