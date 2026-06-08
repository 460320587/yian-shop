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
  { id: 2, order_no: 'Y202601010002', customer_name: '李四', total_amount: 12000, status: 12, customer_status: '已付款', created_at: '2026-01-02 14:30:00' },
  { id: 3, order_no: 'Y202601010003', customer_name: '王五', total_amount: 8000, status: 20, customer_status: '已发货', created_at: '2026-01-03 09:00:00' },
]

let getAdminOrdersMock = vi.fn()
let getAdminOrderDetailMock = vi.fn()
let getAdminOrderFilesMock = vi.fn()
let deleteAdminOrderFileMock = vi.fn()
let confirmAdminOrderPaymentMock = vi.fn()
let shipAdminOrderMock = vi.fn()
let completeAdminOrderMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminOrders: (...args: any[]) => getAdminOrdersMock(...args),
  getAdminOrderDetail: (...args: any[]) => getAdminOrderDetailMock(...args),
  getAdminOrderFiles: (...args: any[]) => getAdminOrderFilesMock(...args),
  deleteAdminOrderFile: (...args: any[]) => deleteAdminOrderFileMock(...args),
  confirmAdminOrderPayment: (...args: any[]) => confirmAdminOrderPaymentMock(...args),
  shipAdminOrder: (...args: any[]) => shipAdminOrderMock(...args),
  completeAdminOrder: (...args: any[]) => completeAdminOrderMock(...args),
}))

const mockOrderFiles = [
  { id: 1, order_id: 1, file_name: 'design.pdf', file_url: '/files/1.pdf', thumb_url: '/files/1_thumb.jpg', file_size: 1024000, file_type: 'pdf', page_count: 2, status: 1, created_at: '2026-01-01 10:00:00' },
  { id: 2, order_id: 1, file_name: 'logo.png', file_url: '/files/2.png', thumb_url: null, file_size: 51200, file_type: 'image', page_count: null, status: 1, created_at: '2026-01-01 11:00:00' },
]

beforeEach(() => {
  getAdminOrdersMock = vi.fn(() => Promise.resolve({ data: mockOrders, total: 3, current_page: 1, last_page: 1 }))
  getAdminOrderDetailMock = vi.fn(() => Promise.resolve({
    id: 1, order_no: 'Y202601010001', customer_name: '张三', total_amount: 5000, status: 11, customer_status: '待付款',
    customer: { id: 1, phone: '13800138000' },
    items: [{ id: 1, product_name: '名片', quantity: 100, unit_price: 50, total_price: 5000 }],
    created_at: '2026-01-01 10:00:00',
  }))
  getAdminOrderFilesMock = vi.fn(() => Promise.resolve({ data: mockOrderFiles }))
  deleteAdminOrderFileMock = vi.fn(() => Promise.resolve({}))
  confirmAdminOrderPaymentMock = vi.fn(() => Promise.resolve({}))
  shipAdminOrderMock = vi.fn(() => Promise.resolve({}))
  completeAdminOrderMock = vi.fn(() => Promise.resolve({}))
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
    expect((wrapper.vm as any).orders).toHaveLength(3)
    expect((wrapper.vm as any).orders[0].order_no).toBe('Y202601010001')
    expect((wrapper.vm as any).total).toBe(3)
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

  it('shows confirm payment button for pending payment orders', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.canConfirmPayment(11)).toBe(true)
    expect(vm.canConfirmPayment(12)).toBe(false)
  })

  it('shows ship button for paid or pending delivery orders', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.canShip(12)).toBe(true)
    expect(vm.canShip(17)).toBe(true)
    expect(vm.canShip(11)).toBe(false)
  })

  it('shows complete button for shipped orders', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    expect(vm.canComplete(20)).toBe(true)
    expect(vm.canComplete(11)).toBe(false)
  })

  it('calls confirm payment API', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleConfirmPayment(mockOrders[0])
    await flushPromises()
    expect(confirmAdminOrderPaymentMock).toHaveBeenCalledWith(1)
    expect(getAdminOrdersMock).toHaveBeenCalledTimes(2)
  })

  it('opens ship dialog and submits', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openShipDialog(mockOrders[1])
    expect((wrapper.vm as any).shipDialogVisible).toBe(true)
    ;(wrapper.vm as any).shipForm.express_company = '顺丰速运'
    ;(wrapper.vm as any).shipForm.tracking_no = 'SF123456'
    await (wrapper.vm as any).handleShip()
    await flushPromises()
    expect(shipAdminOrderMock).toHaveBeenCalledWith(2, { express_company: '顺丰速运', tracking_no: 'SF123456' })
    expect((wrapper.vm as any).shipDialogVisible).toBe(false)
  })

  it('calls complete order API', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleComplete(mockOrders[2])
    await flushPromises()
    expect(completeAdminOrderMock).toHaveBeenCalledWith(3)
    expect(getAdminOrdersMock).toHaveBeenCalledTimes(2)
  })
})
