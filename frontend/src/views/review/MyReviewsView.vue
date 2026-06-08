<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getMyReviews, type ReviewItem } from '@/api/review'

const reviews = ref<ReviewItem[]>([])
const loading = ref(false)

async function loadReviews() {
  loading.value = true
  try {
    const res = await getMyReviews({ page: 1, per_page: 50 })
    reviews.value = res.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadReviews()
})

defineExpose({
  reviews,
  loading,
  loadReviews,
})
</script>

<template>
  <div class="my-reviews-view page-container">
    <h2 class="page-title">我的评价</h2>

    <div v-loading="loading" class="review-list">
      <div v-if="!loading && reviews.length === 0" class="empty-state">
        暂无评价记录
      </div>

      <div v-for="review in reviews" :key="review.id" class="review-item">
        <div class="review-product">
          <img v-if="review.product?.thumbnail" :src="review.product.thumbnail" class="product-thumb" />
          <div v-else class="thumb-placeholder">暂无图片</div>
          <span class="product-name">{{ review.product?.name || '未知商品' }}</span>
        </div>
        <div class="review-body">
          <el-rate v-model="review.rating" disabled />
          <div class="review-content">{{ review.content }}</div>
          <div v-if="review.images?.length" class="review-images">
            <img v-for="(img, idx) in review.images" :key="idx" :src="img" class="review-img" />
          </div>
          <div class="review-date">{{ review.created_at }}</div>
          <div v-if="review.reply" class="review-reply">
            <strong>商家回复：</strong>{{ review.reply }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.my-reviews-view {
  padding: 20px;
}
.review-list {
  min-height: 200px;
}
.empty-state {
  text-align: center;
  padding: 40px;
  color: #909399;
}
.review-item {
  display: flex;
  gap: 16px;
  padding: 16px;
  border-bottom: 1px solid #ebeef5;
}
.review-product {
  width: 100px;
  flex-shrink: 0;
  text-align: center;
}
.product-thumb {
  width: 80px;
  height: 80px;
  border-radius: 4px;
  object-fit: cover;
}
.thumb-placeholder {
  width: 80px;
  height: 80px;
  border-radius: 4px;
  background: #f5f7fa;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #909399;
  font-size: 12px;
  margin: 0 auto;
}
.product-name {
  display: block;
  margin-top: 8px;
  font-size: 13px;
  color: #606266;
}
.review-body {
  flex: 1;
}
.review-content {
  margin-top: 8px;
  color: #303133;
  line-height: 1.6;
}
.review-date {
  margin-top: 4px;
  color: #909399;
  font-size: 13px;
}
.review-images {
  display: flex;
  gap: 8px;
  margin-top: 10px;
  flex-wrap: wrap;
}
.review-img {
  width: 80px;
  height: 80px;
  border-radius: 4px;
  object-fit: cover;
  cursor: pointer;
  border: 1px solid #ebeef5;
}
.review-reply {
  margin-top: 10px;
  background: #f5f7fa;
  padding: 10px 12px;
  border-radius: 4px;
  color: #606266;
  font-size: 14px;
}
</style>
