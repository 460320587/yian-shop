import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import ProductDetailView from '../ProductDetailView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessage: { success: vi.fn(), error: vi.fn(), warning: vi.fn() },
  }
})

let getProductDetailMock = vi.fn()
let getFavoritesMock = vi.fn()
let addFavoriteMock = vi.fn()
let removeFavoriteMock = vi.fn()
let getProductReviewsMock = vi.fn()
let calculatePriceMock = vi.fn()
let addToCartMock = vi.fn()

const mockPricingParams = {
  base_price: 250,
  unit: '本',
  price_tiers: [
    { min_qty: 100, price: 250 },
    { min_qty: 500, price: 200 },
  ],
  paper_options: [
    { id: 1, name: '128g铜版纸', price_factor: 0.85 },
    { id: 2, name: '157g铜版纸', price_factor: 1.00 },
  ],
  color_options: [
    { id: 1, name: '单色', price_factor: 0.35 },
    { id: 2, name: '四色', price_factor: 1.00 },
  ],
  process_options: [
    { id: 1, name: '覆膜', price: 60, unit: '㎡' },
    { id: 2, name: '烫金', price: 200, unit: '㎡' },
  ],
}

vi.mock('@/api/product', () => ({
  getProductDetail: (...args: any[]) => getProductDetailMock(...args),
  calculatePrice: (...args: any[]) => calculatePriceMock(...args),
}))

vi.mock('@/api/favorite', () => ({
  getFavorites: (...args: any[]) => getFavoritesMock(...args),
  addFavorite: (...args: any[]) => addFavoriteMock(...args),
  removeFavorite: (...args: any[]) => removeFavoriteMock(...args),
}))

vi.mock('@/api/review', () => ({
  getProductReviews: (...args: any[]) => getProductReviewsMock(...args),
}))

vi.mock('@/api/cart', () => ({
  addToCart: (...args: any[]) => addToCartMock(...args),
}))

beforeEach(() => {
  getProductDetailMock = vi.fn(() => Promise.resolve({
    id: 1, name: '名片', code: 'CARD-001', description: '高质量名片',
    price_min: 1000, price_max: 5000, status: 1, sales_count: 50,
    category: { id: 1, name: '名片' },
    pricing_params: mockPricingParams,
  }))
  getFavoritesMock = vi.fn(() => Promise.resolve({ data: [], total: 0, current_page: 1, last_page: 1 }))
  addFavoriteMock = vi.fn(() => Promise.resolve({ id: 10, product_id: 1, status: 1 }))
  removeFavoriteMock = vi.fn(() => Promise.resolve({}))
  getProductReviewsMock = vi.fn(() => Promise.resolve({
    data: [
      { id: 1, customer_id: 1, product_id: 1, rating: 5, content: '质量很好', images: null, reply: null, reply_at: null, created_at: '2026-01-01', customer: { id: 1, nickname: '张三' } },
      { id: 2, customer_id: 2, product_id: 1, rating: 4, content: '不错', images: null, reply: '感谢您的评价', reply_at: '2026-01-02', created_at: '2026-01-02', customer: { id: 2, nickname: null } },
    ],
    total: 2, current_page: 1, last_page: 1,
  }))
  calculatePriceMock = vi.fn(() => Promise.resolve({
    product_id: 1,
    quantity: 100,
    unit_price: 2.5,
    breakdown: { base_amount: 250, process_amount: 0, total_amount: 250 },
  }))
  addToCartMock = vi.fn(() => Promise.resolve({
    id: 1, product_id: 1, product_name: '名片', thumbnail: '', quantity: 100, unit_price: 2.5, subtotal: 250, selected: true,
  }))
})



