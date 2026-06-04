import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import BannerManagementView from '../BannerManagementView.vue'

const mockBanners = [
  { id: 1, title: 'Banner1', image: 'img.jpg', image_mobile: '', link_type: 1, link_target: '', sort: 1, status: 1 },
]
const mockAnnouncements = [
  { id: 1, title: '公告1', content: '内容', type: 1, is_popup: 0, sort: 1, status: 1 },
]

let getAdminBannersMock = vi.fn()
let createBannerMock = vi.fn()
let updateBannerMock = vi.fn()
let deleteBannerMock = vi.fn()
let getAdminAnnouncementsMock = vi.fn()
let createAnnouncementMock = vi.fn()
let updateAnnouncementMock = vi.fn()
let deleteAnnouncementMock = vi.fn()

vi.mock('@/api/admin', () => ({
  getAdminBanners: (...args: any[]) => getAdminBannersMock(...args),
  createBanner: (...args: any[]) => createBannerMock(...args),
  updateBanner: (...args: any[]) => updateBannerMock(...args),
  deleteBanner: (...args: any[]) => deleteBannerMock(...args),
  getAdminAnnouncements: (...args: any[]) => getAdminAnnouncementsMock(...args),
  createAnnouncement: (...args: any[]) => createAnnouncementMock(...args),
  updateAnnouncement: (...args: any[]) => updateAnnouncementMock(...args),
  deleteAnnouncement: (...args: any[]) => deleteAnnouncementMock(...args),
}))

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve()) },
    ElMessage: { success: vi.fn(), error: vi.fn() },
  }
})

beforeEach(() => {
  getAdminBannersMock = vi.fn(() => Promise.resolve(mockBanners))
  createBannerMock = vi.fn(() => Promise.resolve({ id: 2, title: 'New', image: '', image_mobile: '', link_type: 1, link_target: '', sort: 2, status: 1 }))
  updateBannerMock = vi.fn(() => Promise.resolve({ id: 1, title: 'Updated', image: '', image_mobile: '', link_type: 1, link_target: '', sort: 1, status: 1 }))
  deleteBannerMock = vi.fn(() => Promise.resolve(null))
  getAdminAnnouncementsMock = vi.fn(() => Promise.resolve(mockAnnouncements))
  createAnnouncementMock = vi.fn(() => Promise.resolve({ id: 2, title: '新公告', content: '', type: 1, is_popup: 0, sort: 1, status: 1 }))
  updateAnnouncementMock = vi.fn(() => Promise.resolve({ id: 1, title: 'Updated', content: '', type: 1, is_popup: 0, sort: 1, status: 1 }))
  deleteAnnouncementMock = vi.fn(() => Promise.resolve(null))
})

describe('BannerManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    return mount(BannerManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders banner management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.banner-management').exists()).toBe(true)
  })

  it('loads banners and announcements on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAdminBannersMock).toHaveBeenCalledTimes(1)
    expect(getAdminAnnouncementsMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).banners).toHaveLength(1)
    expect((wrapper.vm as any).announcements).toHaveLength(1)
  })

  it('creates banner', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openBannerCreate()
    ;(wrapper.vm as any).bannerForm.title = 'New Banner'
    ;(wrapper.vm as any).bannerForm.image = 'new.jpg'
    ;(wrapper.vm as any).saveBanner()
    await flushPromises()
    expect(createBannerMock).toHaveBeenCalledWith(expect.objectContaining({ title: 'New Banner', image: 'new.jpg' }))
    expect((wrapper.vm as any).bannerDialog).toBe(false)
  })

  it('updates banner', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openBannerEdit(mockBanners[0])
    ;(wrapper.vm as any).bannerForm.title = 'Updated Banner'
    ;(wrapper.vm as any).saveBanner()
    await flushPromises()
    expect(updateBannerMock).toHaveBeenCalledWith(1, expect.objectContaining({ title: 'Updated Banner' }))
    expect((wrapper.vm as any).bannerDialog).toBe(false)
  })

  it('deletes banner', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).removeBanner(mockBanners[0])
    await flushPromises()
    expect(deleteBannerMock).toHaveBeenCalledWith(1)
  })

  it('creates announcement', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openAnnoCreate()
    ;(wrapper.vm as any).annoForm.title = 'New Announcement'
    ;(wrapper.vm as any).annoForm.content = 'Content'
    ;(wrapper.vm as any).saveAnno()
    await flushPromises()
    expect(createAnnouncementMock).toHaveBeenCalledWith(expect.objectContaining({ title: 'New Announcement', content: 'Content' }))
    expect((wrapper.vm as any).annoDialog).toBe(false)
  })

  it('updates announcement', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openAnnoEdit(mockAnnouncements[0])
    ;(wrapper.vm as any).annoForm.title = 'Updated Announcement'
    ;(wrapper.vm as any).saveAnno()
    await flushPromises()
    expect(updateAnnouncementMock).toHaveBeenCalledWith(1, expect.objectContaining({ title: 'Updated Announcement' }))
    expect((wrapper.vm as any).annoDialog).toBe(false)
  })

  it('deletes announcement', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).removeAnno(mockAnnouncements[0])
    await flushPromises()
    expect(deleteAnnouncementMock).toHaveBeenCalledWith(1)
  })
})
