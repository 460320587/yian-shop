<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getHome, getCategories } from '@/api/portal'

const banners = ref<any[]>([])
const hotProducts = ref<any[]>([])
const newArrivals = ref<any[]>([])
const categories = ref<any[]>([])

onMounted(async () => {
  try {
    const [homeRes, catRes] = await Promise.all([getHome(), getCategories()])
    banners.value = homeRes.banners || []
    hotProducts.value = homeRes.hot_products || []
    newArrivals.value = homeRes.new_arrivals || []
    categories.value = catRes || []
  } catch (e) {
    console.error('home load failed', e)
  }
})
</script>

<template>
  <div class="home-view">
    <div v-for="banner in banners" :key="banner.id" class="banner">{{ banner.title }}</div>
    <div v-for="cat in categories" :key="cat.id" class="category">{{ cat.name }}</div>
    <div class="section">
      <h3>热销商品</h3>
      <div v-for="p in hotProducts" :key="p.id" class="product">{{ p.name }}</div>
    </div>
    <div class="section">
      <h3>新品上架</h3>
      <div v-for="p in newArrivals" :key="p.id" class="product">{{ p.name }}</div>
    </div>
  </div>
</template>
