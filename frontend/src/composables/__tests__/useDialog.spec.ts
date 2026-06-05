import { describe, it, expect } from 'vitest'
import { useDialog } from '../useDialog'

describe('useDialog', () => {
  it('returns visible false by default', () => {
    const { visible } = useDialog()
    expect(visible.value).toBe(false)
  })

  it('open sets visible to true', () => {
    const { visible, open } = useDialog()
    open()
    expect(visible.value).toBe(true)
  })

  it('close sets visible to false', () => {
    const { visible, open, close } = useDialog()
    open()
    close()
    expect(visible.value).toBe(false)
  })

  it('toggle switches visible state', () => {
    const { visible, toggle } = useDialog()
    toggle()
    expect(visible.value).toBe(true)
    toggle()
    expect(visible.value).toBe(false)
  })

  it('confirmLoading defaults to false', () => {
    const { confirmLoading } = useDialog()
    expect(confirmLoading.value).toBe(false)
  })

  it('startLoading sets confirmLoading true', () => {
    const { confirmLoading, startLoading } = useDialog()
    startLoading()
    expect(confirmLoading.value).toBe(true)
  })

  it('stopLoading sets confirmLoading false', () => {
    const { confirmLoading, startLoading, stopLoading } = useDialog()
    startLoading()
    stopLoading()
    expect(confirmLoading.value).toBe(false)
  })

  it('open resets confirmLoading', () => {
    const { confirmLoading, startLoading, open } = useDialog()
    startLoading()
    open()
    expect(confirmLoading.value).toBe(false)
  })
})
