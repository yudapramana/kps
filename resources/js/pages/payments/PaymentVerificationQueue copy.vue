<template>
  <!-- HEADER -->
  <section class="content-header">
    <div class="container-fluid">
      <h1 class="mb-1">Payment Verification Queue</h1>
      <p class="mb-0 text-muted text-sm">
        Antrian pembayaran menunggu verifikasi
      </p>
    </div>
  </section>

  <!-- CONTENT -->
  <section class="content">
    <div class="container-fluid">
      <div class="card">

        <!-- FILTER -->
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">

            <div class="d-flex align-items-center gap-2">
              <label class="mb-0 mr-2 text-sm text-muted">Tampilkan</label> 
              <select v-model.number="perPage" class="form-control form-control-sm w-auto">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>
              <span class="text-sm text-muted ml-1 mr-3">Entri</span>

              <!-- 🔄 REFRESH -->
              <button
                class="btn btn-outline-secondary btn-sm"
                title="Refresh data"
                @click="fetchData(meta.current_page || 1)"
              >
                <i class="fas fa-sync"></i> Refresh
              </button>
            </div>


            <input
              v-model="search"
              class="form-control form-control-sm w-auto"
              style="min-width:260px"
              placeholder="Cari peserta / email"
            />
          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Peserta</th>
                <th>Bank</th>
                <th>Amount</th>
                <th>Proof</th>
                <th style="width:120px" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="6" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="6" class="text-center text-muted">
                  Tidak ada pembayaran pending
                </td>
              </tr>

              <tr v-for="(item, i) in items" :key="item.id">
                <td>{{ i + 1 + (meta.current_page - 1) * meta.per_page }}</td>

                <td>
                  <strong>{{ item.registration.participant.full_name }}</strong><br>
                  <small class="text-muted">
                    {{ item.registration.participant.email }}
                  </small>
                </td>

                <td>{{ item.registration.bank?.name || '-' }}</td>

                <td>
                  Rp {{ formatPrice(item.amount) }}
                </td>

                <td>
                  <a href="#" @click.prevent="openModal(item)">Lihat</a>
                </td>

                <td class="text-center">
                  <button class="btn btn-primary btn-sm" @click="openModal(item)">
                    Verifikasi
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

  <!-- MODAL -->
  <div class="modal fade" id="verifyModal">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content" v-if="selected">

        <div class="modal-header">
          <div>
            <h5 class="mb-0">Verifikasi Pembayaran</h5>
            <small class="text-muted">
              {{ selected.registration.participant.full_name }}
            </small>
          </div>
          <button class="close" data-dismiss="modal">&times;</button>
        </div>

        <div class="modal-body">

          <!-- META -->
          <div class="border rounded p-2 mb-3">
            <div class="d-flex justify-content-between">
              <span>Bank</span>
              <strong>{{ selected.registration.bank?.name }}</strong>
            </div>
            <div class="d-flex justify-content-between">
              <span>Amount</span>
              <strong class="text-danger">
                Rp {{ formatPrice(selected.amount) }}
              </strong>
            </div>
          </div>

          <!-- PROOF -->
          <div class="mb-3 text-center">

            <!-- LOADING -->
            <div
              v-if="proofLoading"
              class="d-flex justify-content-center align-items-center"
              style="height: 360px;"
            >
              <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
              </div>
            </div>

            <!-- IMAGE -->
            <img
              v-show="!proofLoading && !proofError"
              :src="`/api/v1/secure/payments/proof/${selected.id}`"
              class="img-fluid rounded border"
              style="max-height:360px"
              @load="proofLoading = false"
              @error="
                proofLoading = false;
                proofError = true;
                Toast.fire({
                  icon: 'error',
                  title: 'Bukti pembayaran gagal dimuat'
                })
              "
            />

            <!-- ERROR -->
            <div
              v-if="proofError"
              class="text-danger text-sm"
            >
              Gagal memuat bukti pembayaran
            </div>

          </div>



          <!-- NOTES -->
          <div class="form-group">
            <label class="text-sm">
              Catatan Verifikasi (wajib jika reject)
            </label>

            <!-- TEMPLATE SELECT -->
            <select
              class="form-control form-control-sm mb-2"
              v-model="selectedTemplate"
              @change="applyTemplate"
            >
              <option
                v-for="(tpl, i) in noteTemplates"
                :key="i"
                :value="tpl.value"
              >
                {{ tpl.label }}
              </option>
            </select>

            <!-- MANUAL NOTES -->
            <textarea
              v-model="notes"
              class="form-control form-control-sm"
              rows="3"
              placeholder="Catatan bisa diedit atau ditambahkan..."
            ></textarea>
          </div>


        </div>

        <div class="modal-footer py-2">
          <button class="btn btn-secondary btn-sm" data-dismiss="modal">
            Batal
          </button>

          <button
            class="btn btn-danger btn-sm"
            :disabled="isSubmitting"
            @click="submit('reject')"
          >
            Reject
          </button>

          <button
            class="btn btn-success btn-sm"
            :disabled="isSubmitting"
            @click="submit('approve')"
          >
            Approve
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import api from '@/lib/api'
import publicApi from '@/lib/public'
import { useDebounceFn } from '@vueuse/core'
import { useAuthUserStore } from '../../stores/AuthUserStore.js';
import Swal from 'sweetalert2'

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 2500,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer)
    toast.addEventListener('mouseleave', Swal.resumeTimer)
  }
})

