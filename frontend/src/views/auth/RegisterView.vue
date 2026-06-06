<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useUserStore } from '@/stores/user'
import { register, sendSmsCode } from '@/api/user'
import AppCaptcha from '@/components/Common/AppCaptcha/AppCaptcha.vue'

const router = useRouter()
const userStore = useUserStore()
const loading = ref(false)
const agreed = ref(false)
const captchaRef = ref<InstanceType<typeof AppCaptcha>>()
const captchaValid = ref(false)
const captchaKey = ref('')
const smsCountdown = ref(0)
const smsLoading = ref(false)
let smsTimer: ReturnType<typeof setInterval> | null = null

const form = reactive({
  phone: '',
  password: '',
  password_confirmation: '',
  nickname: '',
  link_person: '',
  qq: '',
  captcha: '',
  sms_code: '',
})

const passwordStrength = computed(() => {
  const pwd = form.password
  if (!pwd) return 0
  let score = 0
  if (pwd.length >= 8) score++
  if (/[A-Z]/.test(pwd)) score++
  if (/[a-z]/.test(pwd)) score++
  if (/\d/.test(pwd)) score++
  if (/[^A-Za-z0-9]/.test(pwd)) score++
  return Math.min(score, 4)
})

const strengthText = computed(() => {
  const map = ['', '弱', '中', '强', '极强']
  return map[passwordStrength.value] || ''
})

const strengthColor = computed(() => {
  const map = ['', '#f56c6c', '#e6a23c', '#67c23a', '#409eff']
  return map[passwordStrength.value] || ''
})

function validate(): boolean {
  if (!form.phone) {
    ElMessage.warning('请输入手机号')
    return false
  }
  if (!/^1[3-9]\d{9}$/.test(form.phone)) {
    ElMessage.warning('手机号格式不正确')
    return false
  }
  if (!form.sms_code) {
    ElMessage.warning('请输入短信验证码')
    return false
  }
  if (!form.password) {
    ElMessage.warning('请输入密码')
    return false
  }
  if (!/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{8,}$/.test(form.password)) {
    ElMessage.warning('密码长度至少8位，需包含字母和数字')
    return false
  }
  if (form.password !== form.password_confirmation) {
    ElMessage.warning('两次密码输入不一致')
    return false
  }
  if (!form.captcha) {
    ElMessage.warning('请输入验证码')
    return false
  }
  if (!captchaValid.value) {
    ElMessage.warning('验证码不正确')
    return false
  }
  if (!agreed.value) {
    ElMessage.warning('请同意用户协议')
    return false
  }
  return true
}

async function handleSendSmsCode() {
  if (!form.phone) {
    ElMessage.warning('请输入手机号')
    return
  }
  if (!/^1[3-9]\d{9}$/.test(form.phone)) {
    ElMessage.warning('手机号格式不正确')
    return
  }
  if (!form.captcha) {
    ElMessage.warning('请先输入图形验证码')
    return
  }
  if (!captchaValid.value) {
    ElMessage.warning('图形验证码不正确')
    return
  }
  smsLoading.value = true
  try {
    await sendSmsCode({
      phone: form.phone,
      captcha_key: captchaKey.value,
      captcha_code: form.captcha,
    })
    ElMessage.success('验证码已发送')
    smsCountdown.value = 60
    if (smsTimer) clearInterval(smsTimer)
    smsTimer = setInterval(() => {
      smsCountdown.value--
      if (smsCountdown.value <= 0) {
        if (smsTimer) clearInterval(smsTimer)
        smsTimer = null
      }
    }, 1000)
  } catch (e: any) {
    const msg = e?.response?.data?.message || '发送失败'
    ElMessage.error(msg)
  } finally {
    smsLoading.value = false
  }
}

async function handleRegister() {
  if (!validate()) return
  loading.value = true
  try {
    const res = await register({
      phone: form.phone,
      password: form.password,
      password_confirmation: form.password_confirmation,
      nickname: form.nickname || undefined,
      link_person: form.link_person || undefined,
      qq: form.qq || undefined,
    })
    userStore.loginSuccess(res.token, {
      id: res.user.id,
      phone: res.user.phone,
      nickname: res.user.nickname,
      avatar: null,
      type: 1,
      auth_status: 0,
      vip_level: 0,
      balance: 0,
    })
    ElMessage.success('注册成功')
    router.push('/')
  } catch (e: any) {
    const msg = e?.response?.data?.message || '注册失败'
    ElMessage.error(msg)
    if (captchaRef.value) {
      captchaRef.value.refreshCaptcha()
      form.captcha = ''
      captchaValid.value = false
    }
  } finally {
    loading.value = false
  }
}

