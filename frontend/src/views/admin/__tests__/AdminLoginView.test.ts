import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import AdminLoginView from '../AdminLoginView.vue'

vi.mock('@/api/admin', () => ({
  adminLogin: vi.fn(() => Promise.resolve({ token: 'admin-token', admin: { id: 1, username: 'admin', real_name: '管理员', role: 'super' } })),
}))

describe('AdminLoginView', () => {
  it('renders admin login form', () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(AdminLoginView, {
      global: { plugins: [createPinia(), router] },
    })
    expect(wrapper.find('.admin-login-page').exists()).toBe(true)
    expect(wrapper.find('h2').text()).toContain('管理后台')
  })
})
