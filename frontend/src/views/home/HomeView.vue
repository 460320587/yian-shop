<script setup lang="ts">
/**
 * 首页视图
 * - Banner 轮播区域
 * - 公告栏
 * - 商品分类入口
 * - 热销推荐 / 新品上市
 */
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getHomeData, getCategories } from '@/api/portal'
import type { Banner, Announcement, HomeProduct } from '@/api/portal'
import {
  Document,
  Picture,
  Box,
  Calendar,
  OfficeBuilding,
  More,
  ArrowRight,
  Bell,
} from '@element-plus/icons-vue'

const router = useRouter()

// 数据
const banners = ref<Banner[]>([])
const announcements = ref<Announcement[]>([])
const hotProducts = ref<HomeProduct[]>([])
const newArrivals = ref<HomeProduct[]>([])
const categories = ref<{ id: number; name: string }[]>([
  { id: 1, name: '名片印刷' },
  { id: 2, name: '宣传册' },
  { id: 3, name: '包装盒' },
  { id: 4, name: '台历挂历' },
  { id: 5, name: '办公用品' },
  { id: 6, name: '更多分类' },
])
const loading = ref(false)

const categoryIcons = [Document, Picture, Box, Calendar, OfficeBuilding, More]

onMounted(async () => {
  loading.value = true
  try {
    const homeData = await getHomeData()
    banners.value = homeData.banners || []
    announcements.value = homeData.announcements || []
    hotProducts.value = homeData.hot_products || []
    newArrivals.value = homeData.new_arrivals || []
  } catch (e) {
    console.error('首页数据加载失败', e)
  }

  try {
    const cats = await getCategories()
    if (cats && cats.length > 0) {
      categories.value = cats.slice(0, 6).map((c) => ({ id: c.id, name: c.name }))
    }
  } catch (e) {
    console.error('分类加载失败', e)
  }
  loading.value = false
})

function goProductList() {
  router.push('/products')
}

function goProductDetail(id: number) {
  router.push(`/product/${id}`)
}

function formatPrice(value: number) {
  return '¥' + (value ?? 0).toFixed(2)
}
</script>

<template>
  <div class="home-view">
    <!-- 公告栏 -->
    <div v-if="announcements.length" class="announcement-bar page-container">
      <el-icon :size="16"><Bell /></el-icon>
      <el-carousel height="24px" direction="vertical" :interval="3000" indicator-position="none">
        <el-carousel-item v-for="a in announcements" :key="a.id">
          <span class="announcement-text">{{ a.title }}</span>
        </el-carousel-item>
      </el-carousel>
    </div>

    <!-- Banner 轮播 -->
    <section class="banner-section">
      <el-carousel height="360px" indicator-position="outside" v-loading="loading">
        <el-carousel-item v-for="banner in banners" :key="banner.id">
          <div
            class="banner-item"
            :style="{ backgroundImage: `url(${banner.image})`, backgroundSize: 'cover', backgroundPosition: 'center' }"
          >
            <div class="banner-content page-container">
              <h2>{{ banner.title }}</h2>
              <el-button type="warning" size="large" round @click="goProductList">
                立即选购
              </el-button>
            </div>
          </div>
        </el-carousel-item>
        <el-carousel-item v-if="!banners.length">
          <div class="banner-item" style="background: linear-gradient(135deg, #e65c00 0%, #ff8c42 100%)">
            <div class="banner-content page-container">
              <h2>专业印刷 品质保证</h2>
              <p>企业宣传册、名片、海报一站式定制</p>
              <el-button type="warning" size="large" round @click="goProductList">
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
          v-for="(cat, idx) in categories"
          :key="cat.id"
          class="category-card"
          @click="goProductList"
        >
          <el-icon :size="32" color="var(--color-primary)">
            <component :is="categoryIcons[idx % categoryIcons.length]" />
          </el-icon>
          <span class="category-name">{{ cat.name }}</span>
        </div>
      </div>
    </section>

    <!-- 热销推荐 -->
    <section class="recommend-section page-container" v-loading="loading">
      <div class="section-header">
        <h3 class="section-title">
          <span class="title-bar" />
          热销推荐
        </h3>
        <el-button text type="primary" @click="goProductList">
          查看更多 <el-icon><ArrowRight /></el-icon>
        </el-button>
      </div>

      <div v-if="hotProducts.length" class="product-grid">
        <div
          v-for="product in hotProducts"
          :key="product.id"
          class="product-card"
          @click="goProductDetail(product.id)"
        >
          <div class="product-image">
            <el-image
              :src="product.thumbnail || 'https://placehold.co/280x200/e4e7ed/999?text=印刷产品'"
              fit="cover"
              class="image"
            />
          </div>
          <div class="product-info">
            <h4 class="product-name">{{ product.name }}</h4>
            <div class="product-meta">
              <span class="product-price">{{ formatPrice(product.min_price) }}</span>
              <span class="product-sold">已售 {{ product.sales_count }}</span>
            </div>
          </div>
        </div>
      </div>
      <el-empty v-else description="暂无热销商品" />
    </section>

    <!-- 新品上市 -->
    <section class="recommend-section page-container" v-loading="loading">
      <div class="section-header">
        <h3 class="section-title">
          <span class="title-bar" />
          新品上市
        </h3>
        <el-button text type="primary" @click="goProductList">
          查看更多 <el-icon><ArrowRight /></el-icon>
        </el-button>
      </div>

      <div v-if="newArrivals.length" class="product-grid">
        <div
          v-for="product in newArrivals"
          :key="product.id"
          class="product-card"
          @click="goProductDetail(product.id)"
        >
          <div class="product-image">
            <el-image
              :src="product.thumbnail || 'https://placehold.co/280x200/e4e7ed/999?text=印刷产品'"
              fit="cover"
              class="image"
            />
          </div>
          <div class="product-info">
            <h4 class="product-name">{{ product.name }}</h4>
            <div class="product-meta">
              <span class="product-price">{{ formatPrice(product.min_price) }}</span>
              <span class="product-sold">已售 {{ product.sales_count }}</span>
            </div>
          </div>
        </div>
      </div>
      <el-empty v-else description="暂无新品" />
    </section>
  </div>
</template>

<style scoped>
.announcement-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 0;
  color: var(--color-primary);
  font-size: 13px;
}
.announcement-text {
  line-height: 24px;
}

.banner-section {
  margin-bottom: 24px;
}

.banner-item {
  height: 100%;
  display: flex;
  align-items: center;
  color: #fff;
}

.banner-content {
  text-shadow: 0 2px 8px rgba(0,0,0,0.3);
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
