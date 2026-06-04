import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../LoginView.vue'

let loginMock = vi.fn()

vi.mock('@/api/user', () => ({
  login: (...args: any[]) => loginMock(...args),
}))

beforeEach(() => {
  loginMock = vi.fn(() => Promise.resolve({ token: 'token', user: { id: 1, phone: '13800138000' } }))
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
    expect(wrapper.find('.login-view').exists() || wrapper.find('.login-page').exists()).toBe(true)
  })

  it('submits login with phone and password', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    if (vm.form) {
      vm.form.phone = '13800138000'
      vm.form.password = 'password'
      if (vm.handleLogin) vm.handleLogin()
    }
    await flushPromises()
    expect(loginMock).toHaveBeenCalledWith(expect.objectContaining({ phone: '13800138000', password: 'password' }))
  })
})
