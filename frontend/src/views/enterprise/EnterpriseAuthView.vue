<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getAuthStatus, getEnterpriseInfo, applyEnterpriseAuth, type EnterpriseAuthInfo } from '@/api/enterprise'

const loading = ref(false)
const authStatus = ref(0)
const authStatusName = ref('')
const enterpriseInfo = ref<EnterpriseAuthInfo | null>(null)

interface FormState {
  company_name: string
  credit_code: string
  legal_person: string
  legal_person_id_card: string
  business_license_img: string
  contact_name: string
  contact_phone: string
  register_address: string
  office_address: string
  valid_date: string
}

const form = ref<FormState>({
  company_name: '',
  credit_code: '',
  legal_person: '',
  legal_person_id_card: '',
  business_license_img: '',
  contact_name: '',
  contact_phone: '',
  register_address: '',
  office_address: '',
  valid_date: '',
})

const showForm = computed(() => {
  return [0, 3, 4].includes(authStatus.value)
})

const showInfo = computed(() => {
  return [2, 20].includes(authStatus.value)
})

const showPending = computed(() => {
  return authStatus.value === 1
})

async function loadStatus() {
  loading.value = true
  try {
    const res = await getAuthStatus()
    authStatus.value = res.auth_status
    authStatusName.value = res.auth_status_name

    if (showInfo.value) {
      await loadInfo()
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function loadInfo() {
  try {
    const res = await getEnterpriseInfo()
    enterpriseInfo.value = res
  } catch (e) {
    console.error(e)
  }
}

async function validateForm(): Promise<boolean> {
  const f = form.value
  if (!f.company_name.trim()) { ElMessage.error('企业名称必填'); return false }
  if (!f.credit_code.trim()) { ElMessage.error('统一社会信用代码必填'); return false }
  if (!f.legal_person.trim()) { ElMessage.error('法人姓名必填'); return false }
  if (!f.legal_person_id_card.trim()) { ElMessage.error('法人身份证号必填'); return false }
  if (!f.business_license_img.trim()) { ElMessage.error('营业执照图片必填'); return false }
  if (!f.contact_name.trim()) { ElMessage.error('联系人姓名必填'); return false }
  if (!f.contact_phone.trim()) { ElMessage.error('联系人电话必填'); return false }
  return true
}

async function submitForm() {
  const valid = await validateForm()
  if (!valid) return

  loading.value = true
  try {
    const res = await applyEnterpriseAuth({
      company_name: form.value.company_name.trim(),
      credit_code: form.value.credit_code.trim(),
      legal_person: form.value.legal_person.trim(),
      legal_person_id_card: form.value.legal_person_id_card.trim(),
      business_license_img: form.value.business_license_img.trim(),
      contact_name: form.value.contact_name.trim(),
      contact_phone: form.value.contact_phone.trim(),
      register_address: form.value.register_address?.trim() || undefined,
      office_address: form.value.office_address?.trim() || undefined,
      valid_date: form.value.valid_date || undefined,
    })
    ElMessage.success('认证申请已提交')
    authStatus.value = res.auth_status
    authStatusName.value = '审核中'
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadStatus()
})

defineExpose({
  authStatus,
  authStatusName,
  enterpriseInfo,
  showForm,
  showInfo,
  showPending,
  form,
  validateForm,
  submitForm,
  loadStatus,
})
</script>

<template>
  <div class="enterprise-auth-view page-container">
    <h2 class="page-title">企业认证</h2>

    <div v-loading="loading" class="auth-panel">
      <div class="status-bar">
        当前状态：<el-tag :type="authStatus === 2 ? 'success' : authStatus === 1 ? 'warning' : 'info'">
          {{ authStatusName || '未提交' }}
        </el-tag>
      </div>

      <div v-if="showPending" class="pending-box">
        <p>您的企业认证申请正在审核中，请耐心等待。</p>
      </div>

      <div v-if="showInfo && enterpriseInfo" class="info-box">
        <el-descriptions :column="1" border>
          <el-descriptions-item label="企业名称">{{ enterpriseInfo.company_name }}</el-descriptions-item>
          <el-descriptions-item label="统一社会信用代码">{{ enterpriseInfo.credit_code }}</el-descriptions-item>
          <el-descriptions-item label="法人姓名">{{ enterpriseInfo.legal_person }}</el-descriptions-item>
          <el-descriptions-item label="法人身份证号">{{ enterpriseInfo.legal_person_id_card }}</el-descriptions-item>
          <el-descriptions-item label="联系人">{{ enterpriseInfo.contact_name }}</el-descriptions-item>
          <el-descriptions-item label="联系电话">{{ enterpriseInfo.contact_phone }}</el-descriptions-item>
          <el-descriptions-item label="注册地址">{{ enterpriseInfo.register_address || '-' }}</el-descriptions-item>
          <el-descriptions-item label="办公地址">{{ enterpriseInfo.office_address || '-' }}</el-descriptions-item>
          <el-descriptions-item label="有效期至">{{ enterpriseInfo.valid_date || '-' }}</el-descriptions-item>
          <el-descriptions-item label="营业执照">
            <img v-if="enterpriseInfo.business_license_img" :src="enterpriseInfo.business_license_img" class="license-img" />
          </el-descriptions-item>
        </el-descriptions>
      </div>

      <div v-if="showForm" class="form-box">
        <el-form label-width="140px">
          <el-form-item label="企业名称" required>
            <el-input v-model="form.company_name" placeholder="请输入企业全称" maxlength="200" />
          </el-form-item>
          <el-form-item label="统一社会信用代码" required>
            <el-input v-model="form.credit_code" placeholder="请输入统一社会信用代码" maxlength="50" />
          </el-form-item>
          <el-form-item label="法人姓名" required>
            <el-input v-model="form.legal_person" placeholder="请输入法人姓名" maxlength="50" />
          </el-form-item>
          <el-form-item label="法人身份证号" required>
            <el-input v-model="form.legal_person_id_card" placeholder="请输入法人身份证号" maxlength="18" />
          </el-form-item>
          <el-form-item label="营业执照图片URL" required>
            <el-input v-model="form.business_license_img" placeholder="请输入营业执照图片URL" maxlength="500" />
          </el-form-item>
          <el-form-item label="联系人姓名" required>
            <el-input v-model="form.contact_name" placeholder="请输入联系人姓名" maxlength="50" />
          </el-form-item>
          <el-form-item label="联系人电话" required>
            <el-input v-model="form.contact_phone" placeholder="请输入联系人电话" maxlength="20" />
          </el-form-item>
          <el-form-item label="注册地址">
            <el-input v-model="form.register_address" placeholder="请输入注册地址" maxlength="500" />
          </el-form-item>
          <el-form-item label="办公地址">
            <el-input v-model="form.office_address" placeholder="请输入办公地址" maxlength="500" />
          </el-form-item>
          <el-form-item label="有效期至">
            <el-input v-model="form.valid_date" placeholder="YYYY-MM-DD" />
          </el-form-item>
          <el-form-item>
            <el-button type="primary" :loading="loading" @click="submitForm">提交认证</el-button>
          </el-form-item>
        </el-form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.enterprise-auth-view {
  max-width: 720px;
  margin: 20px auto;
  padding: 20px;
}
.auth-panel {
  background: #fff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.status-bar {
  margin-bottom: 20px;
  font-size: 15px;
}
.pending-box {
  padding: 40px;
  text-align: center;
  color: #e6a23c;
  background: #fdf6ec;
  border-radius: 4px;
}
.info-box {
  margin-top: 16px;
}
.license-img {
  max-width: 300px;
  max-height: 200px;
  border-radius: 4px;
}
.form-box {
  margin-top: 16px;
}
</style>
