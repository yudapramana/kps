<template>
  <!-- ================= HEADER ================= -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Events</h1>
          <p class="mb-0 text-muted text-sm">
            Manajemen data event (tanggal, lokasi, status).
          </p>
        </div>
        <button class="btn btn-primary btn-sm" @click="openCreateModal">
          + Tambah Event
        </button>
      </div>
    </div>
  </section>

  <!-- ================= CONTENT ================= -->
  <section class="content">
    <div class="container-fluid">
      <div class="card">

        <!-- HEADER -->
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center w-100">
            <div>
              <label class="mb-0 mr-1 text-sm text-muted">Tampilkan</label>
              <select v-model.number="perPage" class="form-control form-control-sm d-inline-block w-auto">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>
              <span class="text-sm text-muted ml-1">Entri</span>
            </div>

            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm w-auto"
              style="min-width: 240px"
              placeholder="Cari nama / tema / lokasi..."
            />
          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Nama Event</th>
                <th>Tanggal</th>
                <th>Lokasi</th>
                <th style="width:90px" class="text-center">Status</th>
                <th style="width:110px" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="6" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="6" class="text-center text-muted">
                  Belum ada event.
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.id">
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>

                <td>
                  <strong>{{ item.name }}</strong><br>
                  <small class="text-muted">{{ item.theme || '-' }}</small>
                </td>

                <td>
                  {{ formatDate(item.start_date) }} –
                  {{ formatDate(item.end_date) }}
                </td>

                <td>
                  {{ item.location || '-' }}<br>
                  <small class="text-muted">{{ item.venue || '-' }}</small>
                </td>

                <td class="text-center">
                  <span class="badge" :class="item.is_active ? 'badge-success' : 'badge-secondary'">
                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>

                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-warning" @click="openEditModal(item)">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger" @click="deleteItem(item)">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
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

    <!-- ================= MODAL ================= -->
    <div class="modal fade" id="eventModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">

          <!-- HEADER -->
          <div class="modal-header py-2">
            <h5 class="modal-title">
              {{ isEdit ? 'Edit Event' : 'Tambah Event' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <!-- BODY -->
          <div class="modal-body">
            <form @submit.prevent="submitForm">

              <!-- ================= BASIC INFO ================= -->
              <h6 class="text-muted mb-3">Basic Event Information</h6>

              <div class="row">
                <div class="col-md-6">

                  <div class="form-group">
                    <label>Nama Event</label>
                    <input
                      v-model="form.name"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Tema</label>
                    <input
                      v-model="form.theme"
                      class="form-control form-control-sm"
                    />
                  </div>

                  <div class="form-group">
                    <label>Lokasi</label>
                    <input
                      v-model="form.location"
                      class="form-control form-control-sm"
                    />
                  </div>

                  <div class="form-group">
                    <label>Venue</label>
                    <input
                      v-model="form.venue"
                      class="form-control form-control-sm"
                    />
                  </div>

                </div>

                <div class="col-md-6">

                  <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input
                      v-model="form.start_date"
                      type="date"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Tanggal Selesai</label>
                    <input
                      v-model="form.end_date"
                      type="date"
                      class="form-control form-control-sm"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Early Bird End Date</label>
                    <input
                      v-model="form.early_bird_end_date"
                      type="date"
                      class="form-control form-control-sm"
                    />
                  </div>

                  <div class="form-group">
                    <label>Status Event</label>
                    <select
                      v-model="form.is_active"
                      class="form-control form-control-sm"
                    >
                      <option :value="true">Aktif</option>
                      <option :value="false">Nonaktif</option>
                    </select>
                  </div>

                </div>
              </div>

              <div class="form-group">
                <label>Deskripsi</label>
                <textarea
                  v-model="form.description"
                  class="form-control form-control-sm"
                  rows="3"
                ></textarea>
              </div>

              <!-- ================= SUBMISSION SETTINGS ================= -->
              <hr>
              <h6 class="text-muted mb-3">Submission Settings</h6>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Submission Open At</label>
                    <input
                      v-model="form.submission_open_at"
                      type="datetime-local"
                      step="60"
                      class="form-control form-control-sm"
                    />
                    <small class="text-muted">
                      Waktu mulai pengiriman abstract / case
                    </small>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label>Submission Deadline</label>
                    <input
                      v-model="form.submission_deadline_at"
                      type="datetime-local"
                      step="60"
                      :min="form.submission_open_at || null"
                      class="form-control form-control-sm"
                    />
                    <small class="text-muted">
                      Harus setelah waktu open
                    </small>
                  </div>
                </div>
              </div>

              <!-- <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Notification Date</label>
                    <input
                      v-model="form.notification_date"
                      type="datetime-local"
                      step="60"
                      class="form-control form-control-sm"
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label>Submission Hard Close</label>
                    <input
                      v-model="form.submission_close_at"
                      type="datetime-local"
                      step="60"
                      class="form-control form-control-sm"
                    />
                    <small class="text-muted">
                      Penutupan paksa (opsional)
                    </small>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label>Submission Control</label>
                <select
                  v-model="form.submission_is_active"
                  class="form-control form-control-sm"
                >
                  <option :value="1">Aktif</option>
                  <option :value="0">Ditutup Manual</option>
                </select>
                <small class="text-muted">
                  Override manual buka / tutup submission
                </small>
              </div> -->

              <!-- ACTION -->
              <div class="text-right mt-3">
                <button
                  class="btn btn-primary btn-sm"
                  :disabled="isSubmitting"
                >
                  Simpan
                </button>
              </div>

            </form>
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
const isEdit = ref(false)
const isSubmitting = ref(false)

const form = ref({
  id: null,
  name: '',
  theme: '',
  description: '',
  start_date: '',
  end_date: '',
  early_bird_end_date: '',
  submission_open_at: '',
  submission_deadline_at: '',
  notification_date: '',
  submission_close_at: '',
  submission_is_active: true,
  location: '',
  venue: '',
  is_active: false,
})

const fetchData = async (page = 1) => {
  isLoading.value = true
  const res = await axios.get('/api/v1/events', {
    params: { page, per_page: perPage.value, search: search.value },
  })
  items.value = res.data.data.data
  meta.value = res.data.data
  isLoading.value = false
}

const openCreateModal = () => {
  isEdit.value = false
  Object.assign(form.value, {
    id: null,
    name: '',
    theme: '',
    description: '',
    start_date: '',
    end_date: '',
    early_bird_end_date: '',
    submission_open_at: '',
    submission_deadline_at: '',
    notification_date: '',
    submission_close_at: '',
    submission_is_active: true,
    location: '',
    venue: '',
    is_active: false,
  })
  $('#eventModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = { ...item }
  $('#eventModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true
  try {
    if (isEdit.value) {
      await axios.put(`/api/v1/events/${form.value.id}`, form.value)
      Toast.fire({ icon: 'success', title: 'Event diperbarui' })
    } else {
      await axios.post('/api/v1/events', form.value)
      Toast.fire({ icon: 'success', title: 'Event ditambahkan' })
    }
    $('#eventModal').modal('hide')
    fetchData(meta.value.current_page)
  } finally {
    isSubmitting.value = false
  }
}

const deleteItem = async (item) => {
  const ok = await Swal.fire({ title: 'Hapus event?', showCancelButton: true })
  if (!ok.isConfirmed) return
  await axios.delete(`/api/v1/events/${item.id}`)
  fetchData(meta.value.current_page)
}

const changePage = (page) => fetchData(page)

watch(search, useDebounceFn(() => fetchData(1), 400))
watch(perPage, () => fetchData(1))
onMounted(fetchData)

const formatDate = (val) => {
  if (!val) return '-'
  const [y, m, d] = val.substring(0, 10).split('-')
  const bln = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des']
  return `${d} ${bln[m - 1]} ${y}`
}
</script>
