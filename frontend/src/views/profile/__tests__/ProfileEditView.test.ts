import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ProfileEditView from '../ProfileEditView.vue'

const mockUser = {
  id: 1,
  phone: '13800138000',
  nickname: '测试用户',
  avatar: 'https://example.com/avatar.jpg',
  type: 1,
  auth_status: 1,
  vip_level: 2,
  balance: 1234.56,
}

const mockPush = vi.fn()
const mockBack = vi.fn()
let updateProfileMock = vi.fn()
let setUserInfoMock = vi.fn()

vi.mock('@/api/profile', () => ({
  updateProfile: (...args: any[]) => updateProfileMock(...args),
}))

vi.mock('@/stores/user', () => ({
  useUserStore: () => ({
    userInfo: mockUser,
    setUserInfo: setUserInfoMock,
  }),
}))

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: mockPush, back: mockBack }),
}))

vi.mock('element-plus', () => ({
  ElMessage: {
    success: vi.fn(),
    error: vi.fn(),
  },
}))

function createWrapper() {
  return mount(ProfileEditView)
}

describe('ProfileEditView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    updateProfileMock = vi.fn(() => Promise.resolve({ ...mockUser, nickname: '新昵称' }))
    setUserInfoMock = vi.fn()
  })

  it('renders form with user data from store', () => {
    const wrapper = createWrapper()
    expect(wrapper.find('.profile-edit-view').exists()).toBe(true)
    expect(wrapper.find('.page-title').text()).toBe('编辑资料')

    const vm = wrapper.vm as any
    expect(vm.form.nickname).toBe('测试用户')
    expect(vm.form.avatar).toBe('https://example.com/avatar.jpg')
  })

  it('submits updated profile and calls API', async () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any

    vm.form.nickname = '新昵称'
    vm.form.link_person = '联系人'
    vm.form.qq = '123456'
    await flushPromises()

    await wrapper.find('[data-testid="submit-btn"]').trigger('click')
    await flushPromises()

    expect(updateProfileMock).toHaveBeenCalledWith({
      nickname: '新昵称',
      avatar: 'https://example.com/avatar.jpg',
      link_person: '联系人',
      qq: '123456',
    })
  })

  it('updates store and navigates back on success', async () => {
    updateProfileMock = vi.fn(() => Promise.resolve({
      id: 1,
      phone: '13800138000',
      nickname: '新昵称',
      avatar: 'https://example.com/avatar.jpg',
      type: 1,
      auth_status: 1,
      vip_level: 2,
      balance: 1234.56,
    }))
    const wrapper = createWrapper()
    const vm = wrapper.vm as any

    vm.form.nickname = '新昵称'
    await flushPromises()

    await wrapper.find('[data-testid="submit-btn"]').trigger('click')
    await flushPromises()

    expect(setUserInfoMock).toHaveBeenCalled()
    expect(mockBack).toHaveBeenCalled()
  })

  it('shows loading during submit', async () => {
    updateProfileMock = vi.fn(() => new Promise(() => {}))
    const wrapper = createWrapper()
    const vm = wrapper.vm as any

    vm.form.nickname = '新昵称'
    await wrapper.find('[data-testid="submit-btn"]').trigger('click')
    await flushPromises()

    expect(vm.submitting).toBe(true)
  })

  it('navigates back on cancel click', async () => {
    const wrapper = createWrapper()
    await wrapper.find('[data-testid="cancel-btn"]').trigger('click')
    expect(mockBack).toHaveBeenCalled()
  })

  it('handles empty optional fields', async () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any

    vm.form.nickname = '仅昵称'
    vm.form.link_person = ''
    vm.form.qq = ''
    await flushPromises()

    await wrapper.find('[data-testid="submit-btn"]').trigger('click')
    await flushPromises()

    expect(updateProfileMock).toHaveBeenCalledWith({
      nickname: '仅昵称',
      avatar: 'https://example.com/avatar.jpg',
      link_person: undefined,
      qq: undefined,
    })
  })

  it('exposes refs and methods', () => {
    const wrapper = createWrapper()
    const vm = wrapper.vm as any
    expect(vm.form).toBeDefined()
    expect(vm.submitting).toBe(false)
    expect(typeof vm.handleSubmit).toBe('function')
    expect(typeof vm.goBack).toBe('function')
  })
})
