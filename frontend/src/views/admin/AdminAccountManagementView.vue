<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { getAdminAccounts, createAdminAccount, updateAdminAccount, toggleAdminAccountStatus, deleteAdminAccount, resetAdminAccountPassword, getAdminRoles } from '@/api/admin'
import { ElMessage, ElMessageBox } from 'element-plus'

const accounts = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const keyword = ref('')
const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)
const roles = ref<any[]>([])

const form = reactive({
  username: '', password: '', password_confirmation: '', real_name: '', phone: '', email: '', role_id: null as number | null,
})

const pwdDialogVisible = ref(false)
const pwdForm = reactive({ password: '', password_confirmation: '' })
const pwdId = ref<number | null>(null)

async function loadList() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (keyword.value) params.keyword = keyword.value
    const res = await getAdminAccounts(params)
    accounts.value = res.data
    total.value = res.total
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

function onSearch() { currentPage.value = 1; loadList() }

function openCreate() {
  isEdit.value = false; editId.value = null
  form.username = ''; form.password = ''; form.password_confirmation = ''
  form.real_name = ''; form.phone = ''; form.email = ''; form.role_id = null
  dialogVisible.value = true
}

async function openEdit(row: any) {
  isEdit.value = true; editId.value = row.id
  form.username = row.username
  form.real_name = row.real_name || ''
  form.phone = row.phone || ''
  form.email = row.email || ''
  form.role_id = row.role_id
  form.password = ''; form.password_confirmation = ''
  dialogVisible.value = true
}

async function handleSave() {
  try {
    if (isEdit.value && editId.value) {
      await updateAdminAccount(editId.value, { real_name: form.real_name, phone: form.phone || undefined, email: form.email || undefined, role_id: form.role_id ?? undefined })
      ElMessage.success('更新成功')
    } else {
      await createAdminAccount({ username: form.username, password: form.password, password_confirmation: form.password_confirmation, real_name: form.real_name, phone: form.phone || undefined, email: form.email || undefined, role_id: form.role_id ?? undefined })
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    loadList()
  } catch (e) { console.error(e) }
}

async function handleToggle(row: any) {
  try { await toggleAdminAccountStatus(row.id); ElMessage.success('操作成功'); loadList() }
  catch (e) { console.error(e) }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm('确定删除该管理员账号吗？', '提示', { type: 'warning' })
    await deleteAdminAccount(row.id)
    ElMessage.success('删除成功')
    loadList()
  } catch (e) { /* cancelled */ }
}

function openResetPassword(row: any) {
  pwdId.value = row.id
  pwdForm.password = ''; pwdForm.password_confirmation = ''
  pwdDialogVisible.value = true
}

async function handleResetPassword() {
  if (!pwdId.value) return
  try {
    await resetAdminAccountPassword(pwdId.value, { password: pwdForm.password, password_confirmation: pwdForm.password_confirmation })
    ElMessage.success('密码重置成功')
    pwdDialogVisible.value = false
  } catch (e) { console.error(e) }
}

async function loadRoles() {
  try { const res = await getAdminRoles(); roles.value = res }
  catch (e) { console.error(e) }
}

onMounted(() => { loadList(); loadRoles() })
</script>

<template>
  <div class="account-management">
    <div class="page-header">
      <h3>管理员账号</h3>
      <div class="filter-bar">
        <el-input v-model="keyword" placeholder="搜索用户名/姓名" clearable style="width: 260px" @keyup.enter="onSearch" />
        <el-button type="primary" @click="onSearch">查询</el-button>
        <el-button type="primary" @click="openCreate">新建账号</el-button>
      </div>
    </div>
    <el-table :data="accounts" v-loading="loading" stripe>
      <el-table-column prop="username" label="用户名" min-width="120" />
      <el-table-column prop="real_name" label="真实姓名" min-width="120" />
      <el-table-column prop="phone" label="手机号" min-width="130" />
      <el-table-column prop="email" label="邮箱" min-width="160" />
      <el-table-column label="角色" min-width="120">
        <template #default="scope">
          <span v-if="scope?.row">{{ scope.row.roleModel?.name || '-' }}</span>
        </template>
      </el-table-column>
      <el-table-column label="状态" width="90">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="scope.row.status === 1 ? 'success' : 'danger'">{{ scope.row.status === 1 ? '正常' : '禁用' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" min-width="160" />
      <el-table-column label="操作" width="260">
        <template #default="scope">
          <el-button v-if="scope?.row" link type="primary" @click="openEdit(scope.row)">编辑</el-button>
          <el-button v-if="scope?.row" link :type="scope.row.status === 1 ? 'danger' : 'success'" @click="handleToggle(scope.row)">{{ scope.row.status === 1 ? '禁用' : '启用' }}</el-button>
          <el-button v-if="scope?.row" link type="warning" @click="openResetPassword(scope.row)">重置密码</el-button>
          <el-button v-if="scope?.row" link type="danger" @click="handleDelete(scope.row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize" :total="total" layout="total, prev, pager, next" class="pagination" @change="loadList" />

    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑管理员' : '新建管理员'" width="520px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="用户名" v-if="!isEdit"><el-input v-model="form.username" /></el-form-item>
        <el-form-item label="密码" v-if="!isEdit"><el-input v-model="form.password" type="password" show-password /></el-form-item>
        <el-form-item label="确认密码" v-if="!isEdit"><el-input v-model="form.password_confirmation" type="password" show-password /></el-form-item>
        <el-form-item label="真实姓名"><el-input v-model="form.real_name" /></el-form-item>
        <el-form-item label="手机号"><el-input v-model="form.phone" /></el-form-item>
        <el-form-item label="邮箱"><el-input v-model="form.email" /></el-form-item>
        <el-form-item label="角色">
          <el-select v-model="form.role_id" placeholder="选择角色" clearable style="width: 100%">
            <el-option v-for="role in roles" :key="role.id" :label="role.name" :value="role.id" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible = false">取消</el-button><el-button type="primary" @click="handleSave">保存</el-button></template>
    </el-dialog>

    <el-dialog v-model="pwdDialogVisible" title="重置密码" width="400px">
      <el-form :model="pwdForm" label-width="100px">
        <el-form-item label="新密码"><el-input v-model="pwdForm.password" type="password" show-password /></el-form-item>
        <el-form-item label="确认密码"><el-input v-model="pwdForm.password_confirmation" type="password" show-password /></el-form-item>
      </el-form>
      <template #footer><el-button @click="pwdDialogVisible = false">取消</el-button><el-button type="primary" @click="handleResetPassword">确认</el-button></template>
    </el-dialog>
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h3 { font-size: 18px; font-weight: 600; margin: 0; }
.filter-bar { display: flex; gap: 12px; }
.pagination { margin-top: 20px; justify-content: flex-end; }
</style>
