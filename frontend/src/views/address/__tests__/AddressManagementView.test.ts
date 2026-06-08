import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import AddressManagementView from '../AddressManagementView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessageBox: { confirm: vi.fn(() => Promise.resolve('confirm')) },
    ElMessage: { success: vi.fn(), error: vi.fn() },
  }
})

const mockAddresses = [
  {
    id: 1,
    contact_name: '张三',
    contact_phone: '13800138000',
    province_name: '广东省',
    city_name: '深圳市',
    county_name: '南山区',
    detail_address: '科技园路1号',
    full_address: '广东省深圳市南山区科技园路1号',
    zip_code: '518000',
    is_default: true,
    tag: '公司',
    created_at: '2026-01-01 10:00:00',
  },
  {
    id: 2,
    contact_name: '李四',
    contact_phone: '13900139000',
    province_name: '广东省',
    city_name: '广州市',
    county_name: '天河区',
    detail_address: '天河路2号',
    full_address: '广东省广州市天河区天河路2号',
    zip_code: null,
    is_default: false,
    tag: null,
    created_at: '2026-01-02 10:00:00',
  },
]

let getAddressesMock = vi.fn()
let createAddressMock = vi.fn()
let updateAddressMock = vi.fn()
let deleteAddressMock = vi.fn()
let setDefaultAddressMock = vi.fn()

vi.mock('element-china-area-data', () => ({
  pcaTextArr: [
    {
      label: '北京市',
      value: '北京市',
      children: [
        {
          label: '市辖区',
          value: '市辖区',
          children: [
            { label: '东城区', value: '东城区' },
            { label: '西城区', value: '西城区' },
          ],
        },
      ],
    },
    {
      label: '广东省',
      value: '广东省',
      children: [
        {
          label: '深圳市',
          value: '深圳市',
          children: [
            { label: '南山区', value: '南山区' },
            { label: '福田区', value: '福田区' },
          ],
        },
        {
          label: '广州市',
          value: '广州市',
          children: [
            { label: '天河区', value: '天河区' },
            { label: '越秀区', value: '越秀区' },
          ],
        },
      ],
    },
  ],
}))

vi.mock('@/api/address', () => ({
  getAddresses: (...args: any[]) => getAddressesMock(...args),
  createAddress: (...args: any[]) => createAddressMock(...args),
  updateAddress: (...args: any[]) => updateAddressMock(...args),
  deleteAddress: (...args: any[]) => deleteAddressMock(...args),
  setDefaultAddress: (...args: any[]) => setDefaultAddressMock(...args),
}))

beforeEach(() => {
  getAddressesMock = vi.fn(() => Promise.resolve({ data: mockAddresses, total: 2, current_page: 1, last_page: 1 }))
  createAddressMock = vi.fn((data) => Promise.resolve({ id: 3, ...data, full_address: data.province_name + data.city_name + data.county_name + data.detail_address, created_at: '2026-01-03 10:00:00' }))
  updateAddressMock = vi.fn((id, data) => Promise.resolve({ ...mockAddresses[0], ...data, full_address: (data.province_name || mockAddresses[0].province_name) + (data.city_name || mockAddresses[0].city_name) + (data.county_name || mockAddresses[0].county_name) + (data.detail_address || mockAddresses[0].detail_address) }))
  deleteAddressMock = vi.fn(() => Promise.resolve({}))
  setDefaultAddressMock = vi.fn((id) => Promise.resolve({ ...mockAddresses.find((a) => a.id === id)!, is_default: true }))
})

