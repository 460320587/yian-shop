import { describe, it, expect, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import UserCenterView from '../UserCenterView.vue'

vi.mock('@/api/user', () => ({
  getUserProfile: vi.fn(() => Promise.resolve({ id: 1, phone: '13800138000', nickname: '张三', avatar: '', vip_level: 3, balance: 50000 })),
}))

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
    expect(wrapper.find('.user-center-view').exists() || wrapper.find('.user-center-page').exists()).toBe(true)
    expect(wrapper.text()).toContain('个人中心')
  })
})
