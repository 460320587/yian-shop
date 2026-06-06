<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useUserStore } from '@/stores/user'
import { login, loginBySms, sendSmsCode } from '@/api/user'
import AppCaptcha from '@/components/Common/AppCaptcha/AppCaptcha.vue'

const router = useRouter()
const userStore = useUserStore()
const loading = ref(false)
const activeTab = ref<'password' | 'sms'>('password')
const form = reactive({ phone: '', password: '', captcha: '', sms_code: '' })
const captchaRef = ref<InstanceType<typeof AppCaptcha>>()
const loginAttempts = ref(0)
const captchaValid = ref(false)
const captchaKey = ref('')
const smsCountdown = ref(0)
const smsLoading = ref(false)
let smsTimer: ReturnType<typeof setInterval> | null = null

const showCaptcha = computed(() => loginAttempts.value >= 3)

async function handleLogin() {
  if (!form.phone) {
    ElMessage.warning('请输入手机号')
    return
  }
  if (!form.password) {
    ElMessage.warning('请输入密码')
    return
  }
  if (showCaptcha.value) {
    if (!form.captcha) {
      ElMessage.warning('请输入验证码')
      return
    }
    if (!captchaValid.value) {
      ElMessage.warning('验证码不正确')
      return
    }
  }

  loading.value = true
  try {
    const res = await login({ phone: form.phone, password: form.password })
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
    ElMessage.success('登录成功')
    loginAttempts.value = 0
    router.push('/')
  } catch (e: any) {
    loginAttempts.value++
    const msg = e?.response?.data?.message || '登录失败'
    ElMessage.error(msg)
    if (showCaptcha.value && captchaRef.value) {
      captchaRef.value.refreshCaptcha()
      form.captcha = ''
      captchaValid.value = false
    }
  } finally {
    loading.value = false
  }
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

async function handleSmsLogin() {
  if (!form.phone) {
    ElMessage.warning('请输入手机号')
    return
  }
  if (!form.sms_code) {
    ElMessage.warning('请输入短信验证码')
    return
  }

  loading.value = true
  try {
    const res = await loginBySms({ phone: form.phone, sms_code: form.sms_code })
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
    ElMessage.success('登录成功')
    router.push('/')
  } catch (e: any) {
    const msg = e?.response?.data?.message || '登录失败'
    ElMessage.error(msg)
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
  loginAttempts,
  showCaptcha,
  captchaValid,
  handleLogin,
  activeTab,
  smsCountdown,
  smsLoading,
  handleSendSmsCode,
  handleSmsLogin,
})
</script>

<template>
  <div class="login-page">
    <div class="login-box">
      <div class="login-header">
        <h2>欢迎登录</h2>
        <p>怡安印刷商城</p>
      </div>
      <el-tabs v-model="activeTab" class="login-tabs">
        <el-tab-pane label="密码登录" name="password">
          <el-form :model="form" class="login-form" label-position="top">
            <el-form-item label="手机号">
              <el-input v-model="form.phone" placeholder="请输入手机号" maxlength="11" />
            </el-form-item>
            <el-form-item label="密码">
              <el-input
                v-model="form.password"
                type="password"
                placeholder="请输入密码"
                show-password
                @keyup.enter="handleLogin"
              />
            </el-form-item>
            <el-form-item v-if="showCaptcha" label="验证码">
              <AppCaptcha
                ref="captchaRef"
                v-model="form.captcha"
                @verify="onCaptchaVerify"
              />
            </el-form-item>
            <el-form-item>
              <el-button
                type="primary"
                class="login-btn"
                :loading="loading"
                @click="handleLogin"
              >
                登录
              </el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <el-tab-pane label="短信登录" name="sms">
          <el-form :model="form" class="login-form" label-position="top">
            <el-form-item label="手机号">
              <el-input v-model="form.phone" placeholder="请输入手机号" maxlength="11" />
            </el-form-item>
            <el-form-item label="图形验证码">
              <AppCaptcha
                ref="captchaRef"
                v-model="form.captcha"
                @verify="onCaptchaVerify"
              />
            </el-form-item>
            <el-form-item label="短信验证码">
              <div class="sms-code-row">
                <el-input
                  v-model="form.sms_code"
                  placeholder="请输入短信验证码"
                  maxlength="6"
                  class="sms-input"
                  @keyup.enter="handleSmsLogin"
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
            <el-form-item>
              <el-button
                type="primary"
                class="login-btn"
                :loading="loading"
                @click="handleSmsLogin"
              >
                登录
              </el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>
      </el-tabs>
      <div class="login-links">
        <router-link to="/forgot-password">忘记密码</router-link>
        <router-link to="/register">立即注册</router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e7ed 100%);
  padding: 24px;
}
.login-box {
  width: 420px;
  padding: 40px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}
.login-header {
  text-align: center;
  margin-bottom: 24px;
}
.login-header h2 {
  font-size: 26px;
  font-weight: 600;
  margin-bottom: 8px;
}
.login-tabs :deep(.el-tabs__nav-wrap::after) {
  height: 1px;
}
.login-tabs :deep(.el-tabs__item) {
  font-size: 16px;
}
.login-form {
  margin-top: 16px;
}
.login-btn {
  width: 100%;
  height: 44px;
  font-size: 16px;
  border-radius: 22px;
}
.login-links {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  margin-top: 16px;
}
.login-links a {
  color: #409eff;
  text-decoration: none;
}
.login-links a:hover {
  text-decoration: underline;
}
.sms-code-row {
  display: flex;
  gap: 12px;
}
.sms-input {
  flex: 1;
}
</style>
