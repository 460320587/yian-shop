import { ref, type UnwrapRef } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'

export interface UseFormResult<T> {
  model: ReturnType<typeof ref<T>>
  rules: ReturnType<typeof ref<FormRules>>
  formRef: ReturnType<typeof ref<FormInstance | undefined>>
  reset: () => void
  setModel: (value: T) => void
  validate: (callback?: (valid: boolean, fields?: any) => void) => Promise<boolean>
}

export function useForm<T extends Record<string, any>>(
  initialModel: T,
  initialRules: FormRules = {},
): UseFormResult<T> {
  const model = ref<T>(JSON.parse(JSON.stringify(initialModel)))
  const rules = ref<FormRules>({ ...initialRules })
  const formRef = ref<FormInstance>()

  function reset() {
    model.value = JSON.parse(JSON.stringify(initialModel)) as UnwrapRef<T>
  }

  function setModel(value: T) {
    model.value = { ...value } as UnwrapRef<T>
  }

  async function validate(callback?: (valid: boolean, fields?: any) => void): Promise<boolean> {
    if (!formRef.value) {
      callback?.(false, undefined)
      return false
    }
    try {
      await formRef.value.validate()
      callback?.(true, undefined)
      return true
    } catch (error: any) {
      callback?.(false, error.fields)
      return false
    }
  }

  return {
    model,
    rules,
    formRef,
    reset,
    setModel,
    validate,
  }
}
