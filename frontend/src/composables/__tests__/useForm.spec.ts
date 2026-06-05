import { describe, it, expect } from 'vitest'
import { useForm } from '../useForm'

describe('useForm', () => {
  it('returns model and rules refs', () => {
    const initial = { name: '' }
    const formRules = { name: [{ required: true, message: '必填' }] }
    const { model, rules } = useForm(initial, formRules)

    expect(model.value).toEqual(initial)
    expect(rules.value).toEqual(formRules)
  })

  it('reset restores initial values', () => {
    const initial = { name: 'Alice', age: 20 }
    const { model, reset } = useForm(initial, {})

    model.value.name = 'Bob'
    model.value.age = 30
    reset()

    expect(model.value).toEqual(initial)
  })

  it('reset works with nested objects', () => {
    const initial = { user: { name: 'Alice' }, tags: ['a'] }
    const { model, reset } = useForm(initial, {})

    model.value.user.name = 'Bob'
    model.value.tags.push('b')
    reset()

    expect(model.value.user.name).toBe('Alice')
    expect(model.value.tags).toEqual(['a'])
  })

  it('setModel replaces model value', () => {
    const { model, setModel } = useForm({ name: '' }, {})
    setModel({ name: 'Charlie' })
    expect(model.value.name).toBe('Charlie')
  })

  it('validate callback receives current model', async () => {
    const initial = { name: 'Test' }
    const { validate } = useForm(initial, {})

    let captured: any
    await validate((valid, fields) => {
      captured = { valid, fields }
    })

    expect(captured).toBeDefined()
  })
})
