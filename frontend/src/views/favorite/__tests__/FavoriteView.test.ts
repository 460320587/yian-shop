import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import FavoriteView from '../FavoriteView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve('confirm')) },
    ElMessage: { success: vi.fn(), error: vi.fn() },
  }
})

const mockFavorites = [
  {
    id: 1,
    customer_id: 1,
    product_id: 1,
    remark: '喜欢这款',
    status: 1,
    product: { id: 1, name: '名片', thumbnail: '/img/card.jpg', price_min: 1000, price_max: 5000, category: { id: 1, name: '名片' } },
    created_at: '2026-01-01 10:00:00',
  },
  {
    id: 2,
    customer_id: 1,
    product_id: 2,
    remark: null,
    status: 1,
    product: { id: 2, name: '宣传单', thumbnail: null, price_min: 2000, price_max: 8000, category: { id: 2, name: '宣传单' } },
    created_at: '2026-01-02 10:00:00',
  },
]

let getFavoritesMock = vi.fn()
let removeFavoriteMock = vi.fn()

vi.mock('@/api/favorite', () => ({
  getFavorites: (...args: any[]) => getFavoritesMock(...args),
  removeFavorite: (...args: any[]) => removeFavoriteMock(...args),
}))

beforeEach(() => {
  getFavoritesMock = vi.fn(() => Promise.resolve({ data: mockFavorites, total: 2, current_page: 1, last_page: 1 }))
  removeFavoriteMock = vi.fn(() => Promise.resolve({}))
})

describe('FavoriteView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/favorites', name: 'Favorites', component: FavoriteView },
        { path: '/product/:id', name: 'ProductDetail', component: { template: '<div>Product</div>' } },
      ],
    })
    return mount(FavoriteView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders favorite page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.favorite-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('我的收藏')
  })

  it('loads favorites on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getFavoritesMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).favorites).toHaveLength(2)
  })

  it('displays product info in list', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('名片')
    expect(wrapper.text()).toContain('宣传单')
  })

  it('shows remark when available', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('喜欢这款')
  })

  it('removes favorite via handleRemove', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleRemove(mockFavorites[0])
    await flushPromises()
    expect(removeFavoriteMock).toHaveBeenCalledWith(1)
  })

  it('shows empty state when no favorites', async () => {
    getFavoritesMock = vi.fn(() => Promise.resolve({ data: [], total: 0, current_page: 1, last_page: 1 }))
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).favorites).toHaveLength(0)
    expect(wrapper.text()).toContain('暂无收藏商品')
  })

  it('navigates to product detail', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const router = (wrapper.vm as any).$router
    const pushSpy = vi.spyOn(router, 'push')
    ;(wrapper.vm as any).goToProduct(1)
    expect(pushSpy).toHaveBeenCalledWith('/product/1')
  })
})
