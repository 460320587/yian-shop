<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getInvoiceTitles, createInvoiceTitle, updateInvoiceTitle, deleteInvoiceTitle } from '@/api/invoice'
import type { InvoiceTitle, CreateInvoiceTitleData } from '@/api/invoice'

const titles = ref<InvoiceTitle[]>([])
const loading = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)
const submitting = ref(false)

const titleTypeOptions = [
  { value: 1, label: '个人' },
  { value: 2, label: '企业' },
]

const categoryOptions = [
  { value: 1, label: '普通发票' },
  { value: 2, label: '专用发票' },
]

interface FormState {
  title_type: number
  invoice_category: number
  company_name: string
  tax_number: string
  register_address: string
  register_phone: string
  bank_name: string
  bank_account: string
  is_default: number
}

const defaultForm: FormState = {
  title_type: 2,
  invoice_category: 1,
  company_name: '',
  tax_number: '',
  register_address: '',
  register_phone: '',
  bank_name: '',
  bank_account: '',
  is_default: 0,
}

const form = ref<FormState>({ ...defaultForm })

async function loadTitles() {
  loading.value = true
  try {
    const res = await getInvoiceTitles()
    titles.value = res
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function openDialog() {
  isEdit.value = false
  editId.value = null
  form.value = { ...defaultForm }
  dialogVisible.value = true
}

function editTitle(title: InvoiceTitle) {
  isEdit.value = true
  editId.value = title.id
  form.value = {
    title_type: title.title_type,
    invoice_category: title.invoice_category,
    company_name: title.company_name,
    tax_number: title.tax_number || '',
    register_address: title.register_address || '',
    register_phone: title.register_phone || '',
    bank_name: title.bank_name || '',
    bank_account: title.bank_account || '',
    is_default: title.is_default,
  }
  dialogVisible.value = true
}

async function submitForm() {
  if (!form.value.company_name.trim()) {
    ElMessage.error('请填写公司名称')
    return
  }

  const payload: CreateInvoiceTitleData = {
    title_type: form.value.title_type,
    invoice_category: form.value.invoice_category,
    company_name: form.value.company_name.trim(),
  }
  if (form.value.tax_number) payload.tax_number = form.value.tax_number
  if (form.value.register_address) payload.register_address = form.value.register_address
  if (form.value.register_phone) payload.register_phone = form.value.register_phone
  if (form.value.bank_name) payload.bank_name = form.value.bank_name
  if (form.value.bank_account) payload.bank_account = form.value.bank_account
  if (form.value.is_default) payload.is_default = 1

  submitting.value = true
  try {
    if (isEdit.value && editId.value) {
      await updateInvoiceTitle(editId.value, payload)
      ElMessage.success('发票抬头已更新')
    } else {
      await createInvoiceTitle(payload)
      ElMessage.success('发票抬头已添加')
    }
    dialogVisible.value = false
    await loadTitles()
  } catch (e) {
    console.error(e)
  } finally {
    submitting.value = false
  }
}

async function deleteTitle(title: InvoiceTitle) {
  if (!confirm('确定要删除该发票抬头吗？')) return
  loading.value = true
  try {
    await deleteInvoiceTitle(title.id)
    ElMessage.success('已删除')
    await loadTitles()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadTitles()
})

defineExpose({
  titles,
  loading,
  dialogVisible,
  isEdit,
  form,
  openDialog,
  submitForm,
  editTitle,
  deleteTitle,
  loadTitles,
})
</script>

<template>
  <div class="invoice-title-view page-container">
    <div class="header-row">
      <h2 class="page-title">发票抬头管理</h2>
      <el-button type="primary" size="small" @click="openDialog">+ 添加抬头</el-button>
    </div>

    <div v-if="loading && titles.length === 0" class="loading-text">加载中...</div>

    <div v-else v-loading="loading" class="title-list">
      <div v-for="title in titles" :key="title.id" class="title-item">
        <div class="title-header">
          <span class="company-name">{{ title.company_name }}</span>
          <span v-if="title.is_default" class="default-tag">默认</span>
          <span class="type-tag">{{ titleTypeOptions.find(o => o.value === title.title_type)?.label }}</span>
        </div>
        <div class="title-meta">
          <span>{{ categoryOptions.find(o => o.value === title.invoice_category)?.label }}</span>
          <span v-if="title.tax_number">税号: {{ title.tax_number }}</span>
        </div>
        <div class="title-actions">
          <el-button link size="small" @click="editTitle(title)">编辑</el-button>
          <el-button link type="danger" size="small" @click="deleteTitle(title)">删除</el-button>
        </div>
      </div>

      <div v-if="titles.length === 0" class="empty-state">
        暂无发票抬头，请点击右上角添加
      </div>
    </div>

    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑发票抬头' : '添加发票抬头'" width="520px">
      <el-form label-width="100px">
        <el-form-item label="抬头类型" required>
          <el-radio-group v-model="form.title_type">
            <el-radio-button v-for="opt in titleTypeOptions" :key="opt.value" :label="opt.value">
              {{ opt.label }}
            </el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="发票类型" required>
          <el-radio-group v-model="form.invoice_category">
            <el-radio-button v-for="opt in categoryOptions" :key="opt.value" :label="opt.value">
              {{ opt.label }}
            </el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="公司名称" required>
          <el-input v-model="form.company_name" placeholder="请填写公司名称" maxlength="200" />
        </el-form-item>
        <el-form-item label="税号">
          <el-input v-model="form.tax_number" placeholder="请填写纳税人识别号" maxlength="20" />
        </el-form-item>
        <el-form-item label="注册地址">
          <el-input v-model="form.register_address" placeholder="请填写注册地址" maxlength="255" />
        </el-form-item>
        <el-form-item label="注册电话">
          <el-input v-model="form.register_phone" placeholder="请填写注册电话" maxlength="20" />
        </el-form-item>
        <el-form-item label="开户银行">
          <el-input v-model="form.bank_name" placeholder="请填写开户银行" maxlength="100" />
        </el-form-item>
        <el-form-item label="银行账号">
          <el-input v-model="form.bank_account" placeholder="请填写银行账号" maxlength="30" />
        </el-form-item>
        <el-form-item label="设为默认">
          <el-checkbox v-model="form.is_default" :true-label="1" :false-label="0">设为默认抬头</el-checkbox>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.invoice-title-view {
  max-width: 680px;
  margin: 20px auto;
  padding: 20px;
}
.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.page-title {
  margin: 0;
}
.loading-text {
  padding: 40px;
  text-align: center;
  color: #909399;
}
.title-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.title-item {
  background: #fff;
  border-radius: 8px;
  padding: 16px 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.title-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}
.company-name {
  font-weight: 600;
  font-size: 15px;
}
.default-tag {
  font-size: 11px;
  padding: 1px 8px;
  background: #ecf5ff;
  color: #409eff;
  border-radius: 10px;
}
.type-tag {
  font-size: 11px;
  padding: 1px 8px;
  background: #f0f9eb;
  color: #67c23a;
  border-radius: 10px;
}
.title-meta {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #606266;
  margin-bottom: 10px;
}
.title-actions {
  display: flex;
  gap: 8px;
}
.empty-state {
  padding: 40px;
  text-align: center;
  color: #909399;
  background: #f5f7fa;
  border-radius: 8px;
}
</style>
