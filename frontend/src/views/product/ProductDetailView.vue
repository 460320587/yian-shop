<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { getProductDetail } from '@/api/product'

const route = useRoute()
const product = ref<any>(null)
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    product.value = await getProductDetail(Number(route.params.id))
  } catch (e) { console.error(e) }
  finally { loading.value = false }
})
</script>

<template>
  <div class="product-detail-view page-container">
    <div v-loading="loading">
      <h2 v-if="product">{{ product.name }}</h2>
      <p v-if="product">{{ product.description }}</p>
    </div>
  </div>
</template>
