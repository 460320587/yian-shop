import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import MyReviewsView from '../MyReviewsView.vue'

let getMyReviewsMock = vi.fn()

vi.mock('@/api/review', () => ({
  getMyReviews: (...args: any[]) => getMyReviewsMock(...args),
}))

beforeEach(() => {
  getMyReviewsMock = vi.fn(() => Promise.resolve({
    data: [
      { id: 1, product_id: 1, rating: 5, content: '质量很好', images: ['/img/review1.jpg', '/img/review2.jpg'], reply: null, reply_at: null, created_at: '2026-01-01', product: { id: 1, name: '名片', thumbnail: '/img/card.jpg' } },
      { id: 2, product_id: 2, rating: 4, content: '不错', images: null, reply: '感谢您的评价', reply_at: '2026-01-02', created_at: '2026-01-02', product: { id: 2, name: '宣传单', thumbnail: null } },
    ],
    total: 2, current_page: 1, last_page: 1,
  }))
})

describe('MyReviewsView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/my-reviews', name: 'MyReviews', component: MyReviewsView },
      ],
    })
    return mount(MyReviewsView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders my reviews page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.my-reviews-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('我的评价')
  })

  it('loads reviews on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getMyReviewsMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).reviews).toHaveLength(2)
  })

  it('shows product info for each review', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('名片')
    expect(wrapper.text()).toContain('宣传单')
  })

  it('shows reply when available', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('感谢您的评价')
  })

  it('shows review images when available', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const imgs = wrapper.findAll('.review-images img')
    expect(imgs.length).toBe(2)
    expect(imgs[0].attributes('src')).toBe('/img/review1.jpg')
    expect(imgs[1].attributes('src')).toBe('/img/review2.jpg')
  })

  it('does not show image section when images is null', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const reviewItems = wrapper.findAll('.review-item')
    const secondReviewImages = reviewItems[1].find('.review-images')
    expect(secondReviewImages.exists()).toBe(false)
  })
})
