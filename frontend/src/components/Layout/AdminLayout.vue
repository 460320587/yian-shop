<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdminStore } from '@/stores/admin'

const router = useRouter()
const route = useRoute()
const adminStore = useAdminStore()
const isCollapse = ref(false)

const adminName = computed(() => adminStore.adminInfo?.real_name || adminStore.adminInfo?.username || '管理员')

const menuItems = [
  { index: '/admin', label: '数据看板' },
  { index: '/admin/orders', label: '订单管理' },
  { index: '/admin/customers', label: '客户管理' },
  { index: '/admin/coupons', label: '优惠券' },
  { index: '/admin/banners', label: 'Banner/公告' },
  { index: '/admin/after-sales', label: '售后管理' },
  { index: '/admin/invoices', label: '发票管理' },
]

const activeMenu = computed(() => route.path)

function handleSelect(index: string) { router.push(index) }
async function handleLogout() { await adminStore.logoutApi(); router.push('/admin/login') }
function toggleCollapse() { isCollapse.value = !isCollapse.value }
</script>

<template>
  <div class="admin-layout">
    <aside class="sidebar" :class="{ collapsed: isCollapse }">
      <div class="sidebar-header"><span v-show="!isCollapse" class="logo-text">怡安后台</span></div>
      <el-menu :default-active="activeMenu" :collapse="isCollapse" :collapse-transition="false" router class="admin-menu" background-color="#1a2a3a" text-color="#b0c4de" active-text-color="#fff">
        <el-menu-item v-for="item in menuItems" :key="item.index" :index="item.index" @click="handleSelect(item.index)"><template #title>{{ item.label }}</template></el-menu-item>
      </el-menu>
    </aside>
    <div class="main-area">
      <header class="admin-header">
        <div class="header-left"><el-button link @click="toggleCollapse">{{ isCollapse ? '展开' : '收起' }}</el-button><span class="page-title">{{ route.meta.title || '管理后台' }}</span></div>
        <div class="header-right"><el-dropdown><span class="user-info">{{ adminName }}</span><template #dropdown><el-dropdown-menu><el-dropdown-item @click="handleLogout">退出登录</el-dropdown-item></el-dropdown-menu></template></el-dropdown></div>
      </header>
      <main class="admin-content"><router-view /></main>
    </div>
  </div>
</template>

<style scoped>
.admin-layout { display: flex; min-height: 100vh; }
.sidebar { width: 220px; background: #1a2a3a; flex-shrink: 0; transition: width 0.3s; display: flex; flex-direction: column; }
.sidebar.collapsed { width: 64px; }
.sidebar-header { height: 60px; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid #2a3a4a; color: #fff; padding: 0 16px; }
.logo-text { font-size: 18px; font-weight: 600; white-space: nowrap; }
.admin-menu { border-right: none; flex: 1; }
.main-area { flex: 1; display: flex; flex-direction: column; min-width: 0; background: #f5f7fa; }
.admin-header { height: 60px; background: #fff; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
.header-left { display: flex; align-items: center; gap: 16px; }
.page-title { font-size: 16px; font-weight: 500; color: #333; }
.user-info { cursor: pointer; font-size: 14px; color: #333; }
.admin-content { flex: 1; padding: 20px 24px; overflow: auto; }
</style>
