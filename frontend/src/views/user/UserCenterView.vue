<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { getUserInfo, getUserDashboard } from '@/api/user'
import type { UserDashboard } from '@/api/user'
import { ElMessage } from 'element-plus'
import {
  Wallet, Coin, Trophy, Bell, Ticket, Document, Box, Van, ChatDotSquare,
} from '@element-plus/icons-vue'

const router = useRouter()
const userStore = useUserStore()
const user = computed(() => userStore.userInfo)
const dashboard = ref<UserDashboard | null>(null)
const loadingDashboard = ref(false)

const vipText = computed(() => {
  const map: Record<number, string> = {
    0: '普通会员',
    1: '铜牌会员',
    2: '银牌会员',
    3: '金牌会员',
    4: '钻石会员',
  }
  return map[user.value?.vip_level ?? 0] || '普通会员'
})

const quickEntries = computed(() => {
  if (!dashboard.value) return []
  const c = dashboard.value.order_status_counts
  return [
    { key: 'pending_payment', label: '待付款', count: c.pending_payment, icon: Document, color: '#f56c6c', status: 11 },
    { key: 'in_progress', label: '进行中', count: c.in_progress, icon: Box, color: '#e6a23c', status: undefined },
    { key: 'pending_delivery', label: '待发货', count: c.pending_delivery, icon: Box, color: '#409eff', status: 17 },
    { key: 'pending_receive', label: '待收货', count: c.pending_receive, icon: Van, color: '#67c23a', status: 54 },
    { key: 'pending_review', label: '待评价', count: c.pending_review, icon: ChatDotSquare, color: '#909399', status: 60 },
  ]
})

const menuGroups = [
  {
    title: '我的服务',
    items: [
      { label: '收货地址', icon: 'Location', path: '/addresses' },
      { label: '我的收藏', icon: 'Star', path: '/favorites' },
      { label: '我的优惠券', icon: 'Ticket', path: '/my-coupons' },
      { label: '我的钱包', icon: 'Wallet', path: '/wallet' },
      { label: '支付密码', icon: 'Lock', path: '/pay-password' },
    ],
  },
  {
    title: '订单相关',
    items: [
      { label: '我的订单', icon: 'Document', path: '/orders' },
      { label: '发票中心', icon: 'DocumentCopy', path: '/invoices' },
      { label: '发票抬头', icon: 'DocumentAdd', path: '/invoice-titles' },
      { label: '我的售后', icon: 'Service', path: '/after-sales' },
      { label: '我的工单', icon: 'Message', path: '/tickets' },
      { label: '样品订单', icon: 'Box', path: '/samples' },
    ],
  },
  {
    title: '其他',
    items: [
      { label: '我的评价', icon: 'ChatDotSquare', path: '/my-reviews' },
      { label: '企业认证', icon: 'OfficeBuilding', path: '/enterprise-auth' },
      { label: 'VIP中心', icon: 'Trophy', path: '/vip' },
      { label: '消息通知', icon: 'Bell', path: '/notifications' },
    ],
  },
]

async function refreshUser() {
  try {
    const res = await getUserInfo()
    userStore.setUserInfo(res)
  } catch (e) {
    console.error(e)
  }
}

async function loadDashboard() {
  loadingDashboard.value = true
  try {
    dashboard.value = await getUserDashboard()
  } catch (e) {
    console.error(e)
  } finally {
    loadingDashboard.value = false
  }
}

function goTo(path: string) {
  router.push(path)
}

function goToOrders(status?: number) {
  if (status !== undefined) {
    router.push(`/orders?status=${status}`)
  } else {
    router.push('/orders')
  }
}

function formatPrice(price: number): string {
  return (price / 100).toFixed(2)
}

function formatTime(time: string): string {
  if (!time) return '-'
  const d = new Date(time.replace(/-/g, '/'))
  return `${d.getMonth() + 1}月${d.getDate()}日`
}

onMounted(() => {
  refreshUser()
  loadDashboard()
})

defineExpose({
  user,
  dashboard,
  vipText,
  quickEntries,
  menuGroups,
  refreshUser,
  loadDashboard,
  goTo,
  goToOrders,
})
</script>

