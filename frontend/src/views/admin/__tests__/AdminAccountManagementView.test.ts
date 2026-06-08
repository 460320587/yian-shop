import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import AdminAccountManagementView from '../AdminAccountManagementView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve()) },
  }
})

const mockAccounts = [
  { id: 1, username: 'admin', real_name: '超级管理员', phone: '13800138000', email: 'admin@example.com', role_id: 1, roleModel: { id: 1, name: '超级管理员' }, status: 1, created_at: '2026-01-01 10:00:00' },
  { id: 2, username: 'operator1', real_name: '操作员', phone: null, email: null, role_id: 2, roleModel: { id: 2, name: '运营' }, status: 0, created_at: '2026-01-02 14:30:00' },
]

const mockRoles = [
  { id: 1, name: '超级管理员', code: 'super', description: '全部权限', status: 1 },
  { id: 2, name: '运营', code: 'operator', description: '日常运营', status: 1 },
]

let getAdminAccountsMock = vi.fn()
let createAdminAccountMock = vi.fn()
let updateAdminAccountMock = vi.fn()
let toggleAdminAccountStatusMock = vi.fn()
let deleteAdminAccountMock = vi.fn()
let resetAdminAccountPasswordMock = vi.fn()
let getAdminRolesMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminAccounts: (...args: any[]) => getAdminAccountsMock(...args),
  createAdminAccount: (...args: any[]) => createAdminAccountMock(...args),
  updateAdminAccount: (...args: any[]) => updateAdminAccountMock(...args),
  toggleAdminAccountStatus: (...args: any[]) => toggleAdminAccountStatusMock(...args),
  deleteAdminAccount: (...args: any[]) => deleteAdminAccountMock(...args),
  resetAdminAccountPassword: (...args: any[]) => resetAdminAccountPasswordMock(...args),
  getAdminRoles: (...args: any[]) => getAdminRolesMock(...args),
}))

beforeEach(() => {
  getAdminAccountsMock = vi.fn(() => Promise.resolve({ data: mockAccounts, total: 2, current_page: 1, last_page: 1 }))
  createAdminAccountMock = vi.fn(() => Promise.resolve({ id: 3, username: 'newadmin', real_name: '新员工' }))
  updateAdminAccountMock = vi.fn(() => Promise.resolve({ id: 1, username: 'admin', real_name: '新名字' }))
  toggleAdminAccountStatusMock = vi.fn(() => Promise.resolve({ status: 0 }))
  deleteAdminAccountMock = vi.fn(() => Promise.resolve({}))
  resetAdminAccountPasswordMock = vi.fn(() => Promise.resolve({}))
  getAdminRolesMock = vi.fn(() => Promise.resolve(mockRoles))
})

describe('AdminAccountManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(AdminAccountManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders admin account management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.account-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('管理员账号')
  })

  it('loads accounts and roles on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminAccountsMock).toHaveBeenCalledTimes(1)
    expect(getAdminRolesMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).accounts).toHaveLength(2)
    expect((wrapper.vm as any).roles).toHaveLength(2)
  })

  it('filters accounts by keyword', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).keyword = 'admin'
    ;(wrapper.vm as any).onSearch()
    await flushPromises()
    expect(getAdminAccountsMock).toHaveBeenLastCalledWith(expect.objectContaining({ keyword: 'admin', page: 1, per_page: 10 }))
  })

  it('creates new admin account', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openCreate()
    ;(wrapper.vm as any).form.username = 'newadmin'
    ;(wrapper.vm as any).form.password = 'pass123'
    ;(wrapper.vm as any).form.password_confirmation = 'pass123'
    ;(wrapper.vm as any).form.real_name = '新员工'
    await (wrapper.vm as any).handleSave()
    await flushPromises()
    expect(createAdminAccountMock).toHaveBeenCalledWith(expect.objectContaining({ username: 'newadmin', real_name: '新员工' }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
  })

  it('opens edit dialog and loads account data', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openEdit(mockAccounts[0])
    expect((wrapper.vm as any).isEdit).toBe(true)
    expect((wrapper.vm as any).form.real_name).toBe('超级管理员')
    expect((wrapper.vm as any).dialogVisible).toBe(true)
  })

  it('updates existing admin account', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openEdit(mockAccounts[0])
    ;(wrapper.vm as any).form.real_name = '新名字'
    await (wrapper.vm as any).handleSave()
    await flushPromises()
    expect(updateAdminAccountMock).toHaveBeenCalledWith(1, expect.objectContaining({ real_name: '新名字' }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
    expect(getAdminAccountsMock).toHaveBeenCalledTimes(2)
  })

  it('toggles admin account status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleToggle(mockAccounts[0])
    await flushPromises()
    expect(toggleAdminAccountStatusMock).toHaveBeenCalledWith(1)
    expect(getAdminAccountsMock).toHaveBeenCalledTimes(2)
  })

  it('deletes admin account', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleDelete(mockAccounts[1])
    await flushPromises()
    expect(deleteAdminAccountMock).toHaveBeenCalledWith(2)
    expect(getAdminAccountsMock).toHaveBeenCalledTimes(2)
  })

  it('resets admin password', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openResetPassword(mockAccounts[0])
    ;(wrapper.vm as any).pwdForm.password = 'newpass123'
    ;(wrapper.vm as any).pwdForm.password_confirmation = 'newpass123'
    await (wrapper.vm as any).handleResetPassword()
    await flushPromises()
    expect(resetAdminAccountPasswordMock).toHaveBeenCalledWith(1, expect.objectContaining({ password: 'newpass123' }))
    expect((wrapper.vm as any).pwdDialogVisible).toBe(false)
  })
})
