import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import ProductDetailView from '../ProductDetailView.vue'

vi.mock('@/api/product', () => ({
  getProductDetail: vi.fn(() => Promise.resolve({
    id: 1, name: '名片', description: '优质名片', thumbnail: '', price_min: 1000, price_max: 5000, sales_count: 50, category: { id: 1, name: '名片' }, specs: [], prices: [],
  })),
}))

describe('ProductDetailView', () => {
  it('renders product detail', async () => {
    const router = createRouter({ history: createWebHistory(), routes: [{ path: '/product/:id', component: { template: '<div />' } }] })
    await router.push('/product/1')
    const wrapper = mount(ProductDetailView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 100))
    expect(wrapper.find('.product-detail-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('名片')
  })
})
