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
    </div>
  </div>
</template>
