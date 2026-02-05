<template>
  <!-- HEADER -->
  <section class="content-header">
    <div class="container-fluid">
      <div>
        <h1 class="mb-1">Finance Dashboard</h1>
        <p class="mb-0 text-muted text-sm">
          Ringkasan pemasukan dan status pembayaran peserta.
        </p>
      </div>
    </div>
  </section>

  <!-- CONTENT -->
  <section class="content">
    <div class="container-fluid">

      <!-- SUMMARY -->
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="info-box bg-success">
            <span class="info-box-icon">
              <i class="fas fa-coins"></i>
            </span>
            <div class="info-box-content">
              <span class="info-box-text">Total Revenue</span>
              <span class="info-box-number">
                {{ formatCurrency(summary.total_paid) }}
              </span>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="info-box bg-warning">
            <span class="info-box-icon">
              <i class="fas fa-clock"></i>
            </span>
            <div class="info-box-content">
              <span class="info-box-text">Total Pending</span>
              <span class="info-box-number">
                {{ formatCurrency(summary.total_pending) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- DAILY REVENUE -->
      <div class="card mb-4">
        <div class="card-header">
          <strong>Daily Revenue</strong>
        </div>

        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th>Tanggal</th>
                <th>Total</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="2" class="text-center">
                  Memuat data...
                </td>
              </tr>

              <tr v-else-if="daily.length === 0">
                <td colspan="2" class="text-center text-muted">
                  Belum ada transaksi.
                </td>
              </tr>

              <tr v-for="d in daily" :key="d.date">
                <td>{{ d.date }}</td>
                <td>{{ formatCurrency(d.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- BREAKDOWN -->
      <div class="row">
        <div
          class="col-md-4"
          v-for="b in breakdowns"
          :key="b.title"
        >
          <div class="card">
            <div class="card-header">
              <strong>{{ b.title }}</strong>
            </div>

            <ul class="list-group list-group-flush">
              <li
                v-for="item in b.items"
                :key="item.name"
                class="list-group-item d-flex justify-content-between text-sm"
              >
                <span>{{ item.name }}</span>
                <span>{{ formatCurrency(item.total) }}</span>
              </li>

              <li
                v-if="b.items.length === 0"
                class="list-group-item text-muted text-center"
              >
                Tidak ada data
              </li>
            </ul>
          </div>
        </div>
      </div>

    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const isLoading = ref(false)

const summary = ref({
  total_paid: 0,
  total_pending: 0,
})

const daily = ref([])
const byBank = ref([])
const byPackage = ref([])
const byCategory = ref([])

const breakdowns = computed(() => [
  { title: 'By Bank', items: byBank.value },
  { title: 'By Package', items: byPackage.value },
  { title: 'By Participant Category', items: byCategory.value },
])

const formatCurrency = (value) =>
  'Rp ' + Number(value || 0).toLocaleString('id-ID')

const fetchData = async () => {
  isLoading.value = true

  try {
    const res = await axios.get('/api/v1/finance/dashboard')
    const data = res.data.data

    summary.value    = data.summary || summary.value
    daily.value      = data.daily || []
    byBank.value     = data.by_bank || []
    byPackage.value  = data.by_package || []
    byCategory.value = data.by_category || []

  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal memuat Finance Dashboard',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchData)
</script>