<template>
  <div class="user-center-view page-container">
    <h2 class="page-title">个人中心</h2>
    <!-- 用户信息卡片 -->
    <div class="user-card">
      <div class="user-avatar">
        <img :src="user?.avatar || '/default-avatar.png'" alt="头像" />
      </div>
      <div class="user-info">
        <div class="user-name">{{ user?.nickname || user?.phone || '用户' }}</div>
        <div class="user-phone">{{ user?.phone }}</div>
        <el-tag size="small" type="warning">{{ vipText }}</el-tag>
      </div>
      <el-button link type="primary" @click="goTo('/profile')">编辑资料</el-button>
    </div>

    <!-- 资产栏 -->
    <div class="asset-bar">
      <div class="asset-item" @click="goTo('/wallet')">
        <div class="asset-value">¥{{ formatPrice(user?.balance || 0) }}</div>
        <div class="asset-label">余额</div>
      </div>
      <div class="asset-item" @click="goTo('/points')">
        <div class="asset-value">{{ user?.points || 0 }}</div>
        <div class="asset-label">积分</div>
      </div>
      <div class="asset-item" @click="goTo('/vip')">
        <div class="asset-value">Lv.{{ user?.vip_level || 0 }}</div>
        <div class="asset-label">VIP等级</div>
      </div>
      <div class="asset-item" @click="goTo('/notifications')">
        <div class="asset-value">{{ dashboard?.unread_notification_count || 0 }}</div>
        <div class="asset-label">消息</div>
      </div>
      <div class="asset-item" @click="goTo('/my-coupons')">
        <div class="asset-value">{{ dashboard?.available_coupon_count || 0 }}</div>
        <div class="asset-label">优惠券</div>
      </div>
    </div>

    <!-- 订单快捷入口 -->
    <div v-loading="loadingDashboard" class="order-quick-bar">
      <div
        v-for="entry in quickEntries"
        :key="entry.key"
        :data-testid="'quick-' + entry.key"
        class="order-quick-item"
        @click="goToOrders(entry.status)">
        <div class="order-quick-icon" :style="{ background: entry.color + '15', color: entry.color }">
          <el-icon :size="22"><component :is="entry.icon" /></el-icon>
        </div>
        <div class="order-quick-count">{{ entry.count }}</div>
        <div class="order-quick-label">{{ entry.label }}</div>
      </div>
    </div>

    <!-- 最近订单 -->
    <div v-loading="loadingDashboard" class="recent-orders-card">
      <div class="recent-orders-header">
        <span class="recent-orders-title">最近订单</span>
        <el-button link type="primary" size="small" @click="goTo('/orders')">查看全部</el-button>
      </div>
      <div v-if="!dashboard?.recent_orders?.length" class="recent-orders-empty">暂无订单</div>
      <div
        v-for="order in dashboard?.recent_orders || []"
        :key="order.id"
        class="recent-order-item"
        @click="goTo('/orders/' + order.id)"
      >
        <div class="recent-order-info">
          <div class="recent-order-no">{{ order.order_no }}</div>
          <div class="recent-order-status">{{ order.customer_status }}</div>
        </div>
        <div class="recent-order-meta">
          <div class="recent-order-time">{{ formatTime(order.created_at) }}</div>
          <div class="recent-order-amount">¥{{ (order.total_amount / 100).toFixed(2) }}</div>
        </div>
      </div>
    </div>

    <!-- 功能菜单 -->
    <div v-for="group in menuGroups" :key="group.title" class="menu-group">
      <div class="menu-title">{{ group.title }}</div>
      <div class="menu-grid">
        <div
          v-for="item in group.items"
          :key="item.path"
          class="menu-item"
          @click="goTo(item.path)"
        >
          <el-icon :size="24" class="menu-icon">
            <component :is="item.icon" />
          </el-icon>
          <span class="menu-label">{{ item.label }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.user-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 24px;
  background: linear-gradient(135deg, #409eff, #67c23a);
  border-radius: 12px;
  color: #fff;
  margin-bottom: 16px;
}
.user-avatar img {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  border: 2px solid #fff;
  object-fit: cover;
}
.user-info {
  flex: 1;
}
.user-name {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 4px;
}
.user-phone {
  font-size: 13px;
  opacity: 0.9;
  margin-bottom: 6px;
}

.asset-bar {
  display: flex;
  background: #fff;
  border-radius: 12px;
  padding: 16px 0;
  margin-bottom: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.asset-item {
  flex: 1;
  text-align: center;
  cursor: pointer;
  transition: opacity 0.2s;
}
.asset-item:hover {
  opacity: 0.8;
}
.asset-item:not(:last-child) {
  border-right: 1px solid #e4e7ed;
}
.asset-value {
  font-size: 18px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 4px;
}
.asset-label {
  font-size: 12px;
  color: #909399;
}

.order-quick-bar {
  display: flex;
  justify-content: space-between;
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.order-quick-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  min-width: 56px;
}
.order-quick-icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.order-quick-count {
  font-size: 15px;
  font-weight: 700;
  color: #303133;
}
.order-quick-label {
  font-size: 12px;
  color: #606266;
}

.recent-orders-card {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.recent-orders-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.recent-orders-title {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
}
.recent-orders-empty {
  padding: 24px;
  text-align: center;
  color: #909399;
  font-size: 13px;
}
.recent-order-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f2f6fc;
  cursor: pointer;
}
.recent-order-item:last-child {
  border-bottom: none;
}
.recent-order-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.recent-order-no {
  font-size: 14px;
  font-weight: 600;
  color: #303133;
}
.recent-order-status {
  font-size: 12px;
  color: #909399;
}
.recent-order-meta {
  text-align: right;
}
.recent-order-time {
  font-size: 12px;
  color: #909399;
  margin-bottom: 2px;
}
.recent-order-amount {
  font-size: 14px;
  font-weight: 600;
  color: #f56c6c;
}

.menu-group {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.menu-title {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 12px;
  padding-left: 4px;
}
.menu-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}
.menu-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 12px 0;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}
.menu-item:hover {
  background: #f5f7fa;
}
.menu-icon {
  color: #409eff;
}
.menu-label {
  font-size: 13px;
  color: #606266;
}

@media (max-width: 600px) {
  .menu-grid {
    grid-template-columns: repeat(4, 1fr);
  }
  .asset-bar {
    flex-wrap: wrap;
  }
  .asset-item {
    flex: 1 1 33%;
    margin-bottom: 8px;
  }
}
</style>
