<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminStore } from '@/stores/admin'

const router = useRouter()
const adminStore = useAdminStore()
const loading = ref(false)
const form = reactive({ username: '', password: '' })

async function handleLogin() {
  loading.value = true
  try {
    await adminStore.login({ username: form.username, password: form.password })
    router.push('/admin')
  } catch { }
  finally { loading.value = false }
}
</script>

<template>
  <div class="admin-login-page">
    <div class="login-box">
      <div class="login-header">
        <h2>管理后台</h2>
        <p>怡安印刷商城 - 运营管理系统</p>
      </div>
      <el-form :model="form" class="login-form">
        <el-form-item><el-input v-model="form.username" placeholder="用户名" /></el-form-item>
        <el-form-item><el-input v-model="form.password" type="password" placeholder="密码" show-password /></el-form-item>
        <el-form-item><el-button type="primary" class="login-btn" :loading="loading" @click="handleLogin">登录</el-button></el-form-item>
      </el-form>
    </div>
  </div>
</template>

<style scoped>
.admin-login-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1a2a3a 0%, #0d1b2a 100%); }
.login-box { width: 420px; padding: 40px; background: #fff; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
.login-header { text-align: center; margin-bottom: 32px; }
.login-header h2 { font-size: 26px; font-weight: 600; margin-bottom: 8px; }
.login-btn { width: 100%; height: 44px; font-size: 16px; border-radius: 22px; }
</style>
