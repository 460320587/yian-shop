<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getAfterSales, cancelAfterSale, type AfterSale } from '@/api/aftersale'

const router = useRouter()
const afterSales = ref<AfterSale[]>([])
const loading = ref(false)

const typeMap: Record<number, string> = {
  1: '退货',
  2: '换货',
  3: '退款',
  4: '补发',
  5: '其他',
}

const statusMap: Record<number, string> = {
  1: '待审核',
  2: '审核通过',
  3: '审核不通过',
  4: '已完成',
  5: '已关闭',
  6: '已取消',
}

const statusTypeMap: Record<number, string> = {
  1: 'warning',
  2: 'success',
  3: 'danger',
  4: 'info',
  5: 'info',
  6: 'info',
}

async function loadAfterSales() {
  loading.value = true
  try {
    const res = await getAfterSales({ page: 1, per_page: 50 })
    afterSales.value = res.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function typeName(type: number): string {
  return typeMap[type] || '其他'
}

function statusName(status: number): string {
  return statusMap[status] || '未知'
}

function canCancel(item: AfterSale): boolean {
  return item.status === 1
}

async function handleCancel(item: AfterSale) {
  if (!canCancel(item)) {
    ElMessage.warning('当前状态不可取消')
    return
  }
  try {
    await ElMessageBox.confirm('确定取消该售后申请吗？', '提示', { type: 'warning' })
    await cancelAfterSale(item.id)
    ElMessage.success('已取消')
    await loadAfterSales()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

function goToApply() {
  router.push('/after-sale-apply')
}

onMounted(() => {
  loadAfterSales()
})

defineExpose({
  afterSales,
  loading,
  typeName,
  statusName,
  canCancel,
  handleCancel,
  goToApply,
  loadAfterSales,
})
</script>

<template>
  <div class="after-sale-list-view page-container">
    <div class="page-header">
      <h2 class="page-title">我的售后</h2>
      <el-button type="primary" @click="goToApply">+ 申请售后</el-button>
    </div>

    <div v-loading="loading" class="after-sale-list">
      <div v-if="!loading && afterSales.length === 0" class="empty-state">
        暂无售后记录
      </div>

      <div v-for="item in afterSales" :key="item.id" class="after-sale-item">
        <div class="item-header">
          <span class="item-no">{{ item.after_sale_no }}</span>
          <span class="item-order">关联订单：{{ item.order_no }}</span>
          <el-tag :type="statusTypeMap[item.status] as any" size="small">{{ statusName(item.status) }}</el-tag>
        </div>
        <div class="item-body">
          <div class="item-type">售后类型：{{ typeName(item.type) }}</div>
          <div class="item-reason">原因：{{ item.reason }}</div>
          <div v-if="item.description" class="item-desc">描述：{{ item.description }}</div>
          <div class="item-products">
            <span v-for="(prod, idx) in item.items" :key="idx" class="product-tag">
              {{ prod.product_name }} x{{ prod.quantity }}
            </span>
          </div>
        </div>
        <div class="item-actions">
          <el-button v-if="canCancel(item)" link type="danger" @click="handleCancel(item)">取消申请</el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.after-sale-list-view {
  padding: 20px;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.after-sale-list {
  min-height: 200px;
}
.empty-state {
  text-align: center;
  padding: 40px;
  color: #909399;
}
.after-sale-item {
  padding: 16px;
  border-bottom: 1px solid #ebeef5;
  transition: background 0.2s;
}
.after-sale-item:hover {
  background: #f5f7fa;
}
.item-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}
.item-no {
  font-weight: 600;
  font-size: 15px;
}
.item-order {
  color: #909399;
  font-size: 13px;
}
.item-body {
  color: #606266;
  font-size: 14px;
  line-height: 1.6;
}
.item-type {
  margin-bottom: 4px;
}
.item-reason {
  margin-bottom: 4px;
}
.item-desc {
  color: #909399;
  margin-bottom: 4px;
}
.item-products {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 4px;
}
.product-tag {
  background: #ecf5ff;
  color: #409eff;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
}
.item-actions {
  margin-top: 8px;
}
</style>
