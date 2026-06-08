import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import EnterpriseAuthManagementView from '../EnterpriseAuthManagementView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve('confirm')) },
    ElMessage: { success: vi.fn(), error: vi.fn(), warning: vi.fn() },
  }
})

const mockAuths = [
  {
    id: 1, customer_id: 1, company_name: '深圳印刷有限公司', credit_code: '91440300123456789X',
    legal_person: '张三', legal_person_id_card: '440301199001011234', business_license_img: '/img/license1.jpg',
    contact_name: '张三', contact_phone: '13800138000', register_address: '深圳市南山区', office_address: '深圳市福田区',
    valid_date: '2026-12-31', auth_status: 1, audit_remark: null, created_at: '2026-01-10 10:00:00',
    customer: { id: 1, nickname: '张三', phone: '13800138000' },
  },
  {
    id: 2, customer_id: 2, company_name: '广州广告设计有限公司', credit_code: '91440100987654321Y',
    legal_person: '李四', legal_person_id_card: '440106198505052345', business_license_img: '/img/license2.jpg',
    contact_name: '李四', contact_phone: '13900139000', register_address: '广州市天河区', office_address: null,
    valid_date: '2027-06-30', auth_status: 2, audit_remark: '审核通过', created_at: '2026-01-09 09:00:00',
    customer: { id: 2, nickname: '李四', phone: '13900139000' },
  },
  {
    id: 3, customer_id: 3, company_name: '北京文化传媒公司', credit_code: '91110000111222333Z',
    legal_person: '王五', legal_person_id_card: '110101199210103456', business_license_img: '/img/license3.jpg',
    contact_name: '王五', contact_phone: '13700137000', register_address: '北京市朝阳区', office_address: null,
    valid_date: null, auth_status: 3, audit_remark: '资料不完整', created_at: '2026-01-08 08:00:00',
    customer: { id: 3, nickname: '王五', phone: '13700137000' },
  },
]

let getAdminEnterpriseAuthsMock = vi.fn()
let getAdminEnterpriseAuthDetailMock = vi.fn()
let auditAdminEnterpriseAuthMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminEnterpriseAuths: (...args: any[]) => getAdminEnterpriseAuthsMock(...args),
  getAdminEnterpriseAuthDetail: (...args: any[]) => getAdminEnterpriseAuthDetailMock(...args),
  auditAdminEnterpriseAuth: (...args: any[]) => auditAdminEnterpriseAuthMock(...args),
}))

beforeEach(() => {
  getAdminEnterpriseAuthsMock = vi.fn(() => Promise.resolve({ data: mockAuths, total: 3, current_page: 1, last_page: 1 }))
  getAdminEnterpriseAuthDetailMock = vi.fn((id) => Promise.resolve(mockAuths.find((a) => a.id === id)))
  auditAdminEnterpriseAuthMock = vi.fn(() => Promise.resolve({ id: 1, auth_status: 2, audit_remark: '审核通过' }))
})

describe('EnterpriseAuthManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(EnterpriseAuthManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders enterprise auth management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.enterprise-auth-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('企业认证审核')
  })

  it('loads enterprise auths on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminEnterpriseAuthsMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).auths).toHaveLength(3)
    expect((wrapper.vm as any).total).toBe(3)
  })

  it('filters by auth_status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).statusFilter = 1
    ;(wrapper.vm as any).onFilter()
    await flushPromises()
    expect(getAdminEnterpriseAuthsMock).toHaveBeenLastCalledWith(expect.objectContaining({ auth_status: 1, page: 1, per_page: 10 }))
  })

  it('filters by keyword', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).keyword = '深圳'
    ;(wrapper.vm as any).onFilter()
    await flushPromises()
    expect(getAdminEnterpriseAuthsMock).toHaveBeenLastCalledWith(expect.objectContaining({ keyword: '深圳', page: 1, per_page: 10 }))
  })

  it('opens detail dialog', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).openDetail(mockAuths[0])
    await flushPromises()
    expect(getAdminEnterpriseAuthDetailMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).detailDialogVisible).toBe(true)
  })

  it('opens audit dialog for pending auth', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openAudit(mockAuths[0])
    expect((wrapper.vm as any).auditDialogVisible).toBe(true)
    expect((wrapper.vm as any).currentAuth?.id).toBe(1)
  })

  it('submits audit with approved status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openAudit(mockAuths[0])
    ;(wrapper.vm as any).auditForm.auth_status = 2
    ;(wrapper.vm as any).auditForm.audit_remark = '审核通过，信息无误'
    await (wrapper.vm as any).handleAudit()
    await flushPromises()
    expect(auditAdminEnterpriseAuthMock).toHaveBeenCalledWith(1, { auth_status: 2, audit_remark: '审核通过，信息无误' })
    expect((wrapper.vm as any).auditDialogVisible).toBe(false)
  })

  it('shows audit button only for pending status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.canAudit(mockAuths[0])).toBe(true) // status 1
    expect(vm.canAudit(mockAuths[1])).toBe(false) // status 2
    expect(vm.canAudit(mockAuths[2])).toBe(false) // status 3
  })

  it('handles pagination change', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).currentPage = 2
    ;(wrapper.vm as any).loadAuths()
    await flushPromises()
    expect(getAdminEnterpriseAuthsMock).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2, per_page: 10 }))
  })
})
