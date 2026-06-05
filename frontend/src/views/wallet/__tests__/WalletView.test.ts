import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { nextTick } from 'vue'
import WalletView from '../WalletView.vue'

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
}))

vi.mock('@/stores/user', () => ({
  useUserStore: () => ({
    userInfo: mockUser,
  }),
}))

vi.mock('element-plus', () => ({
  ElMessage: {
    success: vi.fn(),
    error: vi.fn(),
  },
}))

function createWrapper() {
  return mount(WalletView)
}

describe('WalletView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    rechargeMock = vi.fn(() => Promise.resolve({ payment_no: 'P202601010001', amount: 100, status: 1 }))
    withdrawMock = vi.fn(() => Promise.resolve({ payment_no: 'P202601010002', amount: 50, status: 1 }))
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
  })

  it('opens withdraw dialog and submits', async () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any

    vm.openWithdrawDialog()
    await nextTick()
    expect(vm.withdrawVisible).toBe(true)

    vm.withdrawForm.amount = 50
    await nextTick()

    await vm.submitWithdraw()
    await flushPromises()

    expect(withdrawMock).toHaveBeenCalledWith({ amount: 50 })
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
  })
})
