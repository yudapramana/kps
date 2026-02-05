<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import { useAuthUserStore } from '@/stores/AuthUserStore'
import Swal from 'sweetalert2'

const authUserStore = useAuthUserStore()

const eventData = computed(() => authUserStore.eventData || null)
const eventId   = computed(() => eventData.value?.id || null)

/* ================= STATE ================= */
const items = ref([])
const meta = ref({})
const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)
const selectedRegistration = ref(null)

const showDetail = (item) => {
  selectedRegistration.value = item
  $('#registrationDetailModal').modal('show')
}


/* ================= FETCH ================= */
const fetchData = async (page = 1) => {
  if (!eventId.value) return

  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/registrations', {
      params: {
        event_id: eventId.value,
        page,
        per_page: perPage.value,
        search: search.value,
      },
    })

    items.value = res.data.data.data
    meta.value  = res.data.data
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal memuat data registrasi',
    })
  } finally {
    isLoading.value = false
  }
}

/* ================= HELPERS ================= */

const getTotalTransfer = (item) => {
  const base = Number(item.total_amount || 0)
  const unique = Number(item.unique_code || 0)

  return base + unique
}


const paymentStepClass = (step) => ({
  choose_bank: 'badge-secondary',
  waiting_transfer: 'badge-warning',
  waiting_verification: 'badge-info',
  paid: 'badge-success',
}[step] || 'badge-light')


const getPackageLabel = (item) => {
  if (!item.pricing_item) return '-'

  const wc = item.pricing_item.workshop_count

  if (wc === 0) return 'Symposium'
  if (wc === 1) return 'Symposium + 1 Workshop'
  return `Symposium + ${wc} Workshop`
}

const formatPrice = (val) =>
  Number(val).toLocaleString('id-ID')

const paymentStepBadge = (step) => ({
  choose_bank: 'badge-secondary',
  waiting_transfer: 'badge-warning',
  waiting_verification: 'badge-info',
  paid: 'badge-success',
}[step] || 'badge-light')

watch(search, useDebounceFn(() => fetchData(1), 400))
watch(perPage, () => fetchData(1))
watch(eventId, () => fetchData(1))

onMounted(() => {
  if (eventId.value) fetchData()
})
</script>

<template>
  <!-- HEADER -->
  <section class="content-header">
    <div class="container-fluid">
      <h1 class="mb-1">Registrations</h1>
      <p class="mb-0 text-muted text-sm">
        Data pendaftaran & pembayaran peserta
      </p>
    </div>
  </section>

  <!-- CONTENT -->
  <section class="content">
    <div class="container-fluid">
      <div class="card">

        <!-- FILTER -->
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center w-100">
            <div>
              <label class="mb-0 mr-1 text-sm text-muted">Tampilkan</label>
              <select v-model.number="perPage" class="form-control form-control-sm d-inline-block w-auto">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>

              <!-- REFRESH BUTTON -->
                <button
                class="btn btn-outline-secondary btn-sm ml-2"
                @click="fetchData(meta.current_page || 1)"
                :disabled="isLoading"
                >
                <i class="fas fa-sync-alt"></i>
                </button>
            </div>

            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm w-auto"
              style="min-width:260px"
              placeholder="Cari nama peserta / email..."
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
                <th>Paket</th>
                <th>Bank</th>
                <th>Total</th>
                <th style="width:140px">Payment Step</th>
                <th style="width:90px">Status</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="7" class="text-center text-muted">
                  Belum ada registrasi.
                </td>
              </tr>

              <tr
                v-for="(item, index) in items"
                :key="item.id"
                style="cursor:pointer"
                @click="showDetail(item)"
                >
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>

                <!-- PARTICIPANT -->
                <td>
                    <strong>{{ item.participant.full_name }}</strong><br>
                    <small class="text-muted">{{ item.participant.email || '-' }}</small><br>
                    <small class="text-muted">
                    {{ item.participant.participant_category?.name }}
                    </small>
                </td>

                <!-- PACKAGE -->
                <td>
                    {{ getPackageLabel(item) }}<br>
                    <small class="text-muted">
                    {{ item.pricing_item.bird_type.toUpperCase() }} Bird
                    </small>
                </td>

                <!-- BANK -->
                <td>{{ item.bank?.name || '-' }}</td>

                <!-- TOTAL -->
                <td>
                    Rp {{ formatPrice(getTotalTransfer(item)) }}
                    <div v-if="item.unique_code" class="text-xs text-muted">
                    Kode unik: {{ item.unique_code }}
                    </div>
                </td>

                <!-- PAYMENT STEP -->
                <td>
                    <span class="badge" :class="paymentStepClass(item.payment_step)">
                    {{ item.payment_step }}
                    </span>
                </td>

                <!-- STATUS -->
                <td>
                    <span
                    class="badge"
                    :class="item.status === 'paid'
                        ? 'badge-success'
                        : item.status === 'cancelled'
                        ? 'badge-danger'
                        : 'badge-warning'"
                    >
                    {{ item.status }}
                    </span>
                </td>
                </tr>

            </tbody>
          </table>
        </div>

        <!-- FOOTER -->
        <div class="card-footer clearfix py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted text-sm">
              Menampilkan {{ meta.from || 0 }} – {{ meta.to || 0 }}
              dari {{ meta.total || 0 }} data
            </div>

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


