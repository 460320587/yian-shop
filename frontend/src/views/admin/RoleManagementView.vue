<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue'
import {
  getAdminRoles, createAdminRole, updateAdminRole, deleteAdminRole, toggleAdminRoleStatus,
  getAdminPermissions, getRolePermissions, assignRolePermissions,
} from '@/api/admin'
import { ElMessage, ElMessageBox } from 'element-plus'

const roles = ref<any[]>([])
const loading = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)

const form = reactive({ name: '', code: '', description: '' })

const permissionDialogVisible = ref(false)
const allPermissions = ref<any[]>([])
const selectedPermissionIds = ref<number[]>([])
const permissionRoleId = ref<number | null>(null)

const groupedPermissions = computed(() => {
  const map: Record<string, any[]> = {}
  for (const p of allPermissions.value) {
    if (!map[p.group]) map[p.group] = []
    map[p.group].push(p)
  }
  return Object.entries(map).map(([group, items]) => ({ group, items }))
})

async function loadRoles() {
  loading.value = true
  try {
    const res = await getAdminRoles()
    roles.value = res
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

function openCreate() {
  isEdit.value = false
  editId.value = null
  form.name = ''; form.code = ''; form.description = ''
  dialogVisible.value = true
}

function openEdit(row: any) {
  isEdit.value = true
  editId.value = row.id
  form.name = row.name
  form.code = row.code
  form.description = row.description || ''
  dialogVisible.value = true
}

async function handleSave() {
  try {
    if (isEdit.value && editId.value) {
      await updateAdminRole(editId.value, { name: form.name, code: form.code, description: form.description || undefined })
      ElMessage.success('更新成功')
    } else {
      await createAdminRole({ name: form.name, code: form.code, description: form.description || undefined })
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    loadRoles()
  } catch (e) { console.error(e) }
}

async function handleToggle(row: any) {
  try { await toggleAdminRoleStatus(row.id); ElMessage.success('操作成功'); loadRoles() }
  catch (e) { console.error(e) }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm('确定删除该角色吗？', '提示', { type: 'warning' })
    await deleteAdminRole(row.id)
    ElMessage.success('删除成功')
    loadRoles()
  } catch (e) { /* cancelled */ }
}

async function openPermissionDialog(row: any) {
  permissionRoleId.value = row.id
  selectedPermissionIds.value = []
  try {
    const [allRes, roleRes] = await Promise.all([
      getAdminPermissions(),
      getRolePermissions(row.id),
    ])
    allPermissions.value = allRes
    selectedPermissionIds.value = roleRes.map((p: any) => p.id)
    permissionDialogVisible.value = true
  } catch (e) {
    console.error(e)
    ElMessage.error('加载权限失败')
  }
}

async function handleSavePermissions() {
  if (!permissionRoleId.value) return
  try {
    await assignRolePermissions(permissionRoleId.value, { permission_ids: selectedPermissionIds.value })
    ElMessage.success('权限分配成功')
    permissionDialogVisible.value = false
  } catch (e) { console.error(e) }
}

onMounted(loadRoles)
</script>

<template>
  <div class="role-management">
    <div class="page-header">
      <h3>角色权限</h3>
      <el-button type="primary" @click="openCreate">新建角色</el-button>
    </div>
    <el-table :data="roles" v-loading="loading" stripe>
      <el-table-column prop="name" label="角色名称" min-width="140" />
      <el-table-column prop="code" label="编码" min-width="140" />
      <el-table-column prop="description" label="描述" min-width="200" />
      <el-table-column label="状态" width="90">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="scope.row.status === 1 ? 'success' : 'danger'">{{ scope.row.status === 1 ? '启用' : '禁用' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="280">
        <template #default="scope">
          <el-button v-if="scope?.row" link type="primary" @click="openEdit(scope.row)">编辑</el-button>
          <el-button v-if="scope?.row" link :type="scope.row.status === 1 ? 'danger' : 'success'" @click="handleToggle(scope.row)">{{ scope.row.status === 1 ? '禁用' : '启用' }}</el-button>
          <el-button v-if="scope?.row" link type="warning" @click="openPermissionDialog(scope.row)">权限分配</el-button>
          <el-button v-if="scope?.row" link type="danger" @click="handleDelete(scope.row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑角色' : '新建角色'" width="480px">
      <el-form :model="form" label-width="90px">
        <el-form-item label="角色名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="编码"><el-input v-model="form.code" :disabled="isEdit" /></el-form-item>
        <el-form-item label="描述"><el-input v-model="form.description" type="textarea" :rows="2" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible = false">取消</el-button><el-button type="primary" @click="handleSave">保存</el-button></template>
    </el-dialog>

    <el-dialog v-model="permissionDialogVisible" title="权限分配" width="600px">
      <div v-loading="!allPermissions.length && permissionDialogVisible" class="permission-groups">
        <div v-for="g in groupedPermissions" :key="g.group" class="permission-group">
          <h4 class="group-title">{{ g.group }}</h4>
          <el-checkbox-group v-model="selectedPermissionIds">
            <el-checkbox v-for="p in g.items" :key="p.id" :label="p.id">{{ p.name }}</el-checkbox>
          </el-checkbox-group>
        </div>
      </div>
      <template #footer><el-button @click="permissionDialogVisible = false">取消</el-button><el-button type="primary" @click="handleSavePermissions">保存</el-button></template>
    </el-dialog>
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h3 { font-size: 18px; font-weight: 600; margin: 0; }
.permission-groups { max-height: 400px; overflow-y: auto; }
.permission-group { margin-bottom: 16px; }
.permission-group:last-child { margin-bottom: 0; }
.group-title { font-size: 14px; font-weight: 600; color: #303133; margin-bottom: 8px; padding-bottom: 6px; border-bottom: 1px solid #ebeef5; }
</style>
