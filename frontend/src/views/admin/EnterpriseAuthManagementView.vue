<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import {
  getAdminEnterpriseAuths,
  getAdminEnterpriseAuthDetail,
  auditAdminEnterpriseAuth,
} from '@/api/admin'
import { ElMessage } from 'element-plus'

const auths = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const keyword = ref('')
const statusFilter = ref<number | null>(null)
const detailDialogVisible = ref(false)
const auditDialogVisible = ref(false)
const currentAuth = ref<any>(null)

const auditForm = reactive({
  auth_status: 2,
  audit_remark: '',
})

const statusOptions = [
  { label: '全部', value: null },
  { label: '待审核', value: 1 },
  { label: '已通过', value: 2 },
  { label: '不通过', value: 3 },
  { label: '已驳回', value: 4 },
]

function statusText(status: number): string {
  const map: Record<number, string> = { 0: '未认证', 1: '待审核', 2: '已通过', 3: '不通过', 4: '已驳回', 20: '代认证通过' }
  return map[status] ?? '未知'
}

function statusType(status: number): string {
  const map: Record<number, string> = { 0: 'info', 1: 'warning', 2: 'success', 3: 'danger', 4: 'info', 20: 'success' }
  return map[status] ?? 'info'
}

function canAudit(auth: any): boolean {
  return auth?.auth_status === 1
}

async function loadAuths() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (keyword.value) params.keyword = keyword.value
    if (statusFilter.value !== null && statusFilter.value !== undefined) {
      params.auth_status = statusFilter.value
    }
    const res = await getAdminEnterpriseAuths(params)
    auths.value = res.data
    total.value = res.total
  } catch (e) {
    console.error(e)
    ElMessage.error('加载企业认证列表失败')
  } finally {
    loading.value = false
  }
}

function onFilter() {
  currentPage.value = 1
  loadAuths()
}

async function openDetail(row: any) {
  try {
    const res = await getAdminEnterpriseAuthDetail(row.id)
    currentAuth.value = res
    detailDialogVisible.value = true
  } catch (e) {
    console.error(e)
    ElMessage.error('加载详情失败')
  }
}

function openAudit(row: any) {
  currentAuth.value = row
  auditForm.auth_status = 2
  auditForm.audit_remark = row.audit_remark || ''
  auditDialogVisible.value = true
}

async function handleAudit() {
  if (!currentAuth.value) return
  if (!auditForm.audit_remark.trim()) {
    ElMessage.warning('请输入审核备注')
    return
  }
  try {
    await auditAdminEnterpriseAuth(currentAuth.value.id, {
      auth_status: auditForm.auth_status,
      audit_remark: auditForm.audit_remark.trim(),
    })
    ElMessage.success('审核完成')
    auditDialogVisible.value = false
    loadAuths()
  } catch (e) {
    console.error(e)
  }
}

onMounted(loadAuths)

defineExpose({
  auths,
  total,
  currentPage,
  pageSize,
  loading,
  keyword,
  statusFilter,
  detailDialogVisible,
  auditDialogVisible,
  currentAuth,
  auditForm,
  loadAuths,
  onFilter,
  openDetail,
  openAudit,
  handleAudit,
  canAudit,
  statusText,
  statusType,
})
</script>

