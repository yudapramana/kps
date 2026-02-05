<template>
  <!-- HEADER -->
  <section class="content-header">
    <div class="container-fluid">
      <h1 class="mb-1">Payments</h1>
      <p class="mb-0 text-muted text-sm">
        Daftar pembayaran masuk dari peserta
      </p>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">

        <!-- FILTER -->
        <div class="card-header">
          <div class="row g-2">

            <div class="col-md-2">
              <select v-model="filters.status" class="form-control form-control-sm">
                <option value="">Semua Status</option>
                <option value="pending">Pending</option>
                <option value="verified">Verified</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>

            <div class="col-md-2">
              <select v-model="filters.payment_method" class="form-control form-control-sm">
                <option value="">Semua Metode</option>
                <option value="bank_transfer">Bank Transfer</option>
              </select>
            </div>

            <div class="col-md-2">
              <select v-model="filters.bank_id" class="form-control form-control-sm">
                <option value="">Semua Bank</option>
                <option v-for="b in banks" :key="b.id" :value="b.id">
                  {{ b.name }}
                </option>
              </select>
            </div>

            <div class="col-md-2">
              <input type="date" v-model="filters.date_from" class="form-control form-control-sm" />
            </div>

            <div class="col-md-2">
              <input type="date" v-model="filters.date_to" class="form-control form-control-sm" />
            </div>

            <div class="col-md-2">
              <input
                v-model="filters.search"
                class="form-control form-control-sm"
                placeholder="Cari peserta / email"
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
                <th>Email</th>
                <th>Bank</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Paid At</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="7" class="text-center text-muted">
                  Tidak ada pembayaran
                </td>
              </tr>

              <tr
                v-for="(item, i) in items"
                :key="item.id"
                style="cursor:pointer"
                @click="openDetail(item)"
              >
                <td>{{ i + 1 + (meta.current_page - 1) * meta.per_page }}</td>

                <td>
                  <strong>{{ item.registration.participant.full_name }}</strong><br>
                </td>

                <td>{{ item.registration.participant.email }}</td>

                <td>{{ item.registration.bank?.name || '-' }}</td>

                <td>
                  Rp {{ formatPrice(item.amount) }}
                </td>

                <td>
                  <span
                    class="badge"
                    :class="item.status === 'verified'
                      ? 'badge-success'
                      : item.status === 'rejected'
                      ? 'badge-danger'
                      : 'badge-warning'"
                  >
                    {{ item.status }}
                  </span>
                </td>

                <td>
                  {{ item.paid_at ? formatDate(item.paid_at) : '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- FOOTER -->
        <div class="card-footer py-2">
          <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
              {{ meta.from || 0 }} – {{ meta.to || 0 }} dari {{ meta.total || 0 }}
            </small>

            <ul class="pagination pagination-sm m-0">
              <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                <a href="#" class="page-link" @click.prevent="fetchData(meta.current_page - 1)">«</a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">
                  {{ meta.current_page }} / {{ meta.last_page }}
                </span>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                <a href="#" class="page-link" @click.prevent="fetchData(meta.current_page + 1)">»</a>
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
import axios from 'axios'
import { useDebounceFn } from '@vueuse/core'

const items = ref([])
const meta = ref({})
const isLoading = ref(false)
const banks = ref([])

const filters = ref({
  status: '',
  payment_method: '',
  bank_id: '',
  date_from: '',
  date_to: '',
  search: '',
})

const fetchData = async (page = 1) => {
  isLoading.value = true
  const res = await axios.get('/api/v1/payments', {
    params: { ...filters.value, page },
  })

  items.value = res.data.data.data
  meta.value  = res.data.data
  isLoading.value = false
}

const fetchBanks = async () => {
  const res = await axios.get('/api/v1/banks')
  banks.value = res.data.data.data
}

const openDetail = (item) => {
  // nanti buka modal / route detail
  console.log('OPEN PAYMENT DETAIL', item)
}

const formatPrice = (v) => Number(v).toLocaleString('id-ID')
const formatDate = (v) => new Date(v).toLocaleDateString('id-ID')

watch(filters, useDebounceFn(() => fetchData(1), 500), { deep: true })

onMounted(() => {
  fetchBanks()
  fetchData()
})
</script>
