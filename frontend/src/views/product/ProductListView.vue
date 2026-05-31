<script setup lang="ts">
/**
 * 商品列表页
 * - 分类筛选
 * - 商品卡片列表
 */
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import type { Category } from '@/types'

const router = useRouter()

// 分类数据（骨架）
const categories = ref<Category[]>([
  { id: 0, name: '全部' },
  { id: 1, name: '名片印刷' },
  { id: 2, name: '宣传册' },
  { id: 3, name: '包装盒' },
  { id: 4, name: '台历挂历' },
  { id: 5, name: '办公用品' },
  { id: 6, name: '海报印刷' },
  { id: 7, name: '不干胶标签' },
])

const activeCategory = ref(0)

// 商品列表（骨架）
const products = ref([
  { id: 1, name: '高级铜版纸名片 300g', price: 3500, cover_image: '', sold_count: 1200, category_id: 1 },
  { id: 2, name: '企业宣传册 A4 骑马钉', price: 12800, cover_image: '', sold_count: 856, category_id: 2 },
  { id: 3, name: '牛皮纸手提袋 定制', price: 5600, cover_image: '', sold_count: 2300, category_id: 3 },
  { id: 4, name: '不干胶标签 卷筒', price: 4200, cover_image: '', sold_count: 1540, category_id: 7 },
  { id: 5, name: '台历定制 2026年款', price: 15800, cover_image: '', sold_count: 670, category_id: 4 },
  { id: 6, name: '海报印刷 铜版纸覆膜', price: 2800, cover_image: '', sold_count: 3100, category_id: 6 },
  { id: 7, name: '信封定制 企业专用', price: 1800, cover_image: '', sold_count: 980, category_id: 5 },
  { id: 8, name: '无碳复写联单', price: 3200, cover_image: '', sold_count: 1120, category_id: 5 },
  { id: 9, name: 'PVC会员卡制作', price: 4500, cover_image: '', sold_count: 760, category_id: 1 },
  { id: 10, name: '精装画册印刷', price: 25600, cover_image: '', sold_count: 430, category_id: 2 },
  { id: 11, name: '瓦楞纸箱定制', price: 8900, cover_image: '', sold_count: 620, category_id: 3 },
  { id: 12, name: '展架易拉宝', price: 6800, cover_image: '', sold_count: 1890, category_id: 6 },
])

const filteredProducts = ref(products.value)

function selectCategory(id: number) {
  activeCategory.value = id
  if (id === 0) {
    filteredProducts.value = products.value
  } else {
    filteredProducts.value = products.value.filter((p) => p.category_id === id)
  }
}

function goDetail(id: number) {
  router.push(`/product/${id}`)
}

function formatPrice(value: number) {
  return '¥' + (value / 100).toFixed(2)
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
    <div class="product-grid">
      <div
        v-for="product in filteredProducts"
        :key="product.id"
        class="product-card"
        @click="goDetail(product.id)"
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

    <!-- 分页 -->
    <div class="pagination-bar">
      <el-pagination
        background
        layout="prev, pager, next"
        :total="filteredProducts.length"
        :page-size="12"
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
