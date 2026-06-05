import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useUserStore } from '../user'
import * as userApi from '@/api/user'

vi.mock('@/api/user', () => ({
  getUserInfo: vi.fn(),
  logout: vi.fn(),
}))

describe('userStore.fetchUserInfo', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  afterEach(() => {
    vi.clearAllMocks()
  })

  it('fetchUserInfo 方法必须存在', () => {
    const store = useUserStore()
    expect(typeof store.fetchUserInfo).toBe('function')
  })

  it('fetchUserInfo 成功时更新 userInfo', async () => {
    const mockUser = {
      id: 1,
      phone: '13800138000',
      nickname: 'TestUser',
      avatar: 'https://example.com/avatar.jpg',
      type: 1,
      auth_status: 2,
      vip_level: 3,
      balance: 5000,
      points: 100,
      link_person: '联系人',
      qq: '123456',
    }

    vi.mocked(userApi.getUserInfo).mockResolvedValueOnce({
      data: mockUser,
    } as any)

    const store = useUserStore()
    store.setToken('test-token')

    await store.fetchUserInfo()

    expect(store.userInfo).toEqual(mockUser)
    expect(localStorage.getItem('user_info')).toEqual(JSON.stringify(mockUser))
  })

  it('fetchUserInfo 失败时不应抛异常，userInfo 保持原值', async () => {
    vi.mocked(userApi.getUserInfo).mockRejectedValueOnce(new Error('Network Error'))

    const store = useUserStore()
    store.setToken('test-token')

    // 不应抛出异常
    await expect(store.fetchUserInfo()).resolves.not.toThrow()
    expect(store.userInfo).toBeNull()
  })

  it('未登录时调用 fetchUserInfo 不应发起请求', async () => {
    const store = useUserStore()
    // token 为 null

    await store.fetchUserInfo()

    expect(userApi.getUserInfo).not.toHaveBeenCalled()
  })

  it('fetchUserInfo 成功后将数据写入 localStorage', async () => {
    const mockUser = {
      id: 2,
      phone: '13900139000',
      nickname: 'StorageTest',
      avatar: null,
      type: 2,
      auth_status: 1,
      vip_level: 0,
      balance: 0,
    }

    vi.mocked(userApi.getUserInfo).mockResolvedValueOnce({
      data: mockUser,
    } as any)

    const store = useUserStore()
    store.setToken('test-token')
    await store.fetchUserInfo()

    const stored = JSON.parse(localStorage.getItem('user_info') || '{}')
    expect(stored.id).toBe(2)
    expect(stored.nickname).toBe('StorageTest')
  })
})
