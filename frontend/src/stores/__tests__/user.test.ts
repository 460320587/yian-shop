import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useUserStore } from '../user'

describe('User Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('initial state has no token', () => {
    const store = useUserStore()
    expect(store.isLoggedIn).toBe(false)
    expect(store.userInfo).toBeNull()
    expect(store.token).toBeNull()
  })

  it('loginSuccess sets token and user', () => {
    const store = useUserStore()
    store.loginSuccess('token-123', { id: 1, phone: '13800138000', nickname: '测试', avatar: null, type: 1, auth_status: 0, vip_level: 0, balance: 0 })
    expect(store.isLoggedIn).toBe(true)
    expect(store.token).toBe('token-123')
    expect(store.userInfo?.phone).toBe('13800138000')
    expect(localStorage.getItem('access_token')).toBe('token-123')
  })

  it('logout clears state', () => {
    const store = useUserStore()
    store.loginSuccess('token', { id: 1, phone: '138', nickname: null, avatar: null, type: 1, auth_status: 0, vip_level: 0, balance: 0 })
    store.logout()
    expect(store.isLoggedIn).toBe(false)
    expect(store.token).toBeNull()
    expect(store.userInfo).toBeNull()
    expect(localStorage.getItem('access_token')).toBeNull()
  })

  it('restores token from localStorage on init', () => {
    localStorage.setItem('access_token', 'restored-token')
    const store = useUserStore()
    expect(store.token).toBe('restored-token')
    expect(store.isLoggedIn).toBe(true)
  })
})
