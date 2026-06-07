<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getSampleOrders, getSampleOrderDetail, type SampleOrder } from '@/api/sample'

const orders = ref<SampleOrder[]>([])
const loading = ref(false)
const statusFilter = ref<number | null>(null)
const detailDialogVisible = ref(false)
const currentOrder = ref<SampleOrder | null>(null)

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

async function loadOrders() {
  loading.value = true
  try {
    const params: any = { page: 1, per_page: 50 }
    if (statusFilter.value !== null && statusFilter.value !== undefined) {
      params.status = statusFilter.value
    }
    const res = await getSampleOrders(params)
    orders.value = res.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function onFilter() {
  loadOrders()
}

function statusName(status: number): string {
  return statusMap[status] || '未知'
}

function formatAmount(amount: number): string {
  return '¥' + (amount / 100).toFixed(2)
}

async function openDetail(row: SampleOrder) {
  try {
    const res = await getSampleOrderDetail(row.id)
    currentOrder.value = res
    detailDialogVisible.value = true
  } catch (e) {
    console.error(e)
  }
}

onMounted(() => {
  loadOrders()
})

defineExpose({
  orders,
  loading,
  statusFilter,
  statusName,
  formatAmount,
  openDetail,
  loadOrders,
  onFilter,
  detailDialogVisible,
  currentOrder,
})
</script>

<template>
  <div class="sample-list-view page-container">
    <div class="page-header">
      <h2 class="page-title">我的样品订单</h2>
    </div>

    <div class="filter-bar">
      <el-select v-model="statusFilter" placeholder="状态筛选" clearable style="width: 140px" @change="onFilter">
        <el-option :value="100" label="待付款" />
        <el-option :value="101" label="已付款" />
        <el-option :value="102" label="待发货" />
        <el-option :value="103" label="已发货" />
        <el-option :value="104" label="已完成" />
        <el-option :value="105" label="已取消" />
      </el-select>
    </div>

    <div v-loading="loading" class="sample-list">
      <div v-if="!loading && orders.length === 0" class="empty-state">
        暂无样品订单
      </div>

      <div v-for="item in orders" :key="item.id" class="sample-item">
        <div class="item-header">
          <span class="item-no">{{ item.order_no }}</span>
          <el-tag :type="statusTypeMap[item.status] as any" size="small">{{ statusName(item.status) }}</el-tag>
        </div>
        <div class="item-body">
          <div class="item-product">商品：{{ item.product?.name ?? '-' }}</div>
          <div class="item-quantity">数量：{{ item.quantity }}</div>
          <div class="item-amount">金额：{{ formatAmount(item.total_amount) }}</div>
        </div>
        <div class="item-footer">
          <span class="item-time">{{ item.created_at }}</span>
          <el-button link type="primary" @click="openDetail(item)">详情</el-button>
        </div>
      </div>
    </div>

    <!-- 详情对话框 -->
    <el-dialog v-model="detailDialogVisible" title="样品订单详情" width="480px">
      <div v-if="currentOrder" class="detail-content">
        <div class="detail-row"><span class="label">订单号：</span>{{ currentOrder.order_no }}</div>
        <div class="detail-row"><span class="label">商品：</span>{{ currentOrder.product?.name ?? '-' }}</div>
        <div class="detail-row"><span class="label">数量：</span>{{ currentOrder.quantity }}</div>
        <div class="detail-row"><span class="label">单价：</span>{{ formatAmount(currentOrder.unit_price) }}</div>
        <div class="detail-row"><span class="label">优惠：</span>{{ formatAmount(currentOrder.discount_amount) }}</div>
        <div class="detail-row"><span class="label">总价：</span>{{ formatAmount(currentOrder.total_amount) }}</div>
        <div class="detail-row"><span class="label">状态：</span>
          <el-tag :type="statusTypeMap[currentOrder.status] as any" size="small">{{ statusName(currentOrder.status) }}</el-tag>
        </div>
        <div class="detail-row"><span class="label">备注：</span>{{ currentOrder.remark ?? '-' }}</div>
        <div class="detail-row"><span class="label">创建时间：</span>{{ currentOrder.created_at }}</div>
      </div>
    </el-dialog>
  </div>
</template>

<style scoped>
.sample-list-view {
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
.sample-list {
  min-height: 200px;
}
.empty-state {
  text-align: center;
  padding: 40px;
  color: #909399;
}
.sample-item {
  padding: 16px;
  border-bottom: 1px solid #ebeef5;
  transition: background 0.2s;
}
.sample-item:hover {
  background: #f5f7fa;
}
.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}
.item-no {
  font-weight: 600;
  font-size: 15px;
}
.item-body {
  color: #606266;
  font-size: 14px;
  line-height: 1.6;
}
.item-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 8px;
  font-size: 13px;
  color: #909399;
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
