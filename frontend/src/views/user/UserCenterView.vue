<script setup lang="ts">
import { computed } from 'vue'
import { useUserStore } from '@/stores/user'

const userStore = useUserStore()
const user = computed(() => userStore.userInfo)
</script>

<template>
  <div class="user-center-view page-container">
    <h2 class="page-title">个人中心</h2>
    <div v-if="user">
      <p>手机号: {{ user.phone }}</p>
      <p>昵称: {{ user.nickname || '-' }}</p>
      <p><router-link to="/notifications">消息通知</router-link></p>
      <p><router-link to="/addresses">收货地址</router-link></p>
      <p><router-link to="/favorites">我的收藏</router-link></p>
      <p><router-link to="/my-reviews">我的评价</router-link></p>
    </div>
  </div>
</template>
