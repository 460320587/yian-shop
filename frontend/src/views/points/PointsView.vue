<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getPoints, getPointsRecords, type PointsRecord, type PointsInfo } from '@/api/points'

const pointsInfo = ref<PointsInfo | null>(null)
const records = ref<PointsRecord[]>([])
const loading = ref(false)
const recordType = ref<number | undefined>(undefined)
const total = ref(0)

const typeMap: Record<number, string> = {
  1: '获得',
  2: '消耗',
  3: '过期',
  4: '返积分',
}

function typeName(type: number): string {
  return typeMap[type] || '其他'
}

async function loadRecords() {
  loading.value = true
  try {
    const res = await getPointsRecords({
      type: recordType.value,
      page: 1,
      per_page: 20,
    })
    records.value = res.data || []
    total.value = res.meta?.total || 0
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function loadPointsInfo() {
  try {
    pointsInfo.value = await getPoints()
  } catch (e) {
    console.error(e)
  }
}

onMounted(() => {
  loadPointsInfo()
  loadRecords()
})

defineExpose({
  pointsInfo,
  records,
  loading,
  recordType,
  total,
  loadRecords,
  typeName,
})
</script>

<template>
  <div class="points-view page-container">
    <h2 class="page-title">我的积分</h2>

    <div class="points-card">
      <div class="points-label">当前积分</div>
      <div class="points-value">{{ pointsInfo?.points ?? 0 }}</div>
      <div class="points-grow">
        成长值：{{ pointsInfo?.grow_value ?? 0 }}
      </div>
    </div>

    <div class="records-section">
      <div class="records-header">
        <h3>积分明细</h3>
        <el-select
          v-model="recordType"
          clearable
          placeholder="全部类型"
          size="small"
          style="width: 120px"
          @change="loadRecords"
        >
          <el-option label="获得" :value="1" />
          <el-option label="消耗" :value="2" />
          <el-option label="过期" :value="3" />
          <el-option label="返积分" :value="4" />
        </el-select>
      </div>

      <div v-if="loading" class="record-loading">加载中...</div>
      <div v-else class="record-list">
        <div v-if="records.length === 0" class="record-empty">
          暂无积分记录
        </div>
        <div
          v-for="record in records"
          :key="record.id"
          class="record-row"
        >
          <div class="record-main">
            <span class="record-type">{{ typeName(record.type) }}</span>
            <span class="record-remark">{{ record.remark || '-' }}</span>
          </div>
          <div class="record-meta">
            <span :class="['record-points', record.points > 0 ? 'points-positive' : 'points-negative']">
              {{ record.points > 0 ? '+' : '' }}{{ record.points }}
            </span>
            <span class="record-balance">余额 {{ record.balance_after }}</span>
            <span class="record-time">{{ record.created_at }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.points-view {
  max-width: 560px;
  margin: 20px auto;
  padding: 20px;
}
.points-card {
  background: linear-gradient(135deg, #409eff 0%, #36cfc9 100%);
  border-radius: 16px;
  padding: 40px 32px;
  color: #fff;
  text-align: center;
  margin-bottom: 24px;
}
.points-label {
  font-size: 14px;
  opacity: 0.9;
  margin-bottom: 8px;
}
.points-value {
  font-size: 42px;
  font-weight: 700;
  margin-bottom: 8px;
}
.points-grow {
  font-size: 13px;
  opacity: 0.85;
}
.records-section {
  background: #fff;
  border-radius: 8px;
  padding: 16px 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.records-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.records-header h3 {
  margin: 0;
  font-size: 16px;
}
.record-loading,
.record-empty {
  padding: 40px;
  text-align: center;
  color: #909399;
  font-size: 14px;
}
.record-list {
  border: 1px solid #ebeef5;
  border-radius: 4px;
}
.record-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  border-bottom: 1px solid #ebeef5;
  font-size: 14px;
}
.record-row:last-child {
  border-bottom: none;
}
.record-main {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.record-type {
  font-weight: 600;
  color: #303133;
}
.record-remark {
  color: #909399;
  font-size: 12px;
}
.record-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}
.record-points {
  font-weight: 600;
  font-size: 16px;
}
.points-positive {
  color: #67c23a;
}
.points-negative {
  color: #f56c6c;
}
.record-balance {
  color: #909399;
  font-size: 12px;
}
.record-time {
  color: #c0c4cc;
  font-size: 12px;
}
</style>
