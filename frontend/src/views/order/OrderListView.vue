<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getOrders } from '@/api/order'

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
</script>

<template>
  <div class="order-list-view page-container">
    <h2 class="page-title">我的订单</h2>
    <div v-loading="loading">
      <div v-for="order in orders" :key="order.id" class="order-card">{{ order.order_no }} - {{ order.customer_status }}</div>
    </div>
  </div>
</template>
