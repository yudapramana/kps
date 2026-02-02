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
                  {{ formatDate(item.start_date) }}
                  &ndash;
                  {{ formatDate(item.end_date) }}
                </td>

                <td>
                  {{ item.location || '-' }}<br>
                  <small class="text-muted">{{ item.venue || '-' }}</small>
                </td>

                <td class="text-center">
                  <span
                    class="badge"
                    :class="item.is_active ? 'badge-success' : 'badge-secondary'"
                  >
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
          <div class="modal-header py-2">
            <h5 class="modal-title">
              {{ isEdit ? 'Edit Event' : 'Tambah Event' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body">
            <form @submit.prevent="submitForm">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Nama Event</label>
                    <input v-model="form.name" class="form-control form-control-sm" required />
                  </div>

                  <div class="form-group">
                    <label>Tema</label>
                    <input v-model="form.theme" class="form-control form-control-sm" />
                  </div>

                  <div class="form-group">
                    <label>Lokasi</label>
                    <input v-model="form.location" class="form-control form-control-sm" />
                  </div>

                  <div class="form-group">
                    <label>Venue</label>
                    <input v-model="form.venue" class="form-control form-control-sm" />
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label>Early Bird End Date</label>
                    <input v-model="form.early_bird_end_date" type="date" class="form-control form-control-sm" />
                  </div>

                  <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input v-model="form.start_date" type="date" class="form-control form-control-sm" required />
                  </div>

                  <div class="form-group">
                    <label>Tanggal Selesai</label>
                    <input v-model="form.end_date" type="date" class="form-control form-control-sm" required />
                  </div>

                  <div class="form-group">
                    <label>Status</label>
                    <select v-model="form.is_active" class="form-control form-control-sm">
                      <option :value="true">Aktif</option>
                      <option :value="false">Nonaktif</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label>Deskripsi</label>
                <textarea v-model="form.description" class="form-control form-control-sm" rows="3"></textarea>
              </div>

              <div class="text-right">
                <button class="btn btn-primary btn-sm" :disabled="isSubmitting">
                  <i class="fas fa-save mr-1"></i> Simpan
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
  early_bird_end_date: '',
  start_date: '',
  end_date: '',
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
  form.value = {
    id: null,
    name: '',
    theme: '',
    description: '',
    early_bird_end_date: '',
    start_date: '',
    end_date: '',
    location: '',
    venue: '',
    is_active: false,
  }
  $('#eventModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = { ...item }
  $('#eventModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true
  if (isEdit.value) {
    await axios.put(`/api/v1/events/${form.value.id}`, form.value)
  } else {
    await axios.post('/api/v1/events', form.value)
  }
  $('#eventModal').modal('hide')
  fetchData(meta.value.current_page)
  isSubmitting.value = false
}

const deleteItem = async (item) => {
  if (!confirm(`Hapus event "${item.name}"?`)) return
  await axios.delete(`/api/v1/events/${item.id}`)
  fetchData(meta.value.current_page)
}

const changePage = (page) => fetchData(page)

watch(search, useDebounceFn(() => fetchData(1), 400))
watch(perPage, () => fetchData(1))

onMounted(fetchData)

// Helper: jangan pakai new Date() untuk menghindari +1/-1 hari
const formatDate = (val) => {
  if (!val) return '-'
  const str = String(val).substring(0, 10)
  const [year, month, day] = str.split('-')
  const bulanIndo = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
  ]
  return `${day} ${bulanIndo[parseInt(month, 10) - 1]} ${year}`
}
</script>
