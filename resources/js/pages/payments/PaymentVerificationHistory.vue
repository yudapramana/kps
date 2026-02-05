<template>
  <!-- HEADER -->
  <section class="content-header">
    <div class="container-fluid">
      <h1 class="mb-1">Verification History</h1>
      <p class="mb-0 text-muted text-sm">
        Riwayat verifikasi pembayaran (berbasis payment)
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
              <label class="mb-0 text-sm text-muted mr-2">Tampilkan</label>
              <select v-model.number="perPage" class="form-control form-control-sm w-auto">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>
            </div>

            <div class="d-flex gap-2">
              <input
                v-model="admin"
                class="form-control form-control-sm"
                placeholder="Filter admin"
              />
              <input
                v-model="date"
                type="date"
                class="form-control form-control-sm"
              />
            </div>
          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Peserta</th>
                <th>Bank</th>
                <th>Amount</th>
                <th>Jumlah Verifikasi</th>
                <th style="width:100px" class="text-center">Detail</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="6" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="6" class="text-center text-muted">
                  Belum ada riwayat verifikasi
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

                <td>Rp {{ formatPrice(item.amount) }}</td>

                <td>
                  <span class="badge badge-info">
                    {{ item.verifications.length }}x
                  </span>
                </td>

                <td class="text-center">
                  <button class="btn btn-outline-primary btn-sm"
                    @click="openDetail(item)">
                    Lihat
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- FOOTER -->
        <div class="card-footer py-2">
          <div class="d-flex justify-content-between">
            <small class="text-muted">
              {{ meta.from || 0 }} – {{ meta.to || 0 }} dari {{ meta.total || 0 }}
            </small>

            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a href="#" class="page-link" @click.prevent="changePage(meta.current_page - 1)">«</a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">
                  {{ meta.current_page }} / {{ meta.last_page }}
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

  <!-- DETAIL MODAL -->
  <div class="modal fade" id="detailModal">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content" v-if="selected">

        <div class="modal-header">
          <h5 class="mb-0">Riwayat Verifikasi</h5>
          <button class="close" data-dismiss="modal">&times;</button>
        </div>

        <div class="modal-body">
          <table class="table table-sm table-bordered">
            <thead>
              <tr>
                <th>Action</th>
                <th>Verified By</th>
                <th>Verified At</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="v in selected.verifications" :key="v.id">
                <td>
                  <span
                    class="badge"
                    :class="v.action === 'approve' ? 'badge-success' : 'badge-danger'"
                  >
                    {{ v.action.toUpperCase() }}
                  </span>
                </td>
                <td>{{ v.verified_by.name }}</td>
                <td>{{ formatDateTime(v.verified_at) }}</td>
                <td>{{ v.notes || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>

</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import api from '@/lib/api'
import { useDebounceFn } from '@vueuse/core'
import Swal from 'sweetalert2'

const items = ref([])
const meta = ref({})
const perPage = ref(10)
const admin = ref('')
const date = ref('')
const isLoading = ref(false)

const selected = ref(null)

const fetchData = async (page = 1) => {
  isLoading.value = true
  try {
    const res = await api.get('/api/v1/payment-verifications/history', {
      params: { page, per_page: perPage.value, admin: admin.value, date: date.value }
    })

    items.value = res.data.data.data
    meta.value  = res.data.data
  } catch {
    Swal.fire({ icon: 'error', title: 'Gagal memuat data' })
  } finally {
    isLoading.value = false
  }
}

const openDetail = (item) => {
  selected.value = item
  $('#detailModal').modal('show')
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchData(page)
}

const formatPrice = (v) => Number(v).toLocaleString('id-ID')

const formatDateTime = (value) => {
  if (!value) return '-'

  const d = new Date(value)

  const pad = (n) => String(n).padStart(2, '0')

  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ` +
         `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}


watch([admin, date], useDebounceFn(() => fetchData(1), 400))
watch(perPage, () => fetchData(1))
onMounted(fetchData)
</script>
