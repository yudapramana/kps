<template>
  <!-- HEADER -->
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

  <!-- CONTENT -->
  <section class="content">
    <div class="container-fluid">
      <div class="card">

        <!-- FILTER -->
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">

            <!-- LEFT -->
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

                <!-- PAPER TYPE -->
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

            <!-- RIGHT -->
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

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Nama</th>
                <th>Judul</th>
                <th style="width:140px">Tipe</th>
                <th style="width:160px">Final Status</th>
                <th style="width:140px" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="5" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="5" class="text-center text-muted">
                  Belum ada paper final.
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.id">
                <td>
                  {{ index + 1 + (meta.current_page - 1) * meta.per_page }}
                </td>

                <td>
                  <strong>
                    {{ item.participant.full_name }}
                  </strong>
                </td>

                <td>
                  <strong>{{ item.title }}</strong><br>
                 
                </td>

                <td>
                  <span class="badge text-uppercase" :class="paperTypeBadgeClass(item)">
                    {{ item.paper_type?.name }}
                  </span>
                </td>

                <td>
                  <span
                    class="badge"
                    :class="item.final_status === 'oral_presentation'
                      ? 'badge-success'
                      : 'badge-info'"
                  >
                    {{ formatFinalStatus(item.final_status) }}
                  </span>
                </td>

                <td class="text-center">
                  <button
                    class="btn btn-primary btn-sm"
                    @click="openFinalModal(item)"
                  >
                    <i class="fas fa-flag-checkered"></i> Final
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- FOOTER -->
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

  <!-- FINAL MODAL -->
  <div class="modal fade" id="paperFinalModal">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">

        <div class="modal-header py-2">
          <h5 class="modal-title">Final Presentation</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>

        <div class="modal-body" v-if="selectedPaper">
          <h5 class="fw-bold mb-1">{{ selectedPaper.title }}</h5>
          <div class="text-muted small mb-3">
            {{ selectedPaper.authors.map(a => a.name).join(', ') }}
          </div>

          <div class="form-group">
            <label class="fw-semibold">Final Presentation Status</label>
            <select
              v-model="finalStatus"
              class="form-control form-control-sm"
            >
              <option value="">-- Pilih --</option>
              <option value="oral_presentation">Oral Presentation</option>
              <option value="poster_presentation">Poster Presentation</option>
            </select>
          </div>
        </div>

        <div class="modal-footer py-2">
          <button class="btn btn-secondary btn-sm" data-dismiss="modal">
            Batal
          </button>
          <button
            class="btn btn-success btn-sm"
            :disabled="isSubmitting"
            @click="submitFinal"
          >
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
            Simpan Final
          </button>
        </div>

      </div>
    </div>
  </div>

</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import Swal from 'sweetalert2'

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
})

const items = ref([])
const meta = ref({})
const search = ref('')
const perPage = ref(10)
const paperTypeFilter = ref('')
const isLoading = ref(false)
const selectedPaper = ref(null)
const finalStatus = ref('')
const isSubmitting = ref(false)

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
  } finally {
    isLoading.value = false
  }
}

const openFinalModal = (paper) => {
  selectedPaper.value = paper
  finalStatus.value = paper.final_status
  $('#paperFinalModal').modal('show')
}

const submitFinal = async () => {
  if (!finalStatus.value) {
    return Swal.fire({ icon: 'warning', title: 'Final status belum dipilih' })
  }

  isSubmitting.value = true
  try {
    await axios.put(`/api/v1/papers/${selectedPaper.value.id}/final`, {
      final_status: finalStatus.value,
    })
    Toast.fire({ icon: 'success', title: 'Final presentation disimpan' })
    $('#paperFinalModal').modal('hide')
    fetchData(meta.value.current_page)
  } finally {
    isSubmitting.value = false
  }
}

const paperTypeBadgeClass = (paper) =>
  paper?.paper_type?.code === 'RESEARCH'
    ? 'badge-info'
    : 'badge-purple'

const formatFinalStatus = (val) =>
  val === 'oral_presentation' ? 'Oral' : 'Poster'

const changePage = (p) => fetchData(p)

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
</style>
