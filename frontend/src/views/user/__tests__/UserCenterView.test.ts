import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { useUserStore } from '@/stores/user'
import UserCenterView from '../UserCenterView.vue'

describe('UserCenterView', () => {
  it('renders user center with user info', () => {
    setActivePinia(createPinia())
    const store = useUserStore()
    store.loginSuccess('token', { id: 1, phone: '13800138000', nickname: '测试', avatar: null, type: 1, auth_status: 0, vip_level: 1, balance: 100 })
    const wrapper = mount(UserCenterView, {
      global: { plugins: [createPinia()] },
    })
    expect(wrapper.find('.user-center-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('13800138000')
  })
})
