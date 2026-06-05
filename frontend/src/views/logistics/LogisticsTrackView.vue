<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getLogisticsTracks } from '@/api/logistics'

const route = useRoute()
const router = useRouter()

const logistics = ref<any>(null)
const loading = ref(false)
const errorMsg = ref('')

async function loadLogistics() {
  const id = Number(route.params.id)
  if (!id) {
    errorMsg.value = '订单ID无效'
    return
  }
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await getLogisticsTracks(id)
    logistics.value = res
  } catch (e: any) {
    errorMsg.value = '暂无物流信息'
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.back()
}

function formatTime(time: string): string {
  if (!time) return '-'
  const d = new Date(time)
  const yyyy = d.getFullYear()
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  const hh = String(d.getHours()).padStart(2, '0')
  const mi = String(d.getMinutes()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd} ${hh}:${mi}`
}

onMounted(() => {
  loadLogistics()
})

defineExpose({
  logistics,
  loading,
  errorMsg,
  loadLogistics,
  goBack,
})
</script>

<template>
  <div class="logistics-track-view page-container">
    <div class="header-row">
      <el-button data-testid="back-btn" size="small" @click="goBack">返回</el-button>
      <h2 class="page-title">物流跟踪</h2>
    </div>

    <div v-if="loading && !logistics" class="loading-text">加载中...</div>

    <div v-if="errorMsg && !logistics" class="empty-state">
      {{ errorMsg }}
    </div>

    <div v-if="logistics" class="logistics-panel">
      <div class="info-card">
        <div class="info-row">
          <span class="label">承运商：</span>
          <span class="carrier-name">{{ logistics.carrier_name }}</span>
        </div>
        <div class="info-row">
          <span class="label">运单号：</span>
          <span class="tracking-no">{{ logistics.tracking_no }}</span>
        </div>
        <div class="info-row">
          <span class="label">发货时间：</span>
          <span class="shipped-at">{{ formatTime(logistics.shipped_at) }}</span>
        </div>
        <div v-if="logistics.delivered_at" class="info-row">
          <span class="label">送达时间：</span>
          <span class="delivered-at">{{ formatTime(logistics.delivered_at) }}</span>
        </div>
      </div>

      <div class="timeline-section">
        <h3>物流轨迹</h3>
        <div class="timeline">
          <div
            v-for="(track, index) in logistics.tracks"
            :key="index"
            class="track-item"
            :class="{ active: index === 0 }"
          >
            <div class="track-dot" />
            <div class="track-content">
              <div class="track-desc">{{ track.description }}</div>
              <div class="track-location">{{ track.location }}</div>
              <div class="track-time">{{ formatTime(track.time) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.logistics-track-view {
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
.empty-state {
  padding: 40px;
  text-align: center;
  color: #909399;
  background: #f5f7fa;
  border-radius: 8px;
}
.logistics-panel {
  background: #fff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.info-card {
  margin-bottom: 24px;
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
.carrier-name {
  font-weight: 600;
}
.tracking-no {
  font-weight: 600;
  font-family: monospace;
}
.timeline-section h3 {
  margin-bottom: 16px;
  font-size: 16px;
}
.timeline {
  position: relative;
  padding-left: 20px;
}
.timeline::before {
  content: '';
  position: absolute;
  left: 5px;
  top: 8px;
  bottom: 8px;
  width: 2px;
  background: #dcdfe6;
}
.track-item {
  position: relative;
  padding-bottom: 20px;
  padding-left: 16px;
}
.track-item:last-child {
  padding-bottom: 0;
}
.track-dot {
  position: absolute;
  left: -16px;
  top: 6px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #c0c4cc;
  border: 2px solid #fff;
}
.track-item.active .track-dot {
  background: #409eff;
}
.track-item.active .track-desc {
  color: #409eff;
  font-weight: 600;
}
.track-desc {
  font-size: 14px;
  margin-bottom: 4px;
}
.track-location {
  font-size: 13px;
  color: #606266;
  margin-bottom: 2px;
}
.track-time {
  font-size: 12px;
  color: #909399;
}
</style>
