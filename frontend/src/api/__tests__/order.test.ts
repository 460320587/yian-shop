import { describe, it, expect, vi } from 'vitest'
import { getOrders, getOrderDetail, createOrder, cancelOrder, getOrderProductionSchedule } from '../order'

vi.mock('@/utils/request', () => ({
  get: vi.fn((url: string) => {
    if (url === '/orders') {
      return Promise.resolve({ data: [{ id: 1, order_no: 'Y202601010001', status: 11, customer_status: '待付款', total_amount: 5000, created_at: '2026-01-01' }], total: 1, current_page: 1, last_page: 1 })
    }
    if (url.includes('/production-schedule')) {
      return Promise.resolve({ data: [{ id: 1, order_id: 1, schedule_date: '2026-06-15', process_name: '印刷', status: 2, progress: 50, priority: 2, estimated_hours: 4.5, actual_hours: null, created_at: '2026-06-01' }] })
    }
    if (url.startsWith('/orders/')) {
      return Promise.resolve({ id: 1, order_no: 'Y202601010001', status: 11, customer_status: '待付款', total_amount: 5000, items: [], created_at: '2026-01-01' })
    }
    return Promise.resolve({})
  }),
  post: vi.fn((url: string) => {
    if (url === '/orders') {
      return Promise.resolve({ order_no: 'Y202601010002', total_amount: 3000, status: 11 })
    }
    return Promise.resolve({})
  }),
  put: vi.fn(() => Promise.resolve(null)),
}))

describe('Order API', () => {
  it('getOrders returns paginated orders', async () => {
    const res = await getOrders({ page: 1 })
    expect(res.data).toHaveLength(1)
    expect(res.data[0].order_no).toBe('Y202601010001')
  })

  it('getOrderDetail returns order info', async () => {
    const order = await getOrderDetail('Y202601010001')
    expect(order.order_no).toBe('Y202601010001')
  })

  it('createOrder creates new order', async () => {
    const res = await createOrder({ address_id: 1, items: [{ product_id: 1, quantity: 1 }] })
    expect(res.order_no).toBe('Y202601010002')
  })

  it('cancelOrder cancels order', async () => {
    const res = await cancelOrder('Y202601010001')
    expect(res).toBeNull()
  })

  it('getOrderProductionSchedule returns schedule list', async () => {
    const res = await getOrderProductionSchedule(1)
    expect(res.data).toHaveLength(1)
    expect(res.data[0].process_name).toBe('印刷')
    expect(res.data[0].progress).toBe(50)
  })
})
