import { describe, it, expect } from 'vitest'
import { usePagination } from '../usePagination'

describe('usePagination', () => {
  it('returns default values', () => {
    const { currentPage, pageSize, total } = usePagination()
    expect(currentPage.value).toBe(1)
    expect(pageSize.value).toBe(10)
    expect(total.value).toBe(0)
  })

  it('accepts initial options', () => {
    const { currentPage, pageSize, total } = usePagination({
      page: 2,
      pageSize: 20,
      total: 100,
    })
    expect(currentPage.value).toBe(2)
    expect(pageSize.value).toBe(20)
    expect(total.value).toBe(100)
  })

  it('handleCurrentChange updates currentPage', () => {
    const { currentPage, handleCurrentChange } = usePagination()
    handleCurrentChange(3)
    expect(currentPage.value).toBe(3)
  })

  it('handleSizeChange updates pageSize and resets currentPage', () => {
    const { currentPage, pageSize, handleSizeChange } = usePagination({ page: 5 })
    handleSizeChange(50)
    expect(pageSize.value).toBe(50)
    expect(currentPage.value).toBe(1)
  })

  it('setTotal updates total', () => {
    const { total, setTotal } = usePagination()
    setTotal(200)
    expect(total.value).toBe(200)
  })

  it('reset restores defaults', () => {
    const { currentPage, pageSize, total, reset } = usePagination({
      page: 3,
      pageSize: 20,
      total: 100,
    })
    reset()
    expect(currentPage.value).toBe(1)
    expect(pageSize.value).toBe(10)
    expect(total.value).toBe(0)
  })
})
