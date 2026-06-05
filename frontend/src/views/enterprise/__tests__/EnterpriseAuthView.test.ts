import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import EnterpriseAuthView from '../EnterpriseAuthView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessage: { success: vi.fn(), error: vi.fn() },
  }
})

let getAuthStatusMock = vi.fn()
let getEnterpriseInfoMock = vi.fn()
let applyEnterpriseAuthMock = vi.fn()

vi.mock('@/api/enterprise', () => ({
  getAuthStatus: (...args: any[]) => getAuthStatusMock(...args),
  getEnterpriseInfo: (...args: any[]) => getEnterpriseInfoMock(...args),
  applyEnterpriseAuth: (...args: any[]) => applyEnterpriseAuthMock(...args),
}))

beforeEach(() => {
  getAuthStatusMock = vi.fn(() => Promise.resolve({ auth_status: 0, auth_status_name: '未提交' }))
  getEnterpriseInfoMock = vi.fn(() => Promise.resolve({
    id: 1, customer_id: 1, company_name: '测试公司', credit_code: '91110000XXXX', legal_person: '张三',
    legal_person_id_card: '110101199001011234', business_license_img: 'https://example.com/license.jpg',
    contact_name: '李四', contact_phone: '13800138000', register_address: '北京市', office_address: '上海市',
    valid_date: '2030-01-01', auth_status: 2, audit_remark: null, created_at: '2026-01-01', updated_at: '2026-01-01',
  }))
  applyEnterpriseAuthMock = vi.fn((data) => Promise.resolve({
    id: 1, customer_id: 1, ...data, auth_status: 1, audit_remark: null, created_at: '2026-01-01', updated_at: '2026-01-01',
  }))
})

describe('EnterpriseAuthView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({
      history: createWebHistory(),
      routes: [{ path: '/enterprise-auth', name: 'EnterpriseAuth', component: EnterpriseAuthView }],
    })
    return mount(EnterpriseAuthView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders enterprise auth page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.enterprise-auth-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('企业认证')
  })

  it('loads auth status on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAuthStatusMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).authStatus).toBe(0)
  })

  it('shows form when not authenticated', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).showForm).toBe(true)
  })

  it('shows pending message when auditing', async () => {
    getAuthStatusMock = vi.fn(() => Promise.resolve({ auth_status: 1, auth_status_name: '审核中' }))
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).authStatus).toBe(1)
    expect((wrapper.vm as any).showForm).toBe(false)
    expect((wrapper.vm as any).showInfo).toBe(false)
  })

  it('shows info when authenticated', async () => {
    getAuthStatusMock = vi.fn(() => Promise.resolve({ auth_status: 2, auth_status_name: '认证通过' }))
    const wrapper = mountComponent()
    await flushPromises()
    expect(getEnterpriseInfoMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).showInfo).toBe(true)
    expect((wrapper.vm as any).enterpriseInfo?.company_name).toBe('测试公司')
  })

  it('submits form with valid data', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const formData = {
      company_name: '新公司',
      credit_code: '91110000NEW',
      legal_person: '王五',
      legal_person_id_card: '110101199001015678',
      business_license_img: 'https://example.com/new.jpg',
      contact_name: '赵六',
      contact_phone: '13900139000',
    }
    ;(wrapper.vm as any).form = { ...formData }
    await (wrapper.vm as any).submitForm()
    await flushPromises()
    expect(applyEnterpriseAuthMock).toHaveBeenCalledWith(expect.objectContaining(formData))
    expect((wrapper.vm as any).authStatus).toBe(1)
  })

  it('form validation requires company_name', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form = {
      company_name: '',
      credit_code: '91110000XXXX',
      legal_person: '张三',
      legal_person_id_card: '110101199001011234',
      business_license_img: 'https://example.com/license.jpg',
      contact_name: '李四',
      contact_phone: '13800138000',
    }
    const valid = await (wrapper.vm as any).validateForm()
    expect(valid).toBe(false)
  })
})
