<script setup lang="ts">
/**
 * 首页视图
 * - Banner 轮播区域
 * - 商品分类入口
 * - 推荐商品列表
 */
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  Document,
  Picture,
  Box,
  Calendar,
  OfficeBuilding,
  More,
} from '@element-plus/icons-vue'

const router = useRouter()

// Banner 数据
const banners = [
  { id: 1, title: '专业印刷 品质保证', subtitle: '企业宣传册、名片、海报一站式定制', color: 'linear-gradient(135deg, #e65c00 0%, #ff8c42 100%)' },
  { id: 2, title: '新用户首单立减', subtitle: '注册即享 88 折优惠', color: 'linear-gradient(135deg, #2b3a4a 0%, #4a6078 100%)' },
]

// 分类入口
const categories = [
  { id: 1, name: '名片印刷', icon: Document },
  { id: 2, name: '宣传册', icon: Picture },
  { id: 3, name: '包装盒', icon: Box },
  { id: 4, name: '台历挂历', icon: Calendar },
  { id: 5, name: '办公用品', icon: OfficeBuilding },
  { id: 6, name: '更多分类', icon: More },
]

// 推荐商品（骨架数据）
const recommendProducts = ref([
  { id: 1, name: '高级铜版纸名片 300g', price: 3500, cover_image: '', sold_count: 1200 },
  { id: 2, name: '企业宣传册 A4 骑马钉', price: 12800, cover_image: '', sold_count: 856 },
  { id: 3, name: '牛皮纸手提袋 定制', price: 5600, cover_image: '', sold_count: 2300 },
  { id: 4, name: '不干胶标签 卷筒', price: 4200, cover_image: '', sold_count: 1540 },
  { id: 5, name: '台历定制 2026年款', price: 15800, cover_image: '', sold_count: 670 },
  { id: 6, name: '海报印刷 铜版纸覆膜', price: 2800, cover_image: '', sold_count: 3100 },
  { id: 7, name: '信封定制 企业专用', price: 1800, cover_image: '', sold_count: 980 },
  { id: 8, name: '无碳复写联单', price: 3200, cover_image: '', sold_count: 1120 },
])

function goProductList() {
  router.push('/product')
}

function goProductDetail(id: number) {
  router.push(`/product/${id}`)
}

function formatPrice(value: number) {
  return '¥' + (value / 100).toFixed(2)
}
</script>

<template>
  <div class="home-view">
    <!-- Banner 轮播 -->
    <section class="banner-section">
      <el-carousel height="360px" indicator-position="outside">
        <el-carousel-item v-for="banner in banners" :key="banner.id">
          <div class="banner-item" :style="{ background: banner.color }">
            <div class="banner-content page-container">
              <h2>{{ banner.title }}</h2>
              <p>{{ banner.subtitle }}</p>
              <el-button
                type="warning"
                size="large"
                round
                @click="goProductList"
              >
                立即选购
              </el-button>
            </div>
          </div>
        </el-carousel-item>
      </el-carousel>
    </section>

    <!-- 分类入口 -->
    <section class="category-section page-container">
      <div class="category-grid">
        <div
          v-for="cat in categories"
          :key="cat.id"
          class="category-card"
          @click="goProductList"
        >
          <el-icon :size="32" color="var(--color-primary)">
            <component :is="cat.icon" />
          </el-icon>
          <span class="category-name">{{ cat.name }}</span>
        </div>
      </div>
    </section>

    <!-- 推荐商品 -->
    <section class="recommend-section page-container">
      <div class="section-header">
        <h3 class="section-title">
          <span class="title-bar" />
          热销推荐
        </h3>
        <el-button text type="primary" @click="goProductList">
          查看更多 <el-icon><ArrowRight /></el-icon>
        </el-button>
      </div>

      <div class="product-grid">
        <div
          v-for="product in recommendProducts"
          :key="product.id"
          class="product-card"
          @click="goProductDetail(product.id)"
        >
          <div class="product-image">
            <el-image
              :src="product.cover_image || 'https://placehold.co/280x200/e4e7ed/999?text=印刷产品'"
              fit="cover"
              class="image"
            />
          </div>
          <div class="product-info">
            <h4 class="product-name">{{ product.name }}</h4>
            <div class="product-meta">
              <span class="product-price">{{ formatPrice(product.price) }}</span>
              <span class="product-sold">已售 {{ product.sold_count }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.banner-section {
  margin-bottom: 24px;
}

.banner-item {
  height: 100%;
  display: flex;
  align-items: center;
  color: #fff;
}

.banner-content h2 {
  font-size: 36px;
  font-weight: 700;
  margin-bottom: 12px;
}

.banner-content p {
  font-size: 18px;
  margin-bottom: 24px;
  opacity: 0.95;
}

/* 分类入口 */
.category-section {
  margin-bottom: 32px;
}

.category-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 16px;
}

.category-card {
  background: var(--bg-card);
  border-radius: var(--radius-base);
  padding: 24px 16px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: var(--shadow-light);
}

.category-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-base);
}

.category-name {
  display: block;
  margin-top: 12px;
  font-size: 14px;
  color: var(--text-primary);
}

/* 推荐商品 */
.recommend-section {
  margin-bottom: 32px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 22px;
  font-weight: 600;
}

.title-bar {
  display: inline-block;
  width: 4px;
  height: 22px;
  background: var(--color-primary);
  border-radius: 2px;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.product-card {
  background: var(--bg-card);
  border-radius: var(--radius-base);
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: var(--shadow-light);
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-base);
}

.product-image {
  width: 100%;
  height: 200px;
  overflow: hidden;
}

.image {
  width: 100%;
  height: 100%;
  transition: transform 0.3s;
}

.product-card:hover .image {
  transform: scale(1.05);
}

.product-info {
  padding: 16px;
}

.product-name {
  font-size: 15px;
  color: var(--text-primary);
  margin-bottom: 12px;
  line-height: 1.4;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.product-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.product-price {
  font-size: 18px;
  font-weight: 700;
  color: var(--color-primary);
}

.product-sold {
  font-size: 13px;
  color: var(--text-muted);
}

/* 响应式 */
@media (max-width: 1024px) {
  .product-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .category-grid {
    grid-template-columns: repeat(3, 1fr);
  }

  .product-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .banner-content h2 {
    font-size: 28px;
  }
}
</style>
