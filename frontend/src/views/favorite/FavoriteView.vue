<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getFavorites, removeFavorite, type FavoriteItem } from '@/api/favorite'

const router = useRouter()
const favorites = ref<FavoriteItem[]>([])
const loading = ref(false)

async function loadFavorites() {
  loading.value = true
  try {
    const res = await getFavorites({ page: 1, per_page: 50 })
    favorites.value = res.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function goToProduct(productId: number) {
  router.push('/product/' + productId)
}

async function handleRemove(item: FavoriteItem) {
  try {
    await ElMessageBox.confirm('确定取消收藏该商品吗？', '提示', { type: 'warning' })
    await removeFavorite(item.id)
    ElMessage.success('已取消收藏')
    await loadFavorites()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

function formatPrice(min: number | null, max: number | null): string {
  if (min === null && max === null) return '暂无报价'
  if (min === max) return '¥' + (min ? (min / 100).toFixed(2) : '0.00')
  return '¥' + (min ? (min / 100).toFixed(2) : '0.00') + ' ~ ¥' + (max ? (max / 100).toFixed(2) : '0.00')
}

onMounted(() => {
  loadFavorites()
})

defineExpose({
  favorites,
  loading,
  goToProduct,
  handleRemove,
  loadFavorites,
})
</script>

<template>
  <div class="favorite-view page-container">
    <h2 class="page-title">我的收藏</h2>

    <div v-loading="loading" class="favorite-list">
      <div v-if="!loading && favorites.length === 0" class="empty-state">
        暂无收藏商品
      </div>

      <div
        v-for="item in favorites"
        :key="item.id"
        class="favorite-item"
      >
        <div class="favorite-image" @click="goToProduct(item.product_id)">
          <img v-if="item.product.thumbnail" :src="item.product.thumbnail" :alt="item.product.name" />
          <div v-else class="image-placeholder">暂无图片</div>
        </div>
        <div class="favorite-info" @click="goToProduct(item.product_id)">
          <div class="product-name">{{ item.product.name }}</div>
          <div v-if="item.product.category" class="product-category">{{ item.product.category.name }}</div>
          <div class="product-price">{{ formatPrice(item.product.price_min, item.product.price_max) }}</div>
          <div v-if="item.remark" class="product-remark">备注：{{ item.remark }}</div>
        </div>
        <div class="favorite-actions">
          <el-button link type="danger" @click="handleRemove(item)">取消收藏</el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.favorite-view {
  padding: 20px;
}
.favorite-list {
  min-height: 200px;
}
.favorite-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border-bottom: 1px solid #ebeef5;
  transition: background 0.2s;
}
.favorite-item:hover {
  background: #f5f7fa;
}
.favorite-image {
  width: 100px;
  height: 100px;
  flex-shrink: 0;
  cursor: pointer;
  border-radius: 4px;
  overflow: hidden;
  background: #f5f7fa;
  display: flex;
  align-items: center;
  justify-content: center;
}
.favorite-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.image-placeholder {
  color: #909399;
  font-size: 12px;
}
.favorite-info {
  flex: 1;
  cursor: pointer;
}
.product-name {
  font-weight: 600;
  font-size: 15px;
  color: #303133;
  margin-bottom: 4px;
}
.product-category {
  color: #909399;
  font-size: 13px;
  margin-bottom: 4px;
}
.product-price {
  color: #f56c6c;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 4px;
}
.product-remark {
  color: #606266;
  font-size: 13px;
}
.favorite-actions {
  flex-shrink: 0;
}
</style>
