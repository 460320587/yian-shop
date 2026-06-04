<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getCoupons } from '@/api/coupon'

const coupons = ref<any[]>([])
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const res = await getCoupons()
    coupons.value = res.data
  } catch (e) { console.error(e) }
  finally { loading.value = false }
})
</script>

<template>
  <div class="coupon-view page-container">
    <h2 class="page-title">领券中心</h2>
    <div v-loading="loading">
      <div v-for="c in coupons" :key="c.id" class="coupon-card">{{ c.name }}</div>
    </div>
  </div>
</template>
