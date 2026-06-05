<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { getCaptcha } from '@/api/captcha'

const props = withDefaults(defineProps<{
  modelValue?: string
  length?: number
  refreshable?: boolean
}>(), {
  modelValue: '',
  length: 4,
  refreshable: true,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  verify: [valid: boolean, token: string]
  refresh: []
}>()

const captchaKey = ref('')
const captchaCode = ref('')
const loading = ref(false)
const errorMsg = ref('')

async function refreshCaptcha() {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await getCaptcha()
    captchaKey.value = res.captcha_key
    captchaCode.value = res.captcha_code
    emit('refresh')
  } catch (e) {
    errorMsg.value = '获取验证码失败'
  } finally {
    loading.value = false
  }
}

function verifyCode() {
  const input = props.modelValue.trim()
  if (input.length !== props.length) {
    emit('verify', false, captchaKey.value)
    return
  }
  const valid = input.toLowerCase() === captchaCode.value.toLowerCase()
  emit('verify', valid, captchaKey.value)
}

function handleInput(value: string) {
  emit('update:modelValue', value)
  if (errorMsg.value) {
    errorMsg.value = ''
  }
}

onMounted(() => {
  refreshCaptcha()
})

watch(() => props.modelValue, (val) => {
  if (val.length === props.length) {
    verifyCode()
  }
})

defineExpose({
  captchaKey,
  captchaCode,
  refreshCaptcha,
  verifyCode,
})
</script>

<template>
  <div class="app-captcha">
    <input
      :value="modelValue"
      type="text"
      class="captcha-input"
      :maxlength="length"
      placeholder="请输入验证码"
      @input="(e) => handleInput((e.target as HTMLInputElement).value)"
      @blur="verifyCode"
    >
    <div class="captcha-display">
      <span v-if="captchaCode" class="captcha-code">{{ captchaCode }}</span>
      <span v-else class="captcha-placeholder">--</span>
      <button
        v-if="refreshable"
        data-testid="refresh-btn"
        type="button"
        class="refresh-btn"
        :disabled="loading"
        @click="refreshCaptcha"
      >
        刷新
      </button>
    </div>
    <div v-if="errorMsg" class="error-msg">{{ errorMsg }}</div>
  </div>
</template>

<style scoped>
.app-captcha {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.captcha-input {
  width: 120px;
  padding: 6px 10px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  font-size: 14px;
}
.captcha-display {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  background: #f5f7fa;
  border-radius: 4px;
  border: 1px solid #e4e7ed;
}
.captcha-code {
  font-family: monospace;
  font-size: 16px;
  font-weight: 600;
  color: #409eff;
  letter-spacing: 2px;
}
.captcha-placeholder {
  color: #c0c4cc;
}
.refresh-btn {
  padding: 2px 8px;
  font-size: 12px;
  border: 1px solid #dcdfe6;
  background: #fff;
  border-radius: 4px;
  cursor: pointer;
}
.refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.error-msg {
  width: 100%;
  font-size: 12px;
  color: #f56c6c;
}
</style>