describe('AddressManagementView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({
      history: createWebHistory(),
      routes: [{ path: '/addresses', name: 'Addresses', component: AddressManagementView }],
    })
    return mount(AddressManagementView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders address management page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.address-management-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('收货地址')
  })

  it('loads addresses on mount', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(getAddressesMock).toHaveBeenCalledTimes(1)
    expect((wrapper.vm as any).addresses).toHaveLength(2)
  })

  it('displays default address indicator', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const items = wrapper.findAll('.address-item')
    expect(items[0].classes()).toContain('default')
    expect(items[1].classes()).not.toContain('default')
  })

  it('opens add dialog', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openDialog()
    expect((wrapper.vm as any).dialogVisible).toBe(true)
    expect((wrapper.vm as any).isEdit).toBe(false)
    expect((wrapper.vm as any).form.id).toBeNull()
  })

  it('opens edit dialog with address data', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openDialog(mockAddresses[0])
    expect((wrapper.vm as any).dialogVisible).toBe(true)
    expect((wrapper.vm as any).isEdit).toBe(true)
    expect((wrapper.vm as any).form.contact_name).toBe('张三')
  })

  it('creates new address via submitForm', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const newAddress = {
      contact_name: '王五',
      contact_phone: '13700137000',
      province_name: '浙江省',
      city_name: '杭州市',
      county_name: '西湖区',
      detail_address: '文三路3号',
      zip_code: '310000',
      is_default: false,
      tag: '家',
    }
    ;(wrapper.vm as any).form = { id: null, ...newAddress }
    ;(wrapper.vm as any).isEdit = false
    await (wrapper.vm as any).submitForm()
    await flushPromises()
    expect(createAddressMock).toHaveBeenCalledWith(expect.objectContaining(newAddress))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
  })

  it('updates address via submitForm', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form = { ...mockAddresses[0], contact_name: '张三丰' }
    ;(wrapper.vm as any).isEdit = true
    await (wrapper.vm as any).submitForm()
    await flushPromises()
    expect(updateAddressMock).toHaveBeenCalledWith(1, expect.objectContaining({ contact_name: '张三丰' }))
    expect((wrapper.vm as any).dialogVisible).toBe(false)
  })

  it('deletes address via handleDelete', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleDelete(mockAddresses[1])
    await flushPromises()
    expect(deleteAddressMock).toHaveBeenCalledWith(2)
  })

  it('sets default address via handleSetDefault', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    await (wrapper.vm as any).handleSetDefault(mockAddresses[1])
    await flushPromises()
    expect(setDefaultAddressMock).toHaveBeenCalledWith(2)
  })

  it('form validation requires contact_name', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form = {
      id: null,
      contact_name: '',
      contact_phone: '13800138000',
      province_name: '广东省',
      city_name: '深圳市',
      county_name: '南山区',
      detail_address: '科技园路1号',
      zip_code: '',
      is_default: false,
      tag: '',
    }
    const valid = await (wrapper.vm as any).validateForm()
    expect(valid).toBe(false)
  })

  it('form validation requires valid phone', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).form = {
      id: null,
      contact_name: '张三',
      contact_phone: '12345678901',
      province_name: '广东省',
      city_name: '深圳市',
      county_name: '南山区',
      detail_address: '科技园路1号',
      zip_code: '',
      is_default: false,
      tag: '',
    }
    const valid = await (wrapper.vm as any).validateForm()
    expect(valid).toBe(false)
  })

  it('resets cascader value on new dialog open', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).cascaderValue = ['广东省', '深圳市', '南山区']
    ;(wrapper.vm as any).openDialog()
    expect((wrapper.vm as any).cascaderValue).toEqual([])
    expect((wrapper.vm as any).form.province_name).toBe('')
  })

  it('populates cascader value on edit dialog open', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).openDialog(mockAddresses[0])
    expect((wrapper.vm as any).cascaderValue).toEqual(['广东省', '深圳市', '南山区'])
    expect((wrapper.vm as any).form.province_name).toBe('广东省')
    expect((wrapper.vm as any).form.city_name).toBe('深圳市')
    expect((wrapper.vm as any).form.county_name).toBe('南山区')
  })

  it('fills region fields on cascader change', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleRegionChange(['北京市', '市辖区', '东城区'])
    expect((wrapper.vm as any).form.province_name).toBe('北京市')
    expect((wrapper.vm as any).form.city_name).toBe('市辖区')
    expect((wrapper.vm as any).form.county_name).toBe('东城区')
  })

  it('does not fill region fields when cascader selection is incomplete', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    ;(wrapper.vm as any).handleRegionChange(['北京市', '市辖区'])
    expect((wrapper.vm as any).form.province_name).toBe('')
    expect((wrapper.vm as any).form.city_name).toBe('')
    expect((wrapper.vm as any).form.county_name).toBe('')
  })
})
