<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import {
  getAdminReviews,
  getAdminReviewDetail,
  replyAdminReview,
  toggleAdminReviewShow,
  deleteAdminReview,
} from '@/api/admin'
import { ElMessage, ElMessageBox } from 'element-plus'

const reviews = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const keyword = ref('')
const showFilter = ref<boolean | null>(null)
const detailDialogVisible = ref(false)
const replyDialogVisible = ref(false)
const currentReview = ref<any>(null)
const replyForm = reactive({ reply: '' })

function ratingText(rating: number): string {
  const map: Record<number, string> = { 1: '1星', 2: '2星', 3: '3星', 4: '4星', 5: '5星' }
  return map[rating] ?? '未知'
}

function ratingType(rating: number): string {
  if (rating >= 4) return 'success'
  if (rating >= 3) return 'warning'
  return 'danger'
}

async function loadReviews() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (keyword.value) params.keyword = keyword.value
    if (showFilter.value !== null && showFilter.value !== undefined) {
      params.is_show = showFilter.value
    }
    const res = await getAdminReviews(params)
    reviews.value = res.data
    total.value = res.total
  } catch (e) {
    console.error(e)
    ElMessage.error('加载评价失败')
  } finally {
    loading.value = false
  }
}

function onFilter() {
  currentPage.value = 1
  loadReviews()
}

async function openDetail(row: any) {
  try {
    const res = await getAdminReviewDetail(row.id)
    currentReview.value = res
    detailDialogVisible.value = true
  } catch (e) {
    console.error(e)
    ElMessage.error('加载评价详情失败')
  }
}

function openReply(row: any) {
  currentReview.value = row
  replyForm.reply = row.reply || ''
  replyDialogVisible.value = true
}

async function handleReply() {
  if (!currentReview.value) return
  if (!replyForm.reply.trim()) {
    ElMessage.warning('请输入回复内容')
    return
  }
  try {
    await replyAdminReview(currentReview.value.id, { reply: replyForm.reply.trim() })
    ElMessage.success('回复成功')
    replyDialogVisible.value = false
    loadReviews()
  } catch (e) {
    console.error(e)
  }
}

