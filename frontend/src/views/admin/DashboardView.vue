<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  ShoppingCart, Coin, User, Goods, Document, Service, ArrowUp, ArrowDown,
  TrendCharts, Box, Truck, Check, Warning, Tickets, Shop, Setting,
} from '@element-plus/icons-vue'
import { getDashboardStats } from '@/api/admin'
import type { DashboardStats } from '@/api/admin'

const router = useRouter()
const stats = ref<DashboardStats | null>(null)
const loading = ref(false)

const statCards = computed(() => {
  if (!stats.value) return []
  const s = stats.value
  const fmt = (n: number) => '¥' + (n / 100).toFixed(2)
  const diff = (today: number, yest: number) => {
    if (yest === 0) return { pct: today > 0 ? 100 : 0, up: today >= 0 }
    const pct = ((today - yest) / yest) * 100
    return { pct: Math.abs(Math.round(pct * 10) / 10), up: pct >= 0 }
  }
  const orderDiff = diff(s.today_orders, s.yesterday_orders)
  const salesDiff = diff(s.today_sales, s.yesterday_sales)
  return [
    {
      title: '今日订单', value: String(s.today_orders), key: 'today_orders',
      icon: ShoppingCart, color: '#409eff', bg: '#ecf5ff',
      diff: orderDiff, route: '/admin/orders',
    },
    {
      title: '今日销售额', value: fmt(s.today_sales), key: 'today_sales',
      icon: Coin, color: '#67c23a', bg: '#f0f9eb',
      diff: salesDiff, route: '/admin/orders',
    },
    {
      title: '注册用户', value: String(s.total_customers), key: 'total_customers',
      icon: User, color: '#e6a23c', bg: '#fdf6ec',
      diff: null, route: '/admin/users',
    },
    {
      title: '商品总数', value: String(s.total_products), key: 'total_products',
      icon: Goods, color: '#f56c6c', bg: '#fef0f0',
      diff: null, route: '/admin/products',
    },
    {
      title: '待处理售后', value: String(s.pending_after_sales), key: 'pending_after_sales',
      icon: Service, color: '#909399', bg: '#f4f4f5',
      diff: null, route: '/admin/after-sales',
    },
    {
      title: '待审核发票', value: String(s.pending_invoices), key: 'pending_invoices',
      icon: Document, color: '#b88230', bg: '#fdf6ec',
      diff: null, route: '/admin/invoices',
    },
  ]
})

const statusLabels: Record<string, string> = {
  pending_payment: '待付款',
  paid: '已付款',
  in_production: '生产中',
  pending_delivery: '待发货',
  shipped: '已发货',
  pending_receive: '待收货',
  completed: '已完成',
}

const statusColors: Record<string, string> = {
  pending_payment: '#f56c6c',
  paid: '#e6a23c',
  in_production: '#409eff',
  pending_delivery: '#909399',
  shipped: '#67c23a',
  pending_receive: '#409eff',
  completed: '#67c23a',
}

const orderStatusList = computed(() => {
  if (!stats.value) return []
  const counts = stats.value.order_status_counts || {}
  const max = Math.max(...Object.values(counts), 1)
  return Object.entries(counts).map(([key, count]) => ({
    key, label: statusLabels[key] || key, count, max, color: statusColors[key] || '#409eff',
  }))
})

const salesTrendData = computed(() => {
  if (!stats.value?.sales_trend?.length) return null
  const data = stats.value.sales_trend
  const amounts = data.map(d => d.amount)
  const max = Math.max(...amounts, 1)
  const min = Math.min(...amounts)
  const range = max - min || 1
  const width = 100
  const height = 60
  const pad = 4
  const stepX = (width - pad * 2) / (data.length - 1)
  const points = data.map((d, i) => {
    const x = pad + i * stepX
    const y = pad + height - pad * 2 - ((d.amount - min) / range) * (height - pad * 4)
    return { x, y, date: d.date.slice(5), amount: d.amount, count: d.count }
  })
  const linePath = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ')
  const areaPath = `${linePath} L ${points[points.length - 1].x} ${height - pad} L ${points[0].x} ${height - pad} Z`
  return { points, linePath, areaPath, width, height, max }
})

