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

function canReview(order: any): boolean {
  return order.status === 60
}

function goToReview(order: any) {
  router.push('/review?orderId=' + order.id)
}

defineExpose({
  orders,
  loading,
  canReview,
  goToReview,
})
</script>

<template>
  <div class="order-list-view page-container">
    <h2 class="page-title">我的订单</h2>
    <div v-loading="loading">
      <div v-for="order in orders" :key="order.id" class="order-card">
        <div class="order-info">
          <span>{{ order.order_no }} - {{ order.customer_status }}</span>
          <el-button v-if="canReview(order)" link type="primary" @click="goToReview(order)">去评价</el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.order-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
