/**
 * Vue Router 4 配置
 * - 路由守卫：检查登录状态
 * - 支持懒加载
 */

import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useUserStore } from '@/stores/user'
import MainLayout from '@/components/Layout/MainLayout.vue'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: MainLayout,
    children: [
      {
        path: '',
        name: 'Home',
        component: () => import('@/views/home/HomeView.vue'),
        meta: { title: '首页' },
      },
      {
        path: 'product',
        name: 'ProductList',
        component: () => import('@/views/product/ProductListView.vue'),
        meta: { title: '商品列表' },
      },
      {
        path: 'product/:id',
        name: 'ProductDetail',
        component: () => import('@/views/product/ProductDetailView.vue'),
        meta: { title: '商品详情' },
      },
      {
        path: 'cart',
        name: 'Cart',
        component: () => import('@/views/cart/CartView.vue'),
        meta: { title: '购物车', requiresAuth: true },
      },
      {
        path: 'orders',
        name: 'Orders',
        component: () => import('@/views/order/OrderListView.vue'),
        meta: { title: '我的订单', requiresAuth: true },
      },
      {
        path: 'user',
        name: 'UserCenter',
        component: () => import('@/views/user/UserCenterView.vue'),
        meta: { title: '用户中心', requiresAuth: true },
      },
    ],
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { title: '登录', guestOnly: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/auth/RegisterView.vue'),
    meta: { title: '注册', guestOnly: true },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/home/HomeView.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

/** 全局路由守卫 */
router.beforeEach((to, _from, next) => {
  const userStore = useUserStore()

  // 设置页面标题
  if (to.meta.title) {
    document.title = `${to.meta.title} - 怡安印刷商城`
  } else {
    document.title = '怡安印刷商城'
  }

  // 需要登录的页面
  if (to.meta.requiresAuth && !userStore.isLoggedIn) {
    next({ name: 'Login', query: { redirect: to.fullPath } })
    return
  }

  // 仅游客可访问的页面（如登录/注册），已登录则跳转首页
  if (to.meta.guestOnly && userStore.isLoggedIn) {
    next({ name: 'Home' })
    return
  }

  next()
})

export default router
