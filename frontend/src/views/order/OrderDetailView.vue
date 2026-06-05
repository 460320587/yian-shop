<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getOrderDetail, cancelOrder } from '@/api/order'

const route = useRoute()
const router = useRouter()

const order = ref<any>(null)
const loading = ref(false)

const canPay = computed(() => {
  if (!order.value) return false
  return [1, 11].includes(order.value.status)
})

const canCancel = computed(() => {
  if (!order.value) return false
  return [0, 1, 11].includes(order.value.status)
})

const canReview = computed(() => {
  if (!order.value) return false
  return order.value.status === 60
})

const canAfterSale = computed(() => {
  if (!order.value) return false
  return [15, 17, 20, 54, 55, 60].includes(order.value.status)
})

const canTrackLogistics = computed(() => {
  if (!order.value) return false
  return [20, 54, 55, 60].includes(order.value.status)
})

async function loadOrder() {
  const id = Number(route.params.id)
  if (!id) {
    ElMessage.error('订单ID无效')
    return
  }
  loading.value = true
  try {
    const res = await getOrderDetail(id)
    order.value = res
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.back()
}

function handlePay() {
  if (!order.value) return
  const amount = Math.floor(order.value.total_amount)
  router.push(`/payment?orderNo=${order.value.order_no}&amount=${amount}`)
}

async function handleCancel() {
  if (!order.value) return
  loading.value = true
  try {
    await cancelOrder(order.value.order_no)
    ElMessage.success('订单已取消')
    await loadOrder()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function goToReview() {
  if (!order.value) return
  router.push(`/review?orderId=${order.value.id}`)
}

function goToAfterSale() {
  if (!order.value) return
  router.push(`/after-sale-apply?orderId=${order.value.id}&orderNo=${order.value.order_no}`)
}

function goToLogistics() {
  if (!order.value) return
  router.push(`/order/${order.value.id}/track`)
}

onMounted(() => {
  loadOrder()
})

defineExpose({
  order,
  loading,
  canPay,
  canCancel,
  canReview,
  canAfterSale,
  canTrackLogistics,
  loadOrder,
  goBack,
  handlePay,
  handleCancel,
  goToLogistics,
})
</script>

<template>
  <div class="order-detail-view page-container">
    <div class="header-row">
      <el-button data-testid="back-btn" size="small" @click="goBack">返回</el-button>
      <h2 class="page-title">订单详情</h2>
    </div>

    <div v-if="loading && !order" class="loading-text">加载中...</div>

    <div v-if="order" v-loading="loading" class="detail-panel">
      <div class="info-section">
        <div class="info-row">
          <span class="label">订单号：</span>
          <span class="order-no">{{ order.order_no }}</span>
        </div>
        <div class="info-row">
          <span class="label">订单状态：</span>
          <span class="order-status">{{ order.customer_status }}</span>
        </div>
        <div class="info-row">
          <span class="label">下单时间：</span>
          <span>{{ order.created_at }}</span>
        </div>
        <div class="info-row">
          <span class="label">订单金额：</span>
          <span class="order-amount">¥{{ order.total_amount?.toFixed ? order.total_amount.toFixed(2) : order.total_amount }}</span>
        </div>
      </div>

      <div class="items-section">
        <h3>商品列表</h3>
        <div
          v-for="item in order.items"
          :key="item.id"
          class="order-item"
        >
          <div class="item-name">{{ item.product_name }}</div>
          <div class="item-meta">
            <span>单价：¥{{ item.unit_price }}</span>
            <span>数量：{{ item.quantity }}</span>
            <span>小计：¥{{ item.subtotal }}</span>
          </div>
        </div>
      </div>

      <div class="actions">
        <el-button
          v-if="canPay"
          data-testid="pay-btn"
          type="primary"
          @click="handlePay"
        >去支付</el-button>
        <el-button
          v-if="canCancel"
          data-testid="cancel-btn"
          type="danger"
          @click="handleCancel"
        >取消订单</el-button>
        <el-button
          v-if="canReview"
          data-testid="review-btn"
          type="primary"
          @click="goToReview"
        >去评价</el-button>
        <el-button
          v-if="canAfterSale"
          data-testid="aftersale-btn"
          @click="goToAfterSale"
        >申请售后</el-button>
        <el-button
          v-if="canTrackLogistics"
          data-testid="logistics-btn"
          @click="goToLogistics"
        >查看物流</el-button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.order-detail-view {
  max-width: 680px;
  margin: 20px auto;
  padding: 20px;
}
.header-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}
.page-title {
  margin: 0;
}
.loading-text {
  padding: 40px;
  text-align: center;
  color: #909399;
}
.detail-panel {
  background: #fff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.info-section {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #ebeef5;
}
.info-row {
  display: flex;
  margin-bottom: 10px;
  font-size: 14px;
}
.label {
  color: #606266;
  width: 80px;
  flex-shrink: 0;
}
.order-no {
  font-weight: 600;
}
.order-status {
  color: #e6a23c;
  font-weight: 600;
}
.order-amount {
  color: #f56c6c;
  font-weight: 600;
  font-size: 16px;
}
.items-section {
  margin-bottom: 20px;
}
.items-section h3 {
  margin-bottom: 12px;
  font-size: 16px;
}
.order-item {
  padding: 12px;
  border: 1px solid #ebeef5;
  border-radius: 6px;
  margin-bottom: 10px;
}
.item-name {
  font-weight: 600;
  margin-bottom: 6px;
}
.item-meta {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #606266;
}
.actions {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #ebeef5;
}
</style>
