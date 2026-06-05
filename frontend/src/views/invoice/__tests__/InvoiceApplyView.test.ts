import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import InvoiceApplyView from '../InvoiceApplyView.vue'

const mockOrders = [
  { id: 1, order_no: 'Y202601010001', total_amount: 4620, status: 60 },
  { id: 2, order_no: 'Y202601010002', total_amount: 1234, status: 60 },
]

const mockTitles = [
  { id: 1, title_type: 2, invoice_category: 1, company_name: '怡安印刷公司', tax_number: '91110000123456789X', is_default: 1 },
]

const mockPush = vi.fn()
const mockBack = vi.fn()

let getOrdersMock = vi.fn()
let getInvoiceTitlesMock = vi.fn()
let createInvoiceApplyMock = vi.fn()

vi.mock('@/api/order', () => ({
  getOrders: (...args: any[]) => getOrdersMock(...args),
}))

vi.mock('@/api/invoice', () => ({
  getInvoiceTitles: (...args: any[]) => getInvoiceTitlesMock(...args),
  createInvoiceApply: (...args: any[]) => createInvoiceApplyMock(...args),
}))

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: mockPush, back: mockBack }),
}))

vi.mock('element-plus', () => ({
  ElMessage: {
    success: vi.fn(),
    error: vi.fn(),
  },
}))

function createWrapper() {
  return mount(InvoiceApplyView)
}

describe('InvoiceApplyView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    getOrdersMock = vi.fn(() => Promise.resolve({ data: mockOrders, total: 2, current_page: 1, last_page: 1 }))
    getInvoiceTitlesMock = vi.fn(() => Promise.resolve(mockTitles))
    createInvoiceApplyMock = vi.fn(() => Promise.resolve({ id: 10, invoice_no: 'I202601010010', status: 1 }))
  })

  it('renders form and loads orders and titles', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('.invoice-apply-view').exists()).toBe(true)
    expect(wrapper.find('.page-title').text()).toBe('申请发票')
    expect(getOrdersMock).toHaveBeenCalled()
    expect(getInvoiceTitlesMock).toHaveBeenCalled()

    const vm = wrapper.vm as any
    expect(vm.orders).toHaveLength(2)
    expect(vm.titles).toHaveLength(1)
  })

  it('submits invoice apply', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.form.order_id = 1
    vm.form.title_id = 1
    vm.form.type = 1
    vm.form.email = 'test@example.com'
    vm.form.address = '北京市'
    vm.form.remark = '备注'
    await flushPromises()

    await vm.submitForm()
    await flushPromises()

    expect(createInvoiceApplyMock).toHaveBeenCalledWith({
      order_id: 1,
      title_id: 1,
      type: 1,
      email: 'test@example.com',
      address: '北京市',
      remark: '备注',
    })
    expect(mockBack).toHaveBeenCalled()
  })

  it('validates required fields', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.form.order_id = null
    vm.form.title_id = null
    await flushPromises()

    await vm.submitForm()
    await flushPromises()

    expect(createInvoiceApplyMock).not.toHaveBeenCalled()
  })

  it('navigates back on cancel', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.goBack()
    expect(mockBack).toHaveBeenCalled()
  })

  it('exposes refs and methods', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const vm = wrapper.vm as any
    expect(vm.form).toBeDefined()
    expect(vm.orders).toBeDefined()
    expect(vm.titles).toBeDefined()
    expect(typeof vm.submitForm).toBe('function')
    expect(typeof vm.goBack).toBe('function')
  })
})
