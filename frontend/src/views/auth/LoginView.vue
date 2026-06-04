<script setup lang="ts">
/**
 * 登录页
 * - 手机号 + 密码登录表单
 */
import { ref, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { login } from '@/api/user'
import { ElMessage } from 'element-plus'
import { User, Lock } from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()

const loading = ref(false)

const form = reactive({
  phone: '',
  password: '',
})

const rules = {
  phone: [
    { required: true, message: '请输入手机号', trigger: 'blur' },
    { pattern: /^1[3-9]\d{9}$/, message: '手机号格式不正确', trigger: 'blur' },
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码至少6位', trigger: 'blur' },
  ],
}

const formRef = ref()

async function handleLogin() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

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

    // 跳转回之前页面或首页
    const redirect = route.query.redirect as string
    router.push(redirect || '/')
  } catch (e: any) {
    // 错误已由 request 拦截器处理
  } finally {
    loading.value = false
  }
}

function goRegister() {
  router.push('/register')
}
</script>

<template>
  <div class="login-page">
    <div class="login-box">
      <div class="login-header">
        <h2>欢迎登录</h2>
        <p>怡安印刷商城 - 企业印刷一站式服务平台</p>
      </div>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        size="large"
        class="login-form"
      >
        <el-form-item prop="phone">
          <el-input
            v-model="form.phone"
            placeholder="请输入手机号"
            :prefix-icon="User"
            maxlength="11"
          />
        </el-form-item>

        <el-form-item prop="password">
          <el-input
            v-model="form.password"
            type="password"
            placeholder="请输入密码"
            :prefix-icon="Lock"
            show-password
            @keyup.enter="handleLogin"
          />
        </el-form-item>

        <el-form-item>
          <el-button
            type="primary"
            class="login-btn"
            :loading="loading"
            @click="handleLogin"
          >
            登 录
          </el-button>
        </el-form-item>
      </el-form>

      <div class="login-footer">
        <span>还没有账号？</span>
        <el-button link type="primary" @click="goRegister">立即注册</el-button>
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
}

.login-box {
  width: 420px;
  padding: 40px 36px;
  background: var(--bg-card);
  border-radius: var(--radius-large);
  box-shadow: var(--shadow-base);
}

.login-header {
  text-align: center;
  margin-bottom: 32px;
}

.login-header h2 {
  font-size: 26px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.login-header p {
  font-size: 14px;
  color: var(--text-secondary);
}

.login-form {
  margin-bottom: 16px;
}

.login-btn {
  width: 100%;
  height: 44px;
  font-size: 16px;
  border-radius: 22px;
}

.login-footer {
  text-align: center;
  font-size: 14px;
  color: var(--text-secondary);
}

@media (max-width: 480px) {
  .login-box {
    width: 90%;
    padding: 32px 24px;
  }
}
</style>
