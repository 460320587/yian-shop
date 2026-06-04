<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getCoupons, claimCoupon } from '@/api/coupon'
import { ElMessage } from 'element-plus'
import type { Coupon } from '@/api/coupon'

const coupons = ref<Coupon[]>([])
const loading = ref(false)
const claiming = ref<number | null>(null)

onMounted(() => {
  loadCoupons()
})

async function loadCoupons() {
  loading.value = true
  try {
    const res = await getCoupons({ per_page: 50 })
    coupons.value = res.data || []
  } catch (e) {
    console.error('优惠券加载失败', e)
  }
  loading.value = false
}

async function handleClaim(coupon: Coupon) {
  claiming.value = coupon.id
  try {
    await claimCoupon(coupon.id)
    ElMessage.success('领取成功')
  } catch {
    // 错误由拦截器处理
  }
  claiming.value = null
}

function formatPrice(value: number) {
  return '¥' + (value / 100).toFixed(2)
}

function getCouponTypeLabel(type: number) {
  return { 1: '满减券', 2: '折扣券', 3: '直减券' }[type] || '优惠券'
}

function getCouponDesc(coupon: Coupon) {
  if (coupon.type === 1) return `满${formatPrice(coupon.min_amount)}减${formatPrice(coupon.value)}`
  if (coupon.type === 2) return `${(coupon.value / 10).toFixed(1)}折`
  return `直减${formatPrice(coupon.value)}`
}
</script>

<template>
  <div class="coupon-view page-container">
    <h2 class="page-title">领券中心</h2>

    <div v-loading="loading">
      <div v-if="coupons.length" class="coupon-grid">
        <div v-for="coupon in coupons" :key="coupon.id" class="coupon-card">
          <div class="coupon-left">
            <div class="coupon-value">{{ getCouponDesc(coupon) }}</div>
            <div class="coupon-name">{{ coupon.name }}</div>
            <div class="coupon-time">有效期至 {{ coupon.end_at?.slice(0, 10) }}</div>
          </div>
          <div class="coupon-right">
            <el-button
              type="primary"
              round
              :loading="claiming === coupon.id"
              @click="handleClaim(coupon)"
            >
              立即领取
            </el-button>
            <div class="coupon-stock">
              已领 {{ coupon.claimed_count }}/{{ coupon.total_count > 0 ? coupon.total_count : '不限' }}
            </div>
          </div>
        </div>
      </div>
      <el-empty v-else description="暂无可领取优惠券" />
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

.coupon-left {
  flex: 1;
}

.coupon-value {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-primary);
  margin-bottom: 4px;
}

.coupon-name {
  font-size: 14px;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.coupon-time {
  font-size: 12px;
  color: var(--text-muted);
}

.coupon-right {
  text-align: center;
}

.coupon-stock {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 8px;
}

@media (max-width: 768px) {
  .coupon-grid {
    grid-template-columns: 1fr;
  }
}
</style>
