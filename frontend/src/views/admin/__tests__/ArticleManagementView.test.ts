import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import ArticleManagementView from '../ArticleManagementView.vue'

const mockArticles = [
  { id: 1, title: '公司新闻', slug: 'company-news', type: 1, author: '编辑部', view_count: 120, sort: 1, status: 1, created_at: '2026-01-01 10:00:00' },
  { id: 2, title: '春节公告', slug: 'spring-notice', type: 2, author: null, view_count: 50, sort: 2, status: 0, created_at: '2026-01-02 14:30:00' },
]

let getAdminArticlesMock = vi.fn()
let getAdminArticleDetailMock = vi.fn()
let createAdminArticleMock = vi.fn()
let updateAdminArticleMock = vi.fn()
let deleteAdminArticleMock = vi.fn()
let toggleAdminArticleStatusMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminArticles: (...args: any[]) => getAdminArticlesMock(...args),
  getAdminArticleDetail: (...args: any[]) => getAdminArticleDetailMock(...args),
  createAdminArticle: (...args: any[]) => createAdminArticleMock(...args),
  updateAdminArticle: (...args: any[]) => updateAdminArticleMock(...args),
  deleteAdminArticle: (...args: any[]) => deleteAdminArticleMock(...args),
  toggleAdminArticleStatus: (...args: any[]) => toggleAdminArticleStatusMock(...args),
}))

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve()) },
  }
})

beforeEach(() => {
  getAdminArticlesMock = vi.fn(() => Promise.resolve({ data: mockArticles, total: 2, current_page: 1, last_page: 1 }))
  getAdminArticleDetailMock = vi.fn(() => Promise.resolve({ ...mockArticles[0], content: '新闻内容', summary: '摘要' }))
  createAdminArticleMock = vi.fn(() => Promise.resolve({ id: 3, title: '新文章', slug: 'new-article' }))
  updateAdminArticleMock = vi.fn(() => Promise.resolve({ id: 1, title: '新标题', slug: 'company-news' }))
  deleteAdminArticleMock = vi.fn(() => Promise.resolve({}))
  toggleAdminArticleStatusMock = vi.fn(() => Promise.resolve({ status: 0 }))
})

describe('ArticleManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(ArticleManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders article management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.article-management').exists()).toBe(true)
    expect(wrapper.text()).toContain('文章管理')
  })

  it('loads articles on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminArticlesMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).articles).toHaveLength(2)
  })

  it('filters articles by type', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).typeFilter = 1
    ;(wrapper.vm as any).onSearch()
    await flushPromises()
    expect(getAdminArticlesMock).toHaveBeenLastCalledWith(expect.objectContaining({ type: 1, page: 1, per_page: 10 }))
  })

  it('filters articles by status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).statusFilter = 0
    ;(wrapper.vm as any).onSearch()
    await flushPromises()
    expect(getAdminArticlesMock).toHaveBeenLastCalledWith(expect.objectContaining({ status: 0, page: 1 }))
  })

  it('filters articles by keyword', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).keyword = '新闻'
    ;(wrapper.vm as any).onSearch()
    await flushPromises()
    expect(getAdminArticlesMock).toHaveBeenLastCalledWith(expect.objectContaining({ keyword: '新闻', page: 1 }))
  })

  it('creates new article', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openCreate()
    ;(wrapper.vm as any).form.title = '新文章'
    ;(wrapper.vm as any).form.slug = 'new-article'
    ;(wrapper.vm as any).form.type = 1
    ;(wrapper.vm as any).form.content = '文章内容'
    await (wrapper.vm as any).handleSave()
    await flushPromises()
    expect(createAdminArticleMock).toHaveBeenCalledWith(expect.objectContaining({ title: '新文章', slug: 'new-article', type: 1, content: '文章内容' }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
  })

  it('opens edit dialog and loads article data', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openEdit(mockArticles[0])
    await flushPromises()
    expect(getAdminArticleDetailMock).toHaveBeenCalledWith(1)
    expect((wrapper.vm as any).isEdit).toBe(true)
    expect((wrapper.vm as any).form.title).toBe('公司新闻')
    expect((wrapper.vm as any).dialogVisible).toBe(true)
  })

  it('updates existing article', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openEdit(mockArticles[0])
    await flushPromises()
    ;(wrapper.vm as any).form.title = '新标题'
    await (wrapper.vm as any).handleSave()
    await flushPromises()
    expect(updateAdminArticleMock).toHaveBeenCalledWith(1, expect.objectContaining({ title: '新标题' }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
    expect(getAdminArticlesMock).toHaveBeenCalledTimes(2)
  })

  it('toggles article status', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleToggle(mockArticles[0])
    await flushPromises()
    expect(toggleAdminArticleStatusMock).toHaveBeenCalledWith(1)
    expect(getAdminArticlesMock).toHaveBeenCalledTimes(2)
  })

  it('deletes article', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleDelete(mockArticles[1])
    await flushPromises()
    expect(deleteAdminArticleMock).toHaveBeenCalledWith(2)
    expect(getAdminArticlesMock).toHaveBeenCalledTimes(2)
  })
})
