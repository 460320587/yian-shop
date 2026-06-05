<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getProducts } from '@/api/product'
import { getCategories } from '@/api/portal'
import type { Product } from '@/api/product'
import type { Category } from '@/api/portal'

const products = ref<Product[]>([])
const categories = ref<Category[]>([])
const loading = ref(false)
const total = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)

const sortOptions = [
  { value: 'default', label: '默认排序' },
  { value: 'price_asc', label: '价格从低到高' },
  { value: 'price_desc', label: '价格从高到低' },
  { value: 'newest', label: '最新上架' },
]

interface Filters {
  keyword: string
  category_id: number | null
  min_price: number | null
  max_price: number | null
  sort: string
}

const filters = ref<Filters>({
  keyword: '',
  category_id: null,
  min_price: null,
  max_price: null,
  sort: 'default',
})

async function loadCategories() {
  try {
    const res = await getCategories()
    categories.value = res
  } catch (e) {
    console.error(e)
  }
}

async function loadProducts(page = 1) {
  loading.value = true
  try {
    const params: any = { page, sort: filters.value.sort }
    if (filters.value.keyword) params.keyword = filters.value.keyword
    if (filters.value.category_id) params.category_id = filters.value.category_id
    if (filters.value.min_price) params.min_price = filters.value.min_price
    if (filters.value.max_price) params.max_price = filters.value.max_price

    const res = await getProducts(params)
    products.value = res.data
    total.value = res.total
    currentPage.value = res.current_page
    lastPage.value = res.last_page
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  loadProducts(1)
}

function resetFilters() {
  filters.value = {
    keyword: '',
    category_id: null,
    min_price: null,
    max_price: null,
    sort: 'default',
  }
  loadProducts(1)
}

function handlePageChange(page: number) {
  loadProducts(page)
}

function formatPrice(price: number | null): string {
  if (price === null) return '-'
  return (price / 100).toFixed(2)
}

onMounted(() => {
  loadCategories()
  loadProducts()
})

defineExpose({
  products,
  categories,
  loading,
  filters,
  total,
  currentPage,
  lastPage,
  loadProducts,
  handleSearch,
  resetFilters,
  handlePageChange,
  formatPrice,
})
</script>

<template>
  <div class="product-list-view page-container">
    <h2 class="page-title">全部商品</h2>

    <div class="filter-bar">
      <el-input
        v-model="filters.keyword"
        placeholder="搜索商品"
        class="search-input"
        clearable
        @keyup.enter="handleSearch"
      />
      <el-select v-model="filters.category_id" placeholder="全部分类" clearable class="filter-item" @change="handleSearch">
        <el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" />
      </el-select>
      <el-input-number v-model="filters.min_price" :min="0" placeholder="最低价" class="filter-item" :controls="false" @change="handleSearch" />
      <span class="price-sep">-</span>
      <el-input-number v-model="filters.max_price" :min="0" placeholder="最高价" class="filter-item" :controls="false" @change="handleSearch" />
      <el-select v-model="filters.sort" class="filter-item" @change="handleSearch">
        <el-option v-for="opt in sortOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
      </el-select>
      <el-button type="primary" @click="handleSearch">搜索</el-button>
      <el-button @click="resetFilters">重置</el-button>
    </div>

    <div v-loading="loading" class="product-grid">
      <div v-for="p in products" :key="p.id" class="product-card">
        <div class="product-name">{{ p.name }}</div>
        <div class="product-price">
          ¥{{ formatPrice(p.price_min) }}
          <span v-if="p.price_max && p.price_max !== p.price_min"> - ¥{{ formatPrice(p.price_max) }}</span>
        </div>
        <div v-if="p.category" class="product-category">{{ p.category.name }}</div>
      </div>

      <div v-if="products.length === 0" class="empty-state">
        暂无商品
      </div>
    </div>

    <div v-if="lastPage > 1" class="pagination">
      <el-pagination
        :current-page="currentPage"
        :page-count="lastPage"
        layout="prev, pager, next"
        @current-change="handlePageChange"
      />
    </div>
  </div>
</template>

<style scoped>
.product-list-view {
  max-width: 960px;
  margin: 20px auto;
  padding: 20px;
}
.filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 20px;
  align-items: center;
}
.search-input {
  width: 200px;
}
.filter-item {
  width: 140px;
}
.price-sep {
  color: #909399;
}
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
}
.product-card {
  background: #fff;
  border-radius: 8px;
  padding: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.product-name {
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 8px;
}
.product-price {
  color: #f56c6c;
  font-weight: 600;
  font-size: 15px;
  margin-bottom: 4px;
}
.product-category {
  font-size: 12px;
  color: #909399;
}
.empty-state {
  grid-column: 1 / -1;
  padding: 40px;
  text-align: center;
  color: #909399;
  background: #f5f7fa;
  border-radius: 8px;
}
.pagination {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}
</style>
