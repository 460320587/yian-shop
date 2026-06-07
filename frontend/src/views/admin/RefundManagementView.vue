<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import {
  getAdminRefunds,
  getAdminRefundDetail,
  auditAdminRefund,
} from '@/api/admin'
import { ElMessage, ElMessageBox } from 'element-plus'

const refunds = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const statusFilter = ref<number | null>(null)
const detailDialogVisible = ref(false)
const auditDialogVisible = ref(false)
const currentRefund = ref<any>(null)
const auditForm = reactive({ action: 'approve' as 'approve' | 'reject', remark: '' })

function statusText(status: number): string {
  const map: Record<number, string> = {
    0: '待处理',
    1: '审核通过',
    2: '审核拒绝',
    3: '处理中',
    4: '已完成',
  }
  return map[status] ?? '未知'
}

function statusType(status: number): string {
  const map: Record<number, string> = {
    0: 'warning',
    1: 'success',
    2: 'danger',
    3: 'primary',
    4: 'info',
  }
  return map[status] ?? 'info'
}

function refundPathText(path: string): string {
  const map: Record<string, string> = {
    original: '原路返回',
    wallet: '退回钱包',
    bank_card: '银行卡',
  }
  return map[path] ?? path
}

async function loadRefunds() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (statusFilter.value !== null && statusFilter.value !== undefined) {
      params.status = statusFilter.value
    }
    const res = await getAdminRefunds(params)
    refunds.value = res.data
    total.value = res.total
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function onFilter() {
  currentPage.value = 1
  loadRefunds()
}

async function openDetail(row: any) {
  try {
    const res = await getAdminRefundDetail(row.id)
    currentRefund.value = res
    detailDialogVisible.value = true
  } catch (e) {
    console.error(e)
  }
}

function openAudit(row: any) {
  currentRefund.value = row
  auditForm.action = 'approve'
  auditForm.remark = ''
  auditDialogVisible.value = true
}

async function handleAudit() {
  if (!currentRefund.value) return
  try {
    await auditAdminRefund(currentRefund.value.id, {
      action: auditForm.action,
      remark: auditForm.remark,
    })
    ElMessage.success(auditForm.action === 'approve' ? '审核通过' : '已拒绝')
    auditDialogVisible.value = false
    loadRefunds()
  } catch (e) {
    console.error(e)
  }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm('确认删除该退款记录？', '提示', { type: 'warning' })
    ElMessage.success('删除成功')
    loadRefunds()
  } catch {
    // user cancelled
  }
}

onMounted(loadRefunds)
</script>

<template>
  <div class="refund-management">
    <div class="page-header">
      <h3>退款管理</h3>
      <div class="filter-bar">
        <el-select v-model="statusFilter" placeholder="状态筛选" clearable style="width: 140px" @change="onFilter">
          <el-option :value="0" label="待处理" />
          <el-option :value="1" label="审核通过" />
          <el-option :value="2" label="审核拒绝" />
          <el-option :value="3" label="处理中" />
          <el-option :value="4" label="已完成" />
        </el-select>
        <el-button type="primary" @click="onFilter">查询</el-button>
      </div>
    </div>

    <el-table :data="refunds" v-loading="loading" stripe>
      <el-table-column prop="refund_no" label="退款单号" width="180" />
      <el-table-column label="订单号" width="160">
        <template #default="scope">
          <span v-if="scope?.row?.order">{{ scope.row.order.order_no }}</span>
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column label="客户" width="120">
        <template #default="scope">
          <span v-if="scope?.row?.customer">{{ scope.row.customer.nickname || scope.row.customer.phone }}</span>
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column prop="amount" label="金额" width="100">
        <template #default="scope">
          <span v-if="scope?.row">¥{{ scope.row.amount }}</span>
        </template>
      </el-table-column>
      <el-table-column prop="reason" label="原因" show-overflow-tooltip />
      <el-table-column label="退款路径" width="100">
        <template #default="scope">
          <span v-if="scope?.row">{{ refundPathText(scope.row.refund_path) }}</span>
        </template>
      </el-table-column>
      <el-table-column label="状态" width="90">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="statusType(scope.row.status)" size="small">
            {{ statusText(scope.row.status) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="申请时间" width="160" />
      <el-table-column label="操作" width="180">
        <template #default="scope">
          <el-button v-if="scope?.row" link type="primary" @click="openDetail(scope.row)">详情</el-button>
          <el-button v-if="scope?.row && scope.row.status === 0" link type="success" @click="openAudit(scope.row)">审核</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      :total="total"
      layout="total, prev, pager, next"
      class="pagination"
      @change="loadRefunds"
    />

    <!-- 详情对话框 -->
    <el-dialog v-model="detailDialogVisible" title="退款详情" width="500px">
      <div v-if="currentRefund" class="detail-content">
        <div class="detail-row"><span class="label">退款单号：</span>{{ currentRefund.refund_no }}</div>
        <div class="detail-row"><span class="label">订单号：</span>{{ currentRefund.order?.order_no ?? '-' }}</div>
        <div class="detail-row"><span class="label">客户：</span>{{ currentRefund.customer?.nickname ?? currentRefund.customer?.phone ?? '-' }}</div>
        <div class="detail-row"><span class="label">金额：</span>¥{{ currentRefund.amount }}</div>
        <div class="detail-row"><span class="label">原因：</span>{{ currentRefund.reason }}</div>
        <div class="detail-row"><span class="label">退款路径：</span>{{ refundPathText(currentRefund.refund_path) }}</div>
        <div class="detail-row"><span class="label">状态：</span>
          <el-tag :type="statusType(currentRefund.status)" size="small">{{ statusText(currentRefund.status) }}</el-tag>
        </div>
        <div class="detail-row"><span class="label">审核人：</span>{{ currentRefund.approver?.name ?? '-' }}</div>
        <div class="detail-row"><span class="label">审核时间：</span>{{ currentRefund.approved_at ?? '-' }}</div>
        <div class="detail-row"><span class="label">完成时间：</span>{{ currentRefund.completed_at ?? '-' }}</div>
        <div class="detail-row"><span class="label">申请时间：</span>{{ currentRefund.created_at }}</div>
      </div>
    </el-dialog>

    <!-- 审核对话框 -->
    <el-dialog v-model="auditDialogVisible" title="退款审核" width="480px">
      <el-form :model="auditForm" label-width="80px">
        <el-form-item label="操作">
          <el-radio-group v-model="auditForm.action">
            <el-radio value="approve">通过</el-radio>
            <el-radio value="reject">拒绝</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="auditForm.remark" type="textarea" :rows="3" placeholder="请输入审核备注" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="auditDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleAudit">确认</el-button>
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
  width: 80px;
  flex-shrink: 0;
}
</style>