function onCaptchaVerify(valid: boolean, key: string) {
  captchaValid.value = valid
  captchaKey.value = key
}

defineExpose({
  loading,
  form,
  agreed,
  captchaValid,
  passwordStrength,
  strengthText,
  strengthColor,
  validate,
  handleRegister,
  handleSendSmsCode,
  smsCountdown,
  smsLoading,
})
</script>

<template>
  <div class="register-page">
    <div class="register-box">
      <h2>注册账号</h2>
      <el-form :model="form" class="register-form" label-position="top">
        <el-form-item label="手机号">
          <el-input v-model="form.phone" placeholder="请输入手机号" maxlength="11" />
        </el-form-item>

        <el-form-item label="密码">
          <el-input
            v-model="form.password"
            type="password"
            placeholder="长度至少8位，需包含字母和数字"
            show-password
          />
          <div v-if="form.password" class="password-strength">
            <div class="strength-bar">
              <div
                class="strength-fill"
                :style="{ width: (passwordStrength / 4) * 100 + '%', background: strengthColor }"
              />
            </div>
            <span class="strength-text" :style="{ color: strengthColor }">{{ strengthText }}</span>
          </div>
        </el-form-item>

        <el-form-item label="确认密码">
          <el-input
            v-model="form.password_confirmation"
            type="password"
            placeholder="请再次输入密码"
            show-password
          />
        </el-form-item>

        <el-form-item label="昵称">
          <el-input v-model="form.nickname" placeholder="请输入昵称（选填）" maxlength="50" />
        </el-form-item>

        <el-form-item label="联系人">
          <el-input v-model="form.link_person" placeholder="请输入联系人姓名（选填）" maxlength="50" />
        </el-form-item>

        <el-form-item label="QQ">
          <el-input v-model="form.qq" placeholder="请输入QQ号（选填）" maxlength="20" />
        </el-form-item>

        <el-form-item label="短信验证码">
          <div class="sms-code-row">
            <el-input
              v-model="form.sms_code"
              placeholder="请输入短信验证码"
              maxlength="6"
              class="sms-input"
            />
            <el-button
              type="primary"
              :disabled="smsCountdown > 0"
              :loading="smsLoading"
              @click="handleSendSmsCode"
            >
              {{ smsCountdown > 0 ? `${smsCountdown}s后重发` : '获取验证码' }}
            </el-button>
          </div>
        </el-form-item>

        <el-form-item label="验证码">
          <AppCaptcha
            ref="captchaRef"
            v-model="form.captcha"
            @verify="onCaptchaVerify"
          />
        </el-form-item>

        <el-form-item>
          <el-checkbox v-model="agreed">
            我已阅读并同意
            <el-link type="primary" @click.stop="router.push('/terms')">用户协议</el-link>
          </el-checkbox>
        </el-form-item>

        <el-form-item>
          <el-button
            type="primary"
            class="register-btn"
            :loading="loading"
            @click="handleRegister"
          >
            注册
          </el-button>
        </el-form-item>

        <div class="login-link">
          已有账号？<el-link type="primary" @click="router.push('/login')">立即登录</el-link>
        </div>
      </el-form>
    </div>
  </div>
</template>

<style scoped>
.register-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e7ed 100%);
  padding: 24px;
}
.register-box {
  width: 420px;
  padding: 40px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}
.register-box h2 {
  text-align: center;
  margin-bottom: 32px;
  font-size: 26px;
}
.register-btn {
  width: 100%;
  height: 44px;
  font-size: 16px;
  border-radius: 22px;
}
.password-strength {
  margin-top: 6px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.strength-bar {
  flex: 1;
  height: 4px;
  background: #e4e7ed;
  border-radius: 2px;
  overflow: hidden;
}
.strength-fill {
  height: 100%;
  border-radius: 2px;
  transition: all 0.3s;
}
.strength-text {
  font-size: 12px;
  min-width: 28px;
}
.login-link {
  text-align: center;
  font-size: 14px;
  color: #606266;
  margin-top: 8px;
}
.sms-code-row {
  display: flex;
  gap: 12px;
}
.sms-input {
  flex: 1;
}
</style>
