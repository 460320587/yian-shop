import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import ProductManagementView from '../ProductManagementView.vue'

vi.mock('@/api/admin', () => ({
  getAdminProducts: vi.fn(() => Promise.resolve({
    data: [{ id: 1, name: '名片', code: 'CARD-001', price_min: 1000, price_max: 5000, status: 1, category: { id: 1, name: '名片' } }],
    total: 1, current_page: 1, last_page: 1,
  })),
  createAdminProduct: vi.fn(() => Promise.resolve({ id: 2, name: '新产品', code: 'NEW-001', price_min: 1000, price_max: 5000, status: 1 })),
  toggleProductStatus: vi.fn(() => Promise.resolve({ status: 0 })),
}))

describe('ProductManagementView', () => {
  it('renders product list', async () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(ProductManagementView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 200))
    expect(wrapper.find('.product-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('商品管理')
  })
})
