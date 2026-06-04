<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getDashboardStats } from '@/api/admin'

const stats = ref([
  { title: '今日订单', value: 0, key: 'today_orders' },
  { title: '今日销售额', value: '¥0.00', key: 'today_sales' },
  { title: '注册用户', value: 0, key: 'total_customers' },
  { title: '商品总数', value: 0, key: 'total_products' },
  { title: '待处理售后', value: 0, key: 'pending_after_sales' },
  { title: '待审核发票', value: 0, key: 'pending_invoices' },
])
const recentOrders = ref<any[]>([])
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const data = await getDashboardStats()
    stats.value = [
      { title: '今日订单', value: data.today_orders, key: 'today_orders' },
      { title: '今日销售额', value: '¥' + (data.today_sales / 100).toFixed(2), key: 'today_sales' },
      { title: '注册用户', value: data.total_customers, key: 'total_customers' },
      { title: '商品总数', value: data.total_products, key: 'total_products' },
      { title: '待处理售后', value: data.pending_after_sales, key: 'pending_after_sales' },
      { title: '待审核发票', value: data.pending_invoices, key: 'pending_invoices' },
    ]
    recentOrders.value = data.recent_orders || []
  } catch (e) { console.error(e) }
  finally { loading.value = false }
})
</script>

<template>
  <div class="dashboard-view" v-loading="loading">
    <div class="stats-row">
      <div v-for="stat in stats" :key="stat.key" class="stat-card">
        <div class="stat-value">{{ stat.value }}</div>
        <div class="stat-title">{{ stat.title }}</div>
      </div>
    </div>
    <div class="dashboard-section">
      <h3>最近订单</h3>
      <div v-for="o in recentOrders" :key="o.order_no" class="order-row">{{ o.order_no }} - {{ o.customer_status }} - ¥{{ (o.total_amount / 100).toFixed(2) }}</div>
    </div>
  </div>
</template>

<style scoped>
.stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
.stat-card { background: #fff; border-radius: 8px; padding: 20px; }
.stat-value { font-size: 24px; font-weight: 600; }
.stat-title { font-size: 13px; color: #888; margin-top: 4px; }
.dashboard-section { background: #fff; border-radius: 8px; padding: 20px; }
.order-row { padding: 8px 0; border-bottom: 1px solid #eee; }
</style>
