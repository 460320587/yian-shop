import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { nextTick } from 'vue'
import LogisticsTrackView from '../LogisticsTrackView.vue'

const mockLogistics = {
  carrier_name: '顺丰速运',
  tracking_no: 'SF1234567890',
  status: 1,
  shipped_at: '2026-05-28T10:00:00',
  delivered_at: null,
  tracks: [
    { time: '2026-05-30T18:00:00', location: '北京市', description: '已签收' },
    { time: '2026-05-30T14:00:00', location: '北京市', description: '派送中' },
    { time: '2026-05-30T10:00:00', location: '北京集散中心', description: '到达北京集散中心' },
    { time: '2026-05-30T06:00:00', location: '上海', description: '离开上海' },
    { time: '2026-05-30T02:00:00', location: '上海', description: '已揽收' },
  ],
}

const mockPush = vi.fn()
const mockBack = vi.fn()
let getLogisticsTracksMock = vi.fn()

vi.mock('@/api/logistics', () => ({
  getLogisticsTracks: (...args: any[]) => getLogisticsTracksMock(...args),
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
  return mount(LogisticsTrackView)
}

describe('LogisticsTrackView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    getLogisticsTracksMock = vi.fn(() => Promise.resolve(mockLogistics))
  })

  it('renders loading state initially', async () => {
    getLogisticsTracksMock = vi.fn(() => new Promise(() => {}))
    const wrapper = createWrapper()
    await nextTick()
    expect(wrapper.find('.logistics-track-view').exists()).toBe(true)
    expect(wrapper.find('.page-title').text()).toBe('物流跟踪')
    expect(wrapper.find('.loading-text').exists()).toBe(true)
  })

  it('fetches logistics on mount and renders carrier info', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    expect(getLogisticsTracksMock).toHaveBeenCalledWith(5)
    expect(wrapper.find('.carrier-name').text()).toContain('顺丰速运')
    expect(wrapper.find('.tracking-no').text()).toContain('SF1234567890')
    expect(wrapper.find('.shipped-at').text()).toContain('2026-05-28')
  })

  it('renders track timeline', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const tracks = wrapper.findAll('.track-item')
    expect(tracks.length).toBe(5)
    expect(tracks[0].text()).toContain('已签收')
    expect(tracks[0].text()).toContain('北京市')
    expect(tracks[1].text()).toContain('派送中')
  })

  it('shows empty state when no logistics info', async () => {
    getLogisticsTracksMock = vi.fn(() => Promise.reject(new Error('暂无物流信息')))
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('.empty-state').exists()).toBe(true)
    expect(wrapper.find('.empty-state').text()).toContain('暂无物流信息')
  })

  it('navigates back on back button click', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    await wrapper.find('[data-testid="back-btn"]').trigger('click')
    expect(mockBack).toHaveBeenCalled()
  })

  it('shows delivered time when delivered', async () => {
    getLogisticsTracksMock = vi.fn(() => Promise.resolve({
      ...mockLogistics,
      status: 2,
      delivered_at: '2026-05-30T18:00:00',
    }))
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.find('.delivered-at').exists()).toBe(true)
    expect(wrapper.find('.delivered-at').text()).toContain('2026-05-30')
  })

  it('exposes refs and methods', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const vm = wrapper.vm as any
    expect(vm.logistics).toBeDefined()
    expect(vm.loading).toBe(false)
    expect(typeof vm.goBack).toBe('function')
    expect(typeof vm.loadLogistics).toBe('function')
  })
})
