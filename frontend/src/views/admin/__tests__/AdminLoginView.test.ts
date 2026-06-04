import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import AdminLoginView from '../AdminLoginView.vue'

let adminLoginMock = vi.fn()

vi.mock('@/api/admin', () => ({
  adminLogin: (...args: any[]) => adminLoginMock(...args),
}))

beforeEach(() => {
  adminLoginMock = vi.fn(() => Promise.resolve({ token: 'admin-token', admin: { id: 1, username: 'admin', real_name: '管理员', role: 'super' } }))
})

describe('AdminLoginView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(AdminLoginView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders admin login form', () => {
    const wrapper = mountComponent()
    expect(wrapper.find('.admin-login-page').exists()).toBe(true)
    expect(wrapper.find('h2').text()).toContain('管理后台')
  })

  it('submits login with username and password', async () => {
    const wrapper = mountComponent()
    ;(wrapper.vm as any).form.username = 'admin'
    ;(wrapper.vm as any).form.password = 'password'
    ;(wrapper.vm as any).handleLogin()
    await flushPromises()
    expect(adminLoginMock).toHaveBeenCalledWith({ username: 'admin', password: 'password' })
    expect((wrapper.vm as any).loading).toBe(false)
  })

  it('sets loading during login', async () => {
    let resolveFn: any
    adminLoginMock = vi.fn(() => new Promise((r) => { resolveFn = r }))
    const wrapper = mountComponent()
    ;(wrapper.vm as any).handleLogin()
    expect((wrapper.vm as any).loading).toBe(true)
    resolveFn({ token: 'admin-token', admin: { id: 1 } })
    await flushPromises()
    expect((wrapper.vm as any).loading).toBe(false)
  })
})
