<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { createTicket } from '@/api/ticket'

const router = useRouter()
const loading = ref(false)

const typeOptions = [
  { value: 1, label: '咨询' },
  { value: 2, label: '投诉' },
  { value: 3, label: '建议' },
  { value: 4, label: '售后' },
  { value: 5, label: '其他' },
]

interface FormState {
  type: number
  title: string
  content: string
  expected_resolution: string
  images: string[]
}

const form = ref<FormState>({
  type: 1,
  title: '',
  content: '',
  expected_resolution: '',
  images: [],
})

async function validateForm(): Promise<boolean> {
  if (!form.value.title.trim()) {
    ElMessage.error('请填写工单标题')
    return false
  }
  if (form.value.title.length > 200) {
    ElMessage.error('标题最多200字')
    return false
  }
  if (!form.value.content.trim()) {
    ElMessage.error('请填写工单内容')
    return false
  }
  return true
}

async function submitForm() {
  const valid = await validateForm()
  if (!valid) return

  loading.value = true
  try {
    await createTicket({
      type: form.value.type,
      title: form.value.title.trim(),
      content: form.value.content.trim(),
      expected_resolution: form.value.expected_resolution.trim() || undefined,
      images: form.value.images.length > 0 ? form.value.images : undefined,
    })
    ElMessage.success('工单已提交')
    router.push('/tickets')
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.back()
}

defineExpose({
  form,
  loading,
  validateForm,
  submitForm,
  goBack,
})
</script>

<template>
  <div class="ticket-create-view page-container">
    <h2 class="page-title">提交工单</h2>

    <div v-loading="loading" class="form-panel">
      <el-form label-width="100px">
        <el-form-item label="工单类型" required>
          <el-radio-group v-model="form.type">
            <el-radio-button v-for="opt in typeOptions" :key="opt.value" :label="opt.value">
              {{ opt.label }}
            </el-radio-button>
          </el-radio-group>
        </el-form-item>

        <el-form-item label="标题" required>
          <el-input
            v-model="form.title"
            placeholder="请简要描述问题"
            maxlength="200"
            show-word-limit
          />
        </el-form-item>

        <el-form-item label="内容" required>
          <el-input
            v-model="form.content"
            type="textarea"
            :rows="4"
            placeholder="请详细描述您遇到的问题"
          />
        </el-form-item>

        <el-form-item label="期望解决">
          <el-input
            v-model="form.expected_resolution"
            placeholder="您希望如何解决（选填）"
            maxlength="255"
            show-word-limit
          />
        </el-form-item>
      </el-form>

      <div class="actions">
        <el-button type="primary" :loading="loading" @click="submitForm">提交</el-button>
        <el-button @click="goBack">返回</el-button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ticket-create-view {
  max-width: 680px;
  margin: 20px auto;
  padding: 20px;
}
.form-panel {
  background: #fff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.actions {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #ebeef5;
}
</style>
