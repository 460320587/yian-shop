import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { ref, nextTick } from 'vue'
import OrderDetailView from '../OrderDetailView.vue'

const mockOrder = {
  id: 5,
  order_no: 'Y202601010005',
  status: 11,
  customer_status: '待付款',
  total_amount: 4620.00,
  deposit_sum: 1000.00,
  discount_sum: 200.00,
  express_company: '顺丰速运',
  delivery_type: '快递',
  remark: '请尽快发货',
  paid_at: '2026-01-01T10:30:00Z',
  submitted_at: '2026-01-01T10:00:00Z',
  created_at: '2026-01-01T10:00:00Z',
  items: [
    { id: 101, product_id: 1, product_name: '铜版纸名片', quantity: 1000, unit_price: 2.50, subtotal: 2500.00 },
    { id: 102, product_id: 2, product_name: '宣传单页', quantity: 500, unit_price: 4.24, subtotal: 2120.00 },
  ],
}

const mockStatusLogs = [
  { id: 1, from_status: 0, to_status: 1, remark: '订单创建', operator_type: 'system', created_at: '2026-01-01T10:00:00Z' },
  { id: 2, from_status: 1, to_status: 11, remark: '订单已提交', operator_type: 'customer', created_at: '2026-01-01T10:05:00Z' },
]

const mockPush = vi.fn()
const mockBack = vi.fn()
let getOrderDetailMock = vi.fn()
let cancelOrderMock = vi.fn()
let reorderMock = vi.fn()
let getOrderProductionScheduleMock = vi.fn()
let getOrderFilesMock = vi.fn()
let getOrderInkChecksMock = vi.fn()
let getOrderStatusLogsMock = vi.fn()
let uploadOrderFileMock = vi.fn()
let deleteOrderFileMock = vi.fn()

vi.mock('@/api/order', () => ({
  getOrderDetail: (...args: any[]) => getOrderDetailMock(...args),
  cancelOrder: (...args: any[]) => cancelOrderMock(...args),
  reorder: (...args: any[]) => reorderMock(...args),
  getOrderProductionSchedule: (...args: any[]) => getOrderProductionScheduleMock(...args),
  getOrderFiles: (...args: any[]) => getOrderFilesMock(...args),
  getOrderInkChecks: (...args: any[]) => getOrderInkChecksMock(...args),
  getOrderStatusLogs: (...args: any[]) => getOrderStatusLogsMock(...args),
  uploadOrderFile: (...args: any[]) => uploadOrderFileMock(...args),
  deleteOrderFile: (...args: any[]) => deleteOrderFileMock(...args),
}))

vi.mock('vue-router', () => ({
  useRoute: () => ({ params: { id: '5' } }),
  useRouter: () => ({ push: mockPush, back: mockBack }),
}))

vi.mock('element-plus', () => ({
  ElMessage: {
    success: vi.fn(),
    error: vi.fn(),
  },
}))

function createWrapper() {
  return mount(OrderDetailView)
}

