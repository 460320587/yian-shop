import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import RoleManagementView from '../RoleManagementView.vue'

const mockRoles = [
  { id: 1, name: '超级管理员', code: 'super', description: '全部权限', status: 1 },
  { id: 2, name: '运营', code: 'operator', description: '日常运营', status: 0 },
]

const mockPermissions = [
  { id: 1, name: '用户查看', code: 'user:view', group: '用户管理', type: 'menu' },
  { id: 2, name: '用户编辑', code: 'user:edit', group: '用户管理', type: 'button' },
  { id: 3, name: '订单查看', code: 'order:view', group: '订单管理', type: 'menu' },
]

const mockRolePermissions = [
  { id: 1, name: '用户查看', code: 'user:view', group: '用户管理', type: 'menu' },
]

let getAdminRolesMock = vi.fn()
let createAdminRoleMock = vi.fn()
let updateAdminRoleMock = vi.fn()
let deleteAdminRoleMock = vi.fn()
let toggleAdminRoleStatusMock = vi.fn()
let getAdminPermissionsMock = vi.fn()
let getRolePermissionsMock = vi.fn()
let assignRolePermissionsMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminRoles: (...args: any[]) => getAdminRolesMock(...args),
  createAdminRole: (...args: any[]) => createAdminRoleMock(...args),
  updateAdminRole: (...args: any[]) => updateAdminRoleMock(...args),
  deleteAdminRole: (...args: any[]) => deleteAdminRoleMock(...args),
  toggleAdminRoleStatus: (...args: any[]) => toggleAdminRoleStatusMock(...args),
  getAdminPermissions: (...args: any[]) => getAdminPermissionsMock(...args),
  getRolePermissions: (...args: any[]) => getRolePermissionsMock(...args),
  assignRolePermissions: (...args: any[]) => assignRolePermissionsMock(...args),
}))

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve()) },
  }
})

beforeEach(() => {
  getAdminRolesMock = vi.fn(() => Promise.resolve(mockRoles))
  createAdminRoleMock = vi.fn(() => Promise.resolve({ id: 3, name: '财务', code: 'finance', description: '财务权限', status: 1 }))
  updateAdminRoleMock = vi.fn(() => Promise.resolve({ id: 1, name: '新名称', code: 'super', description: '全部权限', status: 1 }))
  deleteAdminRoleMock = vi.fn(() => Promise.resolve({}))
  toggleAdminRoleStatusMock = vi.fn(() => Promise.resolve({ status: 0 }))
  getAdminPermissionsMock = vi.fn(() => Promise.resolve(mockPermissions))
  getRolePermissionsMock = vi.fn(() => Promise.resolve(mockRolePermissions))
  assignRolePermissionsMock = vi.fn(() => Promise.resolve({}))
})

describe('RoleManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(RoleManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders role management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.role-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('角色权限')
  })

  it('loads roles on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminRolesMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).roles).toHaveLength(2)
  })

  it('creates new role', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openCreate()
    ;(wrapper.vm as any).form.name = '财务'
    ;(wrapper.vm as any).form.code = 'finance'
    ;(wrapper.vm as any).form.description = '财务权限'
    await (wrapper.vm as any).handleSave()
    await flushPromises()
    expect(createAdminRoleMock).toHaveBeenCalledWith(expect.objectContaining({ name: '财务', code: 'finance' }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
  })

  it('opens edit dialog and loads role data', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openEdit(mockRoles[0])
    expect((wrapper.vm as any).isEdit).toBe(true)
    expect((wrapper.vm as any).form.name).toBe('超级管理员')
    expect((wrapper.vm as any).dialogVisible).toBe(true)
  })

  it('updates existing role', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openEdit(mockRoles[0])
    ;(wrapper.vm as any).form.name = '新名称'
    await (wrapper.vm as any).handleSave()
    await flushPromises()
    expect(updateAdminRoleMock).toHaveBeenCalledWith(1, expect.objectContaining({ name: '新名称' }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
    expect(getAdminRolesMock).toHaveBeenCalledTimes(2)
  })

  it('toggles role status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleToggle(mockRoles[0])
    await flushPromises()
    expect(toggleAdminRoleStatusMock).toHaveBeenCalledWith(1)
    expect(getAdminRolesMock).toHaveBeenCalledTimes(2)
  })

  it('deletes role', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleDelete(mockRoles[1])
    await flushPromises()
    expect(deleteAdminRoleMock).toHaveBeenCalledWith(2)
    expect(getAdminRolesMock).toHaveBeenCalledTimes(2)
  })

  it('opens permission dialog and loads permissions', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).openPermissionDialog(mockRoles[0])
    await flushPromises()
    expect(getAdminPermissionsMock).toHaveBeenCalledTimes(1)
    expect(getRolePermissionsMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).permissionDialogVisible).toBe(true)
    expect((wrapper.vm as any).allPermissions).toHaveLength(3)
  })

  it('assigns permissions to role', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).openPermissionDialog(mockRoles[0])
    await flushPromises()
    ;(wrapper.vm as any).selectedPermissionIds = [1, 2, 3]
    await (wrapper.vm as any).handleSavePermissions()
    await flushPromises()
    expect(assignRolePermissionsMock).toHaveBeenCalledWith(1, { permission_ids: [1, 2, 3] })
    expect((wrapper.vm as any).permissionDialogVisible).toBe(false)
  })
})
