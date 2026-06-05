import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import RegisterView from '../RegisterView.vue'

let registerMock = vi.fn()

vi.mock('@/api/user', () => ({
  register: (...args: any[]) => registerMock(...args),
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
    vm.form.password = 'Abc12345'
    vm.form.password_confirmation = 'Abc12345'
    vm.agreed = true
    await vm.handleRegister()
    expect(registerMock).not.toHaveBeenCalled()
  })

  it('validates password mismatch', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = '13800138000'
    vm.form.password = 'Abc12345'
    vm.form.password_confirmation = 'Abc12346'
    vm.agreed = true
    await vm.handleRegister()
    expect(registerMock).not.toHaveBeenCalled()
  })

  it('validates agreement required', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = '13800138000'
    vm.form.password = 'Abc12345'
    vm.form.password_confirmation = 'Abc12345'
    vm.agreed = false
    await vm.handleRegister()
    expect(registerMock).not.toHaveBeenCalled()
  })

  it('submits registration with all fields', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.phone = '13800138000'
    vm.form.password = 'Abc12345'
    vm.form.password_confirmation = 'Abc12345'
    vm.form.nickname = '测试昵称'
    vm.form.link_person = '张三'
    vm.form.qq = '123456'
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
    vm.form.password = 'Abc12345'
    vm.form.password_confirmation = 'Abc12345'
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
})
