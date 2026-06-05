import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import NotificationCenterView from '../NotificationCenterView.vue'

let getNotificationsMock = vi.fn()
let markNotificationReadMock = vi.fn()
let markAllNotificationsReadMock = vi.fn()

vi.mock('@/api/notification', () => ({
  getNotifications: (...args: any[]) => getNotificationsMock(...args),
  markNotificationRead: (...args: any[]) => markNotificationReadMock(...args),
  markAllNotificationsRead: () => markAllNotificationsReadMock(),
}))

beforeEach(() => {
  getNotificationsMock = vi.fn(() =>
    Promise.resolve({
      data: [
        { id: 1, type: 'order', title: '订单已付款', content: '您的订单 Y202601010001 已付款', is_read: 0, action_url: '/orders/1', action_text: '查看订单', created_at: '2026-01-01 10:00:00' },
        { id: 2, type: 'order', title: '订单已发货', content: '您的订单 Y202601010001 已发货', is_read: 1, action_url: '/orders/1', action_text: '查看订单', created_at: '2026-01-02 10:00:00' },
      ],
      total: 2,
      current_page: 1,
      last_page: 1,
    })
  )
  markNotificationReadMock = vi.fn(() => Promise.resolve({}))
  markAllNotificationsReadMock = vi.fn(() => Promise.resolve({}))
})

describe('NotificationCenterView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({
      history: createWebHistory(),
      routes: [{ path: '/notifications', name: 'Notifications', component: NotificationCenterView }],
    })
    return mount(NotificationCenterView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders notification center page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.notification-center-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('消息通知')
  })

  it('loads notifications on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getNotificationsMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).notifications).toHaveLength(2)
  })

  it('displays unread style for unread notifications', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const items = wrapper.findAll('.notification-item')
    expect(items[0].classes()).toContain('unread')
    expect(items[1].classes()).not.toContain('unread')
  })

  it('has unread notifications computed correctly', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).hasUnread).toBe(true)
  })

  it('marks single notification as read via handleMarkRead', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleMarkRead(1)
    await flushPromises()
    expect(markNotificationReadMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).notifications[0].is_read).toBe(1)
  })

  it('marks all notifications as read via handleMarkAllRead', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleMarkAllRead()
    await flushPromises()
    expect(markAllNotificationsReadMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).notifications.every((n: any) => n.is_read === 1)).toBe(true)
  })

  it('shows empty state when no notifications', async () => {
    getNotificationsMock = vi.fn(() =>
      Promise.resolve({ data: [], total: 0, current_page: 1, last_page: 1 })
    )
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).notifications).toHaveLength(0)
    expect((wrapper.vm as any).total).toBe(0)
  })

  it('filters by read status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).filterRead = 0
    ;(wrapper.vm as any).handleFilterChange()
    await flushPromises()
    expect(getNotificationsMock).toHaveBeenLastCalledWith(expect.objectContaining({ is_read: 0, page: 1 }))
  })

  it('handles pagination change', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).currentPage = 2
    ;(wrapper.vm as any).handlePageChange(2)
    await flushPromises()
    expect(getNotificationsMock).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2 }))
  })
})
