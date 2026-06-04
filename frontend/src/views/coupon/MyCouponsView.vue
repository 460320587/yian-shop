<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getMyCoupons } from '@/api/coupon'
import type { CustomerCoupon } from '@/api/coupon'

const coupons = ref<CustomerCoupon[]>([])
const activeStatus = ref(1)
const loading = ref(false)

const tabs = [
  { label: '未使用', value: 1 },
  { label: '已使用', value: 2 },
  { label: '已过期', value: 3 },
]

onMounted(() => {
  loadCoupons()
})

async function loadCoupons() {
  loading.value = true
  try {
    const res = await getMyCoupons({ status: activeStatus.value, per_page: 50 })
    coupons.value = res.data || []
  } catch (e) {
    console.error('加载失败', e)
  }
  loading.value = false
}

function handleTabChange(val: number) {
  activeStatus.value = val
  loadCoupons()
}

function formatPrice(value: number) {
  return '¥' + (value / 100).toFixed(2)
}

function getCouponTypeLabel(type: number) {
  return { 1: '满减券', 2: '折扣券', 3: '直减券' }[type] || '优惠券'
}

function getCouponDesc(cc: CustomerCoupon) {
  const c = cc.coupon
  if (c.type === 1) return `满${formatPrice(c.min_amount)}减${formatPrice(c.value)}`
  if (c.type === 2) return `${(c.value / 10).toFixed(1)}折`
  return `直减${formatPrice(c.value)}`
}

function getStatusLabel(status: number) {
  return { 1: '未使用', 2: '已使用', 3: '已过期', 4: '已作废' }[status] || '未知'
}
</script>

<template>
  <div class="my-coupons-view page-container">
    <h2 class="page-title">我的优惠券</h2>

    <el-tabs v-model="activeStatus" @tab-change="handleTabChange">
      <el-tab-pane v-for="tab in tabs" :key="tab.value" :label="tab.label" :name="tab.value" />
    </el-tabs>

    <div v-loading="loading">
      <div v-if="coupons.length" class="coupon-grid">
        <div
          v-for="cc in coupons"
          :key="cc.id"
          class="coupon-card"
          :class="{ expired: cc.status !== 1 }"
        >
          <div class="coupon-left">
            <div class="coupon-value">{{ getCouponDesc(cc) }}</div>
            <div class="coupon-name">{{ cc.coupon.name }}</div>
            <div class="coupon-code">券码：{{ cc.code }}</div>
            <div class="coupon-time">有效期至 {{ cc.expired_at?.slice(0, 10) || '长期有效' }}</div>
          </div>
          <div class="coupon-right">
            <el-tag :type="cc.status === 1 ? 'success' : 'info'">
              {{ getStatusLabel(cc.status) }}
            </el-tag>
          </div>
        </div>
      </div>
      <el-empty v-else :description="`暂无${tabs.find(t => t.value === activeStatus)?.label}优惠券`" />
    </div>
  </div>
</template>

<style scoped>
.page-title {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 20px;
}

.coupon-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.coupon-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-card);
  border-radius: var(--radius-base);
  padding: 20px 24px;
  box-shadow: var(--shadow-light);
  border-left: 4px solid var(--color-primary);
}

.coupon-card.expired {
  border-left-color: var(--text-muted);
  opacity: 0.7;
}

.coupon-left {
  flex: 1;
}

.coupon-value {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-primary);
  margin-bottom: 4px;
}

.coupon-card.expired .coupon-value {
  color: var(--text-muted);
}

.coupon-name {
  font-size: 14px;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.coupon-code {
  font-size: 12px;
  color: var(--text-secondary);
  margin-bottom: 4px;
}

.coupon-time {
  font-size: 12px;
  color: var(--text-muted);
}

@media (max-width: 768px) {
  .coupon-grid {
    grid-template-columns: 1fr;
  }
}
</style>
