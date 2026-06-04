<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { getSystemConfigs, updateSystemConfig, batchUpdateSystemConfigs } from '@/api/admin'
import { ElMessage } from 'element-plus'

const configs = ref<any[]>([])
const loading = ref(false)
const groupFilter = ref('')
const changedConfigs = reactive<Record<number, string>>({})

const groupOptions = [
  { value: 'basic', label: '基础配置' },
  { value: 'system', label: '系统配置' },
  { value: 'payment', label: '支付配置' },
  { value: 'seo', label: 'SEO配置' },
]

async function loadConfigs() {
  loading.value = true
  try {
    const params: any = {}
    if (groupFilter.value) params.group = groupFilter.value
    configs.value = await getSystemConfigs(params)
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

function onValueChange(config: any, value: string) {
  changedConfigs[config.id] = value
}

async function handleSave(config: any) {
  const newValue = changedConfigs[config.id]
  if (newValue === undefined) return
  try {
    await updateSystemConfig(config.id, { config_value: newValue })
    ElMessage.success('保存成功')
    config.config_value = newValue
    delete changedConfigs[config.id]
  } catch (e) { console.error(e) }
}

async function handleBatchSave() {
  const items = Object.entries(changedConfigs).map(([id, value]) => ({ id: Number(id), config_value: value }))
  if (items.length === 0) { ElMessage.warning('没有修改的配置'); return }
  try {
    await batchUpdateSystemConfigs(items)
    ElMessage.success('批量保存成功')
    items.forEach(({ id, config_value }) => {
      const config = configs.value.find((c) => c.id === id)
      if (config) config.config_value = config_value
    })
    Object.keys(changedConfigs).forEach((key) => delete changedConfigs[Number(key)])
  } catch (e) { console.error(e) }
}

onMounted(loadConfigs)
</script>

<template>
  <div class="system-config-view">
    <div class="page-header">
      <h3>系统配置</h3>
      <div class="filter-bar">
        <el-select v-model="groupFilter" placeholder="配置分组" clearable style="width: 140px" @change="loadConfigs">
          <el-option v-for="opt in groupOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
        </el-select>
        <el-button type="primary" @click="handleBatchSave">保存全部修改</el-button>
      </div>
    </div>
    <el-table :data="configs" v-loading="loading" stripe>
      <el-table-column prop="config_key" label="配置键" width="180" />
      <el-table-column prop="description" label="说明" />
      <el-table-column prop="config_value" label="当前值">
        <template #default="scope">
          <el-input v-if="scope?.row" v-model="changedConfigs[scope.row.id]" :placeholder="scope.row.config_value" size="small" style="width: 200px" @input="onValueChange(scope.row, $event.target?.value ?? $event)" />
        </template>
      </el-table-column>
      <el-table-column prop="group" label="分组" width="100">
        <template #default="scope">{{ scope?.row ? ({ basic: '基础', system: '系统', payment: '支付', seo: 'SEO' }[scope.row.group] || scope.row.group) : '' }}</template>
      </el-table-column>
      <el-table-column label="操作" width="80">
        <template #default="scope">
          <el-button v-if="scope?.row && changedConfigs[scope.row.id] !== undefined" link type="primary" @click="handleSave(scope.row)">保存</el-button>
          <span v-else>-</span>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h3 { font-size: 18px; font-weight: 600; margin: 0; }
.filter-bar { display: flex; gap: 12px; }
</style>
