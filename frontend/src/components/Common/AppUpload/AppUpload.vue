<script setup lang="ts">
/**
 * 通用上传组件
 * - 封装 Element Plus Upload
 * - 支持单/多文件、图片预览、限制数量与格式
 */
import { computed } from 'vue'

export interface UploadFile {
  name: string
  url: string
}

const props = withDefaults(
  defineProps<{
    modelValue: UploadFile[]
    action: string
    accept?: string
    multiple?: boolean
    limit?: number
    disabled?: boolean
    listType?: 'text' | 'picture' | 'picture-card'
  }>(),
  {
    accept: '*',
    multiple: false,
    limit: 0,
    disabled: false,
    listType: 'text',
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', files: UploadFile[]): void
  (e: 'success', response: any, file: any): void
  (e: 'error', err: any, file: any): void
  (e: 'remove', file: UploadFile): void
}>()

const fileList = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
})

const showUploadBtn = computed(() => {
  if (props.limit <= 0) return true
  return fileList.value.length < props.limit
})

function handleSuccess(response: any, uploadFile: any) {
  emit('success', response, uploadFile)
}

function handleError(err: any, uploadFile: any) {
  emit('error', err, uploadFile)
}

function handleRemove(uploadFile: any) {
  const file: UploadFile = { name: uploadFile.name, url: uploadFile.url }
  emit('remove', file)
}
</script>

<template>
  <el-upload
    v-model:file-list="fileList"
    :action="action"
    :accept="accept"
    :multiple="multiple"
    :limit="limit || undefined"
    :disabled="disabled"
    :list-type="listType"
    :show-file-list="true"
    @success="handleSuccess"
    @error="handleError"
    @remove="handleRemove"
  >
    <el-button v-if="showUploadBtn" type="primary" :disabled="disabled">
      点击上传
    </el-button>
    <template #tip>
      <div v-if="$slots.tip" class="el-upload__tip">
        <slot name="tip" />
      </div>
    </template>
  </el-upload>
</template>
