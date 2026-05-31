<script setup lang="ts">
/**
 * 用户中心页
 * - 侧边菜单
 * - 内容区（根据路由或 Tab 切换）
 */
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import {
  User,
  List,
  Location,
  Setting,
  ShoppingCart,
} from '@element-plus/icons-vue'

const router = useRouter()
const userStore = useUserStore()

const activeMenu = ref('profile')

const menuItems = [
  { key: 'profile', label: '个人资料', icon: User },
  { key: 'orders', label: '我的订单', icon: List },
  { key: 'address', label: '收货地址', icon: Location },
  { key: 'cart', label: '购物车', icon: ShoppingCart },
  { key: 'settings', label: '账号设置', icon: Setting },
]

function handleMenuSelect(key: string) {
  activeMenu.value = key
  if (key === 'orders') {
    router.push('/orders')
  } else if (key === 'cart') {
    router.push('/cart')
  }
}
</script>

<template>
  <div class="user-center-view page-container">
    <div class="user-layout">
      <!-- 侧边栏 -->
      <aside class="user-sidebar ya-card">
        <div class="user-profile">
          <el-avatar :size="64" :icon="User" />
          <h3 class="user-name">{{ userStore.userInfo?.name || '用户' }}</h3>
          <p class="user-phone">{{ userStore.userInfo?.phone || '--' }}</p>
        </div>

        <el-menu
          :default-active="activeMenu"
          class="user-menu"
          @select="handleMenuSelect"
        >
          <el-menu-item v-for="item in menuItems" :key="item.key" :index="item.key">
            <el-icon>
              <component :is="item.icon" />
            </el-icon>
            <span>{{ item.label }}</span>
          </el-menu-item>
        </el-menu>
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
              <el-input
                :model-value="userStore.userInfo?.name || ''"
                placeholder="请输入昵称"
                style="max-width: 320px"
              />
            </el-form-item>
            <el-form-item label="手机号">
              <el-input
                :model-value="userStore.userInfo?.phone || ''"
                disabled
                style="max-width: 320px"
              />
            </el-form-item>
            <el-form-item label="邮箱">
              <el-input
                :model-value="userStore.userInfo?.email || ''"
                placeholder="请输入邮箱"
                style="max-width: 320px"
              />
            </el-form-item>
            <el-form-item>
              <el-button type="primary">保存修改</el-button>
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
            <el-form-item label="账号注销">
              <el-button type="danger" text>申请注销账号</el-button>
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

.user-menu {
  border-right: none;
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
