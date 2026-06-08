<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getAdminOrders, getAdminOrderDetail, getAdminOrderFiles, deleteAdminOrderFile, confirmAdminOrderPayment, shipAdminOrder, completeAdminOrder } from '@/api/admin'
import { ElMessage, ElMessageBox } from 'element-plus'

const orders = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const keyword = ref('')
const statusFilter = ref('')
const detailVisible = ref(false)
const detail = ref<any>(null)
const fileDialogVisible = ref(false)
const fileDialogLoading = ref(false)
const orderFiles = ref<any[]>([])
const currentOrderId = ref<number | null>(null)
const shipDialogVisible = ref(false)
const shipForm = ref({ express_company: '', tracking_no: '' })
const currentShipOrder = ref<any>(null)

const statusOptions = [
  { label: '全部', value: '' },
  { label: '待付款', value: '11' },
  { label: '生产中', value: '20' },
  { label: '待收货', value: '30' },
  { label: '已完成', value: '40' },
  { label: '已取消', value: '50' },
]

async function loadOrders() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (keyword.value) params.keyword = keyword.value
    if (statusFilter.value) params.status = statusFilter.value
    const res = await getAdminOrders(params)
    orders.value = res.data
    total.value = res.total
  } catch (e) {
    console.error(e)
    ElMessage.error('加载订单失败')
  } finally {
    loading.value = false
  }
}

function onSearch() {
  currentPage.value = 1
  loadOrders()
}

async function openDetail(row: any) {
  try {
    const res = await getAdminOrderDetail(row.id)
    detail.value = res
    detailVisible.value = true
  } catch (e) {
    console.error(e)
    ElMessage.error('加载订单详情失败')
  }
}

async function openFileDialog(row: any) {
  currentOrderId.value = row.id
  fileDialogVisible.value = true
  fileDialogLoading.value = true
  try {
    const res = await getAdminOrderFiles(row.id)
    orderFiles.value = res.data || []
  } catch (e) {
    console.error(e)
    ElMessage.error('加载订单文件失败')
  } finally {
    fileDialogLoading.value = false
  }
}

async function handleDeleteFile(row: any) {
  try {
    await ElMessageBox.confirm(`确认删除文件 ${row.file_name} 吗？`, '提示', { type: 'warning' })
    await deleteAdminOrderFile(row.id)
    ElMessage.success('文件已删除')
    if (currentOrderId.value !== null) {
      const res = await getAdminOrderFiles(currentOrderId.value)
      orderFiles.value = res.data || []
    }
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

function canConfirmPayment(status: number): boolean {
  return status === 11
}
function canShip(status: number): boolean {
  return status === 12 || status === 17
}
function canComplete(status: number): boolean {
  return status === 20
}

async function handleConfirmPayment(row: any) {
  try {
    await confirmAdminOrderPayment(row.id)
    ElMessage.success('已确认收款')
    loadOrders()
  } catch (e) {
    console.error(e)
  }
}

function openShipDialog(row: any) {
  currentShipOrder.value = row
  shipForm.value = { express_company: '', tracking_no: '' }
  shipDialogVisible.value = true
}

async function handleShip() {
  if (!currentShipOrder.value) return
  if (!shipForm.value.express_company.trim()) {
    ElMessage.warning('请输入物流公司')
    return
  }
  try {
    await shipAdminOrder(currentShipOrder.value.id, {
      express_company: shipForm.value.express_company.trim(),
      tracking_no: shipForm.value.tracking_no.trim() || undefined,
    })
    ElMessage.success('已发货')
    shipDialogVisible.value = false
    loadOrders()
  } catch (e) {
    console.error(e)
  }
}

async function handleComplete(row: any) {
  try {
    await completeAdminOrder(row.id)
    ElMessage.success('订单已完成')
    loadOrders()
  } catch (e) {
    console.error(e)
  }
}

function formatAmount(amount: number): string {
  return '¥' + (amount / 100).toFixed(2)
}

function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i]
}

onMounted(loadOrders)

defineExpose({
  orders,
  loading,
  keyword,
  statusFilter,
  currentPage,
  pageSize,
  total,
  detailVisible,
  detail,
  fileDialogVisible,
  fileDialogLoading,
  orderFiles,
  currentOrderId,
  loadOrders,
  onSearch,
  openDetail,
  openFileDialog,
  handleDeleteFile,
  handleConfirmPayment,
  openShipDialog,
  handleShip,
  handleComplete,
  canConfirmPayment,
  canShip,
  canComplete,
  formatAmount,
  formatFileSize,
})
</script>

