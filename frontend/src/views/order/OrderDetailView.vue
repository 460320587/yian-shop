<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getOrderDetail, cancelOrder, getOrderProductionSchedule, getOrderFiles, getOrderInkChecks, getOrderStatusLogs, uploadOrderFile, deleteOrderFile } from '@/api/order'
import type { ProductionSchedule, OrderFile, InkCoverageCheck, OrderStatusLog } from '@/api/order'

const route = useRoute()
const router = useRouter()

const order = ref<any>(null)
const loading = ref(false)
const schedules = ref<ProductionSchedule[]>([])
const schedulesLoading = ref(false)
const orderFiles = ref<OrderFile[]>([])
const filesLoading = ref(false)
const inkChecks = ref<InkCoverageCheck[]>([])
const inkChecksLoading = ref(false)
const statusLogs = ref<OrderStatusLog[]>([])
const logsLoading = ref(false)
const uploading = ref(false)

const hasProductionSchedule = computed(() => schedules.value.length > 0)
const hasOrderFiles = computed(() => orderFiles.value.length > 0)
const hasInkChecks = computed(() => inkChecks.value.length > 0)
const hasStatusLogs = computed(() => statusLogs.value.length > 0)
const hasDelivery = computed(() => !!order.value?.delivery)
const canUploadFile = computed(() => {
  if (!order.value) return false
  return [0, 1, 11, 12].includes(order.value.status)
})

const canPay = computed(() => {
  if (!order.value) return false
  return [1, 11].includes(order.value.status)
})

const canCancel = computed(() => {
  if (!order.value) return false
  return [0, 1, 11].includes(order.value.status)
})

const canReview = computed(() => {
  if (!order.value) return false
  return order.value.status === 60
})

const canAfterSale = computed(() => {
  if (!order.value) return false
  return [15, 17, 20, 54, 55, 60].includes(order.value.status)
})

const canTrackLogistics = computed(() => {
  if (!order.value) return false
  return [20, 54, 55, 60].includes(order.value.status)
})