async function handleToggleShow(row: any) {
  try {
    await toggleAdminReviewShow(row.id)
    ElMessage.success('状态已更新')
    loadReviews()
  } catch (e) {
    console.error(e)
  }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm('确定删除该评价吗？', '提示', { type: 'warning' })
    await deleteAdminReview(row.id)
    ElMessage.success('删除成功')
    loadReviews()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

onMounted(loadReviews)

defineExpose({
  reviews,
  total,
  currentPage,
  pageSize,
  loading,
  keyword,
  showFilter,
  detailDialogVisible,
  replyDialogVisible,
  currentReview,
  replyForm,
  loadReviews,
  onFilter,
  openDetail,
  openReply,
  handleReply,
  handleToggleShow,
  handleDelete,
})
</script>

<template>
  <div class="review-management">
    <div class="page-header">
      <h3>评价管理</h3>
      <div class="filter-bar">
        <el-select v-model="showFilter" placeholder="显隐状态" clearable style="width: 140px" @change="onFilter">
          <el-option :value="true" label="显示中" />
          <el-option :value="false" label="已隐藏" />
        </el-select>
        <el-input v-model="keyword" placeholder="搜索评价内容" clearable style="width: 200px" @keyup.enter="onFilter" />
        <el-button type="primary" @click="onFilter">查询</el-button>
      </div>
    </div>

    <el-table :data="reviews" v-loading="loading" stripe>
      <el-table-column label="客户" width="120">
        <template #default="scope">
          <span v-if="scope?.row?.customer">{{ scope.row.customer.nickname || '-' }}</span>
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column label="商品" width="160">
        <template #default="scope">
          <div v-if="scope?.row?.product" class="product-cell">
            <img v-if="scope.row.product.thumbnail" :src="scope.row.product.thumbnail" class="product-thumb" />
            <span class="product-name">{{ scope.row.product.name }}</span>
          </div>
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column label="评分" width="80">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="ratingType(scope.row.rating)" size="small">
            {{ ratingText(scope.row.rating) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="content" label="评价内容" show-overflow-tooltip />
      <el-table-column label="回复" width="90">
        <template #default="scope">
          <el-tag v-if="scope?.row?.reply" type="success" size="small">已回复</el-tag>
          <el-tag v-else type="info" size="small">未回复</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="状态" width="90">
        <template #default="scope">
          <el-tag v-if="scope?.row?.is_show" type="success" size="small">显示</el-tag>
          <el-tag v-else type="info" size="small">隐藏</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="160" />
      <el-table-column label="操作" width="220">
        <template #default="scope">
          <el-button v-if="scope?.row" link type="primary" @click="openDetail(scope.row)">详情</el-button>
          <el-button v-if="scope?.row" link type="success" @click="openReply(scope.row)">回复</el-button>
          <el-button v-if="scope?.row" link type="warning" @click="handleToggleShow(scope.row)">
            {{ scope.row.is_show ? '隐藏' : '显示' }}
          </el-button>
          <el-button v-if="scope?.row" link type="danger" @click="handleDelete(scope.row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      :total="total"
      layout="total, prev, pager, next"
      class="pagination"
      @change="loadReviews"
    />

    <!-- 详情对话框 -->
    <el-dialog v-model="detailDialogVisible" title="评价详情" width="560px">
      <div v-if="currentReview" class="detail-content">
        <div class="detail-row"><span class="label">客户：</span>{{ currentReview.customer?.nickname ?? '-' }}</div>
        <div class="detail-row"><span class="label">商品：</span>{{ currentReview.product?.name ?? '-' }}</div>
        <div class="detail-row"><span class="label">订单号：</span>{{ currentReview.order_id ?? '-' }}</div>
        <div class="detail-row"><span class="label">评分：</span>
          <el-tag :type="ratingType(currentReview.rating)" size="small">{{ ratingText(currentReview.rating) }}</el-tag>
        </div>
        <div class="detail-row"><span class="label">评价内容：</span>{{ currentReview.content }}</div>
        <div v-if="currentReview.images?.length" class="detail-row">
          <span class="label">图片：</span>
          <div class="image-list">
            <img v-for="(img, idx) in currentReview.images" :key="idx" :src="img" class="review-image" />
          </div>
        </div>
        <div class="detail-row"><span class="label">商家回复：</span>{{ currentReview.reply ?? '-' }}</div>
        <div class="detail-row"><span class="label">回复时间：</span>{{ currentReview.reply_at ?? '-' }}</div>
        <div class="detail-row"><span class="label">状态：</span>
          <el-tag :type="currentReview.is_show ? 'success' : 'info'" size="small">{{ currentReview.is_show ? '显示' : '隐藏' }}</el-tag>
        </div>
        <div class="detail-row"><span class="label">创建时间：</span>{{ currentReview.created_at }}</div>
      </div>
    </el-dialog>

    <!-- 回复对话框 -->
    <el-dialog v-model="replyDialogVisible" title="回复评价" width="480px">
      <el-form :model="replyForm" label-width="80px">
        <el-form-item label="回复内容">
          <el-input v-model="replyForm.reply" type="textarea" :rows="4" placeholder="请输入回复内容" maxlength="500" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="replyDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleReply">确认</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.page-header h3 {
  font-size: 18px;
  font-weight: 600;
  margin: 0;
}
.filter-bar {
  display: flex;
  gap: 12px;
}
.pagination {
  margin-top: 20px;
  justify-content: flex-end;
}
.detail-content {
  padding: 8px 0;
}
.detail-row {
  display: flex;
  margin-bottom: 12px;
  font-size: 14px;
}
.detail-row .label {
  color: #606266;
  width: 80px;
  flex-shrink: 0;
}
.product-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}
.product-thumb {
  width: 40px;
  height: 40px;
  border-radius: 4px;
  object-fit: cover;
}
.product-name {
  font-size: 13px;
  line-height: 1.4;
}
.image-list {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.review-image {
  width: 80px;
  height: 80px;
  border-radius: 4px;
  object-fit: cover;
}
</style>
