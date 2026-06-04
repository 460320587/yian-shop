import { describe, it, expect, vi } from 'vitest'
import { getProducts, getProductDetail } from '../product'

vi.mock('@/utils/request', () => ({
  get: vi.fn((url: string) => {
    if (url === '/products') {
      return Promise.resolve({ data: [{ id: 1, name: '名片', thumbnail: '', price_min: 1000, price_max: 5000, sales_count: 50 }], total: 1, current_page: 1, last_page: 1 })
    }
    if (url.startsWith('/products/')) {
      return Promise.resolve({ id: 1, name: '名片', description: '优质名片', thumbnail: '', price_min: 1000, price_max: 5000, sales_count: 50, category: { id: 1, name: '名片' }, specs: [], prices: [] })
    }
    return Promise.resolve({})
  }),
}))

describe('Product API', () => {
  it('getProducts returns paginated product list', async () => {
    const res = await getProducts({ page: 1 })
    expect(res.data).toHaveLength(1)
    expect(res.data[0].name).toBe('名片')
    expect(res.total).toBe(1)
  })

  it('getProductDetail returns product info', async () => {
    const product = await getProductDetail(1)
    expect(product.name).toBe('名片')
    expect(product.category.name).toBe('名片')
  })
})
