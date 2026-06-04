import { describe, it, expect, vi } from 'vitest'
import { adminLogin, adminLogout, adminProfile } from '../admin'

vi.mock('@/utils/request', () => ({
  post: vi.fn((url: string) => {
    if (url === '/auth/login') {
      return Promise.resolve({ admin: { id: 1, username: 'admin', real_name: '管理员', role: 'super' }, token: 'admin-token' })
    }
    if (url === '/auth/logout') {
      return Promise.resolve({})
    }
    return Promise.resolve({})
  }),
  get: vi.fn((url: string) => {
    if (url === '/auth/profile') {
      return Promise.resolve({ id: 1, username: 'admin', real_name: '管理员', phone: null, email: null, role: 'super', last_login_at: null })
    }
    return Promise.resolve({})
  }),
}))

describe('Admin API', () => {
  it('adminLogin returns token and admin', async () => {
    const res = await adminLogin({ username: 'admin', password: 'password' })
    expect(res.token).toBe('admin-token')
    expect(res.admin.username).toBe('admin')
    expect(res.admin.role).toBe('super')
  })

  it('adminLogout calls logout endpoint', async () => {
    const res = await adminLogout()
    expect(res).toEqual({})
  })

  it('adminProfile returns admin profile', async () => {
    const profile = await adminProfile()
    expect(profile.username).toBe('admin')
    expect(profile.role).toBe('super')
  })
})
