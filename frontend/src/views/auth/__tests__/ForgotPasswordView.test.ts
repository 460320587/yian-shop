import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import ForgotPasswordView from '../ForgotPasswordView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessage: { success: vi.fn(), error: vi.fn() },
  }
})

let forgotPasswordMock = vi.fn()
let resetPasswordMock = vi.fn()

vi.mock('@/api/user', () => ({
  forgotPassword: (...args: any[]) => forgotPasswordMock(...args),
  resetPassword: (...args: any[]) => resetPasswordMock(...args),
}))

beforeEach(() => {
  forgotPasswordMock = vi.fn(() => Promise.resolve({ token: 'reset_token_123' }))
  resetPasswordMock = vi.fn(() => Promise.resolve({}))
})

describe('ForgotPasswordView', () => {
  async function mountComponent() {
    const router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/forgot-password', name: 'ForgotPassword', component: ForgotPasswordView },
        { path: '/login', name: 'Login', component: { template: '<div>Login</div>' } },
      ],
    })
    await router.push('/forgot-password')
    await router.isReady()
    const wrapper = mount(ForgotPasswordView, {
      global: { plugins: [createPinia(), router] },
    })
    return wrapper
  }

  it('renders forgot password page', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    expect(wrapper.find('.forgot-password-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('忘记密码')
  })

  it('requests reset token with valid phone', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form.phone = '13800138000'
    await (wrapper.vm as any).requestToken()
    await flushPromises()
    expect(forgotPasswordMock).toHaveBeenCalledWith({ phone: '13800138000' })
    expect((wrapper.vm as any).step).toBe(2)
    expect((wrapper.vm as any).form.token).toBe('reset_token_123')
  })

  it('rejects invalid phone format', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form.phone = '12345678901'
    const valid = await (wrapper.vm as any).validatePhone()
    expect(valid).toBe(false)
  })

  it('resets password with token and new password', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form.phone = '13800138000'
    ;(wrapper.vm as any).form.token = 'reset_token_123'
    ;(wrapper.vm as any).form.password = 'new_password'
    ;(wrapper.vm as any).form.password_confirmation = 'new_password'
    ;(wrapper.vm as any).step = 2
    await (wrapper.vm as any).submitReset()
    await flushPromises()
    expect(resetPasswordMock).toHaveBeenCalledWith({
      phone: '13800138000',
      token: 'reset_token_123',
      password: 'new_password',
      password_confirmation: 'new_password',
    })
  })

  it('rejects password mismatch', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form.password = 'new_password'
    ;(wrapper.vm as any).form.password_confirmation = 'different_password'
    const valid = await (wrapper.vm as any).validateResetForm()
    expect(valid).toBe(false)
  })

  it('requires password minimum length', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form.password = '123'
    ;(wrapper.vm as any).form.password_confirmation = '123'
    const valid = await (wrapper.vm as any).validateResetForm()
    expect(valid).toBe(false)
  })

  it('navigates to login after successful reset', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    const router = (wrapper.vm as any).$router
    const pushSpy = vi.spyOn(router, 'push')
    ;(wrapper.vm as any).form.phone = '13800138000'
    ;(wrapper.vm as any).form.token = 'reset_token_123'
    ;(wrapper.vm as any).form.password = 'new_password'
    ;(wrapper.vm as any).form.password_confirmation = 'new_password'
    ;(wrapper.vm as any).step = 2
    await (wrapper.vm as any).submitReset()
    await flushPromises()
    expect(pushSpy).toHaveBeenCalledWith('/login')
  })
})
