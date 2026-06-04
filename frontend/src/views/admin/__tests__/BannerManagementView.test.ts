import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import BannerManagementView from '../BannerManagementView.vue'

vi.mock('@/api/admin', () => ({
  getAdminBanners: vi.fn(() => Promise.resolve([{ id: 1, title: 'Banner1', image: 'img.jpg', image_mobile: '', link_type: 1, link_target: '', sort: 1, status: 1 }])),
  createBanner: vi.fn(() => Promise.resolve({ id: 2, title: 'New', image: '', image_mobile: '', link_type: 1, link_target: '', sort: 2, status: 1 })),
  updateBanner: vi.fn(() => Promise.resolve({ id: 1, title: 'Updated', image: '', image_mobile: '', link_type: 1, link_target: '', sort: 1, status: 1 })),
  deleteBanner: vi.fn(() => Promise.resolve(null)),
  getAdminAnnouncements: vi.fn(() => Promise.resolve([{ id: 1, title: '公告1', content: '内容', type: 1, is_popup: 0, sort: 1, status: 1 }])),
  createAnnouncement: vi.fn(() => Promise.resolve({ id: 2, title: '新公告', content: '', type: 1, is_popup: 0, sort: 1, status: 1 })),
  updateAnnouncement: vi.fn(() => Promise.resolve({ id: 1, title: 'Updated', content: '', type: 1, is_popup: 0, sort: 1, status: 1 })),
  deleteAnnouncement: vi.fn(() => Promise.resolve(null)),
}))

describe('BannerManagementView', () => {
  it('renders banner management', async () => {
    setActivePinia(createPinia())
    const router = createRouter({ history: createWebHistory(), routes: [] })
    const wrapper = mount(BannerManagementView, {
      global: { plugins: [createPinia(), router] },
    })
    await new Promise((r) => setTimeout(r, 100))
    expect(wrapper.find('.banner-management').exists()).toBe(true)
  })
})