describe('ProductDetailView', () => {
  async function mountComponent() {
    const router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/product/:id', name: 'ProductDetail', component: ProductDetailView },
      ],
    })
    await router.push('/product/1')
    await router.isReady()
    return mount(ProductDetailView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders product detail page', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    expect(wrapper.find('.product-detail-view').exists()).toBe(true)
  })

  it('loads product detail on mount', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    expect(getProductDetailMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).product?.name).toBe('名片')
  })

  it('checks favorite status on mount', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    expect(getFavoritesMock).toHaveBeenCalledTimes(1)
  })

  it('marks product as favorited when in favorite list', async () => {
    getFavoritesMock = vi.fn(() => Promise.resolve({
      data: [{ id: 5, product_id: 1, status: 1, product: { id: 1, name: '名片' } }],
      total: 1, current_page: 1, last_page: 1,
    }))
    const wrapper = await mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).isFavorited).toBe(true)
    expect((wrapper.vm as any).favoriteId).toBe(5)
  })

  it('adds favorite when not favorited', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    await (wrapper.vm as any).toggleFavorite()
    await flushPromises()
    expect(addFavoriteMock).toHaveBeenCalledWith({ product_id: 1 })
    expect((wrapper.vm as any).isFavorited).toBe(true)
  })

  it('removes favorite when already favorited', async () => {
    getFavoritesMock = vi.fn(() => Promise.resolve({
      data: [{ id: 5, product_id: 1, status: 1, product: { id: 1, name: '名片' } }],
      total: 1, current_page: 1, last_page: 1,
    }))
    const wrapper = await mountComponent()
    await flushPromises()
    await (wrapper.vm as any).toggleFavorite()
    await flushPromises()
    expect(removeFavoriteMock).toHaveBeenCalledWith(5)
    expect((wrapper.vm as any).isFavorited).toBe(false)
  })

  it('loads product reviews on mount', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    expect(getProductReviewsMock).toHaveBeenCalledWith(1, expect.anything())
    expect((wrapper.vm as any).reviews).toHaveLength(2)
  })

  it('displays review reply when available', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).reviews[1].reply).toBe('感谢您的评价')
  })

  // === 新增测试：参数模板 + 实时计价 ===

  it('renders pricing parameter form when pricing_params exists', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    expect(wrapper.find('.param-form-section').exists()).toBe(true)
    expect(wrapper.find('.paper-options').exists()).toBe(true)
    expect(wrapper.find('.color-options').exists()).toBe(true)
    expect(wrapper.find('.quantity-input').exists()).toBe(true)
    expect(wrapper.find('.process-options').exists()).toBe(true)
  })

  it('does not render param form when pricing_params is null', async () => {
    getProductDetailMock = vi.fn(() => Promise.resolve({
      id: 1, name: '名片', code: 'CARD-001', description: '暂无规格',
      price_min: 1000, price_max: 5000, status: 1, sales_count: 50,
      category: { id: 1, name: '名片' },
      pricing_params: null,
    }))
    const wrapper = await mountComponent()
    await flushPromises()
    expect(wrapper.find('.param-form-section').exists()).toBe(false)
    expect(wrapper.find('.no-params-tip').exists()).toBe(true)
  })

  it('calculates price when parameters change', async () => {
    const wrapper = await mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.selectedParams.paper_id = 2
    vm.selectedParams.color_id = 2
    vm.selectedParams.quantity = 100

    await vm.doCalculatePrice()
    await flushPromises()

    expect(calculatePriceMock).toHaveBeenCalledWith(1, expect.objectContaining({
      quantity: 100,
      paper_id: 2,
      color_id: 2,
      process_ids: [],
    }))
  })

  it('displays price breakdown after calculation', async () => {
    const wrapper = await mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.selectedParams.paper_id = 2
    vm.selectedParams.color_id = 2
    vm.selectedParams.quantity = 100

    await vm.doCalculatePrice()
    await flushPromises()

    expect(vm.priceResult).not.toBeNull()
    expect(vm.priceResult.unit_price).toBe(2.5)
    expect(wrapper.find('.price-section').exists()).toBe(true)
  })

  it('adds to cart with selected quantity', async () => {
    const wrapper = await mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.selectedParams.paper_id = 2
    vm.selectedParams.color_id = 2
    vm.selectedParams.quantity = 100

    await vm.doCalculatePrice()
    await flushPromises()

    await vm.handleAddToCart()
    await flushPromises()

    expect(addToCartMock).toHaveBeenCalledWith(expect.objectContaining({
      product_id: 1,
      quantity: 100,
    }))
  })

  it('shows error when adding to cart without calculating price', async () => {
    const wrapper = await mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.handleAddToCart()
    await flushPromises()

    expect(addToCartMock).not.toHaveBeenCalled()
  })
})
