import { describe, it, expect, vi } from 'vitest'
import { getProducts, getProductDetail, calculatePrice } from '../product'

vi.mock('@/utils/request', () => ({
  get: vi.fn((url: string) => {
    if (url === '/products') {
      return Promise.resolve({ data: [{ id: 1, name: '名片', thumbnail: '', price_min: 1000, price_max: 5000, sales_count: 50 }], total: 1, current_page: 1, last_page: 1 })
    }
    if (url === '/products/1') {
      return Promise.resolve({ id: 1, name: '名片', description: '优质名片', thumbnail: '', price_min: 1000, price_max: 5000, sales_count: 50, category: { id: 1, name: '名片' }, pricing_params: null })
    }
    return Promise.resolve({})
  }),
  post: vi.fn((url: string, data: any) => {
    if (url === '/products/1/price') {
      return Promise.resolve({
        product_id: 1,
        quantity: data.quantity,
        unit_price: 1.6,
        breakdown: { base_amount: 160, process_amount: 0, total_amount: 160 },
      })
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
    expect(product.pricing_params).toBeNull()
  })

  it('calculatePrice returns price result', async () => {
    const result = await calculatePrice(1, { quantity: 100, paper_id: 1, color_id: 2 })
    expect(result.unit_price).toBe(1.6)
    expect(result.breakdown.total_amount).toBe(160)
  })
})
