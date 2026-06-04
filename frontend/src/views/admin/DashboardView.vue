<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getAdminOrders, getAdminCustomers } from '@/api/admin'

const stats = ref([
  { title: '今日订单', value: 0 },
  { title: '注册用户', value: 0 },
])
const recentOrders = ref<any[]>([])
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const [orderRes, customerRes] = await Promise.all([
      getAdminOrders({ per_page: 5 }),
      getAdminCustomers({ per_page: 1 }),
    ])
    recentOrders.value = orderRes.data || []
    stats.value[1].value = customerRes.total || 0
  } catch (e) { console.error(e) }
  finally { loading.value = false }
})
</script>

<template>
  <div class="dashboard-view" v-loading="loading">
    <div class="stats-row">
      <div v-for="stat in stats" :key="stat.title" class="stat-card">
        <div class="stat-value">{{ stat.value }}</div>
        <div class="stat-title">{{ stat.title }}</div>
      </div>
    </div>
    <div class="dashboard-section">
      <h3>最近订单</h3>
      <div v-for="o in recentOrders" :key="o.id" class="order-row">{{ o.order_no }} - {{ o.customer_status }}</div>
    </div>
  </div>
</template>

<style scoped>
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
.stat-card { background: #fff; border-radius: 8px; padding: 20px; }
.stat-value { font-size: 24px; font-weight: 600; }
.stat-title { font-size: 13px; color: #888; margin-top: 4px; }
.dashboard-section { background: #fff; border-radius: 8px; padding: 20px; }
.order-row { padding: 8px 0; border-bottom: 1px solid #eee; }
</style>
