import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import ProductListView from '../ProductListView.vue'

vi.mock('@/api/product', () => ({
  getProducts: vi.fn(() => Promise.resolve({
    data: [{ id: 1, name: '名片', thumbnail: '', price_min: 1000, price_max: 5000, sales_count: 50 }],
    total: 1, current_page: 1, last_page: 1,
  })),
}))

describe('ProductListView', () => {
  it('renders product list', async () => {
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(ProductListView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 100))
    expect(wrapper.find('.product-list-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('名片')
  })
})
