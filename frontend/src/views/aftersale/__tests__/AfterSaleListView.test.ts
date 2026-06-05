import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import AfterSaleListView from '../AfterSaleListView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve('confirm')) },
    ElMessage: { success: vi.fn(), error: vi.fn(), warning: vi.fn() },
  }
})

let getAfterSalesMock = vi.fn()
let cancelAfterSaleMock = vi.fn()

vi.mock('@/api/aftersale', () => ({
  getAfterSales: (...args: any[]) => getAfterSalesMock(...args),
  cancelAfterSale: (...args: any[]) => cancelAfterSaleMock(...args),
}))

beforeEach(() => {
  getAfterSalesMock = vi.fn(() => Promise.resolve({
    data: [
      { id: 1, after_sale_no: 'A202601010001', order_no: 'Y202601010001', type: 1, status: 1, reason: '质量问题', description: '印刷模糊', refund_amount: 5000, approved_amount: 0, created_at: '2026-01-01', items: [{ id: 1, product_name: '名片', quantity: 100, unit_refund: 50 }] },
      { id: 2, after_sale_no: 'A202601010002', order_no: 'Y202601010002', type: 2, status: 4, reason: '尺寸不符', description: null, refund_amount: 0, approved_amount: 0, created_at: '2026-01-02', items: [{ id: 2, product_name: '宣传单', quantity: 50, unit_refund: 100 }] },
    ],
    total: 2, current_page: 1, last_page: 1,
  }))
  cancelAfterSaleMock = vi.fn(() => Promise.resolve({}))
})

describe('AfterSaleListView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/after-sales', name: 'AfterSaleList', component: AfterSaleListView },
        { path: '/after-sale/apply', name: 'AfterSaleApply', component: { template: '<div>Apply</div>' } },
      ],
    })
    return mount(AfterSaleListView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders after sale list page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.after-sale-list-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('我的售后')
  })

  it('loads after sales on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAfterSalesMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).afterSales).toHaveLength(2)
  })

  it('displays after sale type name', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).typeName(1)).toBe('退货')
    expect((wrapper.vm as any).typeName(2)).toBe('换货')
    expect((wrapper.vm as any).typeName(3)).toBe('退款')
  })

  it('displays status name', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect((wrapper.vm as any).statusName(1)).toBe('待审核')
    expect((wrapper.vm as any).statusName(4)).toBe('已完成')
  })

  it('cancels after sale when allowed', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleCancel({ id: 1, status: 1 })
    await flushPromises()
    expect(cancelAfterSaleMock).toHaveBeenCalledWith(1)
  })

  it('does not cancel completed after sale', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleCancel({ id: 2, status: 4 })
    expect(cancelAfterSaleMock).not.toHaveBeenCalled()
  })
})
