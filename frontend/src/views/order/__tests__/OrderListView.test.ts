import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import OrderListView from '../OrderListView.vue'

let getOrdersMock = vi.fn()

vi.mock('@/api/order', () => ({
  getOrders: (...args: any[]) => getOrdersMock(...args),
}))

beforeEach(() => {
  getOrdersMock = vi.fn(() => Promise.resolve({
    data: [
      { id: 1, order_no: 'Y202601010001', total_amount: 5000, status: 11, customer_status: '待付款', created_at: '2026-01-01' },
      { id: 2, order_no: 'Y202601010002', total_amount: 8000, status: 1, customer_status: '已提交', created_at: '2026-01-02' },
      { id: 3, order_no: 'Y202601010003', total_amount: 12000, status: 60, customer_status: '已完成', created_at: '2026-01-03' },
    ],
    total: 3, current_page: 1, last_page: 1,
  }))
})

describe('OrderListView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(OrderListView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders order list page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.order-list-view').exists()).toBe(true)
  })

  it('loads orders on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getOrdersMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).orders).toHaveLength(3)
  })

  it('shows pay button for payable orders', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).canPay({ status: 11 })).toBe(true)
    expect((wrapper.vm as any).canPay({ status: 1 })).toBe(true)
    expect((wrapper.vm as any).canPay({ status: 60 })).toBe(false)
  })

  it('shows review button for completed orders', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).canReview({ status: 60 })).toBe(true)
    expect((wrapper.vm as any).canReview({ status: 11 })).toBe(false)
  })

  it('navigates to payment page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const router = (wrapper.vm as any).$router
    const pushSpy = vi.spyOn(router, 'push')
    ;(wrapper.vm as any).goToPay({ id: 1, order_no: 'Y202601010001', total_amount: 5000 })
    expect(pushSpy).toHaveBeenCalledWith('/payment?orderNo=Y202601010001&amount=5000')
  })
})