<div class="modal fade" id="registrationDetailModal">
  <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <!-- HEADER -->
      <div class="modal-header">
        <div>
          <h5 class="mb-0">Registration Detail</h5>
          <small class="text-muted">
            {{ selectedRegistration?.participant.full_name }}
          </small>
        </div>
        <button class="close" data-dismiss="modal">&times;</button>
      </div>

      <!-- BODY -->
      <div class="modal-body" v-if="selectedRegistration">

        <!-- PARTICIPANT -->
        <div class="border rounded p-2 mb-3">
            <div class="text-muted text-xs">Data Peserta</div>
            <strong>{{ selectedRegistration.participant.full_name }}</strong><br>
            <small class="text-muted">
            {{ selectedRegistration.participant.email || '-' }} ·
            {{ selectedRegistration.participant.participant_category?.name }}
            </small>
        </div>

        <!-- PACKAGE -->
        <div class="border rounded p-2 mb-3">
            <div class="text-muted text-xs">Paket</div>
            <div class="font-weight-bold">
            {{ getPackageLabel(selectedRegistration) }}
            </div>
            <small class="text-muted">
            {{ selectedRegistration.pricing_item.bird_type.toUpperCase() }} Bird
            </small>
        </div>

        <!-- PAYMENT DETAIL -->
        <div class="border rounded p-2 mb-3">
            <div class="text-muted text-xs mb-1">Pembayaran</div>

            <div class="d-flex justify-content-between">
            <span>Harga Paket</span>
            <span>Rp {{ formatPrice(selectedRegistration.total_amount) }}</span>
            </div>

            <div class="d-flex justify-content-between">
            <span>Kode Unik</span>
            <span class="text-danger">
                {{ selectedRegistration.unique_code || '-' }}
            </span>
            </div>

            <hr class="my-1">

            <div class="d-flex justify-content-between font-weight-bold">
            <span>Total Transfer</span>
            <span class="text-danger">
                Rp {{ formatPrice(getTotalTransfer(selectedRegistration)) }}
            </span>
            </div>
        </div>

        <!-- BANK -->
        <div class="border rounded p-2 mb-3"  style="line-height: normal;">
            <div class="text-muted text-xs">Bank Tujuan</div>
            <div class="font-weight-bold p-0 m-0">
            </div>
            <div class="font-weight-bold p-0 m-0">
            {{ selectedRegistration.bank?.account_number || '-' }} <br>
            {{ selectedRegistration.bank?.name || '-' }}
            </div>
            <small class="text-muted">
            a.n {{ selectedRegistration.bank?.account_name || '-' }}
            </small>
        </div>

        <!-- STATUS -->
        <!-- <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted text-xs">Status Pembayaran</span>
            <span
            class="badge"
            :class="paymentStepClass(selectedRegistration.payment_step)"
            >
            {{ selectedRegistration.payment_step }}
            </span>
        </div> -->

        <!-- TIMELINE -->
        <div class="mb-4">
            <div class="text-muted text-xs mb-2">Timeline Pembayaran</div>

            <div class="position-relative">
                <!-- GARIS -->
                <div
                class="position-absolute bg-light"
                style="top:5px; left:0; right:0; height:2px;"
                ></div>

                <!-- STEPS -->
                <div class="d-flex justify-content-between text-xs position-relative">
                <div
                    v-for="step in ['choose_bank','waiting_transfer','waiting_verification','paid']"
                    :key="step"
                    class="text-center flex-fill"
                >
                    <div
                    class="mx-auto rounded-circle mb-1"
                    :class="step === selectedRegistration.payment_step
                        ? 'bg-danger'
                        : 'bg-secondary'"
                    style="width:12px;height:12px"
                    ></div>

                    <div
                    :class="step === selectedRegistration.payment_step
                        ? 'text-danger font-weight-bold'
                        : 'text-muted'"
                    style="font-size:11px"
                    >
                    {{ step.replace('_', ' ') }}
                    </div>
                </div>
                </div>
            </div>
        </div>





        <!-- ITEMS -->
        <div v-if="selectedRegistration.items?.length">
            <div class="text-muted text-xs mb-2">Item Registrasi</div>

            <table class="table table-sm table-bordered mb-0">
                <thead class="thead-light">
                <tr>
                    <th style="width:40px">#</th>
                    <th>Judul</th>
                    <th style="width:110px">Tipe</th>
                </tr>
                </thead>
                <tbody>
                <tr
                    v-for="(ri, idx) in selectedRegistration.items"
                    :key="ri.id"
                >
                    <td>{{ idx + 1 }}</td>
                    <td style="font-size:13px">
                    {{ ri.activity.title }}
                    </td>
                    <td>
                    <span class="badge badge-info text-uppercase">
                        {{ ri.activity_type }}
                    </span>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>



        </div>


      <!-- FOOTER -->
      <div class="modal-footer py-2">
        <button class="btn btn-secondary btn-sm" data-dismiss="modal">
          Tutup
        </button>
      </div>

    </div>
  </div>
</div>


</template>
