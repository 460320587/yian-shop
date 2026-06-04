<script setup lang="ts">
import { ElMessage } from 'element-plus'
function showPlaceholder() { ElMessage.info('商品管理 Admin API 开发中') }
</script>

<template>
  <div class="product-management">
    <div class="page-header"><h3>商品管理</h3><el-button type="primary" @click="showPlaceholder">新建商品</el-button></div>
    <el-empty description="商品管理 Admin API 开发中" />
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h3 { font-size: 18px; font-weight: 600; margin: 0; }
</style>
