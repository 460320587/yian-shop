<script setup lang="ts">
/**
 * 商品列表页
 * - 分类筛选
 * - 商品卡片列表
 */
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { getProducts } from '@/api/product'
import { getCategories } from '@/api/portal'
import type { ProductItem } from '@/api/product'

const router = useRouter()
const route = useRoute()

const categories = ref<{ id: number; name: string }[]>([{ id: 0, name: '全部' }])
const activeCategory = ref(0)
const products = ref<ProductItem[]>([])
const loading = ref(false)
const currentPage = ref(1)
const total = ref(0)
const perPage = 12

onMounted(async () => {
  // 加载分类
  try {
    const cats = await getCategories()
    if (cats) {
      categories.value = [{ id: 0, name: '全部' }, ...cats.map((c) => ({ id: c.id, name: c.name }))]
    }
  } catch (e) {
    console.error('分类加载失败', e)
  }

  // 从 URL 参数恢复分类
  const catId = route.query.category_id
  if (catId) {
    activeCategory.value = Number(catId)
  }

  await loadProducts()
})

async function loadProducts() {
  loading.value = true
  try {
    const res = await getProducts({
      category_id: activeCategory.value || undefined,
      per_page: perPage,
      page: currentPage.value,
    })
    products.value = res.data || []
    total.value = res.total || 0
  } catch (e) {
    console.error('商品加载失败', e)
    products.value = []
  }
  loading.value = false
}

function selectCategory(id: number) {
  activeCategory.value = id
  currentPage.value = 1
  loadProducts()
  // 更新 URL
  router.replace({ query: id ? { category_id: id } : {} })
}

function handlePageChange(page: number) {
  currentPage.value = page
  loadProducts()
}

function goDetail(id: number) {
  router.push(`/product/${id}`)
}

function formatPrice(value: number) {
  return '¥' + (value ?? 0).toFixed(2)
}
</script>

<template>
  <div class="product-list-view page-container">
    <!-- 页面标题 -->
    <div class="page-header">
      <h2 class="page-title">全部商品</h2>
    </div>

    <!-- 分类筛选 -->
    <div class="category-filter">
      <span class="filter-label">分类：</span>
      <div class="filter-tags">
        <span
          v-for="cat in categories"
          :key="cat.id"
          :class="['filter-tag', { active: activeCategory === cat.id }]"
          @click="selectCategory(cat.id)"
        >
          {{ cat.name }}
        </span>
      </div>
    </div>

    <!-- 商品列表 -->
    <div v-loading="loading">
      <div v-if="products.length" class="product-grid">
        <div
          v-for="product in products"
          :key="product.id"
          class="product-card"
          @click="goDetail(product.id)"
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
      <el-empty v-else description="暂无商品" />
    </div>

    <!-- 分页 -->
    <div v-if="total > perPage" class="pagination-bar">
      <el-pagination
        background
        layout="prev, pager, next"
        :total="total"
        :page-size="perPage"
        :current-page="currentPage"
        @current-change="handlePageChange"
      />
    </div>
  </div>
</template>

<style scoped>
.page-header {
  margin-bottom: 20px;
}

.category-filter {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 24px;
  padding: 16px 20px;
  background: var(--bg-card);
  border-radius: var(--radius-base);
}

.filter-label {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
  flex-shrink: 0;
  line-height: 32px;
}

.filter-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.filter-tag {
  padding: 6px 16px;
  font-size: 14px;
  color: var(--text-secondary);
  background: var(--bg-body);
  border-radius: 16px;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-tag:hover {
  color: var(--color-primary);
}

.filter-tag.active {
  background: var(--color-primary);
  color: #fff;
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

.pagination-bar {
  display: flex;
  justify-content: center;
  margin-top: 32px;
}

@media (max-width: 1024px) {
  .product-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .product-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
