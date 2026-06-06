import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import RegisterView from '../RegisterView.vue'

let registerMock = vi.fn()
let sendSmsCodeMock = vi.fn()

vi.mock('@/api/user', () => ({
  register: (...args: any[]) => registerMock(...args),
  sendSmsCode: (...args: any[]) => sendSmsCodeMock(...args),
}))

vi.mock('@/api/captcha', () => ({
  getCaptcha: () => Promise.resolve({ captcha_key: 'key_1', captcha_code: '1234' }),
}))

vi.mock('element-plus', () => ({
  ElMessage: { success: vi.fn(), error: vi.fn(), warning: vi.fn() },
}))

beforeEach(() => {
  registerMock = vi.fn(() =>
    Promise.resolve({
      token: 'test-token',
      user: { id: 1, phone: '13800138000', nickname: '测试用户' },
    })
  )
  sendSmsCodeMock = vi.fn(() => Promise.resolve({}))
})

describe('RegisterView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(RegisterView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders register page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.register-page').exists()).toBe(true)
    expect(wrapper.text()).toContain('注册账号')
  })

  it('shows password strength', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.password = 'Abc12345'
    expect(vm.passwordStrength).toBeGreaterThan(0)
    expect(vm.strengthText).not.toBe('')
  })

  it('validates phone format', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = '123'
    vm.form.sms_code = '123456'
    vm.form.password = 'Abc12345'
    vm.form.password_confirmation = 'Abc12345'
    vm.form.captcha = '1234'
    vm.captchaValid = true
    vm.agreed = true
    await vm.handleRegister()
    expect(registerMock).not.toHaveBeenCalled()
  })

  it('validates sms code required', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = '13800138000'
    vm.form.sms_code = ''
    vm.form.password = 'Abc12345'
    vm.form.password_confirmation = 'Abc12345'
    vm.form.captcha = '1234'
    vm.captchaValid = true
    vm.agreed = true
    await vm.handleRegister()
    expect(registerMock).not.toHaveBeenCalled()
  })

  it('validates password mismatch', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = '13800138000'
    vm.form.sms_code = '123456'
    vm.form.password = 'Abc12345'
    vm.form.password_confirmation = 'Abc12346'
    vm.form.captcha = '1234'
    vm.captchaValid = true
    vm.agreed = true
    await vm.handleRegister()
    expect(registerMock).not.toHaveBeenCalled()
  })

  it('validates agreement required', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = '13800138000'
    vm.form.sms_code = '123456'
    vm.form.password = 'Abc12345'
    vm.form.password_confirmation = 'Abc12345'
    vm.form.captcha = '1234'
    vm.captchaValid = true
    vm.agreed = false
    await vm.handleRegister()
    expect(registerMock).not.toHaveBeenCalled()
  })

  it('submits registration with all fields', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = '13800138000'
    vm.form.sms_code = '123456'
    vm.form.password = 'Abc12345'
    vm.form.password_confirmation = 'Abc12345'
    vm.form.nickname = '测试昵称'
    vm.form.link_person = '张三'
    vm.form.qq = '123456'
    vm.form.captcha = '1234'
    vm.captchaValid = true
    vm.agreed = true
    await vm.handleRegister()
    await flushPromises()

    expect(registerMock).toHaveBeenCalledTimes(1)
    const payload = registerMock.mock.calls[0][0]
    expect(payload.phone).toBe('13800138000')
    expect(payload.password).toBe('Abc12345')
    expect(payload.nickname).toBe('测试昵称')
    expect(payload.link_person).toBe('张三')
    expect(payload.qq).toBe('123456')
  })

  it('submits registration with minimal fields', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = '13800138000'
    vm.form.sms_code = '123456'
    vm.form.password = 'Abc12345'
    vm.form.password_confirmation = 'Abc12345'
    vm.form.captcha = '1234'
    vm.captchaValid = true
    vm.agreed = true
    await vm.handleRegister()
    await flushPromises()

    expect(registerMock).toHaveBeenCalledTimes(1)
    const payload = registerMock.mock.calls[0][0]
    expect(payload.phone).toBe('13800138000')
    expect(payload.nickname).toBeUndefined()
    expect(payload.link_person).toBeUndefined()
    expect(payload.qq).toBeUndefined()
  })

  it('sends sms code with valid phone and captcha', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = '13800138000'
    vm.form.captcha = '1234'
    vm.captchaValid = true
    vm.captchaKey = 'key_1'
    await vm.handleSendSmsCode()
    await flushPromises()

    expect(sendSmsCodeMock).toHaveBeenCalledTimes(1)
    expect(sendSmsCodeMock.mock.calls[0][0]).toEqual({
      phone: '13800138000',
      captcha_key: 'key_1',
      captcha_code: '1234',
    })
    expect(vm.smsCountdown).toBe(60)
  })

  it('does not send sms code without phone', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = ''
    await vm.handleSendSmsCode()
    expect(sendSmsCodeMock).not.toHaveBeenCalled()
  })

  it('does not send sms code with invalid phone', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = '123'
    await vm.handleSendSmsCode()
    expect(sendSmsCodeMock).not.toHaveBeenCalled()
  })

  it('does not send sms code without captcha', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = '13800138000'
    vm.form.captcha = ''
    vm.captchaValid = false
    await vm.handleSendSmsCode()
    expect(sendSmsCodeMock).not.toHaveBeenCalled()
  })
})
