<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import {
  getAdminTickets,
  getAdminTicketDetail,
  processTicket,
} from '@/api/admin'
import { ElMessage } from 'element-plus'

const tickets = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const statusFilter = ref<number | null>(null)
const typeFilter = ref<number | null>(null)
const detailDialogVisible = ref(false)
const processDialogVisible = ref(false)
const currentTicket = ref<any>(null)
const processForm = reactive({ status: 2, remark: '', priority: undefined as number | undefined })

function statusText(status: number): string {
  const map: Record<number, string> = {
    1: '待处理',
    2: '处理中',
    3: '待确认',
    4: '已完成',
    5: '已关闭',
  }
  return map[status] ?? '未知'
}

function statusType(status: number): string {
  const map: Record<number, string> = {
    1: 'danger',
    2: 'warning',
    3: 'primary',
    4: 'success',
    5: 'info',
  }
  return map[status] ?? 'info'
}

function typeText(type: number): string {
  const map: Record<number, string> = {
    1: '咨询',
    2: '投诉',
    3: '建议',
    4: '售后',
  }
  return map[type] ?? '未知'
}

function priorityText(priority: number): string {
  const map: Record<number, string> = {
    1: '低',
    2: '中',
    3: '高',
    4: '紧急',
  }
  return map[priority] ?? '未知'
}

function priorityType(priority: number): string {
  const map: Record<number, string> = {
    1: 'info',
    2: 'success',
    3: 'warning',
    4: 'danger',
  }
  return map[priority] ?? 'info'
}

async function loadTickets() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (statusFilter.value !== null && statusFilter.value !== undefined) {
      params.status = statusFilter.value
    }
    if (typeFilter.value !== null && typeFilter.value !== undefined) {
      params.type = typeFilter.value
    }
    const res = await getAdminTickets(params)
    tickets.value = res.data
    total.value = res.total
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function onFilter() {
  currentPage.value = 1
  loadTickets()
}

async function openDetail(row: any) {
  try {
    const res = await getAdminTicketDetail(row.id)
    currentTicket.value = res
    detailDialogVisible.value = true
  } catch (e) {
    console.error(e)
  }
}

function openProcess(row: any) {
  currentTicket.value = row
  processForm.status = 2
  processForm.remark = ''
  processForm.priority = undefined
  processDialogVisible.value = true
}

async function handleProcess() {
  if (!currentTicket.value) return
  try {
    const payload: any = {
      status: processForm.status,
      remark: processForm.remark || undefined,
    }
    if (processForm.priority !== undefined) {
      payload.priority = processForm.priority
    }
    await processTicket(currentTicket.value.id, payload)
    ElMessage.success('工单已更新')
    processDialogVisible.value = false
    loadTickets()
  } catch (e) {
    console.error(e)
  }
}

onMounted(loadTickets)
</script>

<template>
  <div class="ticket-management">
    <div class="page-header">
      <h3>工单管理</h3>
      <div class="filter-bar">
        <el-select v-model="statusFilter" placeholder="状态筛选" clearable style="width: 140px" @change="onFilter">
          <el-option :value="1" label="待处理" />
          <el-option :value="2" label="处理中" />
          <el-option :value="3" label="待确认" />
          <el-option :value="4" label="已完成" />
          <el-option :value="5" label="已关闭" />
        </el-select>
        <el-select v-model="typeFilter" placeholder="类型筛选" clearable style="width: 140px" @change="onFilter">
          <el-option :value="1" label="咨询" />
          <el-option :value="2" label="投诉" />
          <el-option :value="3" label="建议" />
          <el-option :value="4" label="售后" />
        </el-select>
        <el-button type="primary" @click="onFilter">查询</el-button>
      </div>
    </div>

    <el-table :data="tickets" v-loading="loading" stripe>
      <el-table-column prop="ticket_no" label="工单号" width="170" />
      <el-table-column label="客户" width="130">
        <template #default="scope">
          <span v-if="scope?.row?.customer">{{ scope.row.customer.nickname || scope.row.customer.phone }}</span>
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column label="类型" width="80">
        <template #default="scope">
          <span v-if="scope?.row">{{ typeText(scope.row.type) }}</span>
        </template>
      </el-table-column>
      <el-table-column label="优先级" width="80">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="priorityType(scope.row.priority)" size="small">
            {{ priorityText(scope.row.priority) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="title" label="标题" show-overflow-tooltip />
      <el-table-column label="状态" width="90">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="statusType(scope.row.status)" size="small">
            {{ statusText(scope.row.status) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="160" />
      <el-table-column label="操作" width="180">
        <template #default="scope">
          <el-button v-if="scope?.row" link type="primary" @click="openDetail(scope.row)">详情</el-button>
          <el-button v-if="scope?.row && scope.row.status === 1" link type="success" @click="openProcess(scope.row)">处理</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      :total="total"
      layout="total, prev, pager, next"
      class="pagination"
      @change="loadTickets"
    />

    <!-- 详情对话框 -->
    <el-dialog v-model="detailDialogVisible" title="工单详情" width="560px">
      <div v-if="currentTicket" class="detail-content">
        <div class="detail-row"><span class="label">工单号：</span>{{ currentTicket.ticket_no }}</div>
        <div class="detail-row"><span class="label">客户：</span>{{ currentTicket.customer?.nickname ?? currentTicket.customer?.phone ?? '-' }}</div>
        <div class="detail-row"><span class="label">订单号：</span>{{ currentTicket.order?.order_no ?? '-' }}</div>
        <div class="detail-row"><span class="label">类型：</span>{{ typeText(currentTicket.type) }}</div>
        <div class="detail-row"><span class="label">优先级：</span>
          <el-tag :type="priorityType(currentTicket.priority)" size="small">{{ priorityText(currentTicket.priority) }}</el-tag>
        </div>
        <div class="detail-row"><span class="label">状态：</span>
          <el-tag :type="statusType(currentTicket.status)" size="small">{{ statusText(currentTicket.status) }}</el-tag>
        </div>
        <div class="detail-row"><span class="label">标题：</span>{{ currentTicket.title }}</div>
        <div class="detail-row"><span class="label">内容：</span>{{ currentTicket.content }}</div>
        <div class="detail-row"><span class="label">备注：</span>{{ currentTicket.remark ?? '-' }}</div>
        <div class="detail-row"><span class="label">处理时间：</span>{{ currentTicket.processed_at ?? '-' }}</div>
        <div class="detail-row"><span class="label">完成时间：</span>{{ currentTicket.completed_at ?? '-' }}</div>
        <div class="detail-row"><span class="label">创建时间：</span>{{ currentTicket.created_at }}</div>
      </div>
    </el-dialog>

    <!-- 处理对话框 -->
    <el-dialog v-model="processDialogVisible" title="处理工单" width="480px">
      <el-form :model="processForm" label-width="80px">
        <el-form-item label="状态">
          <el-select v-model="processForm.status" placeholder="请选择状态" style="width: 100%">
            <el-option :value="2" label="处理中" />
            <el-option :value="3" label="待确认" />
            <el-option :value="4" label="已完成" />
            <el-option :value="5" label="已关闭" />
          </el-select>
        </el-form-item>
        <el-form-item label="优先级">
          <el-select v-model="processForm.priority" placeholder="请选择优先级" clearable style="width: 100%">
            <el-option :value="1" label="低" />
            <el-option :value="2" label="中" />
            <el-option :value="3" label="高" />
            <el-option :value="4" label="紧急" />
          </el-select>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="processForm.remark" type="textarea" :rows="3" placeholder="请输入处理备注" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="processDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleProcess">确认</el-button>
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
