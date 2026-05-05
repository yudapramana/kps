<template>
  <!-- ================= HEADER ================= -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Activity Participants</h1>
          <p class="mb-0 text-muted text-sm">
            Daftar peserta berdasarkan workshop atau general symposium.
          </p>
        </div>

        <button
          class="btn btn-sm btn-success"
          @click="exportExcel"
        >
          <i class="fas fa-file-excel mr-1"></i>
          Export Excel
        </button>

        
      </div>
    </div>
  </section>

  <!-- ================= CONTENT ================= -->
  <section class="content">
    <div class="container-fluid">
      <div class="card">

        <!-- FILTER -->
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center w-100 flex-wrap">

            <!-- LEFT FILTER -->
            <div class="d-flex align-items-center mb-2 mb-md-0 flex-wrap">
              <!-- PER PAGE -->
              <div class="mr-3 mb-2">
                <label class="mb-0 mr-1 text-sm text-muted">Tampilkan</label>
                <select
                  v-model.number="perPage"
                  class="form-control form-control-sm d-inline-block w-auto"
                >
                  <option :value="10">10</option>
                  <option :value="25">25</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                </select>
                <span class="text-sm text-muted ml-1">Entri</span>
              </div>

              <!-- ACTIVITY / FILTER -->
              <div class="mr-2 mb-2">
                <select
                  v-model="selectedActivity"
                  class="form-control form-control-sm w-auto"
                  style="min-width: 180px; max-width: 220px;"
                >
                  <option value="">-- Semua Activity --</option>
                  <option
                    v-for="a in activities"
                    :key="String(a.id)"
                    :value="String(a.id)"
                  >
                    {{ a.title }}
                  </option>
                </select>
              </div>

              <!-- PARTICIPANT CATEGORY FILTER -->
              <div class="mr-2 mb-2">
                <select
                  v-model="selectedParticipantCategory"
                  class="form-control form-control-sm"
                >
                  <option value="">-- Semua Kategori Peserta --</option>
                  <option
                    v-for="category in participantCategories"
                    :key="String(category.participant_category_id)"
                    :value="String(category.participant_category_id)"
                  >
                    {{ category.name }}
                  </option>
                </select>
              </div>

              <!-- PACKAGE FILTER -->
              <div class="mr-2 mb-2">
                <select
                  v-model="selectedPackageFilter"
                  class="form-control form-control-sm"
                >
                  <option value="">-- Semua Package --</option>
                  <option value="symposium">Symposium saja</option>
                  <option value="symposium_1_ws">Symposium + 1 Workshop</option>
                  <option value="symposium_2_ws">Symposium + 2 Workshop</option>
                  <option value="ws_nurse">Workshop for Nurse</option>
                </select>
              </div>
            </div>

            <!-- RIGHT: SEARCH + EXPORT -->
            <div class="d-flex align-items-center mb-2">
              <input
                v-model="search"
                type="text"
                class="form-control form-control-sm w-auto mr-2"
                style="min-width:260px"
                placeholder="Cari nama / email / NIK / instansi..."
              />
              
            </div>

          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Nama</th>
                <th>NIK</th>
                <th>Kontak</th>
                <th>Instansi</th>
                <th style="width:150px">Kategori</th>
                <th style="width:170px">Package</th>
                <th style="width:90px">Symposium</th>
                <th style="width:220px">Workshop 1</th>
                <th style="width:220px">Workshop 2</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="10" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="10" class="text-center text-muted">
                  Belum ada peserta pada filter ini.
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.id">
                <td>
                  {{ index + 1 + (meta.current_page - 1) * meta.per_page }}
                </td>

                <td>
                  <strong>{{ item.full_name }}</strong>
                </td>

                <td>
                  <strong>{{ item.nik || '-' }}</strong>
                </td>

                <td>
                  {{ item.email || '-' }}<br>
                  <small class="text-muted">{{ item.mobile_phone || '-' }}</small>
                </td>

                <td>
                  {{ item.institution || '-' }}
                </td>

                <td>
                  <span
                    class="badge"
                    :class="getCategoryBadge(item.participant_category?.name)"
                  >
                    {{ item.participant_category?.name || '-' }}
                  </span>
                </td>

                <td>
                  <span class="badge badge-primary">
                    {{ item.package_label || 'No Package Selected' }}
                  </span>
                </td>

                <td>
                  <span
                    class="badge"
                    :class="item.includes_symposium ? 'badge-success' : 'badge-secondary'"
                  >
                    {{ item.includes_symposium ? 'Ya' : 'Tidak' }}
                  </span>
                </td>

                <td>
                  <span v-if="item.workshop_1" class="badge badge-info">
                    {{ item.workshop_1.title }}
                  </span>
                  <span v-else class="text-muted">-</span>
                </td>

                <td>
                  <span v-if="item.workshop_2" class="badge badge-info">
                    {{ item.workshop_2.title }}
                  </span>
                  <span v-else class="text-muted">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- FOOTER -->
        <div class="card-footer clearfix py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Menampilkan {{ meta.from || 0 }} - {{ meta.total || 0 }} data
            </div>

            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a href="#" class="page-link" @click.prevent="changePage(meta.current_page - 1)">«</a>
              </li>

              <li class="page-item disabled">
                <span class="page-link">
                  Halaman {{ meta.current_page || 1 }} / {{ meta.last_page || 1 }}
                </span>
              </li>

              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page || !meta.last_page }">
                <a href="#" class="page-link" @click.prevent="changePage(meta.current_page + 1)">»</a>
              </li>
            </ul>
          </div>
        </div>

      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'

