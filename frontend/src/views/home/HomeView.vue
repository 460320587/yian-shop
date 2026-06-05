<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  Document,
  Collection,
  Box,
  Calendar,
  OfficeBuilding,
  PictureFilled,
  ShoppingBag,
  Grid,
  Medal,
  Van,
  CircleCheck,
  Headset,
  Timer,
  ArrowRight,
  Lightning,
  StarFilled,
} from '@element-plus/icons-vue'
import { getHome, getCategories } from '@/api/portal'
import ProductCard from '@/components/Business/ProductCard/ProductCard.vue'

const router = useRouter()
const banners = ref<any[]>([])
const announcements = ref<any[]>([])
const hotProducts = ref<any[]>([])
const newArrivals = ref<any[]>([])
const categories = ref<any[]>([])
const loading = ref(false)
const popupVisible = ref(false)
const currentPopupIndex = ref(0)

const popupAnnouncements = computed(() => {
  return announcements.value.filter((a: any) => a.is_popup === 1)
})

const currentPopup = computed(() => {
  return popupAnnouncements.value[currentPopupIndex.value] || null
})

const displayedCategories = computed(() => {
  return categories.value.slice(0, 8)
})

const topSales = computed(() => {
  return [...hotProducts.value]
    .sort((a, b) => (b.sales_count || 0) - (a.sales_count || 0))
    .slice(0, 5)
})

const categoryIcons: Record<string, any> = {
  名片: Document,
  宣传册: Collection,
  画册: Collection,
  包装盒: Box,
  包装: Box,
  台历: Calendar,
  挂历: Calendar,
  办公用品: OfficeBuilding,
  海报: PictureFilled,
  手提袋: ShoppingBag,
  标签: Grid,
  贴纸: Grid,
}

const advantages = [
  { icon: Medal, title: '品质保障', desc: 'ISO认证 色彩精准' },
  { icon: Van, title: '全国配送', desc: '物流快速 破损包赔' },
  { icon: CircleCheck, title: '企业认证', desc: '专票支持 对公结算' },
  { icon: Headset, title: '专属客服', desc: '1对1服务 全程跟进' },
  { icon: Timer, title: '交期准时', desc: '急单可加急 违约赔付' },
]

function getCategoryIcon(name: string) {
  const key = Object.keys(categoryIcons).find((k) => name.includes(k))
  return key ? categoryIcons[key] : Document
}

function getRankClass(index: number) {
  if (index === 0) return 'rank-1'
  if (index === 1) return 'rank-2'
  if (index === 2) return 'rank-3'
  return ''
}

function goToCategory(id: number) {
  router.push(`/products?category_id=${id}`)
}

function goToProduct(id: number) {
  router.push(`/product/${id}`)
}

function handleBannerClick(banner: any) {
  if (!banner.link_target) return
  if (banner.link_type === 1) {
    router.push(`/product/${banner.link_target}`)
  } else if (banner.link_type === 2) {
    router.push(`/products?category_id=${banner.link_target}`)
  } else {
    window.open(banner.link_target, '_blank')
  }
}

function nextPopup() {
  if (currentPopupIndex.value < popupAnnouncements.value.length - 1) {
    currentPopupIndex.value += 1
  } else {
    popupVisible.value = false
    currentPopupIndex.value = 0
  }
}

async function loadData() {
  loading.value = true
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
  } finally {
    loading.value = false
  }
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
  loading,
  displayedCategories,
  topSales,
  loadData,
})
</script>

