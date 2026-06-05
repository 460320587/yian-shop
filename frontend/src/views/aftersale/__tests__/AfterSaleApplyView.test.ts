import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import AfterSaleApplyView from '../AfterSaleApplyView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessage: { success: vi.fn(), error: vi.fn() },
  }
})

let getOrderDetailMock = vi.fn()
let createAfterSaleMock = vi.fn()

vi.mock('@/api/order', () => ({
  getOrderDetail: (...args: any[]) => getOrderDetailMock(...args),
}))

vi.mock('@/api/aftersale', () => ({
  createAfterSale: (...args: any[]) => createAfterSaleMock(...args),
}))

beforeEach(() => {
  getOrderDetailMock = vi.fn(() => Promise.resolve({
    id: 5,
    order_no: 'Y202601010005',
    status: 60,
    customer_status: '已完成',
    total_amount: 100,
    items: [
      { id: 10, product_id: 1, product_name: '名片', quantity: 100, unit_price: 0.5, subtotal: 50 },
      { id: 11, product_id: 2, product_name: '宣传单', quantity: 50, unit_price: 1, subtotal: 50 },
    ],
  }))
  createAfterSaleMock = vi.fn(() => Promise.resolve({
    id: 1, after_sale_no: 'A202601010001', order_no: 'Y202601010005', type: 1, status: 1,
  }))
})

describe('AfterSaleApplyView', () => {
  async function mountComponent(query = { orderId: '5', orderNo: 'Y202601010005' }) {
    const router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/after-sale/apply', name: 'AfterSaleApply', component: AfterSaleApplyView },
        { path: '/after-sales', name: 'AfterSaleList', component: { template: '<div>AfterSales</div>' } },
      ],
    })
    await router.push({ path: '/after-sale/apply', query })
    await router.isReady()
    return mount(AfterSaleApplyView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders after sale apply page', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    expect(wrapper.find('.after-sale-apply-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('申请售后')
  })

  it('loads order detail on mount', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    expect(getOrderDetailMock).toHaveBeenCalledWith(5)
    expect((wrapper.vm as any).orderItems).toHaveLength(2)
  })

  it('shows error when missing query params', async () => {
    const wrapper = await mountComponent({})
    await flushPromises()
    expect((wrapper.vm as any).errorMsg).toContain('参数缺失')
  })

  it('selects items for after sale', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).selectedItems = { 10: true }
    ;(wrapper.vm as any).quantities = { 10: 50 }
    expect((wrapper.vm as any).getSelectedItems()).toEqual([{ order_item_id: 10, quantity: 50 }])
  })

  it('submits after sale with valid data', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form.type = 1
    ;(wrapper.vm as any).form.reason = '质量问题'
    ;(wrapper.vm as any).form.description = '印刷模糊'
    ;(wrapper.vm as any).selectedItems = { 10: true }
    ;(wrapper.vm as any).quantities = { 10: 50 }
    await (wrapper.vm as any).submitForm()
    await flushPromises()
    expect(createAfterSaleMock).toHaveBeenCalledWith(expect.objectContaining({
      order_no: 'Y202601010005',
      type: 1,
      reason: '质量问题',
      items: [{ order_item_id: 10, quantity: 50 }],
    }))
  })

  it('requires at least one item selected', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form.type = 1
    ;(wrapper.vm as any).form.reason = '质量问题'
    ;(wrapper.vm as any).selectedItems = {}
    const valid = await (wrapper.vm as any).validateForm()
    expect(valid).toBe(false)
  })

  it('requires reason', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form.type = 1
    ;(wrapper.vm as any).form.reason = ''
    ;(wrapper.vm as any).selectedItems = { 10: true }
    const valid = await (wrapper.vm as any).validateForm()
    expect(valid).toBe(false)
  })

  it('navigates to after sale list after submit', async () => {
    const wrapper = await mountComponent()
    await flushPromises()
    const router = (wrapper.vm as any).$router
    const pushSpy = vi.spyOn(router, 'push')
    ;(wrapper.vm as any).form.type = 1
    ;(wrapper.vm as any).form.reason = '质量问题'
    ;(wrapper.vm as any).selectedItems = { 10: true }
    ;(wrapper.vm as any).quantities = { 10: 50 }
    await (wrapper.vm as any).submitForm()
    await flushPromises()
    expect(pushSpy).toHaveBeenCalledWith('/after-sales')
  })
})
