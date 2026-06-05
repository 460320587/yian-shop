<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
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

const charStyles = computed(() => {
  return (captchaCode.value || '').split('').map((_, i) => ({
    transform: `rotate(${randomRange(-15, 15)}deg)`,
    fontSize: `${randomRange(18, 24)}px`,
    color: randomColor(),
  }))
})

function randomRange(min: number, max: number): number {
  return Math.floor(Math.random() * (max - min + 1)) + min
}

function randomColor(): string {
  const colors = ['#409eff', '#67c23a', '#e6a23c', '#f56c6c', '#909399', '#606266']
  return colors[Math.floor(Math.random() * colors.length)]
}

function randomLineStyle(): string {
  const top = randomRange(5, 35)
  const left = randomRange(0, 100)
  const width = randomRange(20, 60)
  const rotate = randomRange(-30, 30)
  return `top:${top}px;left:${left}px;width:${width}px;transform:rotate(${rotate}deg);background:${randomColor()}`
}

const noiseLines = computed(() => {
  return Array.from({ length: 4 }, () => randomLineStyle())
})

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
    <el-input
      :model-value="modelValue"
      type="text"
      class="captcha-input"
      :maxlength="length"
      placeholder="请输入验证码"
      @update:model-value="handleInput"
      @blur="verifyCode"
    />
    <div class="captcha-display" @click="refreshCaptcha">
      <div v-for="(line, i) in noiseLines" :key="i" class="noise-line" :style="line" />
      <template v-if="captchaCode">
        <span
          v-for="(char, i) in captchaCode.split('')"
          :key="i"
          class="captcha-char"
          :style="charStyles[i]"
        >
          {{ char }}
        </span>
      </template>
      <span v-else class="captcha-placeholder">--</span>
    </div>
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
}
.captcha-display {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100px;
  height: 40px;
  background: linear-gradient(135deg, #f5f7fa, #e4e7ed);
  border-radius: 4px;
  border: 1px solid #dcdfe6;
  overflow: hidden;
  cursor: pointer;
  user-select: none;
}
.captcha-char {
  font-family: 'Courier New', monospace;
  font-weight: 700;
  display: inline-block;
  transition: all 0.3s;
}
.captcha-placeholder {
  color: #c0c4cc;
  font-size: 14px;
}
.noise-line {
  position: absolute;
  height: 1px;
  opacity: 0.4;
  pointer-events: none;
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