const quickActions = [
  { label: '待处理售后', icon: Warning, route: '/admin/after-sales', color: '#f56c6c' },
  { label: '待审核发票', icon: Tickets, route: '/admin/invoices', color: '#e6a23c' },
  { label: '商品管理', icon: Shop, route: '/admin/products', color: '#409eff' },
  { label: '用户管理', icon: User, route: '/admin/users', color: '#67c23a' },
]

function formatTime(time: string): string {
  if (!time) return '-'
  const d = new Date(time)
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  const hh = String(d.getHours()).padStart(2, '0')
  const mi = String(d.getMinutes()).padStart(2, '0')
  return `${mm}-${dd} ${hh}:${mi}`
}

function goTo(route: string) {
  router.push(route)
}

function goOrderDetail(row: any) {
  router.push(`/admin/orders?id=${row.id}`)
}

async function loadStats() {
  loading.value = true
  try {
    stats.value = await getDashboardStats()
  } catch (e) {
    ElMessage.error('加载看板数据失败')
  } finally {
    loading.value = false
  }
}

onMounted(loadStats)

defineExpose({ stats, loading, loadStats, statCards, salesTrendData })
</script>

<template>
  <div class="dashboard-view" v-loading="loading">
    <!-- 统计卡片 -->
    <div class="stats-row">
      <div
        v-for="card in statCards"
        :key="card.key"
        class="stat-card"
        :style="{ '--card-color': card.color, '--card-bg': card.bg }"
        @click="goTo(card.route)"
      >
        <div class="stat-icon-wrapper">
          <el-icon :size="28" :color="card.color"><component :is="card.icon" /></el-icon>
        </div>
        <div class="stat-body">
          <div class="stat-value">{{ card.value }}</div>
          <div class="stat-title">{{ card.title }}</div>
          <div v-if="card.diff" class="stat-diff" :class="{ up: card.diff.up }">
            <el-icon :size="12"><ArrowUp v-if="card.diff.up" /><ArrowDown v-else /></el-icon>
            <span>{{ card.diff.pct }}% 环比</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 销售趋势 + 订单状态分布 -->
    <div class="charts-row">
      <div class="chart-card">
        <div class="chart-header">
          <el-icon><TrendCharts /></el-icon>
          <span>近7日销售趋势</span>
        </div>
        <div v-if="salesTrendData" class="trend-chart-wrapper">
          <svg :viewBox="`0 0 ${salesTrendData.width} ${salesTrendData.height}`" class="trend-svg">
            <defs>
              <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#409eff" stop-opacity="0.25" />
                <stop offset="100%" stop-color="#409eff" stop-opacity="0.02" />
              </linearGradient>
            </defs>
            <path :d="salesTrendData.areaPath" fill="url(#areaGrad)" />
            <path :d="salesTrendData.linePath" fill="none" stroke="#409eff" stroke-width="1.5" />
            <circle
              v-for="(p, i) in salesTrendData.points"
              :key="i"
              :cx="p.x" :cy="p.y" r="2"
              fill="#409eff"
            />
          </svg>
          <div class="trend-xaxis">
            <span v-for="(p, i) in salesTrendData.points" :key="i">{{ p.date }}</span>
          </div>
        </div>
        <div v-else class="chart-empty">暂无数据</div>
      </div>

      <div class="chart-card">
        <div class="chart-header">
          <el-icon><Box /></el-icon>
          <span>订单状态分布</span>
        </div>
        <div v-if="orderStatusList.length" class="status-list">
          <div v-for="item in orderStatusList" :key="item.key" class="status-row">
            <span class="status-label">{{ item.label }}</span>
            <div class="status-bar-bg">
              <div
                class="status-bar-fill"
                :style="{ width: (item.count / item.max * 100) + '%', background: item.color }"
              />
            </div>
            <span class="status-count">{{ item.count }}</span>
          </div>
        </div>
        <div v-else class="chart-empty">暂无数据</div>
      </div>
    </div>

    <!-- 快捷操作 -->
    <div class="quick-actions-row">
      <div class="section-title">快捷操作</div>
      <div class="quick-actions">
        <div
          v-for="action in quickActions"
          :key="action.label"
          class="quick-action-card"
          @click="goTo(action.route)"
        >
          <el-icon :size="24" :color="action.color"><component :is="action.icon" /></el-icon>
          <span class="quick-label">{{ action.label }}</span>
        </div>
      </div>
    </div>

    <!-- 最近订单 -->
    <div class="dashboard-section">
      <div class="section-header">
        <div class="section-title">最近订单</div>
        <el-button link type="primary" size="small" @click="goTo('/admin/orders')">
          查看全部
        </el-button>
      </div>
      <el-table
        :data="stats?.recent_orders || []"
        size="small"
        stripe
        @row-click="goOrderDetail"
      >
        <el-table-column prop="order_no" label="订单号" width="160" />
        <el-table-column prop="customer_name" label="客户" width="120" />
        <el-table-column label="金额" width="100">
          <template #default="{ row }">
            ¥{{ (row.total_amount / 100).toFixed(2) }}
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag size="small">{{ row.customer_status }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="时间" width="140">
          <template #default="{ row }">
            {{ formatTime(row.created_at) }}
          </template>
        </el-table-column>
      </el-table>
    </div>
  </div>
</template>

<style scoped>
.dashboard-view {
  padding: 20px;
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 14px;
  background: #fff;
  border-radius: 8px;
  padding: 18px 20px;
  cursor: pointer;
  transition: transform 0.15s, box-shadow 0.15s;
  border: 1px solid #ebeef5;
}
.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.stat-icon-wrapper {
  width: 52px;
  height: 52px;
  border-radius: 10px;
  background: var(--card-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-body {
  flex: 1;
  min-width: 0;
}

.stat-value {
  font-size: 22px;
  font-weight: 700;
  color: #303133;
  line-height: 1.2;
}

.stat-title {
  font-size: 13px;
  color: #909399;
  margin-top: 4px;
}

.stat-diff {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  font-size: 12px;
  color: #f56c6c;
  margin-top: 4px;
}
.stat-diff.up {
  color: #67c23a;
}

.charts-row {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  gap: 16px;
  margin-bottom: 20px;
}

.chart-card {
  background: #fff;
  border-radius: 8px;
  padding: 18px 20px;
  border: 1px solid #ebeef5;
}

.chart-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 14px;
}

.trend-chart-wrapper {
  padding: 8px 0;
}

.trend-svg {
  width: 100%;
  height: 140px;
  overflow: visible;
}

.trend-xaxis {
  display: flex;
  justify-content: space-between;
  padding: 0 4px;
  margin-top: 4px;
}
.trend-xaxis span {
  font-size: 11px;
  color: #909399;
}

.status-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.status-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.status-label {
  font-size: 13px;
  color: #606266;
  width: 64px;
  flex-shrink: 0;
  text-align: right;
}

.status-bar-bg {
  flex: 1;
  height: 8px;
  background: #f2f6fc;
  border-radius: 4px;
  overflow: hidden;
}

.status-bar-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.4s ease;
}

.status-count {
  font-size: 13px;
  color: #303133;
  font-weight: 600;
  width: 32px;
  text-align: right;
  flex-shrink: 0;
}

.chart-empty {
  padding: 40px;
  text-align: center;
  color: #909399;
  font-size: 13px;
}

.quick-actions-row {
  margin-bottom: 20px;
}

.quick-actions {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

.quick-action-card {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #fff;
  border-radius: 8px;
  padding: 14px 16px;
  cursor: pointer;
  transition: transform 0.15s, box-shadow 0.15s;
  border: 1px solid #ebeef5;
}
.quick-action-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.quick-label {
  font-size: 14px;
  color: #606266;
}

.dashboard-section {
  background: #fff;
  border-radius: 8px;
  padding: 18px 20px;
  border: 1px solid #ebeef5;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.section-title {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 12px;
}

@media (max-width: 768px) {
  .stats-row {
    grid-template-columns: repeat(2, 1fr);
  }
  .charts-row {
    grid-template-columns: 1fr;
  }
  .quick-actions {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