<template>
  <div v-loading="loading" class="home-view">
    <!-- Hero Banner -->
    <section v-if="banners.length > 0" class="hero-section">
      <div class="page-container">
        <el-carousel height="400px" indicator-position="outside" :interval="5000" class="hero-carousel">
          <el-carousel-item v-for="banner in banners" :key="banner.id">
            <div class="banner-item" @click="handleBannerClick(banner)">
              <img v-if="banner.image" :src="banner.image" :alt="banner.title" class="banner-img">
              <div v-else class="banner-fallback">
                <h2>{{ banner.title }}</h2>
                <p>专业印刷 · 品质保证 · 极速交付</p>
              </div>
            </div>
          </el-carousel-item>
        </el-carousel>
      </div>
    </section>

    <!-- Category Quick Entry -->
    <section v-if="displayedCategories.length > 0" class="section category-section">
      <div class="page-container">
        <div class="category-grid">
          <div
            v-for="cat in displayedCategories"
            :key="cat.id"
            class="category-item"
            @click="goToCategory(cat.id)"
          >
            <div class="category-icon">
              <component :is="getCategoryIcon(cat.name)" />
            </div>
            <span class="category-name">{{ cat.name }}</span>
          </div>
          <div class="category-item category-more" @click="router.push('/products')">
            <div class="category-icon">
              <Grid />
            </div>
            <span class="category-name">更多分类</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Main Content -->
    <section class="section main-section">
      <div class="page-container main-grid">
        <!-- Left: Products -->
        <div class="main-content">
          <!-- Hot Products -->
          <div v-if="hotProducts.length > 0" class="content-block">
            <div class="block-header">
              <div class="block-title">
                <el-icon class="title-icon hot-icon"><Lightning /></el-icon>
                <span>热销推荐</span>
              </div>
              <el-link type="primary" :underline="false" @click="router.push('/products?sort=sales_desc')">
                查看更多 <el-icon><ArrowRight /></el-icon>
              </el-link>
            </div>
            <div class="product-grid">
              <ProductCard
                v-for="p in hotProducts.slice(0, 8)"
                :key="p.id"
                :id="p.id"
                :name="p.name"
                :thumbnail="p.thumbnail"
                :min-price="p.min_price"
                :max-price="p.max_price"
                :sales-count="p.sales_count"
                :category="p.category"
                :is-hot="p.is_hot === 1"
                :is-new="p.is_new === 1"
              />
            </div>
          </div>

          <!-- New Arrivals -->
          <div v-if="newArrivals.length > 0" class="content-block">
            <div class="block-header">
              <div class="block-title">
                <el-icon class="title-icon new-icon"><StarFilled /></el-icon>
                <span>新品上架</span>
              </div>
              <el-link type="primary" :underline="false" @click="router.push('/products?sort=newest')">
                查看更多 <el-icon><ArrowRight /></el-icon>
              </el-link>
            </div>
            <div class="product-grid">
              <ProductCard
                v-for="p in newArrivals.slice(0, 8)"
                :key="p.id"
                :id="p.id"
                :name="p.name"
                :thumbnail="p.thumbnail"
                :min-price="p.min_price"
                :max-price="p.max_price"
                :sales-count="p.sales_count"
                :category="p.category"
                :is-hot="p.is_hot === 1"
                :is-new="p.is_new === 1"
              />
            </div>
          </div>
        </div>

        <!-- Right: Sidebar -->
        <aside class="main-sidebar">
          <!-- Sales Ranking -->
          <div v-if="topSales.length > 0" class="sidebar-card rank-card">
            <div class="sidebar-title">
              <el-icon><Medal /></el-icon>
              <span>热销排行</span>
            </div>
            <div class="rank-list">
              <div
                v-for="(p, idx) in topSales"
                :key="p.id"
                class="rank-item"
                @click="goToProduct(p.id)"
              >
                <span :class="['rank-index', getRankClass(idx)]">{{ idx + 1 }}</span>
                <div class="rank-thumb">
                  <img v-if="p.thumbnail" :src="p.thumbnail" :alt="p.name">
                  <div v-else class="rank-thumb-placeholder">{{ p.name.slice(0, 1) }}</div>
                </div>
                <div class="rank-info">
                  <div class="rank-name" :title="p.name">{{ p.name }}</div>
                  <div class="rank-sales">已售 {{ p.sales_count || 0 }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Announcements -->
          <div v-if="announcements.length > 0" class="sidebar-card">
            <div class="sidebar-title">
              <span>商城公告</span>
            </div>
            <ul class="announcement-list">
              <li
                v-for="a in announcements.slice(0, 5)"
                :key="a.id"
                class="announcement-item"
                :title="a.title"
              >
                <span class="announcement-dot" />
                <span class="announcement-text">{{ a.title }}</span>
              </li>
            </ul>
          </div>
        </aside>
      </div>
    </section>

    <!-- Enterprise Advantages -->
    <section class="section advantage-section">
      <div class="page-container">
        <div class="advantage-header">
          <h2 class="advantage-title">为什么选择怡安印刷</h2>
          <p class="advantage-subtitle">十年深耕印刷行业，服务超过 10,000+ 企业客户</p>
        </div>
        <div class="advantage-grid">
          <div v-for="item in advantages" :key="item.title" class="advantage-item">
            <div class="advantage-icon">
              <el-icon size="28"><component :is="item.icon" /></el-icon>
            </div>
            <h4>{{ item.title }}</h4>
            <p>{{ item.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Popup Announcement -->
    <el-dialog
      v-model="popupVisible"
      :title="currentPopup?.title || '公告'"
      width="460px"
      align-center
      class="popup-dialog"
    >
      <div class="popup-content">{{ currentPopup?.content || currentPopup?.title }}</div>
      <template #footer>
        <div class="popup-footer">
          <span class="popup-counter">
            {{ currentPopupIndex + 1 }} / {{ popupAnnouncements.length }}
          </span>
          <el-button type="primary" @click="nextPopup">
            {{ currentPopupIndex < popupAnnouncements.length - 1 ? '下一条' : '我知道了' }}
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.home-view {
  padding-bottom: 40px;
}

/* Hero */
.hero-section {
  background: linear-gradient(180deg, #fff5ed 0%, #ffffff 100%);
  padding: 24px 0 32px;
}

.hero-carousel {
  border-radius: var(--radius-large);
  overflow: hidden;
}

:deep(.hero-carousel .el-carousel__container) {
  border-radius: var(--radius-large);
}

.banner-item {
  width: 100%;
  height: 400px;
  cursor: pointer;
  position: relative;
}

.banner-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.banner-fallback {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
  color: #fff;
  text-align: center;
}

.banner-fallback h2 {
  font-size: 40px;
  font-weight: 700;
  margin-bottom: 12px;
}

.banner-fallback p {
  font-size: 18px;
  opacity: 0.9;
}

/* Section */
.section {
  margin-bottom: 32px;
}

/* Categories */
.category-section {
  margin-top: -16px;
}

.category-grid {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 16px;
}

.category-item {
  background: var(--bg-card);
  border-radius: var(--radius-large);
  padding: 20px 12px;
  text-align: center;
  box-shadow: var(--shadow-light);
  cursor: pointer;
  transition: all 0.25s ease;
}

.category-item:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-base);
  color: var(--color-primary);
}

.category-icon {
  width: 48px;
  height: 48px;
  margin: 0 auto 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff5ed;
  border-radius: 12px;
  color: var(--color-primary);
  font-size: 24px;
}

.category-item:hover .category-icon {
  background: var(--color-primary);
  color: #fff;
}

.category-name {
  font-size: 14px;
  font-weight: 500;
  color: var(--text-primary);
}

.category-more .category-icon {
  background: #f0f2f5;
  color: var(--text-secondary);
}

/* Main grid */
.main-grid {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 24px;
  align-items: start;
}

.main-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.content-block {
  background: var(--bg-card);
  border-radius: var(--radius-large);
  padding: 24px;
  box-shadow: var(--shadow-light);
}

.block-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.block-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 20px;
  font-weight: 700;
  color: var(--text-primary);
}

.title-icon {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.hot-icon {
  background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
}

.new-icon {
  background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

/* Sidebar */
.main-sidebar {
  display: flex;
  flex-direction: column;
  gap: 20px;
  position: sticky;
  top: 100px;
}

.sidebar-card {
  background: var(--bg-card);
  border-radius: var(--radius-large);
  padding: 20px;
  box-shadow: var(--shadow-light);
}

.sidebar-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 16px;
}

/* Rank */
.rank-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.rank-item {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  padding: 8px;
  border-radius: var(--radius-base);
  transition: background 0.2s;
}

.rank-item:hover {
  background: var(--border-light);
}

.rank-index {
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 12px;
  font-weight: 700;
  color: var(--text-muted);
  background: var(--border-light);
  flex-shrink: 0;
}

.rank-1 {
  background: #ff6b6b;
  color: #fff;
}

.rank-2 {
  background: #ffa726;
  color: #fff;
}

.rank-3 {
  background: #42a5f5;
  color: #fff;
}

.rank-thumb {
  width: 56px;
  height: 56px;
  border-radius: 8px;
  overflow: hidden;
  background: var(--bg-body);
  flex-shrink: 0;
}

.rank-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.rank-thumb-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: var(--text-muted);
  background: linear-gradient(135deg, #f0f2f5 0%, #e4e7ed 100%);
}

.rank-info {
  flex: 1;
  min-width: 0;
}

.rank-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 4px;
}

.rank-sales {
  font-size: 12px;
  color: var(--text-muted);
}

/* Announcements */
.announcement-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.announcement-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--text-secondary);
  cursor: pointer;
}

