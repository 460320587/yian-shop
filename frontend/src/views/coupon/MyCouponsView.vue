<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getMyCoupons, type CustomerCoupon } from '@/api/coupon'

const coupons = ref<CustomerCoupon[]>([])
const loading = ref(false)
const activeTab = ref(1)

const tabs = [
  { label: '未使用', value: 1 },
  { label: '已使用', value: 2 },
  { label: '已过期', value: 3 },
]

function formatPrice(price: number): string {
  return (price / 100).toFixed(2)
}

function formatDate(dateStr: string | null): string {
  return dateStr ? dateStr.split('T')[0] : '-'
}

function couponValueText(c: CustomerCoupon): string {
  if (c.coupon.type === 1) {
    return '¥' + formatPrice(c.coupon.value)
  }
  return c.coupon.value + '折'
}

function statusText(status: number): string {
  const map: Record<number, string> = { 1: '未使用', 2: '已使用', 3: '已过期' }
  return map[status] || '未知'
}

function statusType(status: number): string {
  const map: Record<number, string> = { 1: 'success', 2: 'info', 3: 'danger' }
  return map[status] || 'info'
}

async function loadCoupons() {
  loading.value = true
  try {
    const res = await getMyCoupons(activeTab.value)
    coupons.value = Array.isArray(res) ? res : res.data || []
  } catch (e) {
    console.error(e)
    ElMessage.error('加载优惠券失败')
  } finally {
    loading.value = false
  }
}

function onTabChange() {
  loadCoupons()
}

onMounted(() => {
  loadCoupons()
})

defineExpose({
  coupons,
  loading,
  activeTab,
  tabs,
  loadCoupons,
  onTabChange,
})
</script>

<template>
  <div class="my-coupons-view page-container">
    <h2 class="page-title">我的优惠券</h2>

    <el-tabs v-model="activeTab" @tab-change="onTabChange">
      <el-tab-pane v-for="tab in tabs" :key="tab.value" :label="tab.label" :name="tab.value" />
    </el-tabs>

    <div v-loading="loading">
      <el-empty v-if="!loading && coupons.length === 0" :description="'暂无' + tabs.find(t => t.value === activeTab)?.label + '优惠券'" />

      <div class="coupon-list">
        <div
          v-for="c in coupons"
          :key="c.id"
          :class="['coupon-card', { used: c.status === 2, expired: c.status === 3 }]"
        >
          <div class="coupon-left">
            <div class="coupon-value">{{ couponValueText(c) }}</div>
            <div class="coupon-type">{{ c.coupon.name }}</div>
          </div>
          <div class="coupon-divider" />
          <div class="coupon-right">
            <div class="coupon-header">
              <span class="coupon-code">券码：{{ c.code }}</span>
              <el-tag :type="statusType(c.status)" size="small">{{ statusText(c.status) }}</el-tag>
            </div>
            <div class="coupon-condition">
              满¥{{ formatPrice(c.coupon.min_amount) }}可用
            </div>
            <div class="coupon-date">
              领取时间：{{ formatDate(c.claimed_at) }}
              <span v-if="c.expired_at"> | 过期时间：{{ formatDate(c.expired_at) }}</span>
            </div>
            <div v-if="c.used_at" class="coupon-used">
              使用时间：{{ formatDate(c.used_at) }}
            </div>
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
  margin-top: 16px;
}
.coupon-card {
  display: flex;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
  transition: all 0.2s;
}
.coupon-card.used,
.coupon-card.expired {
  opacity: 0.6;
}
.coupon-left {
  width: 100px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #67c23a, #409eff);
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
  text-align: center;
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
.coupon-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.coupon-code {
  font-family: monospace;
  font-size: 14px;
  font-weight: 600;
  color: #303133;
}
.coupon-condition {
  font-size: 13px;
  color: #f56c6c;
}
.coupon-date {
  font-size: 12px;
  color: #909399;
}
.coupon-used {
  font-size: 12px;
  color: #67c23a;
}
</style>
