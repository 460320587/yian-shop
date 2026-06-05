import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import AppCaptcha from '../AppCaptcha.vue'

let getCaptchaMock = vi.fn()

vi.mock('@/api/captcha', () => ({
  getCaptcha: () => getCaptchaMock(),
}))

function createWrapper(props: any = {}) {
  return mount(AppCaptcha, {
    props: {
      modelValue: '',
      ...props,
    },
  })
}

describe('AppCaptcha', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    getCaptchaMock = vi.fn(() => Promise.resolve({ captcha_key: 'key_1', captcha_code: '1234' }))
  })

  it('renders captcha component', async () => {
    const wrapper = createWrapper()
    await flushPromises()
    expect(wrapper.find('.app-captcha').exists()).toBe(true)
  })

  it('fetches captcha on mount', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    expect(getCaptchaMock).toHaveBeenCalledTimes(1)
    const vm = wrapper.vm as any
    expect(vm.captchaCode).toBe('1234')
    expect(vm.captchaKey).toBe('key_1')
  })

  it('displays captcha code', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    expect(wrapper.text()).toContain('1234')
  })

  it('refreshes captcha when clicking refresh', async () => {
    getCaptchaMock = vi.fn()
      .mockResolvedValueOnce({ captcha_key: 'key_1', captcha_code: '1234' })
      .mockResolvedValueOnce({ captcha_key: 'key_2', captcha_code: '5678' })

    const wrapper = createWrapper()
    await flushPromises()

    await wrapper.find('[data-testid="refresh-btn"]').trigger('click')
    await flushPromises()

    expect(getCaptchaMock).toHaveBeenCalledTimes(2)
    const vm = wrapper.vm as any
    expect(vm.captchaCode).toBe('5678')
  })

  it('emits verify event with valid=true on correct input', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    await wrapper.setProps({ modelValue: '1234' })
    await flushPromises()

    expect(wrapper.emitted('verify')).toBeTruthy()
    expect(wrapper.emitted('verify')![0]).toEqual([true, 'key_1'])
  })

  it('emits verify event with valid=false on incorrect input', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    await wrapper.setProps({ modelValue: '0000' })
    await flushPromises()

    expect(wrapper.emitted('verify')).toBeTruthy()
    expect(wrapper.emitted('verify')![0]).toEqual([false, 'key_1'])
  })

  it('exposes refs and methods', async () => {
    const wrapper = createWrapper()
    await flushPromises()

    const vm = wrapper.vm as any
    expect(typeof vm.refreshCaptcha).toBe('function')
    expect(typeof vm.verifyCode).toBe('function')
  })
})
