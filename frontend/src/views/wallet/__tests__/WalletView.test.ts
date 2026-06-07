import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { nextTick } from 'vue'
import WalletView from '../WalletView.vue'

let getTransactionsMock = vi.fn()
let getPaymentStatusMock = vi.fn()

const mockUser = {
  id: 1,
  phone: '13800138000',
  nickname: '测试用户',
  avatar: null,
  type: 1,
  auth_status: 1,
  vip_level: 2,
  balance: 1234.56,
}

let rechargeMock = vi.fn()
let withdrawMock = vi.fn()

vi.mock('@/api/wallet', () => ({
  recharge: (...args: any[]) => rechargeMock(...args),
  withdraw: (...args: any[]) => withdrawMock(...args),
  getWalletTransactions: (...args: any[]) => getTransactionsMock(...args),
}))

vi.mock('@/api/payment', () => ({
  getPaymentStatus: (...args: any[]) => getPaymentStatusMock(...args),
}))

vi.mock('@/stores/user', () => ({
  useUserStore: () => ({
    userInfo: mockUser,
    fetchUserInfo: vi.fn(() => Promise.resolve()),
  }),
}))

vi.mock('element-plus', () => ({
  ElMessage: {
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
  },
}))

function createWrapper() {
  return mount(WalletView)
}

describe('WalletView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    vi.useFakeTimers({ shouldAdvanceTime: true })
    rechargeMock = vi.fn(() => Promise.resolve({
      payment_id: 99,
      payment_no: 'P202601010001',
      amount: 100,
      gateway: 'wechat',
      status: 0,
      credential: { type: 'qrcode', qrcode_url: 'https://mock.qrcode/test' },
      expire_at: null,
    }))
    withdrawMock = vi.fn(() => Promise.resolve({ payment_no: 'P202601010002', amount: 50, status: 1 }))
    getTransactionsMock = vi.fn(() => Promise.resolve({ list: [
      { id: 1, type: 1, amount: 10000, balance_before: 0, balance_after: 10000, remark: '充值', status: 1, created_at: '2026-01-01 10:00:00' },
      { id: 2, type: 2, amount: -5000, balance_before: 10000, balance_after: 5000, remark: '消费', status: 1, created_at: '2026-01-02 10:00:00' },
    ], total: 2 }))
    getPaymentStatusMock = vi.fn(() => Promise.resolve({ status: 0 }))
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('renders balance from user store', () => {
    const wrapper = createWrapper()
    expect(wrapper.find('.wallet-view').exists()).toBe(true)
    expect(wrapper.find('.page-title').text()).toBe('我的钱包')
    expect(wrapper.find('.balance-amount').text()).toContain('1234.56')
  })

  it('opens recharge dialog and submits', async () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any

    vm.openRechargeDialog()
    await nextTick()
    expect(vm.rechargeVisible).toBe(true)

    vm.rechargeForm.amount = 100
    vm.rechargeForm.gateway = 'wechat'
    await nextTick()

    await vm.submitRecharge()
    await flushPromises()

    expect(rechargeMock).toHaveBeenCalledWith({ amount: 100, gateway: 'wechat' })
    expect(vm.rechargePaymentData).not.toBeNull()
    expect(vm.rechargePaymentData.payment_id).toBe(99)
    expect(vm.rechargeVisible).toBe(false)
  })

  it('shows recharge payment panel after submitting recharge', async () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any

    vm.openRechargeDialog()
    vm.rechargeForm.amount = 100
    await vm.submitRecharge()
    await flushPromises()

    expect(vm.rechargePaymentData).not.toBeNull()
    expect(wrapper.find('.recharge-payment-panel').exists()).toBe(true)
  })

  it('polls payment status after recharge submit', async () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any

    vm.openRechargeDialog()
    vm.rechargeForm.amount = 100
    await vm.submitRecharge()
    await flushPromises()

    expect(getPaymentStatusMock).not.toHaveBeenCalled()

    vi.advanceTimersByTime(3500)
    await flushPromises()

    expect(getPaymentStatusMock).toHaveBeenCalledWith(99)
  })

  it('stops polling when payment succeeds', async () => {
    getPaymentStatusMock = vi.fn(() => Promise.resolve({ status: 1 }))
    const wrapper = createWrapper()
    const vm = wrapper.vm as any

    vm.openRechargeDialog()
    vm.rechargeForm.amount = 100
    await vm.submitRecharge()
    await flushPromises()

    vi.advanceTimersByTime(3500)
    await flushPromises()

    expect(getPaymentStatusMock).toHaveBeenCalledWith(99)
    expect(vm.rechargePaymentData).toBeNull()
  })

  it('opens withdraw dialog and submits', async () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any

    vm.openWithdrawDialog()
    await nextTick()
    expect(vm.withdrawVisible).toBe(true)

    vm.withdrawForm.amount = 50
    vm.withdrawForm.pay_password = '123456'
    await nextTick()

    await vm.submitWithdraw()
    await flushPromises()

    expect(withdrawMock).toHaveBeenCalledWith({ amount: 50, pay_password: '123456' })
  })

  it('shows pay password input in withdraw dialog', async () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any

    vm.openWithdrawDialog()
    await nextTick()

    expect(wrapper.find('.withdraw-pay-password').exists()).toBe(true)
  })

  it('shows transaction list tab', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('.transaction-tab').exists()).toBe(true)
    expect(wrapper.findAll('.transaction-row').length).toBe(2)
  })

  it('shows error when withdraw amount exceeds balance', async () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any

    vm.openWithdrawDialog()
    vm.withdrawForm.amount = 9999
    await nextTick()

    await vm.submitWithdraw()
    await flushPromises()

    expect(withdrawMock).not.toHaveBeenCalled()
  })

  it('validates recharge amount minimum', async () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any

    vm.openRechargeDialog()
    vm.rechargeForm.amount = 0
    await nextTick()

    await vm.submitRecharge()
    await flushPromises()

    expect(rechargeMock).not.toHaveBeenCalled()
  })

  it('exposes refs and methods', () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any
    expect(vm.balance).toBe(1234.56)
    expect(typeof vm.openRechargeDialog).toBe('function')
    expect(typeof vm.openWithdrawDialog).toBe('function')
    expect(typeof vm.submitRecharge).toBe('function')
    expect(typeof vm.submitWithdraw).toBe('function')
    expect(typeof vm.startRechargePolling).toBe('function')
    expect(typeof vm.stopRechargePolling).toBe('function')
  })

  it('filters transactions by type', async () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any
    await flushPromises()

    vm.transactionType = 1
    await nextTick()
    await vm.loadTransactions()
    await flushPromises()

    expect(getTransactionsMock).toHaveBeenCalledWith(expect.objectContaining({ type: 1 }))
  })

  it('clears recharge payment data', async () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any

    vm.openRechargeDialog()
    vm.rechargeForm.amount = 100
    await vm.submitRecharge()
    await flushPromises()

    expect(vm.rechargePaymentData).not.toBeNull()
    vm.clearRechargePayment()
    expect(vm.rechargePaymentData).toBeNull()
  })
})
