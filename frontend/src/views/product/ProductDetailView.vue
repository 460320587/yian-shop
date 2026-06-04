<script setup lang="ts">
/**
 * 商品详情页
 */
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getProductDetail } from '@/api/product'
import { useCartStore } from '@/stores/cart'
import { ElMessage } from 'element-plus'
import { ShoppingCart, ArrowLeft } from '@element-plus/icons-vue'
import type { ProductDetail } from '@/api/product'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()

const productId = Number(route.params.id)
const product = ref<ProductDetail | null>(null)
const loading = ref(false)
const quantity = ref(1)

onMounted(async () => {
  if (!productId) {
    ElMessage.error('商品不存在')
    router.push('/products')
    return
  }
  loading.value = true
  try {
    product.value = await getProductDetail(productId)
  } catch (e) {
    ElMessage.error('商品加载失败')
  }
  loading.value = false
})

function goBack() {
  router.back()
}

function addToCart() {
  if (!product.value) return
  cartStore.addItem({
    id: Date.now(),
    product_id: product.value.id,
    product_name: product.value.name,
    product_image: product.value.cover_image || '',
    price: product.value.price_min,
    quantity: quantity.value,
    selected: true,
  })
  ElMessage.success('已加入购物车')
}

function buyNow() {
  addToCart()
  router.push('/cart')
}

function formatPrice(value: number) {
  return '¥' + (value ?? 0).toFixed(2)
}
</script>

<template>
  <div class="product-detail-view page-container" v-loading="loading">
    <el-button text :icon="ArrowLeft" class="back-btn" @click="goBack">
      返回
    </el-button>

    <div v-if="product" class="detail-layout">
      <!-- 左侧：图片 -->
      <div class="gallery">
        <div class="main-image">
          <el-image
            :src="product.cover_image || 'https://placehold.co/480x480/e4e7ed/999?text=商品主图'"
            fit="cover"
            class="image"
          />
        </div>
      </div>

      <!-- 右侧：信息 -->
      <div class="product-info">
        <h1 class="product-name">{{ product.name }}</h1>

        <div class="price-box">
          <div class="price-row">
            <span class="price-label">价格</span>
            <span class="price-value">{{ formatPrice(product.price_min) }}</span>
            <span v-if="product.price_max > product.price_min" class="price-range">
              ~ {{ formatPrice(product.price_max) }}
            </span>
          </div>
        </div>

        <!-- 数量 -->
        <div class="quantity-row">
          <span class="spec-label">数量</span>
          <el-input-number v-model="quantity" :min="1" :max="9999" />
        </div>

        <!-- 操作按钮 -->
        <div class="action-btns">
          <el-button type="warning" size="large" :icon="ShoppingCart" @click="addToCart">
            加入购物车
          </el-button>
          <el-button type="primary" size="large" @click="buyNow">
            立即购买
          </el-button>
        </div>
      </div>
    </div>

    <el-empty v-else description="商品不存在或已下架" />
  </div>
</template>

<style scoped>
.back-btn {
  margin-bottom: 16px;
}

.detail-layout {
  display: grid;
  grid-template-columns: 480px 1fr;
  gap: 40px;
  background: var(--bg-card);
  border-radius: var(--radius-base);
  padding: 32px;
  box-shadow: var(--shadow-light);
}

/* Gallery */
.gallery {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.main-image {
  width: 100%;
  height: 480px;
  border-radius: var(--radius-base);
  overflow: hidden;
  background: var(--bg-body);
}

.image {
  width: 100%;
  height: 100%;
}

/* Info */
.product-info {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.product-name {
  font-size: 24px;
  font-weight: 600;
  color: var(--text-primary);
}

.price-box {
  background: linear-gradient(90deg, #fff5f0 0%, #fff 100%);
  border-radius: var(--radius-base);
  padding: 20px;
}

.price-row {
  display: flex;
  align-items: baseline;
  gap: 12px;
}

.price-label {
  font-size: 14px;
  color: var(--text-secondary);
  width: 50px;
}

.price-value {
  font-size: 28px;
  font-weight: 700;
  color: var(--color-primary);
}

.price-range {
  font-size: 18px;
  color: var(--color-primary);
}

.quantity-row {
  display: flex;
  align-items: center;
  gap: 16px;
}

.spec-label {
  font-size: 14px;
  color: var(--text-secondary);
  width: 50px;
  flex-shrink: 0;
  line-height: 32px;
}

.action-btns {
  display: flex;
  gap: 16px;
  margin-top: 16px;
}

.action-btns .el-button {
  flex: 1;
  height: 48px;
  font-size: 16px;
  border-radius: 24px;
}

@media (max-width: 1024px) {
  .detail-layout {
    grid-template-columns: 1fr;
  }

  .main-image {
    height: 360px;
  }
}
</style>
