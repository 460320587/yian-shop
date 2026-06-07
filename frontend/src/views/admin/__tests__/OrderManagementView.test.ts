import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import OrderManagementView from '../OrderManagementView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve('confirm')) },
    ElMessage: { success: vi.fn(), error: vi.fn(), warning: vi.fn() },
  }
})

const mockOrders = [
  { id: 1, order_no: 'Y202601010001', customer_name: '张三', total_amount: 5000, status: 11, customer_status: '待付款', created_at: '2026-01-01 10:00:00' },
  { id: 2, order_no: 'Y202601010002', customer_name: '李四', total_amount: 12000, status: 20, customer_status: '生产中', created_at: '2026-01-02 14:30:00' },
]

let getAdminOrdersMock = vi.fn()
let getAdminOrderDetailMock = vi.fn()
let getAdminOrderFilesMock = vi.fn()
let deleteAdminOrderFileMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminOrders: (...args: any[]) => getAdminOrdersMock(...args),
  getAdminOrderDetail: (...args: any[]) => getAdminOrderDetailMock(...args),
  getAdminOrderFiles: (...args: any[]) => getAdminOrderFilesMock(...args),
  deleteAdminOrderFile: (...args: any[]) => deleteAdminOrderFileMock(...args),
}))

const mockOrderFiles = [
  { id: 1, order_id: 1, file_name: 'design.pdf', file_url: '/files/1.pdf', thumb_url: '/files/1_thumb.jpg', file_size: 1024000, file_type: 'pdf', page_count: 2, status: 1, created_at: '2026-01-01 10:00:00' },
  { id: 2, order_id: 1, file_name: 'logo.png', file_url: '/files/2.png', thumb_url: null, file_size: 51200, file_type: 'image', page_count: null, status: 1, created_at: '2026-01-01 11:00:00' },
]

beforeEach(() => {
  getAdminOrdersMock = vi.fn(() => Promise.resolve({ data: mockOrders, total: 2, current_page: 1, last_page: 1 }))
  getAdminOrderDetailMock = vi.fn(() => Promise.resolve({
    id: 1, order_no: 'Y202601010001', customer_name: '张三', total_amount: 5000, status: 11, customer_status: '待付款',
    customer: { id: 1, phone: '13800138000' },
    items: [{ id: 1, product_name: '名片', quantity: 100, unit_price: 50, total_price: 5000 }],
    created_at: '2026-01-01 10:00:00',
  }))
  getAdminOrderFilesMock = vi.fn(() => Promise.resolve({ data: mockOrderFiles }))
  deleteAdminOrderFileMock = vi.fn(() => Promise.resolve({}))
})

describe('OrderManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(OrderManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders order management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.order-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('订单管理')
  })

  it('loads orders on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminOrdersMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).orders).toHaveLength(2)
    expect((wrapper.vm as any).orders[0].order_no).toBe('Y202601010001')
    expect((wrapper.vm as any).total).toBe(2)
  })

  it('filters orders by keyword on search', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).keyword = 'Y202601010001'
    ;(wrapper.vm as any).onSearch()
    await flushPromises()
    expect(getAdminOrdersMock).toHaveBeenLastCalledWith(expect.objectContaining({ keyword: 'Y202601010001', page: 1, per_page: 10 }))
  })

  it('filters orders by status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).statusFilter = '11'
    ;(wrapper.vm as any).onSearch()
    await flushPromises()
    expect(getAdminOrdersMock).toHaveBeenLastCalledWith(expect.objectContaining({ status: '11', page: 1 }))
  })

  it('opens detail and fetches order detail', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openDetail(mockOrders[0])
    await flushPromises()
    expect(getAdminOrderDetailMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).detailVisible).toBe(true)
    expect((wrapper.vm as any).detail?.order_no).toBe('Y202601010001')
  })

  it('handles pagination change', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).currentPage = 2
    ;(wrapper.vm as any).loadOrders()
    await flushPromises()
    expect(getAdminOrdersMock).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2, per_page: 10 }))
  })

  it('opens file dialog and loads order files', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).openFileDialog(mockOrders[0])
    await flushPromises()
    expect(getAdminOrderFilesMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).fileDialogVisible).toBe(true)
    expect((wrapper.vm as any).orderFiles).toHaveLength(2)
  })

  it('deletes order file', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).openFileDialog(mockOrders[0])
    await flushPromises()
    await (wrapper.vm as any).handleDeleteFile(mockOrderFiles[0])
    await flushPromises()
    expect(deleteAdminOrderFileMock).toHaveBeenCalledWith(1)
    expect(getAdminOrderFilesMock).toHaveBeenCalledTimes(2)
  })

  it('formats file size correctly', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.formatFileSize(1024)).toBe('1.00 KB')
    expect(vm.formatFileSize(1024000)).toBe('1000.00 KB')
    expect(vm.formatFileSize(1048576)).toBe('1.00 MB')
  })
})
