import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import MainLayout from '@/components/Layout/MainLayout.vue'
import AdminLayout from '@/components/Layout/AdminLayout.vue'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: MainLayout,
    children: [
      { path: '', name: 'Home', component: () => import('@/views/home/HomeView.vue') },
      { path: '/products', name: 'ProductList', component: () => import('@/views/product/ProductListView.vue') },
      { path: '/product/:id', name: 'ProductDetail', component: () => import('@/views/product/ProductDetailView.vue') },
      { path: '/cart', name: 'Cart', component: () => import('@/views/cart/CartView.vue'), meta: { requiresAuth: true } },
      { path: '/checkout', name: 'Checkout', component: () => import('@/views/order/OrderCreateView.vue'), meta: { requiresAuth: true } },
      { path: '/orders', name: 'OrderList', component: () => import('@/views/order/OrderListView.vue'), meta: { requiresAuth: true } },
      { path: '/order/:id', name: 'OrderDetail', component: () => import('@/views/order/OrderDetailView.vue'), meta: { requiresAuth: true } },
      { path: '/order/:id/track', name: 'LogisticsTrack', component: () => import('@/views/logistics/LogisticsTrackView.vue'), meta: { requiresAuth: true } },
      { path: '/user', name: 'UserCenter', component: () => import('@/views/user/UserCenterView.vue'), meta: { requiresAuth: true } },
      { path: '/coupons', name: 'Coupons', component: () => import('@/views/coupon/CouponView.vue'), meta: { requiresAuth: true } },
      { path: '/my-coupons', name: 'MyCoupons', component: () => import('@/views/coupon/MyCouponsView.vue'), meta: { requiresAuth: true } },
      { path: '/addresses', name: 'Addresses', component: () => import('@/views/address/AddressManagementView.vue'), meta: { requiresAuth: true } },
      { path: '/favorites', name: 'Favorites', component: () => import('@/views/favorite/FavoriteView.vue'), meta: { requiresAuth: true } },
      { path: '/my-reviews', name: 'MyReviews', component: () => import('@/views/review/MyReviewsView.vue'), meta: { requiresAuth: true } },
      { path: '/review', name: 'ReviewForm', component: () => import('@/views/review/ReviewFormView.vue'), meta: { requiresAuth: true } },
      { path: '/payment', name: 'Payment', component: () => import('@/views/payment/PaymentView.vue'), meta: { requiresAuth: true } },
      { path: '/enterprise-auth', name: 'EnterpriseAuth', component: () => import('@/views/enterprise/EnterpriseAuthView.vue'), meta: { requiresAuth: true } },
      { path: '/after-sales', name: 'AfterSaleList', component: () => import('@/views/aftersale/AfterSaleListView.vue'), meta: { requiresAuth: true } },
      { path: '/after-sale-apply', name: 'AfterSaleApply', component: () => import('@/views/aftersale/AfterSaleApplyView.vue'), meta: { requiresAuth: true } },
      { path: '/notifications', name: 'Notifications', component: () => import('@/views/notification/NotificationCenterView.vue'), meta: { requiresAuth: true } },
      { path: '/profile', name: 'ProfileEdit', component: () => import('@/views/profile/ProfileEditView.vue'), meta: { requiresAuth: true } },
      { path: '/vip', name: 'VipCenter', component: () => import('@/views/vip/VipCenterView.vue'), meta: { requiresAuth: true } },
      { path: '/wallet', name: 'Wallet', component: () => import('@/views/wallet/WalletView.vue'), meta: { requiresAuth: true } },
      { path: '/invoice-titles', name: 'InvoiceTitles', component: () => import('@/views/invoice/InvoiceTitleView.vue'), meta: { requiresAuth: true } },
    ],
  },
  { path: '/login', name: 'Login', component: () => import('@/views/auth/LoginView.vue'), meta: { guest: true } },
  { path: '/register', name: 'Register', component: () => import('@/views/auth/RegisterView.vue'), meta: { guest: true } },
  { path: '/forgot-password', name: 'ForgotPassword', component: () => import('@/views/auth/ForgotPasswordView.vue'), meta: { guest: true } },
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAdmin: true },
    children: [
      { path: '', name: 'AdminDashboard', component: () => import('@/views/admin/DashboardView.vue'), meta: { title: '数据看板' } },
      { path: 'orders', name: 'AdminOrders', component: () => import('@/views/admin/OrderManagementView.vue'), meta: { title: '订单管理' } },
      { path: 'customers', name: 'AdminCustomers', component: () => import('@/views/admin/CustomerManagementView.vue'), meta: { title: '客户管理' } },
      { path: 'products', name: 'AdminProducts', component: () => import('@/views/admin/ProductManagementView.vue'), meta: { title: '商品管理' } },
      { path: 'coupons', name: 'AdminCoupons', component: () => import('@/views/admin/CouponManagementView.vue'), meta: { title: '优惠券' } },
      { path: 'banners', name: 'AdminBanners', component: () => import('@/views/admin/BannerManagementView.vue'), meta: { title: 'Banner/公告' } },
      { path: 'after-sales', name: 'AdminAfterSales', component: () => import('@/views/admin/AfterSaleManagementView.vue'), meta: { title: '售后管理' } },
      { path: 'invoices', name: 'AdminInvoices', component: () => import('@/views/admin/InvoiceManagementView.vue'), meta: { title: '发票管理' } },
      { path: 'audit-logs', name: 'AdminAuditLogs', component: () => import('@/views/admin/AuditLogView.vue'), meta: { title: '审计日志' } },
      { path: 'system-configs', name: 'AdminSystemConfigs', component: () => import('@/views/admin/SystemConfigView.vue'), meta: { title: '系统配置' } },
    ],
  },
  { path: '/admin/login', name: 'AdminLogin', component: () => import('@/views/admin/AdminLoginView.vue'), meta: { guestAdmin: true } },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach((to, _from, next) => {
  const token = localStorage.getItem('access_token')
  const adminToken = localStorage.getItem('admin_token')
  if (to.meta.requiresAuth && !token) { next('/login'); return }
  if (to.meta.guest && token) { next('/'); return }
  if (to.meta.requiresAdmin && !adminToken) { next('/admin/login'); return }
  if (to.meta.guestAdmin && adminToken) { next('/admin'); return }
  next()
})

export default router
