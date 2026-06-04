<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { register } from '@/api/user'

const router = useRouter()
const userStore = useUserStore()
const loading = ref(false)
const form = reactive({ phone: '', password: '', password_confirmation: '' })

async function handleRegister() {
  loading.value = true
  try {
    const res = await register({ phone: form.phone, password: form.password, password_confirmation: form.password_confirmation })
    userStore.loginSuccess(res.token, { id: res.user.id, phone: res.user.phone, nickname: res.user.nickname, avatar: null, type: 1, auth_status: 0, vip_level: 0, balance: 0 })
    router.push('/')
  } catch { }
  finally { loading.value = false }
}
</script>

<template>
  <div class="register-page">
    <div class="register-box">
      <h2>注册账号</h2>
      <el-form :model="form" class="register-form">
        <el-form-item><el-input v-model="form.phone" placeholder="手机号" /></el-form-item>
        <el-form-item><el-input v-model="form.password" type="password" placeholder="密码" show-password /></el-form-item>
        <el-form-item><el-input v-model="form.password_confirmation" type="password" placeholder="确认密码" show-password /></el-form-item>
        <el-form-item><el-button type="primary" class="register-btn" :loading="loading" @click="handleRegister">注册</el-button></el-form-item>
      </el-form>
    </div>
  </div>
</template>

<style scoped>
.register-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f5f7fa 0%, #e4e7ed 100%); }
.register-box { width: 420px; padding: 40px; background: #fff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
.register-box h2 { text-align: center; margin-bottom: 32px; font-size: 26px; }
.register-btn { width: 100%; height: 44px; font-size: 16px; border-radius: 22px; }
</style>
