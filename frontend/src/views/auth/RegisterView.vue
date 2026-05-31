<script setup lang="ts">
/**
 * 注册页（骨架）
 */
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { User, Lock, Message } from '@element-plus/icons-vue'

const router = useRouter()
const loading = ref(false)

const form = reactive({
  phone: '',
  password: '',
  confirmPassword: '',
})

const formRef = ref()

const rules = {
  phone: [
    { required: true, message: '请输入手机号', trigger: 'blur' },
    { pattern: /^1[3-9]\d{9}$/, message: '手机号格式不正确', trigger: 'blur' },
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码至少6位', trigger: 'blur' },
  ],
  confirmPassword: [
    { required: true, message: '请确认密码', trigger: 'blur' },
    {
      validator: (_rule: unknown, value: string, callback: (error?: Error) => void) => {
        if (value !== form.password) {
          callback(new Error('两次输入的密码不一致'))
        } else {
          callback()
        }
      },
      trigger: 'blur',
    },
  ],
}

async function handleRegister() {
  await formRef.value?.validate()
  loading.value = true
  setTimeout(() => {
    loading.value = false
    ElMessage.success('注册成功，请登录')
    router.push('/login')
  }, 800)
}

function goLogin() {
  router.push('/login')
}
</script>

<template>
  <div class="register-page">
    <div class="register-box">
      <div class="register-header">
        <h2>注册账号</h2>
        <p>加入怡安印刷商城，享受专业服务</p>
      </div>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        size="large"
        class="register-form"
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
          />
        </el-form-item>

        <el-form-item prop="confirmPassword">
          <el-input
            v-model="form.confirmPassword"
            type="password"
            placeholder="请确认密码"
            :prefix-icon="Message"
            show-password
            @keyup.enter="handleRegister"
          />
        </el-form-item>

        <el-form-item>
          <el-button
            type="primary"
            class="register-btn"
            :loading="loading"
            @click="handleRegister"
          >
            注 册
          </el-button>
        </el-form-item>
      </el-form>

      <div class="register-footer">
        <span>已有账号？</span>
        <el-button link type="primary" @click="goLogin">去登录</el-button>
      </div>
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
}

.register-box {
  width: 420px;
  padding: 40px 36px;
  background: var(--bg-card);
  border-radius: var(--radius-large);
  box-shadow: var(--shadow-base);
}

.register-header {
  text-align: center;
  margin-bottom: 32px;
}

.register-header h2 {
  font-size: 26px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.register-header p {
  font-size: 14px;
  color: var(--text-secondary);
}

.register-btn {
  width: 100%;
  height: 44px;
  font-size: 16px;
  border-radius: 22px;
}

.register-footer {
  text-align: center;
  font-size: 14px;
  color: var(--text-secondary);
}

@media (max-width: 480px) {
  .register-box {
    width: 90%;
    padding: 32px 24px;
  }
}
</style>
