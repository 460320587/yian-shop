<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { getAdminBanners, createBanner, updateBanner, deleteBanner, getAdminAnnouncements, createAnnouncement, updateAnnouncement, deleteAnnouncement } from '@/api/admin'
import { ElMessage, ElMessageBox } from 'element-plus'

const activeTab = ref('banner')
const banners = ref<any[]>([])
const bannerDialog = ref(false)
const bannerForm = reactive<any>({ title: '', image: '', image_mobile: '', link_type: 1, link_target: '', sort: 0, status: 1 })
const bannerEditId = ref<number | null>(null)

function resetBannerForm() { bannerForm.title = ''; bannerForm.image = ''; bannerForm.image_mobile = ''; bannerForm.link_type = 1; bannerForm.link_target = ''; bannerForm.sort = 0; bannerForm.status = 1; bannerEditId.value = null }
async function loadBanners() { try { banners.value = await getAdminBanners() } catch (e) { console.error(e) } }
function openBannerCreate() { resetBannerForm(); bannerDialog.value = true }
function openBannerEdit(row: any) { Object.assign(bannerForm, row); bannerEditId.value = row.id; bannerDialog.value = true }
async function saveBanner() { try { if (bannerEditId.value) { await updateBanner(bannerEditId.value, bannerForm) } else { await createBanner(bannerForm) }; bannerDialog.value = false; loadBanners() } catch (e) { console.error(e) } }
async function removeBanner(row: any) { try { await ElMessageBox.confirm('确认删除？', '提示', { type: 'warning' }); await deleteBanner(row.id); loadBanners() } catch { } }

const announcements = ref<any[]>([])
const annoDialog = ref(false)
const annoForm = reactive<any>({ title: '', content: '', type: 1, is_popup: 0, sort: 0, status: 1 })
const annoEditId = ref<number | null>(null)

function resetAnnoForm() { annoForm.title = ''; annoForm.content = ''; annoForm.type = 1; annoForm.is_popup = 0; annoForm.sort = 0; annoForm.status = 1; annoEditId.value = null }
async function loadAnnouncements() { try { announcements.value = await getAdminAnnouncements() } catch (e) { console.error(e) } }
function openAnnoCreate() { resetAnnoForm(); annoDialog.value = true }
function openAnnoEdit(row: any) { Object.assign(annoForm, row); annoEditId.value = row.id; annoDialog.value = true }
async function saveAnno() { try { if (annoEditId.value) { await updateAnnouncement(annoEditId.value, annoForm) } else { await createAnnouncement(annoForm) }; annoDialog.value = false; loadAnnouncements() } catch (e) { console.error(e) } }
async function removeAnno(row: any) { try { await ElMessageBox.confirm('确认删除？', '提示', { type: 'warning' }); await deleteAnnouncement(row.id); loadAnnouncements() } catch { } }

onMounted(() => { loadBanners(); loadAnnouncements() })
</script>

<template>
  <div class="banner-management">
    <el-tabs v-model="activeTab">
      <el-tab-pane label="Banner" name="banner">
        <div class="section-header"><h3>Banner 列表</h3><el-button type="primary" @click="openBannerCreate">新建</el-button></div>
        <el-table :data="banners" stripe>
          <el-table-column prop="title" label="标题" />
          <el-table-column prop="sort" label="排序" />
          <el-table-column prop="status" label="状态" />
          <el-table-column label="操作"><template #default="scope"><el-button link type="primary" @click="openBannerEdit(scope.row)">编辑</el-button><el-button link type="danger" @click="removeBanner(scope.row)">删除</el-button></template></el-table-column>
        </el-table>
      </el-tab-pane>
      <el-tab-pane label="公告" name="announcement">
        <div class="section-header"><h3>公告列表</h3><el-button type="primary" @click="openAnnoCreate">新建</el-button></div>
        <el-table :data="announcements" stripe>
          <el-table-column prop="title" label="标题" />
          <el-table-column prop="type" label="类型" />
          <el-table-column prop="status" label="状态" />
          <el-table-column label="操作"><template #default="scope"><el-button link type="primary" @click="openAnnoEdit(scope.row)">编辑</el-button><el-button link type="danger" @click="removeAnno(scope.row)">删除</el-button></template></el-table-column>
        </el-table>
      </el-tab-pane>
    </el-tabs>
    <el-dialog v-model="bannerDialog" :title="bannerEditId ? '编辑 Banner' : '新建 Banner'" width="500px"><el-form :model="bannerForm" label-width="90px"><el-form-item label="标题"><el-input v-model="bannerForm.title" /></el-form-item><el-form-item label="图片"><el-input v-model="bannerForm.image" /></el-form-item><el-form-item label="排序"><el-input-number v-model="bannerForm.sort" :min="0" /></el-form-item></el-form><template #footer><el-button @click="bannerDialog = false">取消</el-button><el-button type="primary" @click="saveBanner">保存</el-button></template></el-dialog>
    <el-dialog v-model="annoDialog" :title="annoEditId ? '编辑公告' : '新建公告'" width="500px"><el-form :model="annoForm" label-width="90px"><el-form-item label="标题"><el-input v-model="annoForm.title" /></el-form-item><el-form-item label="内容"><el-input v-model="annoForm.content" type="textarea" :rows="4" /></el-form-item></el-form><template #footer><el-button @click="annoDialog = false">取消</el-button><el-button type="primary" @click="saveAnno">保存</el-button></template></el-dialog>
  </div>
</template>

<style scoped>
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.section-header h3 { font-size: 16px; font-weight: 600; margin: 0; }
</style>
