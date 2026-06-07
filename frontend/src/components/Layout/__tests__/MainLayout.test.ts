import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { ref, computed } from 'vue'
import MainLayout from '../MainLayout.vue'

const mockPush = vi.fn()

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: mockPush }),
  useRoute: () => ({ name: 'Home', path: '/' }),
}))

let unreadCountMock = vi.fn()

vi.mock('@/api/notification', () => ({
  getUnreadCount: (...args: any[]) => unreadCountMock(...args),
}))

let isLoggedInValue = true
let userInfoValue: any = { nickname: 'TestUser', phone: '13800138000' }
let cartCountValue = 3

vi.mock('@/stores/user', () => ({
  useUserStore: () => ({
    isLoggedIn: isLoggedInValue,
    userInfo: userInfoValue,
    fetchUserInfo: vi.fn(),
    logout: vi.fn(),
  }),
}))

vi.mock('@/stores/cart', () => ({
  useCartStore: () => ({
    totalCount: cartCountValue,
  }),
}))

vi.mock('@/api/user', () => ({
  logout: vi.fn(() => Promise.resolve({})),
}))

vi.mock('element-plus', () => ({
  ElMessage: { success: vi.fn(), error: vi.fn() },
}))

describe('MainLayout', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    isLoggedInValue = true
    userInfoValue = { nickname: 'TestUser', phone: '13800138000' }
    cartCountValue = 3
    unreadCountMock = vi.fn(() => Promise.resolve({ unread_count: 0 }))
  })

  function mountComponent() {
    return mount(MainLayout)
  }

  it('renders main layout structure', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.main-layout').exists()).toBe(true)
    expect(wrapper.find('.header').exists()).toBe(true)
    expect(wrapper.find('.main-content').exists()).toBe(true)
    expect(wrapper.find('.footer').exists()).toBe(true)
  })

  it('shows logo and brand name', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.logo-text').text()).toContain('怡安印刷商城')
  })

  it('shows search bar', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.search-bar').exists()).toBe(true)
  })

  it('navigates to products page on search', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.searchKeyword = '名片'
    await vm.handleSearch()
    expect(mockPush).toHaveBeenCalledWith('/products?keyword=%E5%90%8D%E7%89%87')
  })

  it('shows cart action with count badge', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const actions = wrapper.findAll('.action-item')
    const cartAction = actions.find(a => a.text().includes('购物车'))
    expect(cartAction).toBeDefined()
    expect(cartAction!.exists()).toBe(true)
  })

  it('shows notification bell icon', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const bell = wrapper.find('[data-testid="notification-bell"]')
    expect(bell.exists()).toBe(true)
  })

  it('fetches unread count on mount', async () => {
    mountComponent()
    await flushPromises()
    expect(unreadCountMock).toHaveBeenCalledTimes(1)
  })

  it('shows unread badge when there are unread notifications', async () => {
    unreadCountMock = vi.fn(() => Promise.resolve({ unread_count: 5 }))
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).unreadCount).toBe(5)
    const bell = wrapper.find('[data-testid="notification-bell"]')
    expect(bell.exists()).toBe(true)
  })

  it('hides unread badge when no unread notifications', async () => {
    unreadCountMock = vi.fn(() => Promise.resolve({ unread_count: 0 }))
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).unreadCount).toBe(0)
    const bell = wrapper.find('[data-testid="notification-bell"]')
    expect(bell.exists()).toBe(true)
  })

  it('navigates to notifications page on bell click', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const bell = wrapper.find('[data-testid="notification-bell"]')
    await bell.trigger('click')
    expect(mockPush).toHaveBeenCalledWith('/notifications')
  })

  it('shows login/register buttons when not logged in', async () => {
    isLoggedInValue = false
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('登录')
    expect(wrapper.text()).toContain('注册')
  })

  it('shows user dropdown when logged in', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.text()).toContain('TestUser')
  })

  it('has sub navigation links', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const navLinks = wrapper.findAll('.nav-link')
    expect(navLinks.length).toBeGreaterThanOrEqual(1)
  })
})
