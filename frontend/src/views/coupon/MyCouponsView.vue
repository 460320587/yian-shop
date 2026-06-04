<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getMyCoupons } from '@/api/coupon'

const coupons = ref<any[]>([])
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const res = await getMyCoupons()
    coupons.value = res.data
  } catch (e) { console.error(e) }
  finally { loading.value = false }
})
</script>

<template>
  <div class="my-coupons-view page-container">
    <h2 class="page-title">我的优惠券</h2>
    <div v-loading="loading">
      <div v-for="c in coupons" :key="c.id" class="coupon-card">{{ c.coupon.name }}</div>
    </div>
  </div>
</template>
