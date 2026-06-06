<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getPayPasswordStatus, setPayPassword, updatePayPassword, type PayPasswordStatus } from '@/api/pay-password'

const status = ref<PayPasswordStatus | null>(null)
const loading = ref(false)
const submitting = ref(false)

const setForm = ref({
  pay_password: '',
  pay_password_confirmation: '',
})

const updateForm = ref({
  old_pay_password: '',
  pay_password: '',
  pay_password_confirmation: '',
})

async function loadStatus() {
  loading.value = true
  try {
    const res = await getPayPasswordStatus()
    status.value = res
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function resetForms() {
  setForm.value = { pay_password: '', pay_password_confirmation: '' }
  updateForm.value = { old_pay_password: '', pay_password: '', pay_password_confirmation: '' }
}

async function handleSet() {
  if (setForm.value.pay_password.length < 6) {
    ElMessage.error('支付密码至少6位')
    return
  }
  if (setForm.value.pay_password !== setForm.value.pay_password_confirmation) {
    ElMessage.error('两次输入的密码不一致')
    return
  }

  submitting.value = true
  try {
    await setPayPassword({
      pay_password: setForm.value.pay_password,
      pay_password_confirmation: setForm.value.pay_password_confirmation,
    })
    ElMessage.success('支付密码设置成功')
    resetForms()
    await loadStatus()
  } catch (e) {
    console.error(e)
  } finally {
    submitting.value = false
  }
}

async function handleUpdate() {
  if (updateForm.value.pay_password.length < 6) {
    ElMessage.error('支付密码至少6位')
    return
  }
  if (updateForm.value.pay_password !== updateForm.value.pay_password_confirmation) {
    ElMessage.error('两次输入的密码不一致')
    return
  }

  submitting.value = true
  try {
    await updatePayPassword({
      old_pay_password: updateForm.value.old_pay_password,
      pay_password: updateForm.value.pay_password,
      pay_password_confirmation: updateForm.value.pay_password_confirmation,
    })
    ElMessage.success('支付密码修改成功')
    resetForms()
    await loadStatus()
  } catch (e) {
    console.error(e)
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadStatus()
})

defineExpose({
  status,
  setForm,
  updateForm,
  loadStatus,
  handleSet,
  handleUpdate,
})
</script>

<template>
  <div class="pay-password-view page-container">
    <h2 class="page-title">支付密码</h2>

    <div v-if="loading" class="loading-state">加载中...</div>

    <div v-else class="password-panel">
      <!-- 锁定提示 -->
      <div v-if="status?.locked" class="locked-notice">
        <el-alert
          title="支付密码已锁定"
          type="error"
          :description="`您的支付密码因多次输入错误已被锁定，请稍后再试。`"
          show-icon
          :closable="false"
        />
      </div>

      <!-- 设置表单 -->
      <div v-else-if="!status?.has_pay_password" class="set-form">
        <h3>设置支付密码</h3>
        <p class="form-tip">为了账户安全，请设置6-20位支付密码</p>
        <el-form label-width="120px">
          <el-form-item label="支付密码" required>
            <el-input
              v-model="setForm.pay_password"
              type="password"
              placeholder="请输入支付密码"
              show-password
              maxlength="20"
            />
          </el-form-item>
          <el-form-item label="确认密码" required>
            <el-input
              v-model="setForm.pay_password_confirmation"
              type="password"
              placeholder="请再次输入支付密码"
              show-password
              maxlength="20"
            />
          </el-form-item>
        </el-form>
        <div class="actions">
          <el-button type="primary" :loading="submitting" @click="handleSet">确认设置</el-button>
        </div>
      </div>

      <!-- 修改表单 -->
      <div v-else class="update-form">
        <h3>修改支付密码</h3>
        <el-form label-width="120px">
          <el-form-item label="原支付密码" required>
            <el-input
              v-model="updateForm.old_pay_password"
              type="password"
              placeholder="请输入原支付密码"
              show-password
              maxlength="20"
            />
          </el-form-item>
          <el-form-item label="新支付密码" required>
            <el-input
              v-model="updateForm.pay_password"
              type="password"
              placeholder="请输入新支付密码"
              show-password
              maxlength="20"
            />
          </el-form-item>
          <el-form-item label="确认新密码" required>
            <el-input
              v-model="updateForm.pay_password_confirmation"
              type="password"
              placeholder="请再次输入新支付密码"
              show-password
              maxlength="20"
            />
          </el-form-item>
        </el-form>
        <div class="actions">
          <el-button type="primary" :loading="submitting" @click="handleUpdate">确认修改</el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pay-password-view {
  max-width: 560px;
  margin: 20px auto;
  padding: 20px;
}
.password-panel {
  background: #fff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.loading-state {
  text-align: center;
  padding: 40px;
  color: #909399;
}
.locked-notice {
  margin-bottom: 16px;
}
.set-form h3,
.update-form h3 {
  margin-bottom: 8px;
  font-size: 16px;
}
.form-tip {
  color: #909399;
  font-size: 13px;
  margin-bottom: 20px;
}
.actions {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #ebeef5;
}
</style>