async function loadOrder() {
  const id = Number(route.params.id)
  if (!id) {
    ElMessage.error('订单ID无效')
    return
  }
  loading.value = true
  try {
    const res = await getOrderDetail(id)
    order.value = res
    await loadProductionSchedule(id)
    await loadOrderFiles(id)
    await loadInkChecks(id)
    await loadStatusLogs(id)
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function loadOrderFiles(orderId: number) {
  filesLoading.value = true
  try {
    const res = await getOrderFiles(orderId)
    orderFiles.value = res.data || []
  } catch (e) {
    console.error(e)
  } finally {
    filesLoading.value = false
  }
}

async function loadInkChecks(orderId: number) {
  inkChecksLoading.value = true
  try {
    const res = await getOrderInkChecks(orderId)
    inkChecks.value = res.data || []
  } catch (e) {
    console.error(e)
  } finally {
    inkChecksLoading.value = false
  }
}

async function loadStatusLogs(orderId: number) {
  logsLoading.value = true
  try {
    const res = await getOrderStatusLogs(orderId)
    statusLogs.value = res.data || []
  } catch (e) {
    console.error(e)
  } finally {
    logsLoading.value = false
  }
}

function inkCheckResultText(result: number): string {
  const map: Record<number, string> = {
    0: '未通过',
    1: '通过',
  }
  return map[result] ?? '未知'
}

function inkCheckResultType(result: number): string {
  const map: Record<number, string> = {
    0: 'danger',
    1: 'success',
  }
  return map[result] ?? 'info'
}

async function loadProductionSchedule(orderId: number) {
  schedulesLoading.value = true
  try {
    const res = await getOrderProductionSchedule(orderId)
    schedules.value = res.data || []
  } catch (e) {
    console.error(e)
  } finally {
    schedulesLoading.value = false
  }
}

function scheduleStatusText(status: number): string {
  const map: Record<number, string> = {
    0: '待排',
    1: '已排',
    2: '生产中',
    3: '已完成',
    4: '延期',
  }
  return map[status] ?? '未知'
}

function scheduleStatusType(status: number): string {
  const map: Record<number, string> = {
    0: 'info',
    1: 'primary',
    2: 'warning',
    3: 'success',
    4: 'danger',
  }
  return map[status] ?? 'info'
}

function formatLogTime(time: string): string {
  if (!time) return '-'
  return time.replace('T', ' ').replace(/\.\d+Z$/, '')
}

function goBack() {
  router.back()
}

function handlePay() {
  if (!order.value) return
  const amount = Math.floor(order.value.total_amount)
  router.push(`/payment?orderNo=${order.value.order_no}&amount=${amount}`)
}

async function handleCancel() {
  if (!order.value) return
  loading.value = true
  try {
    await cancelOrder(order.value.order_no)
    ElMessage.success('订单已取消')
    await loadOrder()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function goToReview() {
  if (!order.value) return
  router.push(`/review?orderId=${order.value.id}`)
}

function goToAfterSale() {
  if (!order.value) return
  router.push(`/after-sale-apply?orderId=${order.value.id}&orderNo=${order.value.order_no}`)
}

function goToLogistics() {
  if (!order.value) return
  router.push(`/order/${order.value.id}/track`)
}

function formatTrackTime(time: string): string {
  if (!time) return '-'
  const d = new Date(time)
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  const hh = String(d.getHours()).padStart(2, '0')
  const mi = String(d.getMinutes()).padStart(2, '0')
  return `${mm}-${dd} ${hh}:${mi}`
}

async function handleFileUpload(event: Event) {
  const target = event.target as HTMLInputElement
  const files = target.files
  if (!files || files.length === 0 || !order.value) return

  const file = files[0]
  const maxSize = 50 * 1024 * 1024
  if (file.size > maxSize) {
    ElMessage.error('文件大小不能超过 50MB')
    target.value = ''
    return
  }

  const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'tiff', 'tif', 'ai', 'psd', 'eps']
  const ext = file.name.split('.').pop()?.toLowerCase() || ''
  if (!allowedExtensions.includes(ext)) {
    ElMessage.error('不支持的文件格式，仅支持 PDF、JPG、PNG、TIFF、AI、PSD、EPS')
    target.value = ''
    return
  }

  const formData = new FormData()
  formData.append('file', file)
  formData.append('file_name', file.name)

  uploading.value = true
  try {
    await uploadOrderFile(order.value.id, formData)
    ElMessage.success('上传成功')
    await loadOrderFiles(order.value.id)
  } catch (e) {
    console.error(e)
  } finally {
    uploading.value = false
    target.value = ''
  }
}

async function handleDeleteFile(fileId: number) {
  if (!order.value) return
  try {
    await deleteOrderFile(order.value.id, fileId)
    ElMessage.success('文件已删除')
    await loadOrderFiles(order.value.id)
  } catch (e) {
    console.error(e)
  }
}

onMounted(() => {
  loadOrder()
})

defineExpose({
  order,
  loading,
  statusLogs,
  canPay,
  canCancel,
  canReview,
  canAfterSale,
  canTrackLogistics,
  hasDelivery,
  loadOrder,
  goBack,
  handlePay,
  handleCancel,
  goToLogistics,
  formatTrackTime,
})
</script>

<template>
  <div class="order-detail-view page-container">
    <div class="header-row">
      <el-button data-testid="back-btn" size="small" @click="goBack">返回</el-button>
      <h2 class="page-title">订单详情</h2>
    </div>

    <div v-if="loading && !order" class="loading-text">加载中...</div>

    <div v-if="order" v-loading="loading" class="detail-panel">
      <div class="info-section">
        <div class="info-row">
          <span class="label">订单号：</span>
          <span class="order-no">{{ order.order_no }}</span>
        </div>
        <div class="info-row">
          <span class="label">订单状态：</span>
          <span class="order-status">{{ order.customer_status }}</span>
        </div>
        <div class="info-row">
          <span class="label">下单时间：</span>
          <span>{{ order.created_at }}</span>
        </div>
        <div v-if="order.submitted_at" class="info-row">
          <span class="label">提交时间：</span>
          <span class="submitted-at">{{ order.submitted_at }}</span>
        </div>
        <div v-if="order.paid_at" class="info-row">
          <span class="label">支付时间：</span>
          <span class="paid-at">{{ order.paid_at }}</span>
        </div>
        <div class="info-row">
          <span class="label">订单金额：</span>
          <span class="order-amount">¥{{ order.total_amount?.toFixed ? order.total_amount.toFixed(2) : order.total_amount }}</span>
        </div>
        <div v-if="order.deposit_sum" class="info-row">
          <span class="label">定金：</span>
          <span class="deposit-sum">¥{{ order.deposit_sum?.toFixed ? order.deposit_sum.toFixed(2) : order.deposit_sum }}</span>
        </div>
        <div v-if="order.discount_sum" class="info-row">
          <span class="label">优惠金额：</span>
          <span class="discount-sum">¥{{ order.discount_sum?.toFixed ? order.discount_sum.toFixed(2) : order.discount_sum }}</span>
        </div>
        <div v-if="order.express_company" class="info-row">
          <span class="label">快递公司：</span>
          <span class="express-company">{{ order.express_company }}</span>
        </div>
        <div v-if="order.delivery_type" class="info-row">
          <span class="label">配送方式：</span>
          <span class="delivery-type">{{ order.delivery_type }}</span>
        </div>
        <div v-if="order.remark" class="info-row">
          <span class="label">订单备注：</span>
          <span class="remark">{{ order.remark }}</span>
        </div>
      </div>

      <div v-if="hasDelivery" class="delivery-section">
        <div class="delivery-header">
          <h3>物流信息</h3>
          <el-button
            data-testid="delivery-view-all"
            size="small"
            link
            type="primary"
            @click="goToLogistics"
          >查看全部</el-button>
        </div>
        <div class="delivery-info">
          <div class="delivery-info-row">
            <span class="label">承运商：</span>
            <span class="delivery-carrier">{{ order.delivery.carrier_name }}</span>
          </div>
          <div class="delivery-info-row">
            <span class="label">运单号：</span>
            <span class="delivery-tracking-no">{{ order.delivery.tracking_no }}</span>
          </div>
        </div>
        <div class="delivery-tracks">
          <div
            v-for="(track, index) in order.delivery.latest_tracks"
            :key="index"
            class="delivery-track-item"
            :class="{ active: index === 0 }"
          >
            <div class="delivery-track-dot" />
            <div class="delivery-track-content">
              <div class="delivery-track-desc">{{ track.description }}</div>
              <div class="delivery-track-location">{{ track.location }}</div>
              <div class="delivery-track-time">{{ formatTrackTime(track.time) }}</div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="hasProductionSchedule" class="schedule-section">
        <h3>生产进度</h3>
        <div v-loading="schedulesLoading">
          <div
            v-for="schedule in schedules"
            :key="schedule.id"
            class="schedule-item"
          >
            <div class="schedule-header">
              <span class="schedule-process">{{ schedule.process_name }}</span>
              <el-tag :type="scheduleStatusType(schedule.status)" size="small">
                {{ scheduleStatusText(schedule.status) }}
              </el-tag>
            </div>
            <div class="schedule-meta">
              <span>排期：{{ schedule.schedule_date }}</span>
              <span v-if="schedule.estimated_hours">预计：{{ schedule.estimated_hours }}h</span>
            </div>
            <div class="schedule-progress">
              <el-progress :percentage="schedule.progress" :stroke-width="10" />
            </div>
          </div>
        </div>
      </div>

      <div class="files-section">
        <div class="files-section-header">
          <h3>订单文件</h3>
          <div v-if="canUploadFile" class="upload-wrapper">
            <el-button
              data-testid="upload-file-btn"
              size="small"
              type="primary"
              :loading="uploading"
              @click="$refs.fileInput?.click()"
            >上传文件</el-button>
            <input
              ref="fileInput"
              type="file"
              style="display: none"
              accept=".pdf,.jpg,.jpeg,.png,.tiff,.tif,.ai,.psd,.eps"
              @change="handleFileUpload"
            />
          </div>
        </div>
        <div v-loading="filesLoading || uploading">
          <div v-if="!hasOrderFiles" class="files-empty">暂无文件</div>
          <div
            v-for="file in orderFiles"
            :key="file.id"
            class="file-item"
          >
            <div class="file-header">
              <div class="file-name">{{ file.file_name }}</div>
              <el-button
                v-if="canUploadFile"
                data-testid="delete-file-btn"
                size="small"
                type="danger"
                link
                @click="handleDeleteFile(file.id)"
              >删除</el-button>
            </div>
            <div class="file-meta">
              <span>类型：{{ file.file_type }}</span>
              <span>大小：{{ (file.file_size / 1024).toFixed(2) }} KB</span>
            </div>
          </div>
        </div>
        <div v-if="canUploadFile" class="file-hint">
          支持格式：PDF、JPG、PNG、TIFF、AI、PSD、EPS，最大 50MB
        </div>
      </div>

      <div v-if="hasInkChecks" class="ink-checks-section">
        <h3>印前检查</h3>
        <div v-loading="inkChecksLoading">
          <div
            v-for="check in inkChecks"
            :key="check.id"
            class="ink-check-item"
          >
            <div class="ink-check-header">
              <span class="ink-check-file">{{ check.file_name || '未关联文件' }}</span>
              <el-tag :type="inkCheckResultType(check.check_result)" size="small">
                {{ inkCheckResultText(check.check_result) }}
              </el-tag>
            </div>
            <div class="ink-check-meta">
              <span>油墨类型：{{ check.ink_type }}</span>
              <span>检查类型：{{ check.check_type }}</span>
            </div>
            <div class="ink-check-coverage">
              <span>C: {{ check.coverage_c }}%</span>
              <span>M: {{ check.coverage_m }}%</span>
              <span>Y: {{ check.coverage_y }}%</span>
              <span>K: {{ check.coverage_k }}%</span>
              <span class="total">总计: {{ check.total_coverage }}%</span>
            </div>
            <div v-if="check.checked_at" class="ink-check-time">
              检查时间：{{ check.checked_at }}
            </div>
          </div>
        </div>
      </div>

      <div v-if="hasStatusLogs" class="status-logs-section">
        <h3>订单状态流转</h3>
        <div v-loading="logsLoading" class="status-logs-timeline">
          <div
            v-for="(log, index) in statusLogs"
            :key="log.id"
            class="status-log-item"
            :class="{ 'is-last': index === statusLogs.length - 1 }"
          >
            <div class="status-log-dot" />
            <div class="status-log-content">
              <div class="status-log-title">{{ log.remark || '状态变更' }}</div>
              <div class="status-log-meta">
                <span class="status-log-operator">操作方：{{ log.operator_type }}</span>
                <span class="status-log-time">{{ formatLogTime(log.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="items-section">
        <h3>商品列表</h3>
        <div
          v-for="item in order.items"
          :key="item.id"
          class="order-item"
        >
          <div class="item-name">{{ item.product_name }}</div>
          <div class="item-meta">
            <span>单价：¥{{ item.unit_price }}</span>
            <span>数量：{{ item.quantity }}</span>
            <span>小计：¥{{ item.subtotal }}</span>
          </div>
        </div>
      </div>

      <div class="actions">
        <el-button
          v-if="canPay"
          data-testid="pay-btn"
          type="primary"
          @click="handlePay"
        >去支付</el-button>
        <el-button
          v-if="canCancel"
          data-testid="cancel-btn"
          type="danger"
          @click="handleCancel"
        >取消订单</el-button>
        <el-button
          v-if="canReview"
          data-testid="review-btn"
          type="primary"
          @click="goToReview"
        >去评价</el-button>
        <el-button
          v-if="canAfterSale"
          data-testid="aftersale-btn"
          @click="goToAfterSale"
        >申请售后</el-button>
        <el-button
          v-if="canTrackLogistics"
          data-testid="logistics-btn"
          @click="goToLogistics"
        >查看物流</el-button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.order-detail-view {
  max-width: 680px;
  margin: 20px auto;
  padding: 20px;
}
.header-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}
.page-title {
  margin: 0;
}
.loading-text {
  padding: 40px;
  text-align: center;
  color: #909399;
}
.detail-panel {
  background: #fff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.info-section {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #ebeef5;
}
.info-row {
  display: flex;
  margin-bottom: 10px;
  font-size: 14px;
}
.label {
  color: #606266;
  width: 80px;
  flex-shrink: 0;
}
.order-no {
  font-weight: 600;
}
.order-status {
  color: #e6a23c;
  font-weight: 600;
}
.order-amount {
  color: #f56c6c;
  font-weight: 600;
  font-size: 16px;
}
.delivery-section {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #ebeef5;
}
.delivery-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.delivery-header h3 {
  margin: 0;
  font-size: 16px;
}
.delivery-info {
  margin-bottom: 12px;
}
.delivery-info-row {
  display: flex;
  margin-bottom: 6px;
  font-size: 14px;
}
.delivery-carrier {
  font-weight: 600;
}
.delivery-tracking-no {
  font-weight: 600;
  font-family: monospace;
}
.delivery-tracks {
  position: relative;
  padding-left: 16px;
}
.delivery-tracks::before {
  content: '';
  position: absolute;
  left: 5px;
  top: 6px;
  bottom: 6px;
  width: 2px;
  background: #e4e7ed;
}
.delivery-track-item {
  position: relative;
  padding-bottom: 12px;
  padding-left: 12px;
}
.delivery-track-item:last-child {
  padding-bottom: 0;
}
.delivery-track-dot {
  position: absolute;
  left: -12px;
  top: 5px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #c0c4cc;
  border: 2px solid #fff;
}
.delivery-track-item.active .delivery-track-dot {
  background: #409eff;
}
.delivery-track-item.active .delivery-track-desc {
  color: #409eff;
  font-weight: 600;
}
.delivery-track-desc {
  font-size: 13px;
}
.delivery-track-location {
  font-size: 12px;
  color: #606266;
  margin-top: 2px;
}
.delivery-track-time {
  font-size: 11px;
  color: #909399;
  margin-top: 2px;
}
.schedule-section {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #ebeef5;
}
.schedule-section h3 {
  margin-bottom: 12px;
  font-size: 16px;
}
.schedule-item {
  padding: 12px;
  border: 1px solid #ebeef5;
  border-radius: 6px;
  margin-bottom: 10px;
}
.schedule-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}
.schedule-process {
  font-weight: 600;
}
.schedule-meta {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #606266;
  margin-bottom: 8px;
}
.schedule-progress {
  margin-top: 4px;
}
.files-section {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #ebeef5;
}
.files-section h3 {
  margin-bottom: 12px;
  font-size: 16px;
}
.files-section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.files-section-header h3 {
  margin: 0;
  font-size: 16px;
}
.files-empty {
  padding: 20px;
  text-align: center;
  color: #909399;
  font-size: 14px;
}
.file-item {
  padding: 12px;
  border: 1px solid #ebeef5;
  border-radius: 6px;
  margin-bottom: 10px;
}
.file-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}
.file-name {
  font-weight: 600;
}
.file-meta {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #606266;
}
.file-desc {
  margin-top: 6px;
  font-size: 13px;
  color: #909399;
}
.file-hint {
  margin-top: 8px;
  font-size: 12px;
  color: #909399;
}
.items-section {
  margin-bottom: 20px;
}
.items-section h3 {
  margin-bottom: 12px;
  font-size: 16px;
}
.order-item {
  padding: 12px;
  border: 1px solid #ebeef5;
  border-radius: 6px;
  margin-bottom: 10px;
}
.item-name {
  font-weight: 600;
  margin-bottom: 6px;
}
.item-meta {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #606266;
}
.ink-checks-section {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #ebeef5;
}
.ink-checks-section h3 {
  margin-bottom: 12px;
  font-size: 16px;
}
.ink-check-item {
  padding: 12px;
  border: 1px solid #ebeef5;
  border-radius: 6px;
  margin-bottom: 10px;
}
.ink-check-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}
.ink-check-file {
  font-weight: 600;
}
.ink-check-meta {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #606266;
  margin-bottom: 8px;
}
.ink-check-coverage {
  display: flex;
  gap: 12px;
  font-size: 13px;
  flex-wrap: wrap;
}
.ink-check-coverage .total {
  font-weight: 600;
  color: #409eff;
}
.ink-check-time {
  margin-top: 6px;
  font-size: 12px;
  color: #909399;
}
.status-logs-section {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #ebeef5;
}
.status-logs-section h3 {
  margin-bottom: 12px;
  font-size: 16px;
}
.status-logs-timeline {
  position: relative;
  padding-left: 16px;
}
.status-logs-timeline::before {
  content: '';
  position: absolute;
  left: 5px;
  top: 8px;
  bottom: 8px;
  width: 2px;
  background: #e4e7ed;
}
.status-log-item {
  position: relative;
  padding-bottom: 16px;
}
.status-log-item.is-last {
  padding-bottom: 0;
}
.status-log-dot {
  position: absolute;
  left: -13px;
  top: 6px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #409eff;
  border: 2px solid #fff;
  box-shadow: 0 0 0 1px #409eff;
}
.status-log-content {
  padding-left: 8px;
}
.status-log-title {
  font-weight: 600;
  font-size: 14px;
  color: #303133;
}
.status-log-meta {
  display: flex;
  gap: 12px;
  margin-top: 4px;
  font-size: 12px;
  color: #909399;
}
.actions {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #ebeef5;
}
</style>
