<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { login } from '@/api/user'

const router = useRouter()
const userStore = useUserStore()
const loading = ref(false)
const form = reactive({ phone: '', password: '' })

async function handleLogin() {
  loading.value = true
  try {
    const res = await login({ phone: form.phone, password: form.password })
    userStore.loginSuccess(res.token, {
      id: res.user.id, phone: res.user.phone, nickname: res.user.nickname,
      avatar: null, type: 1, auth_status: 0, vip_level: 0, balance: 0,
    })
    router.push('/')
  } catch { /* error handled by interceptor */ }
  finally { loading.value = false }
}
</script>

<template>
  <div class="login-page">
    <div class="login-box">
      <div class="login-header">
        <h2>欢迎登录</h2>
        <p>怡安印刷商城</p>
      </div>
      <el-form :model="form" class="login-form">
        <el-form-item>
          <el-input v-model="form.phone" placeholder="手机号" />
        </el-form-item>
        <el-form-item>
          <el-input v-model="form.password" type="password" placeholder="密码" show-password />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" class="login-btn" :loading="loading" @click="handleLogin">登录</el-button>
        </el-form-item>
      </el-form>
    </div>
  </div>
</template>

<style scoped>
.login-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f5f7fa 0%, #e4e7ed 100%); }
.login-box { width: 420px; padding: 40px; background: #fff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
.login-header { text-align: center; margin-bottom: 32px; }
.login-header h2 { font-size: 26px; font-weight: 600; margin-bottom: 8px; }
.login-btn { width: 100%; height: 44px; font-size: 16px; border-radius: 22px; }
</style>
