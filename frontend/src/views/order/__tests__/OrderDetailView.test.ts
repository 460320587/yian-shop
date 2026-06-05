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
  created_at: '2026-01-01T10:00:00Z',
  items: [
    { id: 101, product_id: 1, product_name: '铜版纸名片', quantity: 1000, unit_price: 2.50, subtotal: 2500.00 },
    { id: 102, product_id: 2, product_name: '宣传单页', quantity: 500, unit_price: 4.24, subtotal: 2120.00 },
  ],
}

const mockPush = vi.fn()
const mockBack = vi.fn()
let getOrderDetailMock = vi.fn()
let cancelOrderMock = vi.fn()
let reorderMock = vi.fn()

vi.mock('@/api/order', () => ({
  getOrderDetail: (...args: any[]) => getOrderDetailMock(...args),
  cancelOrder: (...args: any[]) => cancelOrderMock(...args),
  reorder: (...args: any[]) => reorderMock(...args),
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
})
