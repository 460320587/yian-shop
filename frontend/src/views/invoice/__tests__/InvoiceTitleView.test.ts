import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { nextTick } from 'vue'
import InvoiceTitleView from '../InvoiceTitleView.vue'

const mockTitles = [
  { id: 1, title_type: 2, invoice_category: 1, company_name: '怡安印刷公司', tax_number: '91110000123456789X', register_address: '北京市', register_phone: '010-12345678', bank_name: '工商银行', bank_account: '6222001234567890123', is_default: 1 },
  { id: 2, title_type: 1, invoice_category: 1, company_name: '个人', tax_number: null, register_address: null, register_phone: null, bank_name: null, bank_account: null, is_default: 0 },
]

let getInvoiceTitlesMock = vi.fn()
let createInvoiceTitleMock = vi.fn()
let updateInvoiceTitleMock = vi.fn()
let deleteInvoiceTitleMock = vi.fn()

vi.mock('@/api/invoice', () => ({
  getInvoiceTitles: (...args: any[]) => getInvoiceTitlesMock(...args),
  createInvoiceTitle: (...args: any[]) => createInvoiceTitleMock(...args),
  updateInvoiceTitle: (...args: any[]) => updateInvoiceTitleMock(...args),
  deleteInvoiceTitle: (...args: any[]) => deleteInvoiceTitleMock(...args),
}))

vi.mock('element-plus', () => ({
  ElMessage: {
    success: vi.fn(),
    error: vi.fn(),
  },
}))

function createWrapper() {
  return mount(InvoiceTitleView)
}

describe('InvoiceTitleView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    getInvoiceTitlesMock = vi.fn(() => Promise.resolve(mockTitles))
    createInvoiceTitleMock = vi.fn(() => Promise.resolve({ id: 3, ...mockTitles[0] }))
    updateInvoiceTitleMock = vi.fn(() => Promise.resolve(mockTitles[0]))
    deleteInvoiceTitleMock = vi.fn(() => Promise.resolve({}))
  })

  it('renders loading state initially', async () => {
    getInvoiceTitlesMock = vi.fn(() => new Promise(() => {}))
    const wrapper = createWrapper()
    await nextTick()
    expect(wrapper.find('.invoice-title-view').exists()).toBe(true)
    expect(wrapper.find('.page-title').text()).toBe('发票抬头管理')
    expect(wrapper.find('.loading-text').exists()).toBe(true)
  })

  it('fetches titles on mount and renders list', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    expect(getInvoiceTitlesMock).toHaveBeenCalled()
    const rows = wrapper.findAll('.title-item')
    expect(rows.length).toBe(2)
    expect(rows[0].text()).toContain('怡安印刷公司')
    expect(rows[1].text()).toContain('个人')
  })

  it('marks default title', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const defaultTag = wrapper.findAll('.title-item')[0].find('.default-tag')
    expect(defaultTag.exists()).toBe(true)
    expect(defaultTag.text()).toContain('默认')
  })

  it('opens add dialog and submits new title', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.openDialog()
    await nextTick()

    expect(vm.dialogVisible).toBe(true)
    expect(vm.isEdit).toBe(false)

    vm.form.company_name = '新公司'
    vm.form.title_type = 2
    vm.form.invoice_category = 1
    await nextTick()

    await vm.submitForm()
    await flushPromises()

    expect(createInvoiceTitleMock).toHaveBeenCalledWith(expect.objectContaining({ company_name: '新公司', title_type: 2, invoice_category: 1 }))
  })

  it('opens edit dialog with pre-filled data', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.editTitle(mockTitles[0])
    await nextTick()

    expect(vm.dialogVisible).toBe(true)
    expect(vm.isEdit).toBe(true)
    expect(vm.form.company_name).toBe('怡安印刷公司')
  })

  it('deletes title', async () => {
    vi.stubGlobal('confirm', () => true)
    const wrapper = createWrapper()
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.deleteTitle(mockTitles[1])
    await flushPromises()

    expect(deleteInvoiceTitleMock).toHaveBeenCalledWith(2)
    vi.unstubAllGlobals()
  })

  it('exposes refs and methods', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const vm = wrapper.vm as any
    expect(vm.titles).toBeDefined()
    expect(vm.loading).toBe(false)
    expect(typeof vm.openDialog).toBe('function')
    expect(typeof vm.submitForm).toBe('function')
    expect(typeof vm.deleteTitle).toBe('function')
  })
})
