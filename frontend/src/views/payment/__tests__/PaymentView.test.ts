import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import PaymentView from '../PaymentView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessage: { success: vi.fn(), error: vi.fn() },
  }
})

let createPaymentMock = vi.fn()
let getPaymentStatusMock = vi.fn()
let mockPaymentCallbackMock = vi.fn()

vi.mock('@/api/payment', () => ({
  createPayment: (...args: any[]) => createPaymentMock(...args),
  getPaymentStatus: (...args: any[]) => getPaymentStatusMock(...args),
  mockPaymentCallback: (...args: any[]) => mockPaymentCallbackMock(...args),
}))

beforeEach(() => {
  createPaymentMock = vi.fn(() => Promise.resolve({
    payment_id: 1,
    payment_no: 'P202601010001',
    order_no: 'Y202601010001',
    amount: 50.00,
    gateway: 'wechat',
    status: 0,
    credential: { type: 'qrcode', qrcode_url: 'https://mock.qrcode/1' },
    paid_at: null,
    expire_at: '2026-01-01 12:00:00',
  }))
  getPaymentStatusMock = vi.fn(() => Promise.resolve({
    payment_id: 1,
    payment_no: 'P202601010001',
    order_no: 'Y202601010001',
    amount: 50.00,
    gateway: 'wechat',
    status: 1,
    credential: null,
    paid_at: '2026-01-01 10:05:00',
    expire_at: null,
  }))
  mockPaymentCallbackMock = vi.fn(() => Promise.resolve({}))
})

describe('PaymentView', () => {
  async function mountComponent(query = { orderNo: 'Y202601010001', amount: '5000' }) {
    setActivePinia(createPinia())
    const router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/payment', name: 'Payment', component: PaymentView },
        { path: '/orders', name: 'OrderList', component: { template: '<div>Orders</div>' } },
      ],
    })
    await router.push({ path: '/payment', query })
    await router.isReady()
    return mount(PaymentView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders payment page', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    expect(wrapper.find('.payment-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('订单支付')
  })

  it('displays order info from query', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).orderNo).toBe('Y202601010001')
    expect((wrapper.vm as any).amount).toBe(50.00)
  })

  it('creates payment with selected gateway', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).selectedGateway = 'alipay'
    await (wrapper.vm as any).createPayment()
    await flushPromises()
    expect(createPaymentMock).toHaveBeenCalledWith({
      order_no: 'Y202601010001',
      gateway: 'alipay',
    })
    expect((wrapper.vm as any).paymentData).not.toBeNull()
  })

  it('simulates payment success', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).selectedGateway = 'wechat'
    await (wrapper.vm as any).createPayment()
    await flushPromises()
    await (wrapper.vm as any).mockPaySuccess()
    await flushPromises()
    expect(mockPaymentCallbackMock).toHaveBeenCalledWith(1, { status: 'success' })
  })

  it('navigates to orders after payment success', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    const router = (wrapper.vm as any).$router
    const pushSpy = vi.spyOn(router, 'push')
    ;(wrapper.vm as any).selectedGateway = 'wechat'
    await (wrapper.vm as any).createPayment()
    await flushPromises()
    await (wrapper.vm as any).mockPaySuccess()
    await flushPromises()
    expect(pushSpy).toHaveBeenCalledWith('/orders')
  })

  it('shows error when missing query params', async () => {
    const wrapper = await mountComponent({})
    await flushPromises()
    expect((wrapper.vm as any).errorMsg).toContain('参数缺失')
  })

  it('wallet payment creates success directly', async () => {
    createPaymentMock = vi.fn(() => Promise.resolve({
      payment_id: 2,
      payment_no: 'P202601010002',
      order_no: 'Y202601010001',
      amount: 50.00,
      gateway: 'wallet',
      status: 1,
      paid_at: '2026-01-01 10:00:00',
      expire_at: null,
    }))
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).selectedGateway = 'wallet'
    await (wrapper.vm as any).createPayment()
    await flushPromises()
    expect((wrapper.vm as any).paymentData?.status).toBe(1)
  })
})
