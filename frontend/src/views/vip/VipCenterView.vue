<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getVipInfo, getVipLevels } from '@/api/vip'
import type { VipInfo, VipLevel } from '@/api/vip'

const vipInfo = ref<VipInfo | null>(null)
const vipLevels = ref<VipLevel[]>([])
const loading = ref(false)

async function loadData() {
  loading.value = true
  try {
    const [infoRes, levelsRes] = await Promise.all([
      getVipInfo(),
      getVipLevels(),
    ])
    vipInfo.value = infoRes
    vipLevels.value = levelsRes
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function formatDiscount(discount: number): string {
  if (discount >= 1) return '无折扣'
  return `${(discount * 10).toFixed(1)}折`
}

onMounted(() => {
  loadData()
})

defineExpose({
  vipInfo,
  vipLevels,
  loading,
  loadData,
})
</script>

<template>
  <div class="vip-center-view page-container">
    <h2 class="page-title">VIP中心</h2>

    <div v-if="loading && !vipInfo" class="loading-text">加载中...</div>

    <div v-if="vipInfo" class="vip-panel">
      <div class="current-card">
        <div class="level-header">
          <span class="current-level">{{ vipInfo.current_level_name }}</span>
          <span v-if="!vipInfo.next_level" class="max-level-badge">最高等级</span>
        </div>
        <div class="grow-bar">
          <div class="grow-text">
            <span class="grow-value">{{ vipInfo.current_grow_value }}</span>
            <span v-if="vipInfo.next_level_points"> / {{ vipInfo.next_level_points }} 成长值</span>
          </div>
          <div class="progress-track">
            <div
              class="progress-fill"
              :style="{ width: vipInfo.progress_percent + '%' }"
            />
          </div>
          <div class="progress-percent">{{ vipInfo.progress_percent }}%</div>
        </div>
        <div v-if="vipInfo.next_level_name" class="next-tip">
          再获得 <strong>{{ vipInfo.next_level_points! - vipInfo.current_grow_value }}</strong> 成长值即可升级为 <span class="next-level-name">{{ vipInfo.next_level_name }}</span>
        </div>
        <div v-else class="next-tip max">
          恭喜您已达到最高等级！
        </div>
      </div>

      <div class="levels-section">
        <h3>等级权益</h3>
        <div class="levels-table">
          <div class="table-header">
            <span class="col-level">等级</span>
            <span class="col-points">成长值要求</span>
            <span class="col-discount">印刷折扣</span>
          </div>
          <div
            v-for="level in vipLevels"
            :key="level.level"
            class="level-row"
            :class="{ active: level.level === vipInfo.current_level }"
          >
            <span class="col-level">{{ level.name }}</span>
            <span class="col-points">{{ level.min_points }}</span>
            <span class="col-discount">{{ formatDiscount(level.discount) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.vip-center-view {
  max-width: 680px;
  margin: 20px auto;
  padding: 20px;
}
.loading-text {
  padding: 40px;
  text-align: center;
  color: #909399;
}
.vip-panel {
  display: flex;
  flex-direction: column;
  gap: 24px;
}
.current-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  padding: 28px;
  color: #fff;
}
.level-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}
.current-level {
  font-size: 24px;
  font-weight: 700;
}
.max-level-badge {
  font-size: 12px;
  padding: 2px 10px;
  background: rgba(255, 255, 255, 0.25);
  border-radius: 12px;
}
.grow-bar {
  margin-bottom: 12px;
}
.grow-text {
  font-size: 14px;
  margin-bottom: 8px;
  opacity: 0.9;
}
.grow-value {
  font-size: 22px;
  font-weight: 700;
}
.progress-track {
  height: 8px;
  background: rgba(255, 255, 255, 0.25);
  border-radius: 4px;
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  background: #fff;
  border-radius: 4px;
  transition: width 0.5s ease;
}
.progress-percent {
  font-size: 12px;
  margin-top: 6px;
  opacity: 0.85;
}
.next-tip {
  font-size: 13px;
  opacity: 0.9;
}
.next-tip.max {
  font-weight: 600;
}
.next-level-name {
  font-weight: 600;
}
.levels-section h3 {
  margin-bottom: 16px;
  font-size: 16px;
}
.levels-table {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}
.table-header,
.level-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  padding: 14px 20px;
  font-size: 14px;
}
.table-header {
  background: #f5f7fa;
  font-weight: 600;
  color: #606266;
}
.level-row {
  border-top: 1px solid #ebeef5;
}
.level-row.active {
  background: #ecf5ff;
  color: #409eff;
  font-weight: 600;
}
.col-level,
.col-points,
.col-discount {
  text-align: center;
}
</style>
