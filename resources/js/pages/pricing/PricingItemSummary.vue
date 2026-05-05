<template>
  <!-- ================= HEADER ================= -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Pricing Summary</h1>
          <p class="mb-0 text-muted text-sm">
            Ringkasan jumlah pendaftar per paket (Early / Late, Paid / Unpaid).
          </p>
        </div>

        <button
          class="btn btn-default btn-sm"
          @click="fetchData"
        >
          <i class="fas fa-sync-alt mr-1"></i>
          Refresh
        </button>
      </div>
    </div>
  </section>

  <!-- ================= CONTENT ================= -->
  <section class="content">
    <div class="container-fluid">
      <div class="card">

        <!-- FILTER (opsional event_id) -->
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center w-100 flex-wrap">

            <!-- LEFT FILTER -->
            <div class="d-flex align-items-center mb-2 mb-md-0">
              <div class="mr-3">
                <label class="mb-0 mr-1 text-sm text-muted">Event ID</label>
                <input
                  v-model="eventId"
                  type="text"
                  class="form-control form-control-sm d-inline-block w-auto"
                  style="min-width:140px"
                  placeholder="Kosongkan jika semua event"
                />
              </div>
            </div>

            <!-- INFO -->
            <div class="text-sm text-muted">
              Total paket: {{ items.length }}
            </div>
          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">#</th>
                <th style="width:160px">Kategori Peserta</th>
                <th style="width:180px">Package</th>
                <th style="width:90px">Bird</th>
                <th style="width:120px">Harga</th>

                <th style="width:90px">Total</th>

                <th style="width:90px">Early Total</th>
                <th style="width:90px">Early Paid</th>
                <th style="width:90px">Early Unpaid</th>

                <th style="width:90px">Late Total</th>
                <th style="width:90px">Late Paid</th>
                <th style="width:90px">Late Unpaid</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="12" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="12" class="text-center text-muted">
                  Belum ada data.
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.id">
                <td>
                  {{ index + 1 }}
                </td>

                <td>
                  <span
                    class="badge"
                    :class="getCategoryBadge(item.participant_category_name)"
                  >
                    {{ item.participant_category_name || '-' }}
                  </span>
                </td>

                <td>
                  <span class="badge badge-primary">
                    {{ item.package_label }}
                  </span>
                </td>

                <td>
                  <span
                    class="badge"
                    :class="item.bird_type === 'early' ? 'badge-success' : 'badge-warning'"
                  >
                    {{ item.bird_type ? item.bird_type.toUpperCase() : '-' }}
                  </span>
                </td>

                <td>
                  {{ formatRupiah(item.price) }}
                </td>

                <td class="text-center font-weight-bold">
                  {{ item.total_registrations }}
                </td>

                <td class="text-center">
                  {{ item.early_total }}
                </td>
                <td class="text-center text-success">
                  {{ item.early_paid }}
                </td>
                <td class="text-center text-warning">
                  {{ item.early_unpaid }}
                </td>

                <td class="text-center">
                  {{ item.late_total }}
                </td>
                <td class="text-center text-success">
                  {{ item.late_paid }}
                </td>
                <td class="text-center text-warning">
                  {{ item.late_unpaid }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- FOOTER -->
        <div class="card-footer clearfix py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Total paket: {{ items.length }}
            </div>

            <button
              class="btn btn-link btn-sm text-secondary"
              @click="fetchData"
            >
              <i class="fas fa-sync-alt mr-1"></i>
              Refresh data
            </button>
          </div>
        </div>

      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios'

const items = ref([])
const isLoading = ref(false)
const eventId = ref('')

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

const formatRupiah = (value) => {
  if (!value) return '-'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value)
}

const fetchData = async () => {
  isLoading.value = true

  try {
    const res = await axios.get('/api/v1/pricing-items/summary', {
      params: {
        event_id: eventId.value || null,
      },
    })

    items.value = res.data.data || []
  } catch (err) {
    console.error(err)
  } finally {
    isLoading.value = false
  }
}

watch(eventId, () => fetchData())

onMounted(fetchData)
</script>