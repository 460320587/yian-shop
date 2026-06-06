<script setup lang="ts">
/**
 * 通用表单组件
 * - 封装 Element Plus Form + 常用输入类型
 * - 支持字段配置化、校验规则、loading
 */
import { ref } from 'vue'

export interface FormField {
  prop: string
  label: string
  type?: 'input' | 'number' | 'select' | 'textarea' | 'switch' | 'password'
  options?: { label: string; value: string | number }[]
  placeholder?: string
  disabled?: boolean
  rows?: number
  min?: number
  max?: number
}

const props = withDefaults(
  defineProps<{
    model: Record<string, any>
    fields: FormField[]
    rules?: Record<string, any>
    loading?: boolean
    labelWidth?: string
  }>(),
  {
    loading: false,
    labelWidth: '90px',
  },
)

const emit = defineEmits<{
  (e: 'submit'): void
  (e: 'reset'): void
}>()

const formRef = ref<any>(null)

function handleSubmit() {
  if (!formRef.value) return
  formRef.value.validate((valid: boolean) => {
    if (valid) {
      emit('submit')
    }
  })
}

function handleReset() {
  if (formRef.value) {
    formRef.value.resetFields()
  }
  emit('reset')
}

defineExpose({
  validate: () => formRef.value?.validate(),
  resetFields: () => formRef.value?.resetFields(),
})
</script>

<template>
  <el-form
    ref="formRef"
    :model="model"
    :rules="rules"
    :label-width="labelWidth"
    v-loading="loading"
    @submit.prevent="handleSubmit"
  >
    <el-form-item
      v-for="field in fields"
      :key="field.prop"
      :prop="field.prop"
      :label="field.label"
    >
      <el-input
        v-if="field.type === 'input' || !field.type"
        v-model="model[field.prop]"
        :placeholder="field.placeholder"
        :disabled="field.disabled || loading"
      />
      <el-input
        v-else-if="field.type === 'password'"
        v-model="model[field.prop]"
        type="password"
        show-password
        :placeholder="field.placeholder"
        :disabled="field.disabled || loading"
      />
      <el-input
        v-else-if="field.type === 'textarea'"
        v-model="model[field.prop]"
        type="textarea"
        :rows="field.rows || 3"
        :placeholder="field.placeholder"
        :disabled="field.disabled || loading"
      />
      <el-input-number
        v-else-if="field.type === 'number'"
        v-model="model[field.prop]"
        :min="field.min ?? 0"
        :max="field.max"
        :disabled="field.disabled || loading"
        style="width: 100%"
      />
      <el-select
        v-else-if="field.type === 'select'"
        v-model="model[field.prop]"
        :placeholder="field.placeholder"
        :disabled="field.disabled || loading"
        style="width: 100%"
      >
        <el-option
          v-for="opt in field.options"
          :key="opt.value"
          :label="opt.label"
          :value="opt.value"
        />
      </el-select>
      <el-switch
        v-else-if="field.type === 'switch'"
        v-model="model[field.prop]"
        :disabled="field.disabled || loading"
      />
    </el-form-item>
  </el-form>
</template>
