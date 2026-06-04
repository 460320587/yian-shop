import { describe, it, expect, vi } from 'vitest'
import { getCoupons, claimCoupon, getMyCoupons } from '../coupon'

vi.mock('@/utils/request', () => ({
  get: vi.fn((url: string) => {
    if (url === '/coupons') {
      return Promise.resolve({ data: [{ id: 1, name: '满减券', type: 1, value: 2000, min_amount: 10000, status: 1 }], total: 1, current_page: 1, last_page: 1 })
    }
    if (url === '/my-coupons') {
      return Promise.resolve({ data: [{ id: 1, code: 'SAVE20', coupon_id: 1, status: 1, coupon: { name: '满减券', type: 1, value: 2000 } }], total: 1, current_page: 1, last_page: 1 })
    }
    return Promise.resolve({})
  }),
  post: vi.fn((url: string) => {
    if (url === '/coupons/1/claim') {
      return Promise.resolve({ id: 2, code: 'SAVE20', status: 1, coupon: { name: '满减券' } })
    }
    return Promise.resolve({})
  }),
}))

describe('Coupon API', () => {
  it('getCoupons returns available coupons', async () => {
    const res = await getCoupons()
    expect(res.data).toHaveLength(1)
    expect(res.data[0].name).toBe('满减券')
  })

  it('claimCoupon claims a coupon', async () => {
    const res = await claimCoupon(1)
    expect(res.code).toBe('SAVE20')
    expect(res.status).toBe(1)
  })

  it('getMyCoupons returns my coupons', async () => {
    const res = await getMyCoupons(1)
    expect(res.data).toHaveLength(1)
    expect(res.data[0].coupon.name).toBe('满减券')
  })
})
