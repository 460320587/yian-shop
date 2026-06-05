<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { getHome, getCategories } from '@/api/portal'

const banners = ref<any[]>([])
const announcements = ref<any[]>([])
const hotProducts = ref<any[]>([])
const newArrivals = ref<any[]>([])
const categories = ref<any[]>([])
const popupVisible = ref(false)
const currentPopupIndex = ref(0)

const popupAnnouncements = computed(() => {
  return announcements.value.filter((a: any) => a.is_popup === 1)
})

const currentPopup = computed(() => {
  return popupAnnouncements.value[currentPopupIndex.value] || null
})

async function loadData() {
  try {
    const [homeRes, catRes] = await Promise.all([getHome(), getCategories()])
    banners.value = homeRes.banners || []
    announcements.value = homeRes.announcements || []
    hotProducts.value = homeRes.hot_products || []
    newArrivals.value = homeRes.new_arrivals || []
    categories.value = catRes || []

    if (popupAnnouncements.value.length > 0) {
      popupVisible.value = true
    }
  } catch (e) {
    console.error('home load failed', e)
  }
}

function formatPrice(price: number | null): string {
  if (price === null) return '-'
  return (price / 100).toFixed(2)
}

onMounted(() => {
  loadData()
})

defineExpose({
  banners,
  announcements,
  hotProducts,
  newArrivals,
  categories,
  popupAnnouncements,
  popupVisible,
  currentPopupIndex,
  currentPopup,
  loadData,
})
</script>

<template>
  <div class="home-view">
    <!-- Banner 轮播 -->
    <div v-if="banners.length > 0" class="banner-section">
      <el-carousel height="240px" indicator-position="outside">
        <el-carousel-item v-for="banner in banners" :key="banner.id">
          <div class="banner-item">
            <img v-if="banner.image" :src="banner.image" :alt="banner.title" class="banner-img">
            <div v-else class="banner-placeholder">{{ banner.title }}</div>
          </div>
        </el-carousel-item>
      </el-carousel>
    </div>

    <!-- 分类导航 -->
    <div v-if="categories.length > 0" class="category-section">
      <div v-for="cat in categories" :key="cat.id" class="category-card">
        {{ cat.name }}
      </div>
    </div>

    <!-- 热销商品 -->
    <div v-if="hotProducts.length > 0" class="section">
      <h3 class="section-title">热销商品</h3>
      <div class="product-grid">
        <div v-for="p in hotProducts" :key="p.id" class="product-card">
          <div class="product-name">{{ p.name }}</div>
          <div class="product-price">¥{{ formatPrice(p.min_price) }}<span v-if="p.max_price && p.max_price !== p.min_price"> - ¥{{ formatPrice(p.max_price) }}</span></div>
          <div class="product-category">{{ p.category?.name }}</div>
        </div>
      </div>
    </div>

    <!-- 新品上架 -->
    <div v-if="newArrivals.length > 0" class="section">
      <h3 class="section-title">新品上架</h3>
      <div class="product-grid">
        <div v-for="p in newArrivals" :key="p.id" class="product-card">
          <div class="product-name">{{ p.name }}</div>
          <div class="product-price">¥{{ formatPrice(p.min_price) }}<span v-if="p.max_price && p.max_price !== p.min_price"> - ¥{{ formatPrice(p.max_price) }}</span></div>
          <div class="product-category">{{ p.category?.name }}</div>
        </div>
      </div>
    </div>

    <!-- 公告弹窗 -->
    <el-dialog
      v-model="popupVisible"
      :title="currentPopup?.title || '公告'"
      width="460px"
      align-center
    >
      <div class="popup-content">{{ currentPopup?.content || currentPopup?.title }}</div>
    </el-dialog>
  </div>
</template>

<style scoped>
.home-view {
  max-width: 960px;
  margin: 0 auto;
  padding: 20px;
}
.banner-section {
  margin-bottom: 24px;
  border-radius: 12px;
  overflow: hidden;
}
.banner-item {
  width: 100%;
  height: 240px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f7fa;
}
.banner-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.banner-placeholder {
  font-size: 20px;
  color: #909399;
}
.category-section {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}
.category-card {
  padding: 12px 20px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  font-size: 14px;
}
.section {
  margin-bottom: 24px;
}
.section-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 16px;
}
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 14px;
}
.product-card {
  background: #fff;
  border-radius: 8px;
  padding: 14px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.product-name {
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 6px;
}
.product-price {
  color: #f56c6c;
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 4px;
}
.product-category {
  font-size: 12px;
  color: #909399;
}
.popup-content {
  font-size: 14px;
  line-height: 1.6;
  color: #333;
}
</style>
