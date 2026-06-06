import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { nextTick } from 'vue'
import PayPasswordView from '../PayPasswordView.vue'

let getStatusMock = vi.fn()
let setPayPasswordMock = vi.fn()
let updatePayPasswordMock = vi.fn()

vi.mock('@/api/pay-password', () => ({
  getPayPasswordStatus: (...args: any[]) => getStatusMock(...args),
  setPayPassword: (...args: any[]) => setPayPasswordMock(...args),
  updatePayPassword: (...args: any[]) => updatePayPasswordMock(...args),
}))

vi.mock('element-plus', () => ({
  ElMessage: { success: vi.fn(), error: vi.fn() },
}))

function createWrapper() {
  return mount(PayPasswordView)
}

describe('PayPasswordView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    getStatusMock = vi.fn(() => Promise.resolve({ has_pay_password: false, locked: false, locked_until: null }))
    setPayPasswordMock = vi.fn(() => Promise.resolve({ has_pay_password: true }))
    updatePayPasswordMock = vi.fn(() => Promise.resolve({ has_pay_password: true }))
  })

  it('renders pay password page', async () => {
    const wrapper = createWrapper()
    await flushPromises()
    expect(wrapper.find('.pay-password-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('支付密码')
  })

  it('shows set form when not set', async () => {
    const wrapper = createWrapper()
    await flushPromises()
    expect(wrapper.find('.set-form').exists()).toBe(true)
    expect(wrapper.find('.update-form').exists()).toBe(false)
    expect(wrapper.text()).toContain('设置支付密码')
  })

  it('shows update form when already set', async () => {
    getStatusMock = vi.fn(() => Promise.resolve({ has_pay_password: true, locked: false, locked_until: null }))
    const wrapper = createWrapper()
    await flushPromises()
    expect(wrapper.find('.update-form').exists()).toBe(true)
    expect(wrapper.find('.set-form').exists()).toBe(false)
    expect(wrapper.text()).toContain('修改支付密码')
  })

  it('shows locked state when locked', async () => {
    getStatusMock = vi.fn(() => Promise.resolve({ has_pay_password: true, locked: true, locked_until: '2026-06-01 12:00:00' }))
    const wrapper = createWrapper()
    await flushPromises()
    expect(wrapper.find('.locked-notice').exists()).toBe(true)
    expect(wrapper.find('.update-form').exists()).toBe(false)
  })

  it('submits set form successfully', async () => {
    const wrapper = createWrapper()
    await flushPromises()
    const vm = wrapper.vm as any

    vm.setForm.pay_password = '123456'
    vm.setForm.pay_password_confirmation = '123456'
    await nextTick()

    await vm.handleSet()
    await flushPromises()

    expect(setPayPasswordMock).toHaveBeenCalledWith({
      pay_password: '123456',
      pay_password_confirmation: '123456',
    })
    expect(getStatusMock).toHaveBeenCalledTimes(2)
  })

  it('submits update form successfully', async () => {
    getStatusMock = vi.fn(() => Promise.resolve({ has_pay_password: true, locked: false, locked_until: null }))
    const wrapper = createWrapper()
    await flushPromises()
    const vm = wrapper.vm as any

    vm.updateForm.old_pay_password = '123456'
    vm.updateForm.pay_password = '654321'
    vm.updateForm.pay_password_confirmation = '654321'
    await nextTick()

    await vm.handleUpdate()
    await flushPromises()

    expect(updatePayPasswordMock).toHaveBeenCalledWith({
      old_pay_password: '123456',
      pay_password: '654321',
      pay_password_confirmation: '654321',
    })
    expect(getStatusMock).toHaveBeenCalledTimes(2)
  })

  it('validates password confirmation on set', async () => {
    const wrapper = createWrapper()
    await flushPromises()
    const vm = wrapper.vm as any

    vm.setForm.pay_password = '123456'
    vm.setForm.pay_password_confirmation = '654321'
    await nextTick()

    await vm.handleSet()
    await flushPromises()

    expect(setPayPasswordMock).not.toHaveBeenCalled()
  })

  it('validates password confirmation on update', async () => {
    getStatusMock = vi.fn(() => Promise.resolve({ has_pay_password: true, locked: false, locked_until: null }))
    const wrapper = createWrapper()
    await flushPromises()
    const vm = wrapper.vm as any

    vm.updateForm.old_pay_password = '123456'
    vm.updateForm.pay_password = '111111'
    vm.updateForm.pay_password_confirmation = '222222'
    await nextTick()

    await vm.handleUpdate()
    await flushPromises()

    expect(updatePayPasswordMock).not.toHaveBeenCalled()
  })

  it('exposes refs and methods', async () => {
    const wrapper = createWrapper()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(typeof vm.handleSet).toBe('function')
    expect(typeof vm.handleUpdate).toBe('function')
    expect(typeof vm.loadStatus).toBe('function')
  })
})
