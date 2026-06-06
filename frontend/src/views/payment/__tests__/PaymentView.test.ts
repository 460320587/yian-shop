import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import PaymentView from '../PaymentView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessage: { success: vi.fn(), error: vi.fn(), info: vi.fn() },
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
  vi.useFakeTimers()
  createPaymentMock = vi.fn(() => Promise.resolve({
    payment_id: 1,
    payment_no: 'P202601010001',
    order_no: 'Y202601010001',
    amount: 50.00,
    gateway: 'wechat',
    status: 0,
    credential: { type: 'qrcode', qrcode_url: 'https://mock.qrcode/1' },
    paid_at: null,
    expire_at: new Date(Date.now() + 30 * 60 * 1000).toISOString(),
  }))
  getPaymentStatusMock = vi.fn(() => Promise.resolve({
    payment_id: 1,
    payment_no: 'P202601010001',
    order_no: 'Y202601010001',
    amount: 50.00,
    gateway: 'wechat',
    status: 0,
    credential: null,
    paid_at: null,
    expire_at: null,
  }))
  mockPaymentCallbackMock = vi.fn(() => Promise.resolve({}))
})

afterEach(() => {
  vi.useRealTimers()
})

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

describe('PaymentView', () => {
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

  it('shows error when missing query params', async () => {
    const wrapper = await mountComponent({})
    await flushPromises()
    expect((wrapper.vm as any).errorMsg).toContain('参数缺失')
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

  it('shows pay password input when wallet selected', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    expect(wrapper.find('.pay-password-input').exists()).toBe(false)
    ;(wrapper.vm as any).selectedGateway = 'wallet'
    await flushPromises()
    expect(wrapper.find('.pay-password-input').exists()).toBe(true)
  })

  it('hides pay password input when non-wallet selected', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).selectedGateway = 'wechat'
    await flushPromises()
    expect(wrapper.find('.pay-password-input').exists()).toBe(false)
    ;(wrapper.vm as any).selectedGateway = 'alipay'
    await flushPromises()
    expect(wrapper.find('.pay-password-input').exists()).toBe(false)
  })

  it('sends pay_password when wallet payment', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).selectedGateway = 'wallet'
    ;(wrapper.vm as any).payPassword = '123456'
    await flushPromises()
    await (wrapper.vm as any).createPayment()
    await flushPromises()
    expect(createPaymentMock).toHaveBeenCalledWith({
      order_no: 'Y202601010001',
      gateway: 'wallet',
      pay_password: '123456',
    })
  })

  it('auto-polls payment status every 3 seconds', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    await (wrapper.vm as any).createPayment()
    await flushPromises()

    expect(getPaymentStatusMock).toHaveBeenCalledTimes(0)

    vi.advanceTimersByTime(3000)
    await flushPromises()
    expect(getPaymentStatusMock).toHaveBeenCalledTimes(1)

    vi.advanceTimersByTime(3000)
    await flushPromises()
    expect(getPaymentStatusMock).toHaveBeenCalledTimes(2)
  })

  it('stops polling after payment success', async () => {
    let callCount = 0
    getPaymentStatusMock = vi.fn(() => {
      callCount++
      return Promise.resolve({
        payment_id: 1,
        payment_no: 'P202601010001',
        order_no: 'Y202601010001',
        amount: 50.00,
        gateway: 'wechat',
        status: callCount >= 2 ? 1 : 0,
        credential: null,
        paid_at: callCount >= 2 ? new Date().toISOString() : null,
        expire_at: null,
      })
    })

    const wrapper = await mountComponent()
    await flushPromises()
    await (wrapper.vm as any).createPayment()
    await flushPromises()

    vi.advanceTimersByTime(3000)
    await flushPromises()
    expect(getPaymentStatusMock).toHaveBeenCalledTimes(1)

    vi.advanceTimersByTime(3000)
    await flushPromises()
    expect(getPaymentStatusMock).toHaveBeenCalledTimes(2)

    // After success, should stop polling
    vi.advanceTimersByTime(3000)
    await flushPromises()
    expect(getPaymentStatusMock).toHaveBeenCalledTimes(2)
  })

  it('shows countdown timer', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    await (wrapper.vm as any).createPayment()
    await flushPromises()

    expect((wrapper.vm as any).countdownText).toContain(':')
    expect(wrapper.text()).toContain('支付剩余时间')
  })

  it('marks expired when countdown reaches zero', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    await (wrapper.vm as any).createPayment()
    await flushPromises()

    expect((wrapper.vm as any).isExpired).toBe(false)

    // Advance 31 minutes
    vi.advanceTimersByTime(31 * 60 * 1000)
    await flushPromises()

    expect((wrapper.vm as any).isExpired).toBe(true)
  })

  it('wallet payment auto-navigates on success', async () => {
    createPaymentMock = vi.fn(() => Promise.resolve({
      payment_id: 2,
      payment_no: 'P202601010002',
      order_no: 'Y202601010001',
      amount: 50.00,
      gateway: 'wallet',
      status: 1,
      paid_at: new Date().toISOString(),
      expire_at: null,
    }))

    const wrapper = await mountComponent()
    await flushPromises()
    const router = (wrapper.vm as any).$router
    const pushSpy = vi.spyOn(router, 'push')

    ;(wrapper.vm as any).selectedGateway = 'wallet'
    ;(wrapper.vm as any).payPassword = '123456'
    await (wrapper.vm as any).createPayment()
    await flushPromises()

    expect(createPaymentMock).toHaveBeenCalledWith(expect.objectContaining({
      gateway: 'wallet',
      pay_password: '123456',
    }))
    expect(pushSpy).toHaveBeenCalledWith('/orders')
  })

  it('simulates payment success via mock button', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    await (wrapper.vm as any).createPayment()
    await flushPromises()
    await (wrapper.vm as any).mockPaySuccess()
    await flushPromises()
    expect(mockPaymentCallbackMock).toHaveBeenCalledWith(1, { status: 'success' })
  })

  it('navigates to orders after mock payment success', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    const router = (wrapper.vm as any).$router
    const pushSpy = vi.spyOn(router, 'push')

    await (wrapper.vm as any).createPayment()
    await flushPromises()
    await (wrapper.vm as any).mockPaySuccess()
    await flushPromises()
    expect(pushSpy).toHaveBeenCalledWith('/orders')
  })

  it('cleans up timer on unmount', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    await (wrapper.vm as any).createPayment()
    await flushPromises()

    const clearIntervalSpy = vi.spyOn(global, 'clearInterval')
    wrapper.unmount()
    expect(clearIntervalSpy).toHaveBeenCalled()
  })
})
