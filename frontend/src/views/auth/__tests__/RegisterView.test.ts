import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import RegisterView from '../RegisterView.vue'

vi.mock('@/api/user', () => ({
  register: vi.fn(() => Promise.resolve({ token: 'new-token', user: { id: 2, phone: '13900139000', nickname: '新用户' } })),
}))

describe('RegisterView', () => {
  it('renders register form', () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(RegisterView, {
      global: { plugins: [createPinia(), router] },
    })
    expect(wrapper.find('.register-page').exists()).toBe(true)
    expect(wrapper.find('h2').text()).toContain('注册账号')
  })
})
