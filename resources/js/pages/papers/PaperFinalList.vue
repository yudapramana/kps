<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Paper Finalization</h1>
          <p class="mb-0 text-muted text-sm">
            Penetapan final presentasi (Oral / Poster).
          </p>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <div>
                <label class="mb-0 mr-1 text-sm text-muted">Tampilkan</label>
                <select
                  v-model.number="perPage"
                  class="form-control form-control-sm d-inline-block w-auto"
                >
                  <option :value="10">10</option>
                  <option :value="25">25</option>
                  <option :value="50">50</option>
                </select>
                <span class="text-sm text-muted ml-1 mr-3">Entri</span>

                <select
                  v-model="paperTypeFilter"
                  class="form-control form-control-sm d-inline-block w-auto"
                  style="min-width:160px"
                >
                  <option value="">All Types</option>
                  <option value="RESEARCH">Research</option>
                  <option value="CASE">Case</option>
                </select>
              </div>
            </div>

            <div class="d-flex align-items-center gap-2">
              <input
                v-model="search"
                type="text"
                class="form-control form-control-sm"
                style="min-width:240px"
                placeholder="Cari judul..."
              />

              <button
                class="btn btn-outline-secondary btn-sm"
                @click="fetchData(meta.current_page || 1)"
                :disabled="isLoading"
              >
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': isLoading }"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-sm table-bordered table-hover text-sm mb-0 ranking-table">
            <thead class="thead-light">
              <tr>
                <th style="width:60px" class="text-center">Rank</th>
                <th style="width:180px">Nama</th>
                <th>Judul</th>
                <th style="width:110px">Tipe</th>
                <th style="width:110px" class="text-center">Nilai</th>
                <th style="width:130px" class="text-center">Final</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="6" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="6" class="text-center text-muted">
                  Belum ada paper final.
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center">
                  <strong>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</strong>
                </td>

                <td>
                  <div class="font-weight-bold text-truncate" :title="item.participant?.full_name">
                    {{ item.participant?.full_name || '-' }}
                  </div>
                </td>

                <td>
                  <div class="font-weight-bold line-clamp-2" :title="item.title">
                    {{ item.title }}
                  </div>
                </td>

                <td>
                  <span class="badge text-uppercase" :class="paperTypeBadgeClass(item)">
                    {{ item.paper_type?.name }}
                  </span>
                </td>

                <td class="text-center">
                  <span class="score-pill">
                    {{ formatScore(item.final_score) }}
                  </span>
                </td>

                <td class="text-center">
                  <span
                    class="badge"
                    :class="item.final_status === 'oral_presentation'
                      ? 'badge-success'
                      : item.final_status === 'poster_presentation'
                        ? 'badge-info'
                        : 'badge-secondary'"
                  >
                    {{ formatFinalStatus(item.final_status) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="card-footer clearfix py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }}
              dari {{ meta.total || 0 }} data
            </div>

            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a href="#" class="page-link" @click.prevent="changePage(meta.current_page - 1)">«</a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">
                  Halaman {{ meta.current_page }} / {{ meta.last_page }}
                </span>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
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
import Swal from 'sweetalert2'

const items = ref([])
const meta = ref({})
const search = ref('')
const perPage = ref(10)
const paperTypeFilter = ref('')
const isLoading = ref(false)

const fetchData = async (page = 1) => {
  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/papers/final', {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
        paper_type: paperTypeFilter.value,
      },
    })
    items.value = res.data.data.data
    meta.value = res.data.data
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal memuat data ranking paper',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  } finally {
    isLoading.value = false
  }
}

const paperTypeBadgeClass = (paper) =>
  paper?.paper_type?.code === 'RESEARCH'
    ? 'badge-info'
    : 'badge-purple'

const formatFinalStatus = (val) => {
  if (val === 'oral_presentation') return 'Oral'
  if (val === 'poster_presentation') return 'Poster'
  return '-'
}

const formatScore = (val) => {
  if (val === null || val === undefined || val === '') return '-'
  return Number(val).toFixed(2)
}

const changePage = (p) => {
  if (p < 1 || p > meta.value.last_page) return
  fetchData(p)
}

watch(search, useDebounceFn(() => fetchData(1), 400))
watch(perPage, () => fetchData(1))
watch(paperTypeFilter, () => fetchData(1))
onMounted(fetchData)
</script>

<style scoped>
.badge-purple {
  background-color: #6f42c1;
  color: #fff;
}

.ranking-table th,
.ranking-table td {
  padding: 0.45rem 0.5rem;
  vertical-align: middle;
}

.ranking-table thead th {
  position: sticky;
  top: 0;
  z-index: 1;
  background: #f8f9fa;
}

.score-pill {
  display: inline-block;
  min-width: 64px;
  padding: 0.2rem 0.45rem;
  border-radius: 999px;
  background: #f1f3f5;
  font-weight: 700;
  text-align: center;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>