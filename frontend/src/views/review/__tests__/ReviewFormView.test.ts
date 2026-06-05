import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import ReviewFormView from '../ReviewFormView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessage: { success: vi.fn(), error: vi.fn() },
  }
})

let getProductDetailMock = vi.fn()
let submitReviewMock = vi.fn()

vi.mock('@/api/product', () => ({
  getProductDetail: (...args: any[]) => getProductDetailMock(...args),
}))

vi.mock('@/api/review', () => ({
  submitReview: (...args: any[]) => submitReviewMock(...args),
}))

beforeEach(() => {
  getProductDetailMock = vi.fn(() => Promise.resolve({
    id: 1, name: '名片', thumbnail: '/img/card.jpg', price_min: 1000, price_max: 5000,
  }))
  submitReviewMock = vi.fn(() => Promise.resolve({
    id: 10, product_id: 1, order_id: 5, rating: 5, content: '质量很好',
  }))
})

describe('ReviewFormView', () => {
  async function mountComponent(query = { orderId: '5', productId: '1' }) {
    const router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/review', name: 'ReviewForm', component: ReviewFormView },
        { path: '/orders', name: 'OrderList', component: { template: '<div>Orders</div>' } },
      ],
    })
    await router.push({ path: '/review', query })
    await router.isReady()
    return mount(ReviewFormView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders review form page', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    expect(wrapper.find('.review-form-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('商品评价')
  })

  it('loads product info on mount', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    expect(getProductDetailMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).product?.name).toBe('名片')
  })

  it('submits review with valid data', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form.rating = 5
    ;(wrapper.vm as any).form.content = '质量很好，印刷清晰'
    await (wrapper.vm as any).submitReview()
    await flushPromises()
    expect(submitReviewMock).toHaveBeenCalledWith(5, expect.objectContaining({
      product_id: 1,
      rating: 5,
      content: '质量很好，印刷清晰',
    }))
  })

  it('requires rating', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form.rating = 0
    ;(wrapper.vm as any).form.content = '测试内容'
    const valid = await (wrapper.vm as any).validateForm()
    expect(valid).toBe(false)
  })

  it('requires content minimum length', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form.rating = 5
    ;(wrapper.vm as any).form.content = '短'
    const valid = await (wrapper.vm as any).validateForm()
    expect(valid).toBe(false)
  })

  it('navigates to orders after submit', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    const router = (wrapper.vm as any).$router
    const pushSpy = vi.spyOn(router, 'push')
    ;(wrapper.vm as any).form.rating = 5
    ;(wrapper.vm as any).form.content = '质量很好，印刷清晰'
    await (wrapper.vm as any).submitReview()
    await flushPromises()
    expect(pushSpy).toHaveBeenCalledWith('/orders')
  })

  it('shows error when missing query params', async () => {
    const wrapper = await mountComponent({})
    await flushPromises()
    expect((wrapper.vm as any).errorMsg).toContain('参数缺失')
  })
})