<template>
  <div class="order-management">
    <div class="page-header">
      <h3>订单管理</h3>
      <div class="filter-bar">
        <el-input v-model="keyword" placeholder="搜索订单号/客户" clearable style="width: 240px" @keyup.enter="onSearch" />
        <el-select v-model="statusFilter" placeholder="订单状态" clearable style="width: 140px" @change="onSearch">
          <el-option v-for="opt in statusOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
        </el-select>
        <el-button type="primary" @click="onSearch">查询</el-button>
      </div>
    </div>
    <el-table :data="orders" v-loading="loading" stripe>
      <el-table-column prop="order_no" label="订单号" min-width="160" />
      <el-table-column prop="customer_name" label="客户" min-width="120" />
      <el-table-column label="金额" min-width="100">
        <template #default="scope">
          <span v-if="scope?.row">{{ formatAmount(scope.row.total_amount) }}</span>
        </template>
      </el-table-column>
      <el-table-column prop="customer_status" label="状态" min-width="100">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="scope.row.status === 11 ? 'warning' : scope.row.status === 40 ? 'success' : 'info'">
            {{ scope.row.customer_status }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" min-width="160" />
      <el-table-column label="操作" width="260">
        <template #default="scope">
          <el-button v-if="scope?.row" link type="primary" @click="openDetail(scope.row)">查看</el-button>
          <el-button v-if="scope?.row" link type="success" @click="openFileDialog(scope.row)">文件</el-button>
          <el-button v-if="scope?.row && canConfirmPayment(scope.row.status)" link type="warning" @click="handleConfirmPayment(scope.row)">确认收款</el-button>
          <el-button v-if="scope?.row && canShip(scope.row.status)" link type="warning" @click="openShipDialog(scope.row)">发货</el-button>
          <el-button v-if="scope?.row && canComplete(scope.row.status)" link type="danger" @click="handleComplete(scope.row)">完成</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      :total="total"
      layout="total, prev, pager, next"
      class="pagination"
      @change="loadOrders"
    />

    <!-- 订单详情对话框 -->
    <el-dialog v-model="detailVisible" title="订单详情" width="600px">
      <div v-if="detail" class="detail-content">
        <p><strong>订单号：</strong>{{ detail.order_no }}</p>
        <p><strong>客户：</strong>{{ detail.customer_name }}</p>
        <p><strong>金额：</strong>{{ formatAmount(detail.total_amount) }}</p>
        <p><strong>状态：</strong>{{ detail.customer_status }}</p>
        <p><strong>创建时间：</strong>{{ detail.created_at }}</p>
        <div v-if="detail.items?.length" class="items-section">
          <h4>订单明细</h4>
          <el-table :data="detail.items" size="small">
            <el-table-column prop="product_name" label="商品" />
            <el-table-column prop="quantity" label="数量" width="80" />
            <el-table-column label="单价" width="100">
              <template #default="s"><span v-if="s?.row">{{ formatAmount(s.row.unit_price) }}</span></template>
            </el-table-column>
          </el-table>
        </div>
      </div>
    </el-dialog>

    <!-- 发货对话框 -->
    <el-dialog v-model="shipDialogVisible" title="订单发货" width="480px">
      <el-form :model="shipForm" label-width="90px">
        <el-form-item label="物流公司" required>
          <el-input v-model="shipForm.express_company" placeholder="请输入物流公司名称" maxlength="50" />
        </el-form-item>
        <el-form-item label="运单号">
          <el-input v-model="shipForm.tracking_no" placeholder="请输入运单号" maxlength="50" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="shipDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleShip">确认发货</el-button>
      </template>
    </el-dialog>

    <!-- 订单文件对话框 -->
    <el-dialog v-model="fileDialogVisible" title="订单文件" width="600px">
      <div v-loading="fileDialogLoading">
        <div v-if="!fileDialogLoading && orderFiles.length === 0" class="empty-state">
          暂无文件
        </div>
        <el-table v-else :data="orderFiles" size="small" stripe>
          <el-table-column prop="file_name" label="文件名" min-width="160" />
          <el-table-column label="大小" width="100">
            <template #default="scope">
              <span v-if="scope?.row">{{ formatFileSize(scope.row.file_size) }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="file_type" label="类型" width="80" />
          <el-table-column prop="page_count" label="页数" width="70">
            <template #default="scope">
              <span v-if="scope?.row">{{ scope.row.page_count ?? '-' }}</span>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="120">
            <template #default="scope">
              <template v-if="scope?.row">
                <el-button link type="primary" size="small" @click="window.open(scope.row.file_url, '_blank')">下载</el-button>
                <el-button link type="danger" size="small" @click="handleDeleteFile(scope.row)">删除</el-button>
              </template>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </el-dialog>
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h3 { font-size: 18px; font-weight: 600; margin: 0; }
.filter-bar { display: flex; gap: 12px; }
.pagination { margin-top: 20px; justify-content: flex-end; }
.detail-content p { margin: 8px 0; }
.items-section { margin-top: 16px; }
.items-section h4 { margin-bottom: 8px; font-size: 14px; }
.empty-state { text-align: center; padding: 32px; color: #909399; }
</style>
