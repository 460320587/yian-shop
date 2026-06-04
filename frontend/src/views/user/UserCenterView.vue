<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { logout as apiLogout } from '@/api/user'
import { ElMessage } from 'element-plus'
import {
  User, List, Location, Setting, ShoppingCart, Ticket, Wallet, Medal,
} from '@element-plus/icons-vue'

const router = useRouter()
const userStore = useUserStore()

const activeMenu = ref('profile')

const menuItems = [
  { key: 'profile', label: '个人资料', icon: User },
  { key: 'orders', label: '我的订单', icon: List },
  { key: 'coupons', label: '我的优惠券', icon: Ticket },
  { key: 'address', label: '收货地址', icon: Location },
  { key: 'cart', label: '购物车', icon: ShoppingCart },
  { key: 'settings', label: '账号设置', icon: Setting },
]

onMounted(() => {
  if (userStore.isLoggedIn && !userStore.userInfo) {
    userStore.fetchUserInfo()
  }
})

function handleMenuSelect(key: string) {
  activeMenu.value = key
  if (key === 'orders') router.push('/orders')
  else if (key === 'cart') router.push('/cart')
  else if (key === 'coupons') router.push('/my-coupons')
}

async function handleLogout() {
  try {
    await apiLogout()
  } catch { /* ignore */ }
  userStore.logout()
  ElMessage.success('已退出登录')
  router.push('/')
}

const user = userStore.userInfo
</script>

<template>
  <div class="user-center-view page-container">
    <div class="user-layout">
      <!-- 侧边栏 -->
      <aside class="user-sidebar ya-card">
        <div class="user-profile">
          <el-avatar :size="64" :icon="User" />
          <h3 class="user-name">{{ user?.nickname || user?.phone || '用户' }}</h3>
          <p class="user-phone">{{ user?.phone || '--' }}</p>
          <div v-if="user" class="user-stats">
            <div class="stat-item">
              <el-icon :size="18"><Medal /></el-icon>
              <span>VIP {{ user.vip_level || 0 }}</span>
            </div>
            <div class="stat-item">
              <el-icon :size="18"><Wallet /></el-icon>
              <span>余额 ¥{{ (user.balance ?? 0).toFixed(2) }}</span>
            </div>
          </div>
        </div>

        <el-menu :default-active="activeMenu" class="user-menu" @select="handleMenuSelect">
          <el-menu-item v-for="item in menuItems" :key="item.key" :index="item.key">
            <el-icon><component :is="item.icon" /></el-icon>
            <span>{{ item.label }}</span>
          </el-menu-item>
        </el-menu>

        <div class="logout-bar">
          <el-button type="danger" text @click="handleLogout">退出登录</el-button>
        </div>
      </aside>

      <!-- 内容区 -->
      <main class="user-content ya-card">
        <template v-if="activeMenu === 'profile'">
          <h3 class="content-title">个人资料</h3>
          <el-form label-width="100px" class="profile-form">
            <el-form-item label="头像">
              <el-avatar :size="80" :icon="User" />
            </el-form-item>
            <el-form-item label="昵称">
              <el-input :model-value="user?.nickname || ''" placeholder="请输入昵称" style="max-width: 320px" disabled />
            </el-form-item>
            <el-form-item label="手机号">
              <el-input :model-value="user?.phone || ''" disabled style="max-width: 320px" />
            </el-form-item>
            <el-form-item>
              <el-tag v-if="user?.auth_status === 1" type="success">已认证</el-tag>
              <el-tag v-else type="info">未认证</el-tag>
            </el-form-item>
          </el-form>
        </template>

        <template v-if="activeMenu === 'address'">
          <h3 class="content-title">收货地址</h3>
          <el-empty description="暂无收货地址">
            <el-button type="primary">新增地址</el-button>
          </el-empty>
        </template>

        <template v-if="activeMenu === 'settings'">
          <h3 class="content-title">账号设置</h3>
          <el-form label-width="120px">
            <el-form-item label="修改密码">
              <el-button>修改登录密码</el-button>
            </el-form-item>
          </el-form>
        </template>
      </main>
    </div>
  </div>
</template>

<style scoped>
.user-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 20px;
  align-items: start;
}

/* 侧边栏 */
.user-sidebar {
  padding: 0;
  overflow: hidden;
}

.user-profile {
  text-align: center;
  padding: 32px 20px 24px;
  border-bottom: 1px solid var(--border-light);
}

.user-name {
  font-size: 18px;
  font-weight: 600;
  margin-top: 12px;
  margin-bottom: 4px;
}

.user-phone {
  font-size: 13px;
  color: var(--text-muted);
}

.user-stats {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 16px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: var(--text-secondary);
}

.user-menu {
  border-right: none;
}

.logout-bar {
  padding: 16px;
  border-top: 1px solid var(--border-light);
  text-align: center;
}

/* 内容区 */
.user-content {
  min-height: 500px;
}

.content-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border-light);
}

.profile-form {
  max-width: 500px;
}

@media (max-width: 768px) {
  .user-layout {
    grid-template-columns: 1fr;
  }

  .user-sidebar {
    margin-bottom: 16px;
  }
}
</style>
