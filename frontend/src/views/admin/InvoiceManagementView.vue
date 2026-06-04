<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getAdminInvoices, auditInvoice, issueInvoice } from '@/api/admin'
import { ElMessage, ElMessageBox } from 'element-plus'

const invoices = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const statusFilter = ref('')

const statusOptions = [
  { label: '全部', value: '' },
  { label: '待审核', value: '11' },
  { label: '已通过', value: '12' },
  { label: '已驳回', value: '14' },
  { label: '已开具', value: '15' },
]

const typeMap: Record<number, string> = { 1: '普票', 2: '专票' }

async function loadInvoices() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (statusFilter.value) params.status = statusFilter.value
    const res = await getAdminInvoices(params)
    invoices.value = res.data
    total.value = res.total
  } catch (e) {
    console.error(e)
    ElMessage.error('加载发票失败')
  } finally {
    loading.value = false
  }
}

function onSearch() {
  currentPage.value = 1
  loadInvoices()
}

async function handleAudit(row: any, status: number) {
  try {
    await ElMessageBox.confirm(`确认${status === 12 ? '通过' : '驳回'}？`, '提示', { type: 'warning' })
    await auditInvoice(row.id, { status })
    ElMessage.success('审核成功')
    loadInvoices()
  } catch { }
}

async function handleIssue(row: any) {
  try {
    await ElMessageBox.confirm('确认开具？', '提示', { type: 'warning' })
    await issueInvoice(row.id)
    ElMessage.success('开具成功')
    loadInvoices()
  } catch { }
}

function formatType(type: number): string {
  return typeMap[type] || '其他'
}

function formatAmount(amount: number): string {
  return '¥' + (amount / 100).toFixed(2)
}

function formatStatus(status: number): string {
  const map: Record<number, string> = { 11: '待审核', 12: '已通过', 14: '已驳回', 15: '已开具' }
  return map[status] || '未知'
}

function statusTagType(status: number): string {
  const map: Record<number, string> = { 11: 'warning', 12: 'success', 14: 'danger', 15: 'info' }
  return map[status] || 'info'
}

onMounted(loadInvoices)
</script>

<template>
  <div class="invoice-management">
    <div class="page-header">
      <h3>发票管理</h3>
      <div class="filter-bar">
        <el-select v-model="statusFilter" placeholder="发票状态" clearable style="width: 140px" @change="onSearch">
          <el-option v-for="opt in statusOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
        </el-select>
      </div>
    </div>
    <el-table :data="invoices" v-loading="loading" stripe>
      <el-table-column prop="order_no" label="订单号" min-width="160" />
      <el-table-column label="类型" width="80">
        <template #default="scope"><span v-if="scope?.row">{{ formatType(scope.row.type) }}</span></template>
      </el-table-column>
      <el-table-column prop="title" label="抬头" min-width="160" />
      <el-table-column prop="tax_no" label="税号" min-width="180" />
      <el-table-column label="金额" width="100">
        <template #default="scope"><span v-if="scope?.row">{{ formatAmount(scope.row.amount) }}</span></template>
      </el-table-column>
      <el-table-column label="状态" width="100">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="statusTagType(scope.row.status)">{{ formatStatus(scope.row.status) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" min-width="160" />
      <el-table-column label="操作" width="140">
        <template #default="scope">
          <template v-if="scope?.row && scope.row.status === 11">
            <el-button link type="success" @click="handleAudit(scope.row, 12)">通过</el-button>
            <el-button link type="danger" @click="handleAudit(scope.row, 14)">驳回</el-button>
          </template>
          <el-button v-else-if="scope?.row && scope.row.status === 12" link type="primary" @click="handleIssue(scope.row)">开具</el-button>
          <span v-else>-</span>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize" :total="total" layout="total, prev, pager, next" class="pagination" @change="loadInvoices" />
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h3 { font-size: 18px; font-weight: 600; margin: 0; }
.filter-bar { display: flex; gap: 12px; }
.pagination { margin-top: 20px; justify-content: flex-end; }
</style>
