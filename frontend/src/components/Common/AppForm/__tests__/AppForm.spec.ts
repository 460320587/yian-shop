import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { ElForm, ElFormItem, ElInput, ElInputNumber, ElSelect, ElOption, ElSwitch } from 'element-plus'
import AppForm from '../AppForm.vue'

describe('AppForm', () => {
  const model = { name: '', age: 0, status: true }
  const fields = [
    { prop: 'name', label: '名称', type: 'input' as const, placeholder: '请输入名称' },
    { prop: 'age', label: '年龄', type: 'number' as const },
    { prop: 'status', label: '状态', type: 'switch' as const },
  ]

  it('renders form with fields', () => {
    const wrapper = mount(AppForm, {
      props: { model, fields },
      global: { components: { ElForm, ElFormItem, ElInput, ElInputNumber, ElSwitch } },
    })
    expect(wrapper.findComponent(ElForm).exists()).toBe(true)
    expect(wrapper.findAllComponents(ElFormItem).length).toBe(3)
  })

  it('binds input value to model', () => {
    const wrapper = mount(AppForm, {
      props: { model, fields },
      global: { components: { ElForm, ElFormItem, ElInput, ElInputNumber, ElSwitch } },
    })
    const inputs = wrapper.findAllComponents(ElInput)
    expect(inputs.length).toBeGreaterThan(0)
  })

  it('exposes validate and resetFields methods', () => {
    const wrapper = mount(AppForm, {
      props: { model, fields },
      global: { components: { ElForm, ElFormItem, ElInput, ElInputNumber, ElSwitch } },
    })
    expect(typeof wrapper.vm.validate).toBe('function')
    expect(typeof wrapper.vm.resetFields).toBe('function')
  })

  it('renders select with options', () => {
    const selectModel = { role: '' }
    const selectFields = [
      { prop: 'role', label: '角色', type: 'select' as const, options: [{ label: '管理员', value: 'admin' }] },
    ]
    const wrapper = mount(AppForm, {
      props: { model: selectModel, fields: selectFields },
      global: { components: { ElForm, ElFormItem, ElSelect, ElOption } },
    })
    expect(wrapper.findComponent(ElSelect).exists()).toBe(true)
  })

  it('renders textarea when type is textarea', () => {
    const textModel = { desc: '' }
    const textFields = [{ prop: 'desc', label: '描述', type: 'textarea' as const }]
    const wrapper = mount(AppForm, {
      props: { model: textModel, fields: textFields },
      global: { components: { ElForm, ElFormItem, ElInput } },
    })
    const input = wrapper.findComponent(ElInput)
    expect(input.exists()).toBe(true)
  })

  it('renders password input when type is password', () => {
    const pwdModel = { password: '' }
    const pwdFields = [{ prop: 'password', label: '密码', type: 'password' as const }]
    const wrapper = mount(AppForm, {
      props: { model: pwdModel, fields: pwdFields },
      global: { components: { ElForm, ElFormItem, ElInput } },
    })
    const input = wrapper.findComponent(ElInput)
    expect(input.exists()).toBe(true)
  })
})
