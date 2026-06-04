<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getProducts } from '@/api/product'

const products = ref<any[]>([])
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const res = await getProducts({ page: 1 })
    products.value = res.data
  } catch (e) { console.error(e) }
  finally { loading.value = false }
})
</script>

<template>
  <div class="product-list-view page-container">
    <h2 class="page-title">全部商品</h2>
    <div v-loading="loading" class="product-grid">
      <div v-for="p in products" :key="p.id" class="product-card">{{ p.name }}</div>
    </div>
  </div>
</template>
