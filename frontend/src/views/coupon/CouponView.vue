<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getCoupons, claimCoupon, type Coupon } from '@/api/coupon'

const coupons = ref<Coupon[]>([])
const loading = ref(false)
const claimingId = ref<number | null>(null)

function formatPrice(price: number): string {
  return (price / 100).toFixed(2)
}

function formatDate(dateStr: string): string {
  return dateStr.split('T')[0]
}

function couponTypeText(type: number): string {
  return type === 1 ? '满减券' : '折扣券'
}

function couponValueText(c: Coupon): string {
  if (c.type === 1) {
    return '¥' + formatPrice(c.value)
  }
  return c.value + '折'
}

function isExpired(c: Coupon): boolean {
  return new Date(c.end_at) < new Date()
}

function isClaimed(c: Coupon): boolean {
  return c.claimed_count >= c.total_count
}

async function loadCoupons() {
  loading.value = true
  try {
    const res = await getCoupons()
    coupons.value = Array.isArray(res) ? res : res.data || []
  } catch (e) {
    console.error(e)
    ElMessage.error('加载优惠券失败')
  } finally {
    loading.value = false
  }
}

async function handleClaim(c: Coupon) {
  if (isExpired(c) || isClaimed(c)) return
  claimingId.value = c.id
  try {
    await claimCoupon(c.id)
    ElMessage.success('领取成功')
    await loadCoupons()
  } catch (e: any) {
    const msg = e?.response?.data?.message || '领取失败'
    ElMessage.error(msg)
  } finally {
    claimingId.value = null
  }
}

onMounted(() => {
  loadCoupons()
})

defineExpose({
  coupons,
  loading,
  claimingId,
  loadCoupons,
  handleClaim,
  formatPrice,
  formatDate,
})
</script>

<template>
  <div class="coupon-view page-container">
    <h2 class="page-title">领券中心</h2>
    <div v-loading="loading">
      <el-empty v-if="!loading && coupons.length === 0" description="暂无优惠券" />

      <div class="coupon-list">
        <div
          v-for="c in coupons"
          :key="c.id"
          :class="['coupon-card', { expired: isExpired(c), claimed: isClaimed(c) }]"
        >
          <div class="coupon-left">
            <div class="coupon-value">{{ couponValueText(c) }}</div>
            <div class="coupon-type">{{ couponTypeText(c.type) }}</div>
          </div>
          <div class="coupon-divider" />
          <div class="coupon-right">
            <div class="coupon-name">{{ c.name }}</div>
            <div class="coupon-condition">
              满¥{{ formatPrice(c.min_amount) }}可用
              <span v-if="c.max_discount">，最高减¥{{ formatPrice(c.max_discount) }}</span>
            </div>
            <div class="coupon-date">
              有效期：{{ formatDate(c.start_at) }} 至 {{ formatDate(c.end_at) }}
            </div>
            <div class="coupon-meta">
              <span>已领 {{ c.claimed_count }}/{{ c.total_count }}</span>
              <span>每人限领 {{ c.per_customer_limit }} 张</span>
            </div>
            <el-button
              size="small"
              type="primary"
              :disabled="isExpired(c) || isClaimed(c)"
              :loading="claimingId === c.id"
              @click="handleClaim(c)"
            >
              {{ isExpired(c) ? '已过期' : isClaimed(c) ? '已领完' : '立即领取' }}
            </el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.coupon-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 16px;
}
.coupon-card {
  display: flex;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
  transition: all 0.2s;
}
.coupon-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
.coupon-card.expired,
.coupon-card.claimed {
  opacity: 0.6;
}
.coupon-left {
  width: 100px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f56c6c, #e6a23c);
  color: #fff;
  padding: 16px;
}
.coupon-value {
  font-size: 24px;
  font-weight: 700;
}
.coupon-type {
  font-size: 12px;
  margin-top: 4px;
}
.coupon-divider {
  width: 1px;
  background: repeating-linear-gradient(
    to bottom,
    #e4e7ed 0,
    #e4e7ed 6px,
    transparent 6px,
    transparent 12px
  );
}
.coupon-right {
  flex: 1;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.coupon-name {
  font-weight: 600;
  font-size: 15px;
}
.coupon-condition {
  font-size: 13px;
  color: #f56c6c;
}
.coupon-date {
  font-size: 12px;
  color: #909399;
}
.coupon-meta {
  font-size: 12px;
  color: #909399;
  display: flex;
  gap: 12px;
}
</style>
