import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import OrderCreateView from '../OrderCreateView.vue'

let createOrderMock = vi.fn()
let getCartMock = vi.fn()
let getAddressesMock = vi.fn()
let getMyCouponsMock = vi.fn()
let getInvoiceTitlesMock = vi.fn()
let getUserInfoMock = vi.fn()

vi.mock('@/api/order', () => ({
  createOrder: (...args: any[]) => createOrderMock(...args),
}))

vi.mock('@/api/cart', () => ({
  getCart: () => getCartMock(),
}))

vi.mock('@/api/address', () => ({
  getAddresses: (params: any) => getAddressesMock(params),
}))

vi.mock('@/api/coupon', () => ({
  getMyCoupons: (status?: number) => getMyCouponsMock(status),
}))

vi.mock('@/api/invoice', () => ({
  getInvoiceTitles: (...args: any[]) => getInvoiceTitlesMock(...args),
}))

vi.mock('element-plus', () => ({
  ElMessage: { success: vi.fn(), error: vi.fn(), warning: vi.fn() },
}))

vi.mock('@/api/user', () => ({
  getUserInfo: () => getUserInfoMock(),
}))

beforeEach(() => {
  createOrderMock = vi.fn(() =>
    Promise.resolve({ order_no: 'Y202601010001', total_amount: 1000, status: 1 })
  )
  getCartMock = vi.fn(() =>
    Promise.resolve({
      items: [
        {
          id: 1,
          product_id: 101,
          product_name: '测试商品A',
          thumbnail: '/a.jpg',
          quantity: 2,
          unit_price: 500,
          subtotal: 1000,
          selected: true,
        },
        {
          id: 2,
          product_id: 102,
          product_name: '测试商品B',
          thumbnail: '/b.jpg',
          quantity: 1,
          unit_price: 300,
          subtotal: 300,
          selected: false,
        },
      ],
      summary: { total_count: 2, selected_count: 1, selected_subtotal: 1000 },
    })
  )
  getAddressesMock = vi.fn(() =>
    Promise.resolve({
      data: [
        {
          id: 1,
          contact_name: '张三',
          contact_phone: '13800138000',
          province_name: '北京市',
          city_name: '北京市',
          county_name: '朝阳区',
          detail_address: 'xxx路xxx号',
          full_address: '北京市北京市朝阳区xxx路xxx号',
          is_default: true,
          tag: '公司',
        },
        {
          id: 2,
          contact_name: '李四',
          contact_phone: '13900139000',
          province_name: '上海市',
          city_name: '上海市',
          county_name: '浦东新区',
          detail_address: 'yyy路yyy号',
          full_address: '上海市上海市浦东新区yyy路yyy号',
          is_default: false,
          tag: null,
        },
      ],
      total: 2,
      current_page: 1,
      last_page: 1,
    })
  )
  getMyCouponsMock = vi.fn(() =>
    Promise.resolve({
      data: [
        {
          id: 1,
          code: 'COUPON001',
          coupon_id: 1,
          status: 1,
          claimed_at: '2026-01-01',
          used_at: null,
          expired_at: null,
          coupon: {
            id: 1,
            name: '满100减20',
            description: null,
            type: 1,
            value: 2000,
            min_amount: 10000,
            max_discount: null,
            start_at: '2026-01-01',
            end_at: '2026-12-31',
            total_count: 100,
            claimed_count: 10,
            per_customer_limit: 1,
            status: 1,
          },
        },
      ],
      total: 1,
      current_page: 1,
      last_page: 1,
    })
  )
  getInvoiceTitlesMock = vi.fn(() =>
    Promise.resolve([
      {
        id: 1,
        title_type: 1,
        invoice_category: 1,
        company_name: '测试公司',
        tax_number: '91110000XXXXXXXX',
        register_address: null,
        register_phone: null,
        bank_name: null,
        bank_account: null,
        is_default: 1,
      },
    ])
  )
  getUserInfoMock = vi.fn(() =>
    Promise.resolve({
      id: 1,
      phone: '13800138000',
      nickname: '测试用户',
      avatar: null,
      type: 3,
      auth_status: 0,
      vip_level: 0,
      balance: 5000,
    })
  )
})

