import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import RegisterView from '../RegisterView.vue'

let registerMock = vi.fn()

vi.mock('@/api/user', () => ({
  register: (...args: any[]) => registerMock(...args),
}))

beforeEach(() => {
  registerMock = vi.fn(() => Promise.resolve({ token: 'token', user: { id: 1, phone: '13800138000' } }))
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
    expect(wrapper.find('.register-view').exists() || wrapper.find('.register-page').exists()).toBe(true)
  })

  it('submits registration', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    if (vm.form) {
      vm.form.phone = '13800138000'
      vm.form.password = 'password'
      if (vm.handleRegister) vm.handleRegister()
    }
    await flushPromises()
    expect(registerMock).toHaveBeenCalledWith(expect.objectContaining({ phone: '13800138000', password: 'password' }))
  })
})
