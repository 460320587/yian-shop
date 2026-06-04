<script setup lang="ts">
import { ref } from 'vue'
import { createOrder } from '@/api/order'
import { useRouter } from 'vue-router'

const router = useRouter()
const loading = ref(false)

async function submitOrder() {
  loading.value = true
  try {
    const res = await createOrder({ address_id: 1, items: [{ product_id: 1, quantity: 1 }] })
    router.push('/orders')
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}
</script>

<template>
  <div class="order-create-view page-container">
    <h2 class="page-title">确认订单</h2>
    <el-button type="primary" :loading="loading" @click="submitOrder">提交订单</el-button>
  </div>
</template>
