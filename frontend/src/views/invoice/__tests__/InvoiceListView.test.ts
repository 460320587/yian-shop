import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { nextTick } from 'vue'
import InvoiceListView from '../InvoiceListView.vue'

const mockInvoices = [
  { id: 1, invoice_no: 'I202601010001', status: 1, type: 1, title: '怡安印刷公司', amount: 462000, created_at: '2026-01-01T10:00:00Z' },
  { id: 2, invoice_no: 'I202601010002', status: 4, type: 2, title: '怡安印刷公司', amount: 123400, created_at: '2026-01-02T10:00:00Z' },
  { id: 3, invoice_no: 'I202601010003', status: 0, type: 1, title: '个人', amount: 5600, created_at: '2026-01-03T10:00:00Z' },
]

const mockResponse = {
  data: mockInvoices,
  meta: { total: 3, current_page: 1, last_page: 1, per_page: 10 },
}

let getInvoicesMock = vi.fn()
let cancelInvoiceMock = vi.fn()

vi.mock('@/api/invoice', () => ({
  getInvoices: (...args: any[]) => getInvoicesMock(...args),
  cancelInvoice: (...args: any[]) => cancelInvoiceMock(...args),
}))

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: vi.fn() }),
}))

vi.mock('element-plus', () => ({
  ElMessage: {
    success: vi.fn(),
    error: vi.fn(),
  },
}))

function createWrapper() {
  return mount(InvoiceListView)
}

describe('InvoiceListView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    getInvoicesMock = vi.fn(() => Promise.resolve(mockResponse))
    cancelInvoiceMock = vi.fn(() => Promise.resolve({}))
  })

  it('renders loading state initially', async () => {
    getInvoicesMock = vi.fn(() => new Promise(() => {}))
    const wrapper = createWrapper()
    await nextTick()
    expect(wrapper.find('.invoice-list-view').exists()).toBe(true)
    expect(wrapper.find('.page-title').text()).toBe('发票中心')
    expect(wrapper.find('.loading-text').exists()).toBe(true)
  })

  it('fetches invoices on mount', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    expect(getInvoicesMock).toHaveBeenCalled()
    const items = wrapper.findAll('.invoice-item')
    expect(items.length).toBe(3)
  })

  it('renders invoice info correctly', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const items = wrapper.findAll('.invoice-item')
    expect(items[0].text()).toContain('I202601010001')
    expect(items[0].text()).toContain('怡安印刷公司')
    expect(items[0].text()).toContain('已申请')
    expect(items[1].text()).toContain('已开票')
    expect(items[2].text()).toContain('已取消')
  })

  it('filters by status tab', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.handleStatusChange(1)
    await flushPromises()

    expect(getInvoicesMock).toHaveBeenLastCalledWith(expect.objectContaining({ status: 1 }))
  })

  it('shows cancel button for applied invoice', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const items = wrapper.findAll('.invoice-item')
    expect(items[0].find('[data-testid="cancel-btn"]').exists()).toBe(true)
    expect(items[1].find('[data-testid="cancel-btn"]').exists()).toBe(false)
  })

  it('cancels invoice and refreshes list', async () => {
    vi.stubGlobal('confirm', () => true)
    const wrapper = createWrapper()
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.cancelInvoice(mockInvoices[0])
    await flushPromises()

    expect(cancelInvoiceMock).toHaveBeenCalledWith(1)
    vi.unstubAllGlobals()
  })

  it('shows empty state when no invoices', async () => {
    getInvoicesMock = vi.fn(() => Promise.resolve({ data: [], meta: { total: 0, current_page: 1, last_page: 1, per_page: 10 } }))
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('.empty-state').exists()).toBe(true)
  })

  it('exposes refs and methods', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const vm = wrapper.vm as any
    expect(vm.invoices).toBeDefined()
    expect(vm.loading).toBe(false)
    expect(typeof vm.loadInvoices).toBe('function')
    expect(typeof vm.cancelInvoice).toBe('function')
  })
})
