import { describe, it, expect, vi } from 'vitest'
import { getHome, getCategories } from '../portal'

vi.mock('@/utils/request', () => ({
  get: vi.fn((url: string) => {
    if (url === '/portal/home') {
      return Promise.resolve({
        banners: [{ id: 1, title: 'Banner1', image: 'img.jpg', image_mobile: '', link_type: 1, link_target: '', sort: 1 }],
        announcements: [{ id: 1, title: '公告', type: 1, is_popup: 0 }],
        hot_products: [{ id: 1, name: '商品1', thumbnail: '', min_price: 10, max_price: 20, sales_count: 100, is_hot: 1, is_new: 0, category: { id: 1, name: '分类' } }],
        new_arrivals: [],
      })
    }
    if (url === '/categories') {
      return Promise.resolve([{ id: 1, name: '分类1', children: [] }])
    }
    return Promise.resolve({})
  }),
}))

describe('Portal API', () => {
  it('getHome returns home data', async () => {
    const data = await getHome()
    expect(data.banners).toHaveLength(1)
    expect(data.banners[0].title).toBe('Banner1')
    expect(data.hot_products).toHaveLength(1)
    expect(data.new_arrivals).toEqual([])
  })

  it('getCategories returns category list', async () => {
    const data = await getCategories()
    expect(Array.isArray(data)).toBe(true)
    expect(data[0].name).toBe('分类1')
  })
})
