<script setup lang="ts">
/**
 * 订单列表页（骨架）
 */
import { ref } from 'vue'
import type { Order, OrderStatus } from '@/types'

const activeTab = ref('all')

const tabs = [
  { label: '全部订单', value: 'all' },
  { label: '待付款', value: OrderStatus.PENDING_PAYMENT },
  { label: '待发货', value: OrderStatus.PAID },
  { label: '待收货', value: OrderStatus.SHIPPED },
  { label: '已完成', value: OrderStatus.COMPLETED },
]

// 骨架订单数据
const orders = ref<Order[]>([
  {
    id: 1,
    order_no: 'YA202606010001',
    status: OrderStatus.PENDING_PAYMENT,
    total_amount: 3500,
    items: [
      { id: 1, product_id: 1, product_name: '高级铜版纸名片 300g', product_image: '', price: 3500, quantity: 1 },
    ],
    created_at: '2026-06-01 10:30:00',
  },
  {
    id: 2,
    order_no: 'YA202605280002',
    status: OrderStatus.COMPLETED,
    total_amount: 12800,
    items: [
      { id: 2, product_id: 2, product_name: '企业宣传册 A4 骑马钉', product_image: '', price: 12800, quantity: 1 },
    ],
    created_at: '2026-05-28 14:20:00',
  },
])

const statusMap: Record<string, string> = {
  [OrderStatus.PENDING_PAYMENT]: '待付款',
  [OrderStatus.PAID]: '待发货',
  [OrderStatus.SHIPPED]: '待收货',
  [OrderStatus.DELIVERED]: '已送达',
  [OrderStatus.COMPLETED]: '已完成',
  [OrderStatus.CANCELLED]: '已取消',
}

const statusTypeMap: Record<string, string> = {
  [OrderStatus.PENDING_PAYMENT]: 'warning',
  [OrderStatus.PAID]: 'primary',
  [OrderStatus.SHIPPED]: 'success',
  [OrderStatus.DELIVERED]: 'success',
  [OrderStatus.COMPLETED]: 'info',
  [OrderStatus.CANCELLED]: 'info',
}

function formatPrice(value: number) {
  return '¥' + (value / 100).toFixed(2)
}
</script>

<template>
  <div class="order-list-view page-container">
    <h2 class="page-title">
      <span class="title-bar" />
      我的订单
    </h2>

    <!-- 状态标签 -->
    <el-tabs v-model="activeTab" class="order-tabs">
      <el-tab-pane
        v-for="tab in tabs"
        :key="tab.value"
        :label="tab.label"
        :name="tab.value"
      />
    </el-tabs>

    <!-- 订单列表 -->
    <div class="order-list">
      <div v-for="order in orders" :key="order.id" class="order-card ya-card">
        <div class="order-header">
          <div class="order-meta">
            <span class="order-no">订单号：{{ order.order_no }}</span>
            <span class="order-time">{{ order.created_at }}</span>
          </div>
          <el-tag :type="statusTypeMap[order.status] as any">
            {{ statusMap[order.status] }}
          </el-tag>
        </div>

        <div class="order-items">
          <div
            v-for="item in order.items"
            :key="item.id"
            class="order-item"
          >
            <el-image
              :src="item.product_image || 'https://placehold.co/80x80/e4e7ed/999?text=图'"
              fit="cover"
              class="item-image"
            />
            <div class="item-info">
              <p class="item-name">{{ item.product_name }}</p>
              <p v-if="item.spec_info" class="item-spec">{{ item.spec_info }}</p>
            </div>
            <div class="item-price">
              <p>{{ formatPrice(item.price) }}</p>
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
            <el-button v-if="order.status === OrderStatus.PENDING_PAYMENT" type="primary">
              立即付款
            </el-button>
            <el-button>查看详情</el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.title-bar {
  display: inline-block;
  width: 4px;
  height: 22px;
  background: var(--color-primary);
  border-radius: 2px;
  margin-right: 10px;
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
  margin-bottom: 4px;
}

.item-spec {
  font-size: 12px;
  color: var(--text-muted);
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
