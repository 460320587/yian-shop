<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getOrders } from '@/api/order'

const router = useRouter()
const orders = ref<any[]>([])
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const res = await getOrders({ page: 1 })
    orders.value = res.data
  } catch (e) { console.error(e) }
  finally { loading.value = false }
})

function canPay(order: any): boolean {
  return order.status === 1 || order.status === 11
}

function goToPay(order: any) {
  router.push('/payment?orderNo=' + order.order_no + '&amount=' + order.total_amount)
}

function canReview(order: any): boolean {
  return order.status === 60
}

function goToReview(order: any) {
  router.push('/review?orderId=' + order.id)
}

function canApplyAfterSale(order: any): boolean {
  return [15, 17, 20, 54, 55, 60].includes(order.status)
}

function goToAfterSale(order: any) {
  router.push('/after-sale-apply?orderId=' + order.id + '&orderNo=' + order.order_no)
}

function goToDetail(order: any) {
  router.push('/order/' + order.id)
}

defineExpose({
  orders,
  loading,
  canPay,
  goToPay,
  canReview,
  goToReview,
  canApplyAfterSale,
  goToAfterSale,
  goToDetail,
})
</script>

<template>
  <div class="order-list-view page-container">
    <h2 class="page-title">我的订单</h2>
    <div v-loading="loading">
      <div
        v-for="order in orders"
        :key="order.id"
        class="order-card"
        :data-order-id="order.id"
        @click="goToDetail(order)"
      >
        <div class="order-info">
          <span>{{ order.order_no }} - {{ order.customer_status }}</span>
          <div class="order-actions">
            <el-button v-if="canPay(order)" link type="primary" @click.stop="goToPay(order)">去支付</el-button>
            <el-button v-if="canApplyAfterSale(order)" link type="warning" @click.stop="goToAfterSale(order)">申请售后</el-button>
            <el-button v-if="canReview(order)" link type="primary" @click.stop="goToReview(order)">去评价</el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.order-card {
  cursor: pointer;
  transition: background 0.2s;
}
.order-card:hover {
  background: #f5f7fa;
}
.order-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.order-actions {
  display: flex;
  gap: 8px;
}
</style>
