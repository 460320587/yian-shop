<script setup lang="ts">
/**
 * 商品详情页
 * - 图片展示
 * - 名称、价格、规格
 * - 加入购物车按钮
 */
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import { ElMessage } from 'element-plus'
import { ShoppingCart, ArrowLeft } from '@element-plus/icons-vue'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()

const productId = Number(route.params.id)

// 骨架商品数据
const product = ref({
  id: productId,
  name: '高级铜版纸名片 300g',
  description: '采用300g高级铜版纸，四色印刷，覆哑膜，手感细腻，色彩还原度高。适用于商务名片、企业名片定制。',
  price: 3500,
  original_price: 5000,
  cover_image: 'https://placehold.co/480x480/e4e7ed/999?text=商品主图',
  images: [
    'https://placehold.co/480x480/e4e7ed/999?text=商品主图',
    'https://placehold.co/480x480/e4e7ed/999?text=详情图1',
    'https://placehold.co/480x480/e4e7ed/999?text=详情图2',
  ],
  stock: 9999,
  sold_count: 1200,
  specs: [
    { id: 1, name: '数量', values: ['100张', '200张', '500张', '1000张'] },
    { id: 2, name: '覆膜', values: ['不覆膜', '哑膜', '亮膜'] },
  ],
})

const selectedImage = ref(product.value.images[0])
const quantity = ref(1)
const selectedSpecs = ref<Record<number, string>>({})

// 初始化默认规格
product.value.specs?.forEach((spec) => {
  selectedSpecs.value[spec.id] = spec.values[0]
})

function goBack() {
  router.back()
}

function addToCart() {
  const specInfo = Object.values(selectedSpecs.value).join(' / ')
  cartStore.addItem({
    id: Date.now(),
    product_id: product.value.id,
    product_name: product.value.name,
    product_image: product.value.cover_image,
    spec_info: specInfo,
    price: product.value.price,
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
  return '¥' + (value / 100).toFixed(2)
}
</script>

<template>
  <div class="product-detail-view page-container">
    <!-- 返回按钮 -->
    <el-button text :icon="ArrowLeft" class="back-btn" @click="goBack">
      返回
    </el-button>

    <div class="detail-layout">
      <!-- 左侧：图片 -->
      <div class="gallery">
        <div class="main-image">
          <el-image :src="selectedImage" fit="cover" class="image" />
        </div>
        <div class="thumb-list">
          <div
            v-for="(img, idx) in product.images"
            :key="idx"
            :class="['thumb-item', { active: selectedImage === img }]"
            @click="selectedImage = img"
          >
            <el-image :src="img" fit="cover" class="thumb-image" />
          </div>
        </div>
      </div>

      <!-- 右侧：信息 -->
      <div class="product-info">
        <h1 class="product-name">{{ product.name }}</h1>
        <p class="product-desc">{{ product.description }}</p>

        <div class="price-box">
          <div class="price-row">
            <span class="price-label">优惠价</span>
            <span class="price-value">{{ formatPrice(product.price) }}</span>
          </div>
          <div class="price-row original">
            <span class="price-label">原价</span>
            <span class="original-price">{{ formatPrice(product.original_price || 0) }}</span>
          </div>
        </div>

        <!-- 规格选择 -->
        <div v-for="spec in product.specs" :key="spec.id" class="spec-row">
          <span class="spec-label">{{ spec.name }}</span>
          <div class="spec-options">
            <span
              v-for="val in spec.values"
              :key="val"
              :class="['spec-option', { active: selectedSpecs[spec.id] === val }]"
              @click="selectedSpecs[spec.id] = val"
            >
              {{ val }}
            </span>
          </div>
        </div>

        <!-- 数量 -->
        <div class="quantity-row">
          <span class="spec-label">数量</span>
          <el-input-number v-model="quantity" :min="1" :max="product.stock" />
          <span class="stock-hint">库存 {{ product.stock }} 件</span>
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

.thumb-list {
  display: flex;
  gap: 10px;
}

.thumb-item {
  width: 80px;
  height: 80px;
  border-radius: 6px;
  overflow: hidden;
  cursor: pointer;
  border: 2px solid transparent;
  transition: border-color 0.2s;
}

.thumb-item.active,
.thumb-item:hover {
  border-color: var(--color-primary);
}

.thumb-image {
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

.product-desc {
  font-size: 14px;
  color: var(--text-secondary);
  line-height: 1.8;
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

.price-row.original {
  margin-top: 8px;
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

.original-price {
  font-size: 16px;
  color: var(--text-muted);
  text-decoration: line-through;
}

.spec-row {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.spec-label {
  font-size: 14px;
  color: var(--text-secondary);
  width: 50px;
  flex-shrink: 0;
  line-height: 32px;
}

.spec-options {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.spec-option {
  padding: 6px 16px;
  font-size: 14px;
  color: var(--text-primary);
  background: var(--bg-body);
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.spec-option:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.spec-option.active {
  background: var(--color-primary);
  color: #fff;
  border-color: var(--color-primary);
}

.quantity-row {
  display: flex;
  align-items: center;
  gap: 16px;
}

.stock-hint {
  font-size: 13px;
  color: var(--text-muted);
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
