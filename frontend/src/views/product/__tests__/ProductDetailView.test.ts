import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import ProductDetailView from '../ProductDetailView.vue'

let getProductDetailMock = vi.fn()

vi.mock('@/api/product', () => ({
  getProductDetail: (...args: any[]) => getProductDetailMock(...args),
}))

beforeEach(() => {
  getProductDetailMock = vi.fn(() => Promise.resolve({
    id: 1, name: '名片', code: 'CARD-001', description: '高质量名片',
    price_min: 1000, price_max: 5000, status: 1, sales_count: 50,
    category: { id: 1, name: '名片' },
  }))
})

describe('ProductDetailView', () => {
  function mountComponent() {
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(ProductDetailView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders product detail page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.product-detail-view').exists() || wrapper.find('.product-detail-page').exists()).toBe(true)
  })

  it('loads product detail on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getProductDetailMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).product?.name).toBe('名片')
  })
})
