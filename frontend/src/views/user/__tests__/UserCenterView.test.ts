import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import UserCenterView from '../UserCenterView.vue'

let getUserInfoMock = vi.fn()

vi.mock('@/api/user', () => ({
  getUserInfo: () => getUserInfoMock(),
}))

beforeEach(() => {
  getUserInfoMock = vi.fn(() =>
    Promise.resolve({
      id: 1,
      phone: '13800138000',
      nickname: '张三',
      avatar: null,
      type: 3,
      auth_status: 2,
      vip_level: 3,
      balance: 50000,
      points: 1200,
    })
  )
})

describe('UserCenterView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(UserCenterView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders user center page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.user-center-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('个人中心')
  })

  it('displays user info', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('张三')
    expect(wrapper.text()).toContain('13800138000')
    expect(wrapper.text()).toContain('金牌会员')
  })

  it('displays assets', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('¥500.00')
    expect(wrapper.text()).toContain('1200')
    expect(wrapper.text()).toContain('Lv.3')
  })

  it('displays menu groups', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('我的服务')
    expect(wrapper.text()).toContain('订单相关')
    expect(wrapper.text()).toContain('其他')
    expect(wrapper.text()).toContain('收货地址')
    expect(wrapper.text()).toContain('我的订单')
    expect(wrapper.text()).toContain('VIP中心')
  })

  it('refreshes user info on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getUserInfoMock).toHaveBeenCalledTimes(1)
    const vm = wrapper.vm as any
    expect(vm.user).not.toBeNull()
  })
})
