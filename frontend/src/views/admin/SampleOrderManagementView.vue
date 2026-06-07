<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import {
  getAdminSampleOrders,
  getAdminSampleOrderDetail,
  updateAdminSampleOrderStatus,
  deleteAdminSampleOrder,
} from '@/api/admin'
import { ElMessage, ElMessageBox } from 'element-plus'

const orders = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const statusFilter = ref<number | null>(null)
const keywordFilter = ref('')
const detailDialogVisible = ref(false)
const statusDialogVisible = ref(false)
const currentOrder = ref<any>(null)
const statusForm = reactive({ status: 100, remark: '' })

const statusMap: Record<number, string> = {
  100: '待付款',
  101: '已付款',
  102: '待发货',
  103: '已发货',
  104: '已完成',
  105: '已取消',
}

const statusTypeMap: Record<number, string> = {
  100: 'warning',
  101: 'success',
  102: 'primary',
  103: 'primary',
  104: 'success',
  105: 'info',
}

const validTransitions: Record<number, number[]> = {
  100: [105],
  101: [102, 103, 105],
  102: [103, 105],
  103: [104],
}

function statusText(status: number): string {
  return statusMap[status] || '未知'
}

function formatAmount(amount: number): string {
  return '¥' + (amount / 100).toFixed(2)
}

function validNextStatuses(current: number): number[] {
  return validTransitions[current] || []
}

async function loadOrders() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (statusFilter.value !== null && statusFilter.value !== undefined) {
      params.status = statusFilter.value
    }
    if (keywordFilter.value.trim()) {
      params.keyword = keywordFilter.value.trim()
    }
    const res = await getAdminSampleOrders(params)
    orders.value = res.data
    total.value = res.total
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function onFilter() {
  currentPage.value = 1
  loadOrders()
}

async function openDetail(row: any) {
  try {
    const res = await getAdminSampleOrderDetail(row.id)
    currentOrder.value = res
    detailDialogVisible.value = true
  } catch (e) {
    console.error(e)
  }
}

function openStatusDialog(row: any) {
  currentOrder.value = row
  statusForm.status = validNextStatuses(row.status)[0] ?? row.status
  statusForm.remark = ''
  statusDialogVisible.value = true
}

async function handleUpdateStatus() {
  if (!currentOrder.value) return
  try {
    await updateAdminSampleOrderStatus(currentOrder.value.id, {
      status: statusForm.status,
      remark: statusForm.remark || undefined,
    })
    ElMessage.success('状态更新成功')
    statusDialogVisible.value = false
    loadOrders()
  } catch (e) {
    console.error(e)
  }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm('确认删除该样品订单？', '提示', { type: 'warning' })
    await deleteAdminSampleOrder(row.id)
    ElMessage.success('已删除')
    loadOrders()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

onMounted(loadOrders)
</script>

<template>
  <div class="sample-order-management">
    <div class="page-header">
      <h3>样品订单管理</h3>
      <div class="filter-bar">
        <el-input v-model="keywordFilter" placeholder="客户手机/昵称/订单号" clearable style="width: 200px" @keyup.enter="onFilter" />
        <el-select v-model="statusFilter" placeholder="状态筛选" clearable style="width: 140px" @change="onFilter">
          <el-option :value="100" label="待付款" />
          <el-option :value="101" label="已付款" />
          <el-option :value="102" label="待发货" />
          <el-option :value="103" label="已发货" />
          <el-option :value="104" label="已完成" />
          <el-option :value="105" label="已取消" />
        </el-select>
        <el-button type="primary" @click="onFilter">查询</el-button>
      </div>
    </div>

    <el-table :data="orders" v-loading="loading" stripe>
      <el-table-column prop="order_no" label="订单号" width="170" />
      <el-table-column label="客户" width="130">
        <template #default="scope">
          <span v-if="scope?.row?.customer">{{ scope.row.customer.nickname || scope.row.customer.phone }}</span>
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column label="商品" width="140">
        <template #default="scope">
          <span v-if="scope?.row?.product">{{ scope.row.product.name }}</span>
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column prop="quantity" label="数量" width="80" />
      <el-table-column label="金额" width="100">
        <template #default="scope">
          <span v-if="scope?.row">{{ formatAmount(scope.row.total_amount) }}</span>
        </template>
      </el-table-column>
      <el-table-column label="状态" width="90">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="statusTypeMap[scope.row.status] as any" size="small">
            {{ statusText(scope.row.status) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="160" />
      <el-table-column label="操作" width="200">
        <template #default="scope">
          <template v-if="scope?.row">
            <el-button link type="primary" @click="openDetail(scope.row)">详情</el-button>
            <el-button v-if="validNextStatuses(scope.row.status).length > 0" link type="success" @click="openStatusDialog(scope.row)">改状态</el-button>
            <el-button link type="danger" @click="handleDelete(scope.row)">删除</el-button>
          </template>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      :total="total"
      layout="total, prev, pager, next"
      class="pagination"
      @change="loadOrders"
    />

    <!-- 详情对话框 -->
    <el-dialog v-model="detailDialogVisible" title="样品订单详情" width="520px">
      <div v-if="currentOrder" class="detail-content">
        <div class="detail-row"><span class="label">订单号：</span>{{ currentOrder.order_no }}</div>
        <div class="detail-row"><span class="label">客户：</span>{{ currentOrder.customer?.nickname ?? currentOrder.customer?.phone ?? '-' }}</div>
        <div class="detail-row"><span class="label">商品：</span>{{ currentOrder.product?.name ?? '-' }}</div>
        <div class="detail-row"><span class="label">数量：</span>{{ currentOrder.quantity }}</div>
        <div class="detail-row"><span class="label">单价：</span>{{ formatAmount(currentOrder.unit_price) }}</div>
        <div class="detail-row"><span class="label">优惠：</span>{{ formatAmount(currentOrder.discount_amount) }}</div>
        <div class="detail-row"><span class="label">总价：</span>{{ formatAmount(currentOrder.total_amount) }}</div>
        <div class="detail-row"><span class="label">状态：</span>
          <el-tag :type="statusTypeMap[currentOrder.status] as any" size="small">{{ statusText(currentOrder.status) }}</el-tag>
        </div>
        <div class="detail-row"><span class="label">备注：</span>{{ currentOrder.remark ?? '-' }}</div>
        <div class="detail-row"><span class="label">创建时间：</span>{{ currentOrder.created_at }}</div>
      </div>
    </el-dialog>

    <!-- 状态更新对话框 -->
    <el-dialog v-model="statusDialogVisible" title="更新状态" width="400px">
      <el-form :model="statusForm" label-width="80px">
        <el-form-item label="新状态">
          <el-select v-model="statusForm.status" placeholder="请选择状态" style="width: 100%">
            <el-option v-for="s in validNextStatuses(currentOrder?.status ?? 0)" :key="s" :value="s" :label="statusText(s)" />
          </el-select>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="statusForm.remark" type="textarea" :rows="2" placeholder="选填" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="statusDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleUpdateStatus">确认</el-button>
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
