import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { ElUpload, ElButton } from 'element-plus'
import AppUpload from '../AppUpload.vue'

describe('AppUpload', () => {
  it('renders upload component', () => {
    const wrapper = mount(AppUpload, {
      props: { modelValue: [], action: '/api/upload' },
      global: { components: { ElUpload, ElButton } },
    })
    expect(wrapper.findComponent(ElUpload).exists()).toBe(true)
  })

  it('passes action and accept props', () => {
    const wrapper = mount(AppUpload, {
      props: { modelValue: [], action: '/api/upload', accept: 'image/*' },
      global: { components: { ElUpload, ElButton } },
    })
    const upload = wrapper.findComponent(ElUpload)
    expect(upload.props('action')).toBe('/api/upload')
    expect(upload.props('accept')).toBe('image/*')
  })

  it('shows upload button by default', () => {
    const wrapper = mount(AppUpload, {
      props: { modelValue: [], action: '/api/upload' },
      global: { components: { ElUpload, ElButton } },
    })
    expect(wrapper.findComponent(ElButton).exists()).toBe(true)
  })

  it('hides upload button when limit reached', () => {
    const wrapper = mount(AppUpload, {
      props: {
        modelValue: [{ name: 'a.jpg', url: '/a.jpg' }],
        action: '/api/upload',
        limit: 1,
      },
      global: { components: { ElUpload, ElButton } },
    })
    expect(wrapper.findComponent(ElButton).exists()).toBe(false)
  })

  it('emits success event', () => {
    const wrapper = mount(AppUpload, {
      props: { modelValue: [], action: '/api/upload' },
      global: { components: { ElUpload, ElButton } },
    })
    const upload = wrapper.findComponent(ElUpload)
    upload.vm.$emit('success', { url: '/file.jpg' }, { name: 'file.jpg' })
    expect(wrapper.emitted('success')).toBeTruthy()
  })

  it('emits error event', () => {
    const wrapper = mount(AppUpload, {
      props: { modelValue: [], action: '/api/upload' },
      global: { components: { ElUpload, ElButton } },
    })
    const upload = wrapper.findComponent(ElUpload)
    upload.vm.$emit('error', new Error('fail'), { name: 'file.jpg' })
    expect(wrapper.emitted('error')).toBeTruthy()
  })
})
