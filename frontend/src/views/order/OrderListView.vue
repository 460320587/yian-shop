<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getOrders, cancelOrder } from '@/api/order'
import { ORDER_STATUS_MAP } from '@/types'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { Order } from '@/api/order'

const router = useRouter()

const tabs = [
  { label: '全部订单', value: 0 },
  { label: '待付款', value: 11 },
  { label: '生产中', value: 13 },
  { label: '待收货', value: 54 },
  { label: '已完成', value: 60 },
]

const activeTab = ref(0)
const orders = ref<Order[]>([])
const loading = ref(false)
const currentPage = ref(1)
const total = ref(0)
const perPage = 10

onMounted(() => {
  loadOrders()
})

async function loadOrders() {
  loading.value = true
  try {
    const params: Record<string, unknown> = { page: currentPage.value, per_page: perPage }
    if (activeTab.value !== 0) params.status = activeTab.value
    const res = await getOrders(params)
    orders.value = res.data || []
    total.value = res.total || 0
  } catch (e) {
    console.error('订单加载失败', e)
  }
  loading.value = false
}

function handleTabChange(val: number) {
  activeTab.value = val
  currentPage.value = 1
  loadOrders()
}

function handlePageChange(page: number) {
  currentPage.value = page
  loadOrders()
}

async function handleCancel(order: Order) {
  try {
    await ElMessageBox.confirm('确定取消该订单吗？', '提示', { type: 'warning' })
    await cancelOrder(order.id)
    ElMessage.success('订单已取消')
    loadOrders()
  } catch {
    // 取消
  }
}

function getStatusInfo(status: number) {
  return ORDER_STATUS_MAP[status] || { label: '未知', type: 'info' }
}

function formatPrice(value: number) {
  return '¥' + (value ?? 0).toFixed(2)
}
</script>

<template>
  <div class="order-list-view page-container">
    <h2 class="page-title">我的订单</h2>

    <el-tabs v-model="activeTab" @tab-change="handleTabChange" class="order-tabs">
      <el-tab-pane v-for="tab in tabs" :key="tab.value" :label="tab.label" :name="tab.value" />
    </el-tabs>

    <div v-loading="loading">
      <div v-if="orders.length">
        <div v-for="order in orders" :key="order.id" class="order-card ya-card">
          <div class="order-header">
            <div class="order-meta">
              <span class="order-no">订单号：{{ order.order_no }}</span>
              <span class="order-time">{{ order.created_at }}</span>
            </div>
            <el-tag :type="getStatusInfo(order.status).type as any">
              {{ getStatusInfo(order.status).label }}
            </el-tag>
          </div>

          <div class="order-items">
            <div v-for="item in order.items" :key="item.id || item.product_id" class="order-item">
              <el-image
                :src="'https://placehold.co/80x80/e4e7ed/999?text=图'"
                fit="cover"
                class="item-image"
              />
              <div class="item-info">
                <p class="item-name">{{ item.product_name }}</p>
              </div>
              <div class="item-price">
                <p>{{ formatPrice(item.unit_price) }}</p>
                <p class="item-qty">x{{ item.quantity }}</p>
              </div>
            </div>
          </div>

          <div class="order-footer">
            <span class="order-total">
              共 {{ order.items.length }} 件，实付
              <strong>{{ formatPrice(order.total_amount) }}</strong>
            </span>
            <div class="order-actions">
              <el-button v-if="order.status === 11" type="primary" size="small">立即付款</el-button>
              <el-button v-if="[11, 13].includes(order.status)" size="small" @click="handleCancel(order)">
                取消订单
              </el-button>
            </div>
          </div>
        </div>

        <div v-if="total > perPage" class="pagination-bar">
          <el-pagination
            background
            layout="prev, pager, next"
            :total="total"
            :page-size="perPage"
            :current-page="currentPage"
            @current-change="handlePageChange"
          />
        </div>
      </div>
      <el-empty v-else description="暂无订单" />
    </div>
  </div>
</template>

<style scoped>
.page-title {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 20px;
}
.order-tabs {
  margin-bottom: 20px;
}
.order-card {
  margin-bottom: 16px;
  padding: 0;
  overflow: hidden;
}
.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  background: var(--bg-body);
}
.order-meta {
  display: flex;
  gap: 16px;
  font-size: 14px;
  color: var(--text-secondary);
}
.order-no {
  font-weight: 500;
  color: var(--text-primary);
}
.order-items {
  padding: 16px 24px;
}
.order-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 12px 0;
  border-bottom: 1px solid var(--border-light);
}
.order-item:last-child {
  border-bottom: none;
}
.item-image {
  width: 80px;
  height: 80px;
  border-radius: 6px;
  flex-shrink: 0;
}
.item-info {
  flex: 1;
}
.item-name {
  font-size: 14px;
  color: var(--text-primary);
}
.item-price {
  text-align: right;
  font-size: 14px;
  color: var(--text-primary);
}
.item-qty {
  color: var(--text-muted);
  margin-top: 4px;
}
.order-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  border-top: 1px solid var(--border-light);
}
.order-total {
  font-size: 14px;
  color: var(--text-secondary);
}
.order-total strong {
  font-size: 18px;
  color: var(--color-primary);
  margin-left: 4px;
}
.order-actions {
  display: flex;
  gap: 10px;
}
.pagination-bar {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}
@media (max-width: 768px) {
  .order-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  .order-footer {
    flex-direction: column;
    gap: 12px;
  }
}
</style>
