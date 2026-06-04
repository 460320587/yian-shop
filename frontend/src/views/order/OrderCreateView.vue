<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getCart } from '@/api/cart'
import { createOrder } from '@/api/order'
import { getMyCoupons } from '@/api/coupon'
import { ElMessage } from 'element-plus'
import type { CartItem } from '@/api/cart'
import type { CustomerCoupon } from '@/api/coupon'

const router = useRouter()

const cartItems = ref<CartItem[]>([])
const summary = ref({ total_count: 0, selected_count: 0, selected_subtotal: 0 })
const myCoupons = ref<CustomerCoupon[]>([])
const selectedCouponCode = ref('')
const loading = ref(false)
const submitting = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const cartData = await getCart()
    cartItems.value = (cartData.items || []).filter((i) => i.selected)
    summary.value = cartData.summary || { total_count: 0, selected_count: 0, selected_subtotal: 0 }

    // 加载可用优惠券
    const couponRes = await getMyCoupons({ status: 1, per_page: 50 })
    myCoupons.value = couponRes.data || []
  } catch (e) {
    console.error('加载失败', e)
  }
  loading.value = false
})

const finalTotal = ref(0)

async function submitOrder() {
  if (cartItems.value.length === 0) {
    ElMessage.warning('没有可结算的商品')
    return
  }
  submitting.value = true
  try {
    const data: { address_id?: number; coupon_code?: string } = { address_id: 1 }
    if (selectedCouponCode.value) {
      data.coupon_code = selectedCouponCode.value
    }
    const order = await createOrder(data)
    ElMessage.success('订单创建成功')
    router.push('/orders')
  } catch (e: any) {
    // 错误由拦截器处理
  }
  submitting.value = false
}

function formatPrice(value: number) {
  return '¥' + (value ?? 0).toFixed(2)
}
</script>

<template>
  <div class="checkout-view page-container" v-loading="loading">
    <h2 class="page-title">确认订单</h2>

    <el-empty v-if="!cartItems.length" description="没有可结算的商品" />

    <div v-else class="checkout-layout">
      <!-- 商品清单 -->
      <el-card class="checkout-section">
        <template #header>
          <span>商品清单</span>
        </template>
        <div v-for="item in cartItems" :key="item.id" class="checkout-item">
          <el-image
            :src="item.thumbnail || 'https://placehold.co/80x80/e4e7ed/999?text=图'"
            fit="cover"
            class="item-image"
          />
          <div class="item-info">
            <p class="item-name">{{ item.product_name }}</p>
            <p v-if="item.spec_info" class="item-spec">{{ item.spec_info }}</p>
          </div>
          <div class="item-price">
            <p>{{ formatPrice(item.unit_price) }} x {{ item.quantity }}</p>
            <p class="item-subtotal">{{ formatPrice(item.subtotal) }}</p>
          </div>
        </div>
      </el-card>

      <!-- 优惠券 -->
      <el-card class="checkout-section">
        <template #header>
          <span>优惠券</span>
        </template>
        <el-radio-group v-model="selectedCouponCode">
          <el-radio label="">不使用优惠券</el-radio>
          <el-radio
            v-for="cc in myCoupons"
            :key="cc.id"
            :label="cc.code"
          >
            {{ cc.coupon.name }}（{{ cc.code }}）
          </el-radio>
        </el-radio-group>
        <el-empty v-if="!myCoupons.length" description="暂无可用优惠券" :image-size="60" />
      </el-card>

      <!-- 结算信息 -->
      <el-card class="checkout-section">
        <template #header>
          <span>结算信息</span>
        </template>
        <div class="summary-row">
          <span>商品总额</span>
          <span>{{ formatPrice(summary.selected_subtotal) }}</span>
        </div>
        <div class="summary-row total">
          <span>应付总额</span>
          <span class="total-price">{{ formatPrice(summary.selected_subtotal) }}</span>
        </div>
        <div class="submit-bar">
          <el-button type="primary" size="large" :loading="submitting" @click="submitOrder">
            提交订单
          </el-button>
        </div>
      </el-card>
    </div>
  </div>
</template>

<style scoped>
.page-title {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 20px;
}
.checkout-layout {
  display: flex;
  flex-direction: column;
  gap: 20px;
  max-width: 800px;
}
.checkout-section {
  margin-bottom: 0;
}
.checkout-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 12px 0;
  border-bottom: 1px solid var(--border-light);
}
.checkout-item:last-child {
  border-bottom: none;
}
.item-image {
  width: 60px;
  height: 60px;
  border-radius: 4px;
}
.item-info {
  flex: 1;
}
.item-name {
  font-size: 14px;
  color: var(--text-primary);
}
.item-spec {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 4px;
}
.item-price {
  text-align: right;
}
.item-subtotal {
  font-weight: bold;
  color: var(--color-primary);
}
.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  font-size: 14px;
}
.summary-row.total {
  font-size: 16px;
  font-weight: 600;
  border-top: 1px solid var(--border-light);
  padding-top: 12px;
  margin-top: 8px;
}
.total-price {
  color: var(--color-primary);
  font-size: 20px;
}
.submit-bar {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}
</style>
