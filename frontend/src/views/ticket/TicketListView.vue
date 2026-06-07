<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getTickets, cancelTicket, type Ticket } from '@/api/ticket'

const router = useRouter()
const tickets = ref<Ticket[]>([])
const loading = ref(false)
const statusFilter = ref<number | null>(null)

const typeMap: Record<number, string> = {
  1: '咨询',
  2: '投诉',
  3: '建议',
  4: '售后',
  5: '其他',
}

const statusMap: Record<number, string> = {
  0: '已取消',
  1: '待处理',
  2: '处理中',
  3: '待确认',
  4: '已完成',
  5: '已关闭',
}

const statusTypeMap: Record<number, string> = {
  0: 'info',
  1: 'danger',
  2: 'warning',
  3: 'primary',
  4: 'success',
  5: 'info',
}

const priorityMap: Record<number, string> = {
  1: '低',
  2: '中',
  3: '高',
  4: '紧急',
}

const priorityTypeMap: Record<number, string> = {
  1: 'info',
  2: 'success',
  3: 'warning',
  4: 'danger',
}

async function loadTickets() {
  loading.value = true
  try {
    const params: any = { page: 1, per_page: 50 }
    if (statusFilter.value !== null && statusFilter.value !== undefined) {
      params.status = statusFilter.value
    }
    const res = await getTickets(params)
    tickets.value = res.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function onFilter() {
  loadTickets()
}

function typeName(type: number): string {
  return typeMap[type] || '未知'
}

function statusName(status: number): string {
  return statusMap[status] || '未知'
}

function priorityName(priority: number): string {
  return priorityMap[priority] || '未知'
}

function canCancel(item: Ticket): boolean {
  return item.status === 1
}

async function handleCancel(item: Ticket) {
  if (!canCancel(item)) {
    ElMessage.warning('当前状态不可撤诉')
    return
  }
  try {
    await ElMessageBox.confirm('确定撤诉该工单吗？', '提示', { type: 'warning' })
    await cancelTicket(item.id)
    ElMessage.success('已撤诉')
    await loadTickets()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

function goToCreate() {
  router.push('/ticket-create')
}

onMounted(() => {
  loadTickets()
})

defineExpose({
  tickets,
  loading,
  statusFilter,
  typeName,
  statusName,
  priorityName,
  canCancel,
  handleCancel,
  goToCreate,
  loadTickets,
  onFilter,
})
</script>

<template>
  <div class="ticket-list-view page-container">
    <div class="page-header">
      <h2 class="page-title">我的工单</h2>
      <el-button type="primary" @click="goToCreate">+ 提交工单</el-button>
    </div>

    <div class="filter-bar">
      <el-select v-model="statusFilter" placeholder="状态筛选" clearable style="width: 140px" @change="onFilter">
        <el-option :value="1" label="待处理" />
        <el-option :value="2" label="处理中" />
        <el-option :value="3" label="待确认" />
        <el-option :value="4" label="已完成" />
        <el-option :value="5" label="已关闭" />
        <el-option :value="0" label="已取消" />
      </el-select>
    </div>

    <div v-loading="loading" class="ticket-list">
      <div v-if="!loading && tickets.length === 0" class="empty-state">
        暂无工单记录
      </div>

      <div v-for="item in tickets" :key="item.id" class="ticket-item">
        <div class="item-header">
          <span class="item-no">{{ item.ticket_no }}</span>
          <span v-if="item.order" class="item-order">关联订单：{{ item.order.order_no }}</span>
          <el-tag :type="statusTypeMap[item.status] as any" size="small">{{ statusName(item.status) }}</el-tag>
        </div>
        <div class="item-body">
          <div class="item-type">类型：{{ typeName(item.type) }}</div>
          <div class="item-title">标题：{{ item.title }}</div>
          <div class="item-content">内容：{{ item.content }}</div>
          <div v-if="item.remark" class="item-remark">客服备注：{{ item.remark }}</div>
        </div>
        <div class="item-footer">
          <span class="item-priority">
            优先级：<el-tag :type="priorityTypeMap[item.priority] as any" size="small">{{ priorityName(item.priority) }}</el-tag>
          </span>
          <span class="item-time">{{ item.created_at }}</span>
        </div>
        <div class="item-actions">
          <el-button v-if="canCancel(item)" link type="danger" @click="handleCancel(item)">撤诉</el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ticket-list-view {
  padding: 20px;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.filter-bar {
  margin-bottom: 16px;
}
.ticket-list {
  min-height: 200px;
}
.empty-state {
  text-align: center;
  padding: 40px;
  color: #909399;
}
.ticket-item {
  padding: 16px;
  border-bottom: 1px solid #ebeef5;
  transition: background 0.2s;
}
.ticket-item:hover {
  background: #f5f7fa;
}
.item-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}
.item-no {
  font-weight: 600;
  font-size: 15px;
}
.item-order {
  color: #909399;
  font-size: 13px;
}
.item-body {
  color: #606266;
  font-size: 14px;
  line-height: 1.6;
}
.item-type {
  margin-bottom: 4px;
}
.item-title {
  margin-bottom: 4px;
}
.item-content {
  color: #909399;
  margin-bottom: 4px;
}
.item-remark {
  color: #e6a23c;
  margin-bottom: 4px;
}
.item-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 8px;
  font-size: 13px;
  color: #909399;
}
.item-actions {
  margin-top: 8px;
}
</style>
