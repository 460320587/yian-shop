import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import TicketCreateView from '../TicketCreateView.vue'

vi.mock('element-plus', async () => {
  const actual = await vi.importActual('element-plus')
  return {
    ...actual as any,
    ElMessage: { success: vi.fn(), error: vi.fn() },
  }
})

let createTicketMock = vi.fn()

vi.mock('@/api/ticket', () => ({
  createTicket: (...args: any[]) => createTicketMock(...args),
}))

beforeEach(() => {
  createTicketMock = vi.fn(() => Promise.resolve({ id: 3, ticket_no: 'TK202601010003', status: 1 }))
})

describe('TicketCreateView', () => {
  function mountComponent() {
    setActivePinia(createPinia())
    const router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/ticket-create', name: 'TicketCreate', component: TicketCreateView },
        { path: '/tickets', name: 'TicketList', component: { template: '<div>List</div>' } },
      ],
    })
    return mount(TicketCreateView, {
      global: { plugins: [createPinia(), router] },
    })
  }

  it('renders ticket create page', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('.ticket-create-view').exists()).toBe(true)
    expect(wrapper.text()).toContain('提交工单')
  })

  it('submits ticket with valid form', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.type = 2
    vm.form.title = '印刷质量问题'
    vm.form.content = '颜色与样品不符'
    vm.form.expected_resolution = '重新印刷'
    await vm.submitForm()
    await flushPromises()
    expect(createTicketMock).toHaveBeenCalledWith(expect.objectContaining({
      type: 2,
      title: '印刷质量问题',
      content: '颜色与样品不符',
      expected_resolution: '重新印刷',
    }))
  })

  it('validates required fields', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.title = ''
    vm.form.content = ''
    const valid = await vm.validateForm()
    expect(valid).toBe(false)
    expect(createTicketMock).not.toHaveBeenCalled()
  })

  it('validates title max length', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const vm = wrapper.vm as any
    vm.form.title = 'a'.repeat(201)
    vm.form.content = 'test'
    const valid = await vm.validateForm()
    expect(valid).toBe(false)
  })

  it('navigates to list after success', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    const router = (wrapper.vm as any).$router
    const pushSpy = vi.spyOn(router, 'push')
    const vm = wrapper.vm as any
    vm.form.type = 1
    vm.form.title = 'test'
    vm.form.content = 'content'
    await vm.submitForm()
    await flushPromises()
    expect(pushSpy).toHaveBeenCalledWith('/tickets')
  })
})