<template>
  <div class="enterprise-auth-management">
    <div class="page-header">
      <h3>企业认证审核</h3>
      <div class="filter-bar">
        <el-input v-model="keyword" placeholder="搜索企业名称/信用代码" clearable style="width: 240px" @keyup.enter="onFilter" />
        <el-select v-model="statusFilter" placeholder="认证状态" clearable style="width: 140px" @change="onFilter">
          <el-option v-for="opt in statusOptions" :key="String(opt.value)" :label="opt.label" :value="opt.value" />
        </el-select>
        <el-button type="primary" @click="onFilter">查询</el-button>
      </div>
    </div>

    <el-table :data="auths" v-loading="loading" stripe>
      <el-table-column prop="id" label="ID" width="70" />
      <el-table-column label="客户" width="140">
        <template #default="scope">
          <span v-if="scope?.row?.customer">{{ scope.row.customer.nickname || scope.row.customer.phone }}</span>
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column prop="company_name" label="企业名称" min-width="180" show-overflow-tooltip />
      <el-table-column prop="credit_code" label="统一信用代码" width="170" />
      <el-table-column label="状态" width="90">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="statusType(scope.row.auth_status)" size="small">
            {{ statusText(scope.row.auth_status) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="提交时间" width="160" />
      <el-table-column label="操作" width="180">
        <template #default="scope">
          <el-button v-if="scope?.row" link type="primary" @click="openDetail(scope.row)">详情</el-button>
          <el-button v-if="scope?.row && canAudit(scope.row)" link type="success" @click="openAudit(scope.row)">审核</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      :total="total"
      layout="total, prev, pager, next"
      class="pagination"
      @change="loadAuths"
    />

    <!-- 详情对话框 -->
    <el-dialog v-model="detailDialogVisible" title="认证详情" width="600px">
      <div v-if="currentAuth" class="detail-content">
        <div class="detail-row"><span class="label">企业名称：</span>{{ currentAuth.company_name }}</div>
        <div class="detail-row"><span class="label">统一信用代码：</span>{{ currentAuth.credit_code }}</div>
        <div class="detail-row"><span class="label">法人：</span>{{ currentAuth.legal_person }}</div>
        <div class="detail-row"><span class="label">法人身份证：</span>{{ currentAuth.legal_person_id_card }}</div>
        <div class="detail-row"><span class="label">联系人：</span>{{ currentAuth.contact_name }} {{ currentAuth.contact_phone }}</div>
        <div class="detail-row"><span class="label">注册地址：</span>{{ currentAuth.register_address ?? '-' }}</div>
        <div class="detail-row"><span class="label">办公地址：</span>{{ currentAuth.office_address ?? '-' }}</div>
        <div class="detail-row"><span class="label">有效期：</span>{{ currentAuth.valid_date ?? '-' }}</div>
        <div class="detail-row"><span class="label">状态：</span>
          <el-tag :type="statusType(currentAuth.auth_status)" size="small">{{ statusText(currentAuth.auth_status) }}</el-tag>
        </div>
        <div class="detail-row"><span class="label">审核备注：</span>{{ currentAuth.audit_remark ?? '-' }}</div>
        <div v-if="currentAuth.business_license_img" class="detail-row">
          <span class="label">营业执照：</span>
          <img :src="currentAuth.business_license_img" class="license-img" />
        </div>
      </div>
    </el-dialog>

    <!-- 审核对话框 -->
    <el-dialog v-model="auditDialogVisible" title="审核企业认证" width="480px">
      <div v-if="currentAuth" class="audit-info">
        <p><strong>企业：</strong>{{ currentAuth.company_name }}</p>
        <p><strong>信用代码：</strong>{{ currentAuth.credit_code }}</p>
      </div>
      <el-form :model="auditForm" label-width="90px">
        <el-form-item label="审核结果" required>
          <el-select v-model="auditForm.auth_status" placeholder="请选择" style="width: 100%">
            <el-option :value="2" label="通过" />
            <el-option :value="3" label="不通过" />
            <el-option :value="4" label="驳回" />
          </el-select>
        </el-form-item>
        <el-form-item label="审核备注" required>
          <el-input v-model="auditForm.audit_remark" type="textarea" :rows="3" placeholder="请输入审核备注" maxlength="500" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="auditDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleAudit">确认审核</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.page-header h3 {
  font-size: 18px;
  font-weight: 600;
  margin: 0;
}
.filter-bar {
  display: flex;
  gap: 12px;
}
.pagination {
  margin-top: 20px;
  justify-content: flex-end;
}
.detail-content {
  padding: 8px 0;
}
.detail-row {
  display: flex;
  margin-bottom: 12px;
  font-size: 14px;
}
.detail-row .label {
  color: #606266;
  width: 100px;
  flex-shrink: 0;
}
.license-img {
  max-width: 300px;
  max-height: 200px;
  border-radius: 4px;
  object-fit: contain;
}
.audit-info {
  margin-bottom: 16px;
  padding: 12px;
  background: #f5f7fa;
  border-radius: 4px;
}
.audit-info p {
  margin: 4px 0;
  font-size: 14px;
}
</style>