.announcement-item:hover {
  color: var(--color-primary);
}

.announcement-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--color-primary-light);
  flex-shrink: 0;
}

.announcement-text {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Advantages */
.advantage-section {
  background: var(--bg-footer);
  padding: 48px 0;
  margin-bottom: 0;
}

.advantage-header {
  text-align: center;
  margin-bottom: 40px;
}

.advantage-title {
  font-size: 28px;
  font-weight: 700;
  color: #fff;
  margin-bottom: 8px;
}

.advantage-subtitle {
  font-size: 15px;
  color: #a0aec0;
}

.advantage-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 24px;
}

.advantage-item {
  text-align: center;
  padding: 24px 16px;
  border-radius: var(--radius-large);
  background: rgba(255, 255, 255, 0.04);
  transition: background 0.25s;
}

.advantage-item:hover {
  background: rgba(255, 255, 255, 0.08);
}

.advantage-icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: rgba(230, 92, 0, 0.15);
  color: var(--color-primary-light);
}

.advantage-item h4 {
  color: #fff;
  font-size: 16px;
  margin-bottom: 6px;
}

.advantage-item p {
  color: #a0aec0;
  font-size: 13px;
}

/* Popup */
.popup-content {
  font-size: 14px;
  line-height: 1.7;
  color: var(--text-secondary);
  min-height: 80px;
}

.popup-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.popup-counter {
  font-size: 13px;
  color: var(--text-muted);
}

/* Responsive */
@media (max-width: 1024px) {
  .main-grid {
    grid-template-columns: 1fr;
  }

  .main-sidebar {
    position: static;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
  }

  .category-grid {
    grid-template-columns: repeat(4, 1fr);
  }

  .product-grid {
    grid-template-columns: repeat(3, 1fr);
  }

  .advantage-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .banner-item {
    height: 180px;
  }

  .banner-fallback h2 {
    font-size: 24px;
  }

  .category-grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
  }

  .category-item {
    padding: 14px 8px;
  }

  .category-icon {
    width: 36px;
    height: 36px;
    font-size: 18px;
  }

  .category-name {
    font-size: 12px;
  }

  .product-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }

  .main-sidebar {
    grid-template-columns: 1fr;
  }

  .advantage-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }

  .advantage-title {
    font-size: 22px;
  }
}
</style>
