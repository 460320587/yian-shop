<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getCart } from '@/api/cart'

const cartItems = ref<any[]>([])
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const res = await getCart()
    cartItems.value = res.items
  } catch (e) { console.error(e) }
  finally { loading.value = false }
})
</script>

<template>
  <div class="cart-view page-container">
    <h2 class="page-title">购物车</h2>
    <div v-loading="loading">
      <div v-for="item in cartItems" :key="item.id" class="cart-item">{{ item.product_name }} x{{ item.quantity }}</div>
    </div>
  </div>
</template>
