<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getProductDetail } from '@/api/product'
import { getFavorites, addFavorite, removeFavorite } from '@/api/favorite'

const route = useRoute()
const product = ref<any>(null)
const loading = ref(false)
const isFavorited = ref(false)
const favoriteId = ref<number | null>(null)

async function loadProduct() {
  loading.value = true
  try {
    product.value = await getProductDetail(Number(route.params.id))
    await checkFavoriteStatus()
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
  toggleFavorite,
  checkFavoriteStatus,
})
</script>

<template>
  <div class="product-detail-view page-container">
    <div v-loading="loading">
      <div v-if="product" class="product-header">
        <h2>{{ product.name }}</h2>
        <el-button
          :type="isFavorited ? 'danger' : 'default'"
          :icon="isFavorited ? 'StarFilled' : 'Star'"
          @click="toggleFavorite"
        >
          {{ isFavorited ? '已收藏' : '收藏' }}
        </el-button>
      </div>
      <p v-if="product">{{ product.description }}</p>
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
</style>
