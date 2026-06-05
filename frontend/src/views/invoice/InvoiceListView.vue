<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getInvoices, cancelInvoice } from '@/api/invoice'
import type { Invoice } from '@/api/invoice'

const invoices = ref<Invoice[]>([])
const loading = ref(false)
const activeStatus = ref<number | null>(null)
const total = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)

const statusTabs = [
  { value: null, label: '全部' },
  { value: 1, label: '已申请' },
  { value: 2, label: '已审核' },
  { value: 3, label: '已驳回' },
  { value: 4, label: '已开票' },
  { value: 0, label: '已取消' },
]

const statusMap: Record<number, string> = {
  0: '已取消',
  1: '已申请',
  2: '已审核',
  3: '已驳回',
  4: '已开票',
  5: '已打印',
  6: '已作废',
}

const typeMap: Record<number, string> = {
  1: '普通发票',
  2: '专用发票',
}

function getStatusLabel(status: number): string {
  return statusMap[status] ?? '未知'
}

function getTypeLabel(type: number): string {
  return typeMap[type] ?? '未知'
}

function formatAmount(amount: number | { amount: number; currency: string }): string {
  if (typeof amount === 'number') {
    return amount.toFixed(2)
  }
  if (amount && typeof amount.amount === 'number') {
    return (amount.amount / 100).toFixed(2)
  }
  return '0.00'
}

async function loadInvoices(page = 1) {
  loading.value = true
  try {
    const params: any = { page }
    if (activeStatus.value !== null) {
      params.status = activeStatus.value
    }
    const res = await getInvoices(params)
    invoices.value = res.data
    total.value = res.meta.total
    currentPage.value = res.meta.current_page
    lastPage.value = res.meta.last_page
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function handleStatusChange(status: number | null) {
  activeStatus.value = status
  loadInvoices(1)
}

function canCancel(invoice: Invoice): boolean {
  return invoice.status === 1
}

async function cancelInvoiceItem(invoice: Invoice) {
  if (!confirm('确定要取消该发票申请吗？')) return
  loading.value = true
  try {
    await cancelInvoice(invoice.id)
    ElMessage.success('发票申请已取消')
    await loadInvoices(currentPage.value)
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadInvoices()
})

defineExpose({
  invoices,
  loading,
  activeStatus,
  loadInvoices,
  cancelInvoice: cancelInvoiceItem,
  handleStatusChange,
})
</script>

<template>
  <div class="invoice-list-view page-container">
    <div class="header-row">
      <h2 class="page-title">发票中心</h2>
      <el-button type="primary" size="small" @click="$router.push('/invoice-apply')">申请发票</el-button>
    </div>

    <div class="status-tabs">
      <button
        v-for="tab in statusTabs"
        :key="tab.value ?? 'all'"
        class="tab-btn"
        :class="{ active: activeStatus === tab.value }"
        @click="handleStatusChange(tab.value)"
      >
        {{ tab.label }}
      </button>
    </div>

    <div v-if="loading && invoices.length === 0" class="loading-text">加载中...</div>

    <div v-else v-loading="loading" class="invoice-list">
      <div v-for="invoice in invoices" :key="invoice.id" class="invoice-item">
        <div class="invoice-header">
          <span class="invoice-no">{{ invoice.invoice_no }}</span>
          <span class="invoice-status" :class="'status-' + invoice.status">{{ getStatusLabel(invoice.status) }}</span>
        </div>
        <div class="invoice-body">
          <div class="info-row">
            <span class="label">抬头：</span>
            <span>{{ invoice.title }}</span>
          </div>
          <div class="info-row">
            <span class="label">类型：</span>
            <span>{{ getTypeLabel(invoice.type) }}</span>
          </div>
          <div class="info-row">
            <span class="label">金额：</span>
            <span class="amount">¥{{ formatAmount(invoice.amount) }}</span>
          </div>
          <div class="info-row">
            <span class="label">时间：</span>
            <span>{{ invoice.created_at }}</span>
          </div>
        </div>
        <div v-if="canCancel(invoice)" class="invoice-actions">
          <el-button data-testid="cancel-btn" link type="danger" size="small" @click="cancelInvoiceItem(invoice)">取消申请</el-button>
        </div>
      </div>

      <div v-if="invoices.length === 0" class="empty-state">
        暂无发票申请
      </div>
    </div>

    <div v-if="lastPage > 1" class="pagination">
      <el-pagination
        :current-page="currentPage"
        :page-count="lastPage"
        layout="prev, pager, next"
        @current-change="loadInvoices"
      />
    </div>
  </div>
</template>

<style scoped>
.invoice-list-view {
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
.status-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}
.tab-btn {
  padding: 6px 14px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  background: #fff;
  cursor: pointer;
  font-size: 13px;
  color: #606266;
}
.tab-btn.active {
  background: #409eff;
  color: #fff;
  border-color: #409eff;
}
.loading-text {
  padding: 40px;
  text-align: center;
  color: #909399;
}
.invoice-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.invoice-item {
  background: #fff;
  border-radius: 8px;
  padding: 16px 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.invoice-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}
.invoice-no {
  font-weight: 600;
  font-size: 14px;
  font-family: monospace;
}
.invoice-status {
  font-size: 12px;
  padding: 2px 10px;
  border-radius: 10px;
}
.status-0 { background: #f4f4f5; color: #909399; }
.status-1 { background: #ecf5ff; color: #409eff; }
.status-2 { background: #f0f9eb; color: #67c23a; }
.status-3 { background: #fdf6ec; color: #e6a23c; }
.status-4 { background: #f0f9eb; color: #67c23a; }
.status-5 { background: #ecf5ff; color: #409eff; }
.status-6 { background: #fef0f0; color: #f56c6c; }
.invoice-body {
  margin-bottom: 10px;
}
.info-row {
  display: flex;
  margin-bottom: 6px;
  font-size: 13px;
}
.label {
  color: #606266;
  width: 56px;
  flex-shrink: 0;
}
.amount {
  color: #f56c6c;
  font-weight: 600;
}
.empty-state {
  padding: 40px;
  text-align: center;
  color: #909399;
  background: #f5f7fa;
  border-radius: 8px;
}
.pagination {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}
</style>