describe('OrderCreateView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({
      history: createWebHistory(),
      routes: [],
    })
    return mount(OrderCreateView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders checkout page with title', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.order-create-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('确认订单')
  })

  it('loads cart items and shows only selected', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    expect(vm.selectedItems.length).toBe(1)
    expect(vm.selectedItems[0].product_name).toBe('测试商品A')
    expect(wrapper.text()).toContain('测试商品A')
    expect(wrapper.text()).not.toContain('测试商品B')
  })

  it('loads addresses and selects default', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    expect(vm.addresses.length).toBe(2)
    expect(vm.selectedAddressId).toBe(1)
    expect(vm.selectedAddress.contact_name).toBe('张三')
    expect(wrapper.text()).toContain('张三')
    expect(wrapper.text()).toContain('李四')
  })

  it('can select different address', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.selectAddress(2)
    await flushPromises()
    expect(vm.selectedAddressId).toBe(2)
    expect(vm.selectedAddress.contact_name).toBe('李四')
  })

  it('loads coupons', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    expect(vm.coupons.length).toBe(1)
    expect(vm.coupons[0].code).toBe('COUPON001')
  })

  it('loads invoice titles', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    expect(vm.invoiceTitles.length).toBe(1)
    expect(vm.invoiceTitles[0].company_name).toBe('测试公司')
  })

  it('loads user info', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    expect(vm.userInfo).not.toBeNull()
    expect(vm.userInfo.balance).toBe(5000)
  })

  it('calculates subtotal correctly', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    expect(vm.subtotal).toBe(1000)
    expect(vm.formatPrice(vm.subtotal)).toBe('10.00')
  })

  it('can select invoice title', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.selectInvoiceTitle(vm.invoiceTitles[0])
    expect(vm.invoiceTitleInput).toBe('测试公司')
    expect(vm.invoiceTaxNoInput).toBe('91110000XXXXXXXX')
  })

  it('submits order with address and cart items', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.submitOrder()
    await flushPromises()

    expect(createOrderMock).toHaveBeenCalledTimes(1)
    const payload = createOrderMock.mock.calls[0][0]
    expect(payload.address_id).toBe(1)
    expect(payload.cart_item_ids).toEqual([1])
    expect(payload.coupon_code).toBeUndefined()
  })

  it('warns when no address selected', async () => {
    getAddressesMock = vi.fn(() => Promise.resolve({ data: [], total: 0, current_page: 1, last_page: 1 }))
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.submitOrder()
    await flushPromises()

    expect(createOrderMock).not.toHaveBeenCalled()
  })

  it('submits order with coupon when selected', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.selectedCouponCode = 'COUPON001'
    await vm.submitOrder()
    await flushPromises()

    const payload = createOrderMock.mock.calls[0][0]
    expect(payload.coupon_code).toBe('COUPON001')
  })

  it('validates invoice email format', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const vm = wrapper.vm as any
    vm.invoiceType = 1
    vm.invoiceTitleInput = '测试公司'
    vm.invoiceEmailInput = 'invalid-email'
    await vm.submitOrder()
    await flushPromises()

    expect(createOrderMock).not.toHaveBeenCalled()

    vm.invoiceEmailInput = 'test@example.com'
    createOrderMock.mockClear()
    await vm.submitOrder()
    await flushPromises()

    expect(createOrderMock).toHaveBeenCalledTimes(1)
  })

  it('shows empty cart message when no selected items', async () => {
    getCartMock = vi.fn(() =>
      Promise.resolve({
        items: [],
        summary: { total_count: 0, selected_count: 0, selected_subtotal: 0 },
      })
    )
    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.text()).toContain('购物车暂无选中商品')
  })

  it('shows empty address message when no addresses', async () => {
    getAddressesMock = vi.fn(() =>
      Promise.resolve({ data: [], total: 0, current_page: 1, last_page: 1 })
    )
    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.text()).toContain('去添加地址')
  })
})
