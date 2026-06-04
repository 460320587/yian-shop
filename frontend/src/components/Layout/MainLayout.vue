<script setup lang="ts">
/**
 * 主布局组件
 * - 顶部导航栏（含搜索、购物车入口、用户入口）
 * - 底部 Footer
 * - 响应式：PC 为主，兼容平板
 */
import { computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useCartStore } from '@/stores/cart'
import { logout as apiLogout } from '@/api/user'
import { ElMessage } from 'element-plus'
import {
  ShoppingCart,
  User,
  Search,
  Goods,
  List,
  Phone,
} from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const cartStore = useCartStore()

const isLoggedIn = computed(() => userStore.isLoggedIn)
const cartCount = computed(() => cartStore.totalCount)

const navItems = [
  { name: 'Home', label: '首页', path: '/' },
  { name: 'ProductList', label: '全部商品', path: '/products' },
]

const activeNav = computed(() => route.name as string)

function goLogin() {
  router.push('/login')
}

function goRegister() {
  router.push('/register')
}

onMounted(() => {
  // 如果有 token 但没有用户信息，尝试获取
  if (userStore.isLoggedIn && !userStore.userInfo) {
    userStore.fetchUserInfo()
  }
})

async function logout() {
  try {
    await apiLogout()
  } catch {
    // 忽略退出接口错误
  }
  userStore.logout()
  ElMessage.success('已退出登录')
  router.push('/')
}

function goCart() {
  router.push('/cart')
}

function goUser() {
  router.push('/user')
}
</script>

<template>
  <div class="main-layout">
    <!-- 顶部导航栏 -->
    <header class="header">
      <div class="header-inner page-container">
        <!-- Logo -->
        <div class="logo" @click="router.push('/')">
          <Goods class="logo-icon" />
          <span class="logo-text">怡安印刷商城</span>
        </div>

        <!-- 搜索栏 -->
        <div class="search-bar">
          <el-input
            placeholder="搜索印刷产品..."
            :prefix-icon="Search"
            class="search-input"
          />
        </div>

        <!-- 右侧操作区 -->
        <div class="header-actions">
          <!-- 购物车 -->
          <div class="action-item" @click="goCart">
            <el-badge :value="cartCount" :hidden="cartCount === 0">
              <ShoppingCart class="action-icon" />
            </el-badge>
            <span>购物车</span>
          </div>

          <!-- 未登录 -->
          <template v-if="!isLoggedIn">
            <el-button type="primary" text @click="goLogin">登录</el-button>
            <el-button type="primary" @click="goRegister">注册</el-button>
          </template>

          <!-- 已登录 -->
          <template v-else>
            <el-dropdown>
              <div class="action-item user-dropdown">
                <el-avatar :size="28" :icon="User" />
                <span>{{ userStore.userInfo?.nickname || userStore.userInfo?.phone || '用户' }}</span>
              </div>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item @click="goUser">个人中心</el-dropdown-item>
                  <el-dropdown-item @click="router.push('/orders')">我的订单</el-dropdown-item>
                  <el-dropdown-item divided @click="logout">退出登录</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </template>
        </div>
      </div>

      <!-- 二级导航 -->
      <nav class="sub-nav">
        <div class="sub-nav-inner page-container">
          <router-link
            v-for="item in navItems"
            :key="item.name"
            :to="item.path"
            :class="['nav-link', { active: activeNav === item.name }]"
          >
            {{ item.label }}
          </router-link>
        </div>
      </nav>
    </header>

    <!-- 主内容区 -->
    <main class="main-content">
      <router-view />
    </main>

    <!-- 底部 Footer -->
    <footer class="footer">
      <div class="footer-inner page-container">
        <div class="footer-sections">
          <div class="footer-section">
            <h4>关于我们</h4>
            <p>怡安印刷商城致力于为企业和个人提供高品质、快捷的印刷服务。</p>
          </div>
          <div class="footer-section">
            <h4>服务支持</h4>
            <ul>
              <li><a href="#">帮助中心</a></li>
              <li><a href="#">配送说明</a></li>
              <li><a href="#">售后服务</a></li>
            </ul>
          </div>
          <div class="footer-section">
            <h4>联系方式</h4>
            <p><Phone class="inline-icon" /> 400-888-9999</p>
            <p>周一至周日 9:00-18:00</p>
          </div>
        </div>
        <div class="footer-bottom">
          <p>怡安印刷商城 版权所有</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.main-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* Header */
.header {
  background: var(--bg-header);
  box-shadow: var(--shadow-light);
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: var(--header-height);
  gap: 24px;
}

.logo {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  flex-shrink: 0;
}

.logo-icon {
  width: 32px;
  height: 32px;
  color: var(--color-primary);
}

.logo-text {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-primary);
  white-space: nowrap;
}

.search-bar {
  flex: 1;
  max-width: 480px;
}

.search-input :deep(.el-input__wrapper) {
  border-radius: 20px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-shrink: 0;
}

.action-item {
  display: flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  color: var(--text-secondary);
  font-size: 14px;
  transition: color 0.2s;
}

.action-item:hover {
  color: var(--color-primary);
}

.action-icon {
  width: 20px;
  height: 20px;
}

.user-dropdown {
  gap: 6px;
}

/* Sub Nav */
.sub-nav {
  border-top: 1px solid var(--border-light);
}

.sub-nav-inner {
  display: flex;
  gap: 32px;
  height: 48px;
  align-items: center;
}

.nav-link {
  font-size: 15px;
  color: var(--text-secondary);
  font-weight: 500;
  transition: color 0.2s;
  position: relative;
}

.nav-link:hover,
.nav-link.active {
  color: var(--color-primary);
}

.nav-link.active::after {
  content: '';
  position: absolute;
  bottom: -14px;
  left: 0;
  right: 0;
  height: 2px;
  background: var(--color-primary);
  border-radius: 1px;
}

/* Main Content */
.main-content {
  flex: 1;
  padding: 24px 0;
}

/* Footer */
.footer {
  background: var(--bg-footer);
  color: #a0aec0;
  padding: 40px 0 16px;
}

.footer-sections {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 32px;
  margin-bottom: 32px;
}

.footer-section h4 {
  color: #fff;
  font-size: 16px;
  margin-bottom: 16px;
}

.footer-section p {
  font-size: 14px;
  line-height: 1.8;
}

.footer-section ul li {
  margin-bottom: 8px;
}

.footer-section ul li a {
  color: #a0aec0;
  font-size: 14px;
  transition: color 0.2s;
}

.footer-section ul li a:hover {
  color: #fff;
}

.inline-icon {
  width: 14px;
  height: 14px;
  vertical-align: middle;
  margin-right: 4px;
}

.footer-bottom {
  border-top: 1px solid #3d5166;
  padding-top: 16px;
  text-align: center;
  font-size: 13px;
}

/* 响应式：平板及以下 */
@media (max-width: 768px) {
  .header-inner {
    flex-wrap: wrap;
    height: auto;
    padding: 12px 16px;
  }

  .search-bar {
    order: 3;
    max-width: 100%;
    width: 100%;
  }

  .sub-nav-inner {
    gap: 20px;
    padding: 0 16px;
  }

  .footer-sections {
    grid-template-columns: 1fr;
    gap: 24px;
  }
}
</style>