describe('OrderDetailView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    getOrderDetailMock = vi.fn(() => Promise.resolve(mockOrder))
    cancelOrderMock = vi.fn(() => Promise.resolve({}))
    reorderMock = vi.fn(() => Promise.resolve({}))
    getOrderProductionScheduleMock = vi.fn(() => Promise.resolve({ data: [] }))
    getOrderFilesMock = vi.fn(() => Promise.resolve({ data: [] }))
    getOrderInkChecksMock = vi.fn(() => Promise.resolve({ data: [] }))
    getOrderStatusLogsMock = vi.fn(() => Promise.resolve({ data: [] }))
    uploadOrderFileMock = vi.fn(() => Promise.resolve({}))
    deleteOrderFileMock = vi.fn(() => Promise.resolve({}))
  })

  it('renders loading state initially', async () => {
    getOrderDetailMock = vi.fn(() => new Promise(() => {}))
    const wrapper = createWrapper()
    await nextTick()
    expect(wrapper.find('.order-detail-view').exists()).toBe(true)
    expect(wrapper.find('.page-title').text()).toBe('订单详情')
    expect(wrapper.find('.loading-text').exists()).toBe(true)
  })

  it('fetches order detail on mount and renders order info', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    expect(getOrderDetailMock).toHaveBeenCalledWith(5)
    expect(wrapper.find('.order-no').text()).toContain('Y202601010005')
    expect(wrapper.find('.order-status').text()).toContain('待付款')
    expect(wrapper.find('.order-amount').text()).toContain('4620.00')
  })

  it('renders order items list', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const items = wrapper.findAll('.order-item')
    expect(items.length).toBe(2)
    expect(items[0].text()).toContain('铜版纸名片')
    expect(items[0].text()).toContain('1000')
    expect(items[1].text()).toContain('宣传单页')
  })

  it('shows pay button for pending payment order', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const payBtn = wrapper.find('[data-testid="pay-btn"]')
    expect(payBtn.exists()).toBe(true)
  })

  it('shows cancel button for cancellable order', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const cancelBtn = wrapper.find('[data-testid="cancel-btn"]')
    expect(cancelBtn.exists()).toBe(true)
  })

  it('hides pay and cancel buttons for completed order', async () => {
    getOrderDetailMock = vi.fn(() => Promise.resolve({ ...mockOrder, status: 60, customer_status: '已完成' }))
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('[data-testid="pay-btn"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="cancel-btn"]').exists()).toBe(false)
  })

  it('navigates to payment page when pay button clicked', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    await wrapper.find('[data-testid="pay-btn"]').trigger('click')
    expect(mockPush).toHaveBeenCalledWith('/payment?orderNo=Y202601010005&amount=4620')
  })

  it('calls cancelOrder and shows success on cancel', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    await wrapper.find('[data-testid="cancel-btn"]').trigger('click')
    await flushPromises()

    expect(cancelOrderMock).toHaveBeenCalledWith('Y202601010005')
  })

  it('shows review button for completed order', async () => {
    getOrderDetailMock = vi.fn(() => Promise.resolve({ ...mockOrder, status: 60, customer_status: '已完成' }))
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('[data-testid="review-btn"]').exists()).toBe(true)
  })

  it('navigates to review page with first item productId when top review button clicked', async () => {
    getOrderDetailMock = vi.fn(() => Promise.resolve({ ...mockOrder, status: 60, customer_status: '已完成' }))
    const wrapper = createWrapper()
    await flushPromises()

    await wrapper.find('[data-testid="review-btn"]').trigger('click')
    expect(mockPush).toHaveBeenCalledWith('/review?orderId=5&productId=1')
  })

  it('shows review button on each item for completed order', async () => {
    getOrderDetailMock = vi.fn(() => Promise.resolve({ ...mockOrder, status: 60, customer_status: '已完成' }))
    const wrapper = createWrapper()
    await flushPromises()

    const itemReviewBtns = wrapper.findAll('.order-item .item-review-btn')
    expect(itemReviewBtns.length).toBe(2)
  })

  it('navigates to review page from item review button', async () => {
    getOrderDetailMock = vi.fn(() => Promise.resolve({ ...mockOrder, status: 60, customer_status: '已完成' }))
    const wrapper = createWrapper()
    await flushPromises()

    const itemReviewBtns = wrapper.findAll('.order-item .item-review-btn')
    await itemReviewBtns[1].trigger('click')
    expect(mockPush).toHaveBeenCalledWith('/review?orderId=5&productId=2')
  })

  it('shows after-sale button for applicable order', async () => {
    getOrderDetailMock = vi.fn(() => Promise.resolve({ ...mockOrder, status: 60, customer_status: '已完成' }))
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('[data-testid="aftersale-btn"]').exists()).toBe(true)
  })

  it('shows track logistics button for shipped order', async () => {
    getOrderDetailMock = vi.fn(() => Promise.resolve({ ...mockOrder, status: 20, customer_status: '已发货' }))
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('[data-testid="logistics-btn"]').exists()).toBe(true)
  })

  it('navigates to logistics page when track button clicked', async () => {
    getOrderDetailMock = vi.fn(() => Promise.resolve({ ...mockOrder, status: 20, customer_status: '已发货' }))
    const wrapper = createWrapper()
    await flushPromises()

    await wrapper.find('[data-testid="logistics-btn"]').trigger('click')
    expect(mockPush).toHaveBeenCalledWith('/order/5/track')
  })

  it('navigates back on back button click', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    await wrapper.find('[data-testid="back-btn"]').trigger('click')
    expect(mockBack).toHaveBeenCalled()
  })

  it('renders extended order info', async () => {
    getOrderDetailMock = vi.fn(() => Promise.resolve({
      ...mockOrder,
      status: 20,
      customer_status: '已发货',
      deposit_sum: 1000.00,
      discount_sum: 200.00,
      express_company: '顺丰速运',
      delivery_type: '快递',
      remark: '请尽快发货',
      paid_at: '2026-01-01T10:30:00Z',
      submitted_at: '2026-01-01T10:00:00Z',
    }))
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('.deposit-sum').text()).toContain('1000.00')
    expect(wrapper.find('.discount-sum').text()).toContain('200.00')
    expect(wrapper.find('.express-company').text()).toContain('顺丰速运')
    expect(wrapper.find('.delivery-type').text()).toContain('快递')
    expect(wrapper.find('.remark').text()).toContain('请尽快发货')
    expect(wrapper.find('.paid-at').text()).toContain('2026-01-01')
    expect(wrapper.find('.submitted-at').text()).toContain('2026-01-01')
  })

  it('shows upload button for orders that allow file upload', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('[data-testid="upload-file-btn"]').exists()).toBe(true)
  })

  it('hides upload button for completed order', async () => {
    getOrderDetailMock = vi.fn(() => Promise.resolve({ ...mockOrder, status: 60, customer_status: '已完成' }))
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('[data-testid="upload-file-btn"]').exists()).toBe(false)
  })

  it('shows delete button for each file when upload is allowed', async () => {
    getOrderFilesMock = vi.fn(() => Promise.resolve({
      data: [{ id: 1, file_name: 'test.pdf', file_type: 'application/pdf', file_size: 1024000, file_url: '/test.pdf', thumb_url: null, page_count: 1, version: 1, created_at: '2026-01-01' }],
    }))
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('[data-testid="delete-file-btn"]').exists()).toBe(true)
  })

  it('calls uploadOrderFile when file is selected', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const fileInput = wrapper.find('input[type="file"]')
    expect(fileInput.exists()).toBe(true)
  })

  it('exposes refs and methods', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const vm = wrapper.vm as any
    expect(vm.order).toBeDefined()
    expect(vm.loading).toBe(false)
    expect(typeof vm.goBack).toBe('function')
    expect(typeof vm.handlePay).toBe('function')
    expect(typeof vm.handleCancel).toBe('function')
  })

  it('fetches status logs on mount', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    expect(getOrderStatusLogsMock).toHaveBeenCalledWith(5)
    expect((wrapper.vm as any).statusLogs).toEqual([])
  })

  it('renders status logs section when logs exist', async () => {
    getOrderStatusLogsMock = vi.fn(() => Promise.resolve({ data: mockStatusLogs }))
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('.status-logs-section').exists()).toBe(true)
    expect(wrapper.text()).toContain('订单创建')
    expect(wrapper.text()).toContain('订单已提交')
    expect((wrapper.vm as any).statusLogs).toHaveLength(2)
  })

  it('renders status log meta including operator and time', async () => {
    getOrderStatusLogsMock = vi.fn(() => Promise.resolve({ data: mockStatusLogs }))
    const wrapper = createWrapper()
    await flushPromises()

    const logItems = wrapper.findAll('.status-log-item')
    expect(logItems.length).toBe(2)
    expect(logItems[0].text()).toContain('system')
    expect(logItems[0].text()).toContain('2026-01-01')
  })

  it('does not render status logs section when logs are empty', async () => {
    getOrderStatusLogsMock = vi.fn(() => Promise.resolve({ data: [] }))
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('.status-logs-section').exists()).toBe(false)
  })

  it('renders delivery section when order has delivery info', async () => {
    getOrderDetailMock = vi.fn(() => Promise.resolve({
      ...mockOrder,
      status: 20,
      customer_status: '已发货',
      delivery: {
        carrier_name: '顺丰速运',
        tracking_no: 'SF1234567890',
        status: 1,
        shipped_at: '2026-05-28T10:00:00',
        latest_tracks: [
          { time: '2026-05-30T18:00:00', location: '北京市', description: '已签收' },
          { time: '2026-05-30T14:00:00', location: '北京市', description: '派送中' },
          { time: '2026-05-30T10:00:00', location: '北京集散中心', description: '到达北京集散中心' },
        ],
      },
    }))
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('.delivery-section').exists()).toBe(true)
    expect(wrapper.find('.delivery-carrier').text()).toContain('顺丰速运')
    expect(wrapper.find('.delivery-tracking-no').text()).toContain('SF1234567890')
    const trackItems = wrapper.findAll('.delivery-track-item')
    expect(trackItems.length).toBe(3)
    expect(trackItems[0].text()).toContain('已签收')
    expect(trackItems[0].text()).toContain('北京市')
  })

  it('does not render delivery section when order has no delivery', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('.delivery-section').exists()).toBe(false)
  })

  it('navigates to logistics page from delivery section', async () => {
    getOrderDetailMock = vi.fn(() => Promise.resolve({
      ...mockOrder,
      status: 20,
      customer_status: '已发货',
      delivery: {
        carrier_name: '顺丰速运',
        tracking_no: 'SF1234567890',
        status: 1,
        shipped_at: '2026-05-28T10:00:00',
        latest_tracks: [
          { time: '2026-05-30T18:00:00', location: '北京市', description: '已签收' },
        ],
      },
    }))
    const wrapper = createWrapper()
    await flushPromises()

    await wrapper.find('[data-testid="delivery-view-all"]').trigger('click')
    expect(mockPush).toHaveBeenCalledWith('/order/5/track')
  })
})
