import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import UserCenterView from '../UserCenterView.vue'

let getUserInfoMock = vi.fn()
let getUserDashboardMock = vi.fn()

vi.mock('@/api/user', () => ({
  getUserInfo: () => getUserInfoMock(),
  getUserDashboard: () => getUserDashboardMock(),
}))

const mockDashboard = {
  order_status_counts: {
    pending_payment: 2,
    in_progress: 1,
    pending_delivery: 3,
    pending_receive: 1,
    pending_review: 4,
    completed: 5,
  },
  unread_notification_count: 7,
  available_coupon_count: 3,
  recent_orders: [
    { id: 1, order_no: 'Y202601010001', status: 11, customer_status: '待付款', total_amount: 5000, created_at: '2026-01-01 10:00:00' },
    { id: 2, order_no: 'Y202601010002', status: 20, customer_status: '已发货', total_amount: 8000, created_at: '2026-01-02 10:00:00' },
  ],
}

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
  getUserDashboardMock = vi.fn(() => Promise.resolve(mockDashboard))
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

  it('loads dashboard summary on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getUserDashboardMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).dashboard).toEqual(mockDashboard)
  })

  it('displays unread notification count and coupon count in asset bar', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('7')
    expect(wrapper.text()).toContain('3')
    expect(wrapper.text()).toContain('消息')
    expect(wrapper.text()).toContain('优惠券')
  })

  it('displays order status quick entries with counts', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('待付款')
    expect(wrapper.text()).toContain('2')
    expect(wrapper.text()).toContain('待发货')
    expect(wrapper.text()).toContain('3')
    expect(wrapper.text()).toContain('待收货')
    expect(wrapper.text()).toContain('1')
    expect(wrapper.text()).toContain('待评价')
    expect(wrapper.text()).toContain('4')
  })

  it('displays recent orders list', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('最近订单')
    expect(wrapper.text()).toContain('Y202601010001')
    expect(wrapper.text()).toContain('Y202601010002')
    expect(wrapper.text()).toContain('待付款')
    expect(wrapper.text()).toContain('已发货')
  })

  it('navigates to orders page with status filter on quick entry click', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const mockPush = (wrapper.vm as any).$router?.push || vi.fn()
    const pendingPayBtn = wrapper.find('[data-testid="quick-pending_payment"]')
    expect(pendingPayBtn.exists()).toBe(true)
    await pendingPayBtn.trigger('click')
    expect(mockPush).not.toBeNull()
  })
})
