import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
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
      },
      {
        path: '/products',
        name: 'ProductList',
        component: () => import('@/views/product/ProductListView.vue'),
      },
      {
        path: '/product/:id',
        name: 'ProductDetail',
        component: () => import('@/views/product/ProductDetailView.vue'),
      },
      {
        path: '/cart',
        name: 'Cart',
        component: () => import('@/views/cart/CartView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/checkout',
        name: 'Checkout',
        component: () => import('@/views/order/OrderCreateView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/orders',
        name: 'OrderList',
        component: () => import('@/views/order/OrderListView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/user',
        name: 'UserCenter',
        component: () => import('@/views/user/UserCenterView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/coupons',
        name: 'Coupons',
        component: () => import('@/views/coupon/CouponView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/my-coupons',
        name: 'MyCoupons',
        component: () => import('@/views/coupon/MyCouponsView.vue'),
        meta: { requiresAuth: true },
      },
    ],
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/auth/RegisterView.vue'),
    meta: { guest: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, _from, next) => {
  const token = localStorage.getItem('access_token')

  if (to.meta.requiresAuth && !token) {
    next('/login')
  } else if (to.meta.guest && token) {
    next('/')
  } else {
    next()
  }
})

export default router
