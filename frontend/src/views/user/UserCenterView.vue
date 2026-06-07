<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { getUserInfo } from '@/api/user'
import { ElMessage } from 'element-plus'

const router = useRouter()
const userStore = useUserStore()
const user = computed(() => userStore.userInfo)

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

function goTo(path: string) {
  router.push(path)
}

function formatPrice(price: number): string {
  return (price / 100).toFixed(2)
}

onMounted(() => {
  refreshUser()
})

defineExpose({
  user,
  vipText,
  menuGroups,
  refreshUser,
  goTo,
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
}
</style>