const noteTemplates = [
  {
    value: '',
    label: '-- Pilih template catatan --'
  },
  {
    value: 'Bukti pembayaran tidak jelas atau buram. Mohon unggah ulang bukti pembayaran yang lebih jelas.',
    label: 'Bukti tidak jelas / buram'
  },
  {
    value: 'Nominal pembayaran tidak sesuai dengan tagihan yang tertera di sistem.',
    label: 'Nominal tidak sesuai'
  },
  {
    value: 'Bukti pembayaran bukan milik peserta yang bersangkutan.',
    label: 'Bukti bukan milik peserta'
  },
  {
    value: 'Pembayaran belum masuk ke rekening tujuan. Silakan konfirmasi ulang.',
    label: 'Dana belum masuk'
  },
  {
    value: 'Bukti pembayaran valid dan sesuai. Pembayaran disetujui.',
    label: 'Pembayaran valid (approve)'
  }
]

const selectedTemplate = ref('')

const applyTemplate = () => {
  if (!selectedTemplate.value) return
  notes.value = selectedTemplate.value
}



const authUserStore = useAuthUserStore();

const items = ref([])
const meta = ref({})
const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)

const selected = ref(null)
const notes = ref('')
const isSubmitting = ref(false)
const proofLoading = ref(true)
const proofError   = ref(false)

const openModal = (item) => {
  selected.value = item
  notes.value = ''

  proofLoading.value = true
  proofError.value   = false

  $('#verifyModal').modal('show')
}

const fetchData = async (page = 1) => {
  isLoading.value = true

  try {
    const res = await api.get('/api/v1/payment-verifications/queue', {
      params: { page, per_page: perPage.value, search: search.value },
    })

    items.value = res.data.data.data
    meta.value  = res.data.data
    
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: 'Gagal memuat data pembayaran',
    })
  } finally {
    isLoading.value = false
  }
}


const submit = async (action) => {
  if (action === 'reject' && !notes.value.trim()) {
    Swal.fire({
      icon: 'warning',
      title: 'Catatan wajib',
      text: 'Catatan wajib diisi untuk reject',
    })
    return
  }

  const confirm = await Swal.fire({
    title: action === 'approve' ? 'Approve pembayaran?' : 'Reject pembayaran?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya',
    cancelButtonText: 'Batal',
  })

  if (!confirm.isConfirmed) return

  isSubmitting.value = true

  Swal.fire({
    title: 'Memproses...',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  })

  try {
    await api.post(`/api/v1/payment-verifications/${selected.value.id}/verify`, {
      action,
      notes: notes.value,
    })

    Swal.close() // ✅ TUTUP LOADING DULU

    $('#verifyModal').modal('hide')
    fetchData(meta.value.current_page)

    Toast.fire({
      icon: 'success',
      title: 'Verifikasi pembayaran berhasil',
    })

  } catch (e) {
    Swal.close()

    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: 'Verifikasi pembayaran gagal',
    })
  } finally {
    isSubmitting.value = false
  }
}



const formatPrice = (v) => Number(v).toLocaleString('id-ID')

watch(search, useDebounceFn(() => fetchData(1), 400))
watch(perPage, () => fetchData(1))

onMounted(fetchData)
</script>
