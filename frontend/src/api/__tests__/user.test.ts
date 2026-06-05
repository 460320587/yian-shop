import { describe, it, expect, vi } from 'vitest'
import { login, register, getUserInfo, logout } from '../user'

vi.mock('@/utils/request', () => ({
  post: vi.fn((url: string) => {
    if (url === '/auth/login') {
      return Promise.resolve({ token: 'test-token', user: { id: 1, phone: '13800138000', nickname: '测试用户', avatar: null } })
    }
    if (url === '/auth/register') {
      return Promise.resolve({ token: 'new-token', user: { id: 2, phone: '13900139000', nickname: '新用户', avatar: null } })
    }
    if (url === '/auth/logout') {
      return Promise.resolve({})
    }
    return Promise.resolve({})
  }),
  get: vi.fn((url: string) => {
    if (url === '/user/profile') {
      return Promise.resolve({ id: 1, phone: '13800138000', nickname: '测试用户', avatar: null, type: 1, auth_status: 0, vip_level: 1, balance: 100 })
    }
    return Promise.resolve({})
  }),
}))

describe('User API', () => {
  it('login returns token and user', async () => {
    const res = await login({ phone: '13800138000', password: 'password123' })
    expect(res.token).toBe('test-token')
    expect(res.user.phone).toBe('13800138000')
  })

  it('register returns token and user', async () => {
    const res = await register({ phone: '13900139000', password: 'password123', password_confirmation: 'password123' })
    expect(res.token).toBe('new-token')
    expect(res.user.phone).toBe('13900139000')
  })

  it('getUserInfo returns user info', async () => {
    const user = await getUserInfo()
    expect(user.phone).toBe('13800138000')
    expect(user.vip_level).toBe(1)
  })

  it('logout calls logout endpoint', async () => {
    const res = await logout()
    expect(res).toEqual({})
  })
})
