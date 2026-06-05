import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../LoginView.vue'

let loginMock = vi.fn()

vi.mock('@/api/user', () => ({
  login: (...args: any[]) => loginMock(...args),
}))

vi.mock('@/api/captcha', () => ({
  getCaptcha: () => Promise.resolve({ captcha_key: 'key_1', captcha_code: '1234' }),
}))

vi.mock('element-plus', () => ({
  ElMessage: { success: vi.fn(), error: vi.fn(), warning: vi.fn() },
}))

beforeEach(() => {
  loginMock = vi.fn(() =>
    Promise.resolve({
      token: 'test-token',
      user: { id: 1, phone: '13800138000', nickname: '测试用户' },
    })
  )
})

describe('LoginView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(LoginView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders login page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.login-page').exists()).toBe(true)
    expect(wrapper.text()).toContain('欢迎登录')
  })

  it('logs in with phone and password', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = '13800138000'
    vm.form.password = 'Abc12345'
    await vm.handleLogin()
    await flushPromises()

    expect(loginMock).toHaveBeenCalledTimes(1)
    expect(loginMock.mock.calls[0][0]).toEqual({
      phone: '13800138000',
      password: 'Abc12345',
    })
  })

  it('shows captcha after 3 failed attempts', async () => {
    loginMock = vi.fn(() => Promise.reject({ response: { data: { message: '密码错误' } } }))
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = '13800138000'
    vm.form.password = 'wrong'

    await vm.handleLogin()
    await vm.handleLogin()
    expect(vm.showCaptcha).toBe(false)

    await vm.handleLogin()
    await flushPromises()
    expect(vm.showCaptcha).toBe(true)
  })

  it('requires captcha when shown', async () => {
    loginMock = vi.fn(() => Promise.reject({ response: { data: { message: '密码错误' } } }))
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = '13800138000'
    vm.form.password = 'wrong'

    for (let i = 0; i < 3; i++) {
      await vm.handleLogin()
    }
    await flushPromises()
    expect(vm.showCaptcha).toBe(true)

    await vm.handleLogin()
    expect(loginMock).toHaveBeenCalledTimes(3)
  })
})
