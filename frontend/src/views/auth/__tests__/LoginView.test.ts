import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../LoginView.vue'

vi.mock('@/api/user', () => ({
  login: vi.fn(() => Promise.resolve({ token: 'test-token', user: { id: 1, phone: '13800138000', nickname: '测试' } })),
}))

describe('LoginView', () => {
  it('renders login form', () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(LoginView, {
      global: { plugins: [createPinia(), router] },
    })
    expect(wrapper.find('.login-page').exists()).toBe(true)
    expect(wrapper.find('h2').text()).toContain('欢迎登录')
  })
})
