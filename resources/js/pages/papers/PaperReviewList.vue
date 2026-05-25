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
                <th>PIDN</th>
                <th>Judul</th>
                <th style="width:140px">Tipe</th>
                <th style="width:140px">Status</th>
                <th style="width:160px" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="6" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="6" class="text-center text-muted">
                  Tidak ada paper untuk direview.
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.id">
                <td>
                  {{ index + 1 + (meta.current_page - 1) * meta.per_page }}
                </td>

                <td>
                  <strong>
                    #{{ String(item.id).padStart(3, '0') }}
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
                <span style="font-size: larger; font-weight: bolder;">#{{ String(selectedPaper.id).padStart(3, '0') }}</span> {{ selectedPaper.title }}
            </h5>

            <!-- <div class="text-muted small mb-1">
                {{ selectedPaper.authors.map(a => a.name).join(', ') }}
            </div> -->

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

            <!-- REVIEWER SCORE -->
            <div v-if="isReviewerUser">
              <div class="form-group">
                <label class="fw-semibold">Reviewer Score</label>
                <input
                  v-model.number="decision.reviewer_score"
                  type="number"
                  min="0"
                  max="100"
                  step="0.01"
                  class="form-control form-control-sm"
                  placeholder="Masukkan nilai 0 - 100"
                  :disabled="reviewerScoreLocked"
                />
                <small class="text-muted">
                  Nilai hanya bisa disimpan sekali. Gunakan rentang 0 - 100 dengan 2 desimal.
                </small>
              </div>

              <div
                v-if="reviewerScoreLocked"
                class="alert alert-warning py-2 px-3 mb-0"
              >
                Reviewer score sudah disimpan dan tidak bisa diubah lagi.
              </div>
            </div>

            <!-- ADMIN / NON REVIEWER DECISION -->
            <div v-else>
              <div class="mb-3">
                <label class="fw-semibold d-block mb-2">Reviewer Score</label>

                <div
                  v-if="hasReviewerScore"
                  class="alert alert-info py-2 px-3 mb-0"
                >
                  <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <span class="mb-0">Nilai dari reviewer</span>
                    <strong style="font-size: 1.1rem;">
                      {{ formattedReviewerScore }}
                    </strong>
                  </div>
                </div>

                <div
                  v-else
                  class="alert alert-warning py-2 px-3 mb-0"
                >
                  Review decision belum bisa dilakukan karena reviewer belum mengisi nilai.
                </div>
              </div>

              <fieldset :disabled="!hasReviewerScore" class="decision-fieldset">
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
              </fieldset>
            </div>

        </div>

        <div class="modal-footer py-2">
          <button class="btn btn-secondary btn-sm" data-dismiss="modal">
            Batal
          </button>

          <button
            v-if="isReviewerUser"
            class="btn btn-primary btn-sm"
            :disabled="isSubmitting || reviewerScoreLocked"
            @click="submitReviewerScore"
          >
            <span
              v-if="isSubmitting"
              class="spinner-border spinner-border-sm me-1"
            ></span>
            Simpan Reviewer Score
          </button>

          <button
            v-else
            class="btn btn-success btn-sm"
            :disabled="isSubmitting || !hasReviewerScore"
            @click="submitDecision"
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
import { ref, watch, onMounted, computed } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthUserStore } from '../../stores/AuthUserStore'

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
})

const hasReviewerScore = computed(() => {
  const score = selectedPaper.value?.reviewer_score
  return score !== null && score !== '' && !Number.isNaN(Number(score))
})

const formattedReviewerScore = computed(() => {
  if (!hasReviewerScore.value) return '-'
  return Number(selectedPaper.value.reviewer_score).toFixed(2)
})

const authUserStore = useAuthUserStore()
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
  reviewer_score: null,
})

const username = computed(() => authUserStore.user?.username || '')
const isReviewerUser = computed(() =>
  username.value.toLowerCase().includes('reviewer')
)

const reviewerScoreLocked = computed(() => {
  const score = selectedPaper.value?.reviewer_score
  return score !== null && Number(score) !== 0
})

const openReviewModal = (paper) => {
  selectedPaper.value = paper
  decision.value = {
    status: paper.status === 'accepted' || paper.status === 'rejected' ? paper.status : '',
    final_status: paper.final_status,
    reviewer_score: paper.reviewer_score,
  }
  $('#paperReviewModal').modal('show')
}

const escapeHtml = (value = '') => {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

const submitReviewerScore = async () => {
  if (reviewerScoreLocked.value) {
    return Swal.fire({
      icon: 'warning',
      title: 'Reviewer score sudah dikunci',
      text: 'Nilai yang sudah disimpan tidak dapat diubah lagi.',
    })
  }

  if (
    decision.value.reviewer_score === null ||
    decision.value.reviewer_score === '' ||
    Number(decision.value.reviewer_score) < 0 ||
    Number(decision.value.reviewer_score) > 100
  ) {
    return Swal.fire({
      icon: 'warning',
      title: 'Reviewer score tidak valid',
      text: 'Masukkan nilai antara 0 sampai 100.',
    })
  }

  const confirmResult = await Swal.fire({
    icon: 'question',
    title: 'Konfirmasi reviewer score',
    html: `
      <div style="text-align:left">
        <p class="mb-2">Anda yakin ingin menyimpan nilai untuk paper berikut?</p>
        <div style="padding:10px 12px;background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;">
          <div style="font-size:12px;color:#6c757d;margin-bottom:4px;">Paper ID</div>
          <div style="font-weight:600;">#${String(selectedPaper.value?.id ?? 0).padStart(3, '0')}</div>
          <div style="font-size:12px;color:#6c757d;margin-bottom:4px;">Judul Paper</div>
          <div style="font-weight:600;">${escapeHtml(selectedPaper.value?.title ?? '-')}</div>
        </div>
        <div style="margin-top:12px;padding:10px 12px;background:#eef6ff;border:1px solid #b6d4fe;border-radius:6px;">
          <div style="font-size:12px;color:#6c757d;margin-bottom:4px;">Reviewer Score</div>
          <div style="font-weight:700;font-size:18px;">${Number(decision.value.reviewer_score).toFixed(2)}</div>
        </div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Ya, simpan nilai',
    cancelButtonText: 'Batal',
    reverseButtons: true,
    focusCancel: true,
  })

  if (!confirmResult.isConfirmed) {
    return
  }

  isSubmitting.value = true

  try {
    await axios.put(
      `/api/v1/papers/${selectedPaper.value.id}/review`,
      {
        reviewer_score: Number(decision.value.reviewer_score),
      }
    )

    Toast.fire({
      icon: 'success',
      title: 'Reviewer score berhasil disimpan',
    })

    $('#paperReviewModal').modal('hide')
    fetchData(meta.value.current_page || 1)
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal menyimpan reviewer score',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  } finally {
    isSubmitting.value = false
  }
}

const submitDecision = async () => {

  if (!hasReviewerScore.value) {
    return Swal.fire({
      icon: 'warning',
      title: 'Reviewer score belum tersedia',
      text: 'Review decision hanya bisa disimpan setelah reviewer memberi nilai.',
    })
  }


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
      {
        status: decision.value.status,
        final_status:
          decision.value.status === 'accepted'
            ? decision.value.final_status
            : null,
      }
    )

    Toast.fire({
      icon: 'success',
      title: 'Keputusan review berhasil disimpan',
    })

    $('#paperReviewModal').modal('hide')
    fetchData(meta.value.current_page || 1)
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

.decision-fieldset[disabled] {
  opacity: 0.65;
  pointer-events: none;
}
</style>