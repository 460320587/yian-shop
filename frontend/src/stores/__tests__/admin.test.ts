import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAdminStore } from '../admin'

vi.mock('@/api/admin', () => ({
  adminLogin: vi.fn(() => Promise.resolve({ token: 'admin-token-123', admin: { id: 1, username: 'admin', real_name: '管理员', role: 'super' } })),
  adminLogout: vi.fn(() => Promise.resolve({})),
  adminProfile: vi.fn(() => Promise.resolve({ id: 1, username: 'admin', real_name: '管理员', phone: null, email: null, role: 'super', last_login_at: null })),
}))

describe('Admin Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('initial state is not logged in', () => {
    const store = useAdminStore()
    expect(store.isLoggedIn).toBe(false)
    expect(store.adminInfo).toBeNull()
  })

  it('login sets token and admin info', async () => {
    const store = useAdminStore()
    await store.login({ username: 'admin', password: 'password' })
    expect(store.isLoggedIn).toBe(true)
    expect(store.token).toBe('admin-token-123')
    expect(store.adminInfo?.username).toBe('admin')
    expect(store.adminInfo?.role).toBe('super')
    expect(localStorage.getItem('admin_token')).toBe('admin-token-123')
  })

  it('logout clears state', async () => {
    const store = useAdminStore()
    await store.login({ username: 'admin', password: 'password' })
    store.logout()
    expect(store.isLoggedIn).toBe(false)
    expect(store.token).toBeNull()
    expect(store.adminInfo).toBeNull()
    expect(localStorage.getItem('admin_token')).toBeNull()
  })

  it('restores token from localStorage on init', () => {
    localStorage.setItem('admin_token', 'restored-token')
    const store = useAdminStore()
    expect(store.token).toBe('restored-token')
    expect(store.isLoggedIn).toBe(true)
  })
})
