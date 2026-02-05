<template>
  <!-- HEADER -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Paper Review</h1>
          <p class="mb-0 text-muted text-sm">
            Review dan validasi abstract / case yang masuk.
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

                        <!-- FILTER PAPER TYPE -->
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

                <!-- REFRESH -->
                <button
                    class="btn btn-outline-secondary btn-sm"
                    @click="fetchData(meta.current_page || 1)"
                    :disabled="isLoading"
                    title="Refresh"
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
                <th style="width:140px">Status</th>
                <th style="width:160px" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="5" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="5" class="text-center text-muted">
                  Tidak ada paper untuk direview.
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
                  <span class="badge text-uppercase ms-2" :class="paperTypeBadgeClass(item)">
                    {{ item.paper_type?.name }}
                  </span>
                </td>

                <td>
                  <span class="badge badge-warning" v-if="item.status === 'submitted'">
                    Submitted
                  </span>
                  <span class="badge badge-primary" v-else-if="item.status === 'under_review'">
                    Under Review
                  </span>
                </td>

                <!-- AKSI -->
                <td class="text-center">
                <button class="btn btn-primary btn-sm" @click="openReviewModal(item)">
                    <i class="fas fa-search"></i> Review
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

    <!-- REVIEW MODAL -->
    <div class="modal fade" id="paperReviewModal">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

        <div class="modal-header py-2">
            <h5 class="modal-title">
                Paper Review
                <span
                    v-if="selectedPaper?.paper_type"
                    class="badge text-uppercase ms-2"
                    :class="paperTypeBadgeClass(selectedPaper)"
                >
                    {{ selectedPaper.paper_type?.name }}
                </span>
            </h5>

            <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
            </button>
        </div>

        <div class="modal-body" v-if="selectedPaper">

            <!-- BASIC INFO -->
            <div class="mb-3">
            <h5 class="fw-bold mb-1">
                {{ selectedPaper.title }}
            </h5>

            <div class="text-muted small mb-1">
                {{ selectedPaper.authors.map(a => a.name).join(', ') }}
            </div>

            <!-- META DATE -->
            <div class="small text-muted">
                <span>
                <strong>Submitted:</strong>
                {{ formatDateTime(selectedPaper.submitted_at) }}
                </span>
                <span class="mx-2">•</span>
                <span>
                <strong>Reviewed:</strong>
                {{ formatDateTime(selectedPaper.reviewed_at) }}
                </span>
            </div>
            </div>

            <!-- ABSTRACT -->
            <div class="mb-4">
            <div class="fw-semibold mb-2">
                Abstract / Case Summary
            </div>

            <div
                class="p-3 border rounded-3 bg-white"
                style="text-align: justify; line-height: 1.7; font-size: 0.95rem;"
            >
                {{ selectedPaper.abstract }}
            </div>
            </div>

            <!-- FILE -->
            <div class="mb-3">
            <div class="fw-semibold mb-1">Submitted File</div>
            <a
                :href="selectedPaper.gdrive_link"
                target="_blank"
                class="text-primary"
            >
                Preview File
            </a>
            </div>

            <hr>

            <!-- DECISION -->
            <div class="form-group">
            <label class="fw-semibold">
                Review Decision
            </label>
            <select
                v-model="decision.status"
                class="form-control form-control-sm"
            >
                <option value="">-- Pilih Status --</option>
                <option value="accepted">Accepted</option>
                <option value="rejected">Rejected</option>
            </select>
            </div>

            <div
            class="form-group"
            v-if="decision.status === 'accepted'"
            >
            <label class="fw-semibold">
                Final Presentation Status
            </label>
            <select
                v-model="decision.final_status"
                class="form-control form-control-sm"
            >
                <option value="">-- Pilih Final Status --</option>
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
            @click="submitReview"
            >
            <span
                v-if="isSubmitting"
                class="spinner-border spinner-border-sm me-1"
            ></span>
            Simpan Keputusan
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
  timerProgressBar: true,
})


const items = ref([])
const meta = ref({})
const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)
const selectedPaper = ref(null)
const isSubmitting = ref(false)
const paperTypeFilter = ref('')


const decision = ref({
  status: '',
  final_status: null,
})

const openReviewModal = (paper) => {
  selectedPaper.value = paper
  decision.value = {
    status: paper.status,
    final_status: paper.final_status,
  }
  $('#paperReviewModal').modal('show')
}

const submitReview = async () => {
  if (!decision.value.status) {
    return Swal.fire({
      icon: 'warning',
      title: 'Status belum dipilih',
    })
  }

  if (
    decision.value.status === 'accepted' &&
    !decision.value.final_status
  ) {
    return Swal.fire({
      icon: 'warning',
      title: 'Final presentation belum dipilih',
    })
  }

  isSubmitting.value = true

  try {
    await axios.put(
      `/api/v1/papers/${selectedPaper.value.id}/review`,
      decision.value
    )

    Toast.fire({
      icon: 'success',
      title: 'Keputusan review berhasil disimpan',
    })

    $('#paperReviewModal').modal('hide')
    fetchData(meta.value.current_page)
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal menyimpan keputusan',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  } finally {
    isSubmitting.value = false
  }
}



const fetchData = async (page = 1) => {
  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/papers/review', {
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
      title: 'Gagal memuat data paper',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  } finally {
    isLoading.value = false
  }
}



const acceptPaper = async (item) => {
  await axios.post(`/api/v1/papers/${item.id}/accept`)
  fetchData(meta.value.current_page)
}

const rejectPaper = async (item) => {
  await axios.post(`/api/v1/papers/${item.id}/reject`)
  fetchData(meta.value.current_page)
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchData(page)
}


const paperTypeBadgeClass = (paper) => {
  const code = paper?.paper_type?.code
  if (!code) return 'badge-secondary'

  return code === 'RESEARCH'
    ? 'badge-info'
    : 'badge-purple'
}


const formatDateTime = (val) => {
  if (!val) return '-'
  const d = new Date(val)
  return d.toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

watch(paperTypeFilter, () => fetchData(1))
watch(search, useDebounceFn(() => fetchData(1), 400))
watch(perPage, () => fetchData(1))
onMounted(fetchData)
</script>


<style scoped>
.badge-purple {
  background-color: #6f42c1;
  color: #fff;
}
</style>