const exportExcel = () => {
  const params = new URLSearchParams({
    search: search.value || '',
    activity_filter: selectedActivity.value || '',
    participant_category_id: selectedParticipantCategory.value || '',
    package_filter: selectedPackageFilter.value || '',
  })

  window.location.href = `/api/v1/activity-participants/export?${params.toString()}`
}

const getCategoryBadge = (name) => {
  if (!name) return 'badge-secondary'

  name = name.toLowerCase()

  if (name.includes('general practitioner') || name.includes('internship')) {
    return 'badge-primary'
  }

  if (name.includes('speaker') || name.includes('faculty') || name.includes('sponsor')) {
    return 'badge-dark'
  }

  if (name.includes('specialist')) {
    return 'badge-danger'
  }

  if (name.includes('student') || name.includes('nurse')) {
    return 'badge-success'
  }

  return 'badge-secondary'
}

const items = ref([])
const activities = ref([])
const participantCategories = ref([])
const meta = ref({})
const search = ref('')
const perPage = ref(50)
const selectedActivity = ref('')
const selectedParticipantCategory = ref('')
const selectedPackageFilter = ref('')
const isLoading = ref(false)

const fetchData = async (page = 1) => {
  isLoading.value = true

  try {
    const res = await axios.get('/api/v1/activity-participants', {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
        activity_filter: selectedActivity.value || null,
        participant_category_id: selectedParticipantCategory.value || null,
        package_filter: selectedPackageFilter.value || null,
      },
    })

    items.value = res.data.data?.data || []
    meta.value = res.data.data || {}
    activities.value = res.data.activities || []
    participantCategories.value = res.data.participant_categories || []
  } catch (err) {
    console.error(err)
    items.value = []
    meta.value = {}
    activities.value = []
    participantCategories.value = []
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (!page || page < 1) return
  if (meta.value.last_page && page > meta.value.last_page) return
  fetchData(page)
}

watch(selectedActivity, () => fetchData(1))
watch(selectedParticipantCategory, () => fetchData(1))
watch(selectedPackageFilter, () => fetchData(1))
watch(search, useDebounceFn(() => fetchData(1), 400))
watch(perPage, () => fetchData(1))

onMounted(fetchData)
</script>