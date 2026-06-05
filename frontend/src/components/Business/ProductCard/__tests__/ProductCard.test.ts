import { describe, it, expect, vi } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import ProductCard from '../ProductCard.vue'

const routerPush = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: routerPush }),
}))

describe('ProductCard', () => {
  beforeEach(() => {
    routerPush.mockReset()
  })

  it('renders product name and price', () => {
    const wrapper = mount(ProductCard, {
      props: {
        id: 1,
        name: '企业名片',
        minPrice: 1500,
        maxPrice: 3000,
        salesCount: 1250,
        category: { id: 1, name: '名片' },
      },
    })
    expect(wrapper.text()).toContain('企业名片')
    expect(wrapper.text()).toContain('名片')
    expect(wrapper.text()).toContain('已售 1.3k')
  })

  it('shows placeholder when no thumbnail', () => {
    const wrapper = mount(ProductCard, {
      props: { id: 1, name: '海报' },
    })
    expect(wrapper.find('.image-placeholder').exists()).toBe(true)
    expect(wrapper.find('.image-placeholder').text()).toBe('海')
  })

  it('shows hot and new tags', () => {
    const wrapper = mount(ProductCard, {
      props: { id: 1, name: 'X', isHot: true, isNew: true },
    })
    expect(wrapper.find('.tag-hot').exists()).toBe(true)
    expect(wrapper.find('.tag-new').exists()).toBe(true)
  })

  it('navigates to product detail on click', async () => {
    const wrapper = mount(ProductCard, {
      props: { id: 42, name: 'X' },
      global: { stubs: { RouterLink: RouterLinkStub } },
    })
    await wrapper.trigger('click')
    expect(routerPush).toHaveBeenCalledWith('/product/42')
  })

  it('formats sales count', () => {
    const wrapper = mount(ProductCard, {
      props: { id: 1, name: 'X', salesCount: 15000 },
    })
    expect(wrapper.text()).toContain('已售 1.5万')
  })
})
