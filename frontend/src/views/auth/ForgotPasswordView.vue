<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { forgotPassword, resetPassword } from '@/api/user'

const router = useRouter()
const step = ref(1)
const loading = ref(false)

interface FormState {
  phone: string
  token: string
  password: string
  password_confirmation: string
}

const form = ref<FormState>({
  phone: '',
  token: '',
  password: '',
  password_confirmation: '',
})

async function validatePhone(): Promise<boolean> {
  const phone = form.value.phone.trim()
  if (!phone) {
    ElMessage.error('请输入手机号')
    return false
  }
  if (!/^1[3-9]\d{9}$/.test(phone)) {
    ElMessage.error('手机号格式不正确')
    return false
  }
  return true
}

async function requestToken() {
  const valid = await validatePhone()
  if (!valid) return

  loading.value = true
  try {
    const res = await forgotPassword({ phone: form.value.phone.trim() })
    form.value.token = res.token
    step.value = 2
    ElMessage.success('重置码已发送')
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function validateResetForm(): Promise<boolean> {
  const { token, password, password_confirmation } = form.value
  if (!token.trim()) {
    ElMessage.error('请输入重置码')
    return false
  }
  if (!password || password.length < 6) {
    ElMessage.error('密码长度不能少于6位')
    return false
  }
  if (password !== password_confirmation) {
    ElMessage.error('两次输入的密码不一致')
    return false
  }
  return true
}

async function submitReset() {
  const valid = await validateResetForm()
  if (!valid) return

  loading.value = true
  try {
    await resetPassword({
      phone: form.value.phone.trim(),
      token: form.value.token.trim(),
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
    })
    ElMessage.success('密码重置成功，请重新登录')
    router.push('/login')
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

defineExpose({
  step,
  loading,
  form,
  validatePhone,
  requestToken,
  validateResetForm,
  submitReset,
})
</script>

<template>
  <div class="forgot-password-view page-container">
    <h2 class="page-title">忘记密码</h2>

    <div class="steps">
      <div :class="['step', { active: step === 1 }]">1. 验证手机号</div>
      <div :class="['step', { active: step === 2 }]">2. 设置新密码</div>
    </div>

    <div v-if="step === 1" class="step-panel">
      <el-form label-width="100px">
        <el-form-item label="手机号">
          <el-input
            v-model="form.phone"
            placeholder="请输入注册手机号"
            maxlength="11"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="loading" @click="requestToken">
            获取重置码
          </el-button>
        </el-form-item>
      </el-form>
    </div>

    <div v-if="step === 2" class="step-panel">
      <el-form label-width="100px">
        <el-form-item label="手机号">
          <el-input v-model="form.phone" disabled />
        </el-form-item>
        <el-form-item label="重置码">
          <el-input v-model="form.token" placeholder="请输入重置码" />
        </el-form-item>
        <el-form-item label="新密码">
          <el-input
            v-model="form.password"
            type="password"
            placeholder="请输入新密码（至少6位）"
            show-password
          />
        </el-form-item>
        <el-form-item label="确认密码">
          <el-input
            v-model="form.password_confirmation"
            type="password"
            placeholder="请再次输入新密码"
            show-password
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="loading" @click="submitReset">
            重置密码
          </el-button>
          <el-button link @click="step = 1">上一步</el-button>
        </el-form-item>
      </el-form>
    </div>

    <div class="extra-links">
      <router-link to="/login">返回登录</router-link>
    </div>
  </div>
</template>

<style scoped>
.forgot-password-view {
  max-width: 480px;
  margin: 40px auto;
  padding: 24px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.page-title {
  text-align: center;
  margin-bottom: 24px;
}
.steps {
  display: flex;
  justify-content: center;
  gap: 24px;
  margin-bottom: 24px;
  color: #909399;
}
.step {
  padding: 8px 16px;
  border-bottom: 2px solid #dcdfe6;
}
.step.active {
  color: #409eff;
  border-bottom-color: #409eff;
  font-weight: 600;
}
.step-panel {
  margin-bottom: 16px;
}
.extra-links {
  text-align: center;
  font-size: 14px;
}
</style>
