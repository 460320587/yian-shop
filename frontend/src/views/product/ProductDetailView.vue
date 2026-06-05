<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getProductDetail } from '@/api/product'
import { getFavorites, addFavorite, removeFavorite } from '@/api/favorite'
import { getProductReviews, type ReviewItem } from '@/api/review'

const route = useRoute()
const product = ref<any>(null)
const loading = ref(false)
const isFavorited = ref(false)
const favoriteId = ref<number | null>(null)
const reviews = ref<ReviewItem[]>([])
const reviewTotal = ref(0)

async function loadProduct() {
  loading.value = true
  try {
    product.value = await getProductDetail(Number(route.params.id))
    await checkFavoriteStatus()
    await loadReviews()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function checkFavoriteStatus() {
  try {
    const res = await getFavorites({ page: 1, per_page: 100 })
    const found = res.data.find((item) => item.product_id === Number(route.params.id))
    if (found) {
      isFavorited.value = true
      favoriteId.value = found.id
    } else {
      isFavorited.value = false
      favoriteId.value = null
    }
  } catch (e) {
    console.error(e)
  }
}

async function loadReviews() {
  try {
    const res = await getProductReviews(Number(route.params.id), { page: 1, per_page: 10 })
    reviews.value = res.data
    reviewTotal.value = res.total
  } catch (e) {
    console.error(e)
  }
}

async function toggleFavorite() {
  if (!product.value) return
  try {
    if (isFavorited.value && favoriteId.value) {
      await removeFavorite(favoriteId.value)
      ElMessage.success('已取消收藏')
      isFavorited.value = false
      favoriteId.value = null
    } else {
      const res = await addFavorite({ product_id: product.value.id })
      ElMessage.success('收藏成功')
      isFavorited.value = true
      favoriteId.value = res.id
    }
  } catch (e) {
    console.error(e)
  }
}

onMounted(() => {
  loadProduct()
})

defineExpose({
  product,
  loading,
  isFavorited,
  favoriteId,
  reviews,
  reviewTotal,
  toggleFavorite,
  checkFavoriteStatus,
  loadReviews,
})
</script>

<template>
  <div class="product-detail-view page-container">
    <div v-loading="loading">
      <div v-if="product" class="product-header">
        <h2>{{ product.name }}</h2>
        <el-button
          :type="isFavorited ? 'danger' : 'default'"
          @click="toggleFavorite"
        >
          {{ isFavorited ? '已收藏' : '收藏' }}
        </el-button>
      </div>
      <p v-if="product">{{ product.description }}</p>

      <div v-if="reviews.length > 0" class="review-section">
        <h3>用户评价（{{ reviewTotal }}）</h3>
        <div v-for="review in reviews" :key="review.id" class="review-item">
          <div class="review-header">
            <span class="reviewer">{{ review.customer?.nickname || '匿名用户' }}</span>
            <el-rate v-model="review.rating" disabled />
            <span class="review-date">{{ review.created_at }}</span>
          </div>
          <div class="review-content">{{ review.content }}</div>
          <div v-if="review.reply" class="review-reply">
            <strong>商家回复：</strong>{{ review.reply }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.product-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.review-section {
  margin-top: 32px;
  padding-top: 20px;
  border-top: 1px solid #ebeef5;
}
.review-section h3 {
  margin-bottom: 16px;
  font-size: 18px;
}
.review-item {
  padding: 16px 0;
  border-bottom: 1px solid #ebeef5;
}
.review-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}
.reviewer {
  font-weight: 600;
  color: #303133;
}
.review-date {
  color: #909399;
  font-size: 13px;
}
.review-content {
  color: #606266;
  line-height: 1.6;
  margin-bottom: 8px;
}
.review-reply {
  background: #f5f7fa;
  padding: 10px 12px;
  border-radius: 4px;
  color: #606266;
  font-size: 14px;
}
</style>
