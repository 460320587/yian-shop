import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import ReviewManagementView from '../ReviewManagementView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve('confirm')) },
    ElMessage: { success: vi.fn(), error: vi.fn(), warning: vi.fn() },
  }
})

const mockReviews = [
  {
    id: 1,
    customer_id: 1,
    product_id: 1,
    order_id: 5,
    rating: 5,
    content: '质量很好，印刷清晰',
    images: ['/uploads/review_1.jpg'],
    reply: null,
    reply_at: null,
    is_show: true,
    created_at: '2026-01-10 10:00:00',
    customer: { id: 1, nickname: '张三' },
    product: { id: 1, name: '铜版纸名片', thumbnail: '/img/card.jpg' },
  },
  {
    id: 2,
    customer_id: 2,
    product_id: 2,
    order_id: 6,
    rating: 3,
    content: '一般般，有色差',
    images: null,
    reply: '感谢您的反馈，我们会改进',
    reply_at: '2026-01-11 14:00:00',
    is_show: false,
    created_at: '2026-01-11 09:00:00',
    customer: { id: 2, nickname: '李四' },
    product: { id: 2, name: '宣传单页', thumbnail: '/img/flyer.jpg' },
  },
]

let getAdminReviewsMock = vi.fn()
let getAdminReviewDetailMock = vi.fn()
let replyAdminReviewMock = vi.fn()
let toggleAdminReviewShowMock = vi.fn()
let deleteAdminReviewMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminReviews: (...args: any[]) => getAdminReviewsMock(...args),
  getAdminReviewDetail: (...args: any[]) => getAdminReviewDetailMock(...args),
  replyAdminReview: (...args: any[]) => replyAdminReviewMock(...args),
  toggleAdminReviewShow: (...args: any[]) => toggleAdminReviewShowMock(...args),
  deleteAdminReview: (...args: any[]) => deleteAdminReviewMock(...args),
}))

beforeEach(() => {
  getAdminReviewsMock = vi.fn(() => Promise.resolve({ data: mockReviews, total: 2, current_page: 1, last_page: 1 }))
  getAdminReviewDetailMock = vi.fn((id) => Promise.resolve(mockReviews.find((r) => r.id === id)))
  replyAdminReviewMock = vi.fn(() => Promise.resolve({ id: 1, reply: '感谢评价', reply_at: '2026-01-12 10:00:00' }))
  toggleAdminReviewShowMock = vi.fn(() => Promise.resolve({ is_show: false }))
  deleteAdminReviewMock = vi.fn(() => Promise.resolve({}))
})

describe('ReviewManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(ReviewManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders review management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.review-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('评价管理')
  })

  it('loads reviews on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminReviewsMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).reviews).toHaveLength(2)
    expect((wrapper.vm as any).total).toBe(2)
  })

  it('filters by is_show', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).showFilter = true
    ;(wrapper.vm as any).onFilter()
    await flushPromises()
    expect(getAdminReviewsMock).toHaveBeenLastCalledWith(expect.objectContaining({ is_show: true, page: 1, per_page: 10 }))
  })

  it('filters by keyword', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).keyword = '印刷'
    ;(wrapper.vm as any).onFilter()
    await flushPromises()
    expect(getAdminReviewsMock).toHaveBeenLastCalledWith(expect.objectContaining({ keyword: '印刷', page: 1, per_page: 10 }))
  })

  it('opens detail dialog', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).openDetail(mockReviews[0])
    await flushPromises()
    expect(getAdminReviewDetailMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).detailDialogVisible).toBe(true)
  })

  it('opens reply dialog and submits reply', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openReply(mockReviews[0])
    expect((wrapper.vm as any).replyDialogVisible).toBe(true)
    ;(wrapper.vm as any).replyForm.reply = '感谢您的评价'
    await (wrapper.vm as any).handleReply()
    await flushPromises()
    expect(replyAdminReviewMock).toHaveBeenCalledWith(1, { reply: '感谢您的评价' })
    expect((wrapper.vm as any).replyDialogVisible).toBe(false)
  })

  it('toggles show status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleToggleShow(mockReviews[0])
    await flushPromises()
    expect(toggleAdminReviewShowMock).toHaveBeenCalledWith(1)
    expect(getAdminReviewsMock).toHaveBeenCalledTimes(2)
  })

  it('deletes review', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleDelete(mockReviews[0])
    await flushPromises()
    expect(deleteAdminReviewMock).toHaveBeenCalledWith(1)
    expect(getAdminReviewsMock).toHaveBeenCalledTimes(2)
  })

  it('handles pagination change', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).currentPage = 2
    ;(wrapper.vm as any).loadReviews()
    await flushPromises()
    expect(getAdminReviewsMock).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2, per_page: 10 }))
  })
})
