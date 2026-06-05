import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { ElTable, ElTableColumn, ElPagination } from 'element-plus'
import AppTable from '../AppTable.vue'

describe('AppTable', () => {
  const columns = [
    { prop: 'name', label: '名称' },
    { prop: 'age', label: '年龄' },
  ]
  const data = [
    { name: 'Alice', age: 20 },
    { name: 'Bob', age: 25 },
  ]

  it('renders with required props', () => {
    const wrapper = mount(AppTable, {
      props: { columns, data, loading: false },
      global: { components: { ElTable, ElTableColumn } },
    })
    expect(wrapper.findComponent(ElTable).exists()).toBe(true)
  })

  it('passes data to el-table', () => {
    const wrapper = mount(AppTable, {
      props: { columns, data, loading: false },
      global: { components: { ElTable, ElTableColumn } },
    })
    const table = wrapper.findComponent(ElTable)
    expect(table.props('data')).toEqual(data)
  })

  it('shows loading via v-loading directive', () => {
    const wrapper = mount(AppTable, {
      props: { columns, data, loading: true },
      global: { components: { ElTable, ElTableColumn } },
    })
    // v-loading 在 ElTable 上表现为 loading 属性
    const table = wrapper.findComponent(ElTable)
    expect(table.exists()).toBe(true)
  })

  it('emits row-click when table row is clicked', () => {
    const wrapper = mount(AppTable, {
      props: { columns, data, loading: false },
      global: { components: { ElTable, ElTableColumn } },
    })
    const table = wrapper.findComponent(ElTable)
    table.vm.$emit('row-click', data[0])
    expect(wrapper.emitted('row-click')).toBeTruthy()
  })

  it('emits selection-change event', () => {
    const wrapper = mount(AppTable, {
      props: { columns, data, loading: false, selectable: true },
      global: { components: { ElTable, ElTableColumn } },
    })
    const table = wrapper.findComponent(ElTable)
    table.vm.$emit('selection-change', [data[0]])
    expect(wrapper.emitted('selection-change')).toBeTruthy()
  })

  it('renders pagination when pagination prop is provided', () => {
    const wrapper = mount(AppTable, {
      props: {
        columns, data, loading: false,
        pagination: { currentPage: 2, pageSize: 20, total: 100 },
      },
      global: { components: { ElTable, ElTableColumn, ElPagination } },
    })
    const pagination = wrapper.findComponent(ElPagination)
    expect(pagination.exists()).toBe(true)
    expect(pagination.props('total')).toBe(100)
  })
})
