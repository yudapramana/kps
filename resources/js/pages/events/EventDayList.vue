<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import axios from 'axios'
import { useAuthUserStore } from '../../stores/AuthUserStore'

const authUserStore = useAuthUserStore()

/* =============================
 * EVENT AKTIF
 * ============================= */
const eventData = computed(() => authUserStore.eventData || null)
const eventId = computed(() => eventData.value?.id || null)

/* =============================
 * STATE
 * ============================= */
const items = ref([])
const isLoading = ref(false)
const isEdit = ref(false)
const perPage = ref(10)

const meta = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
})

const form = ref({
  id: null,
  date: '',
  label: '',
})

/* =============================
 * FETCH DATA
 * ============================= */
const fetchData = async (page = 1) => {
  if (!eventId.value) return

  isLoading.value = true
  const res = await axios.get('/api/v1/event-days', {
    params: {
      event_id: eventId.value,
      per_page: perPage.value,
      page,
    },
  })

  items.value = res.data.data.data
  meta.value = res.data.data
  isLoading.value = false
}

/* =============================
 * ACTIONS
 * ============================= */
const generateDays = async () => {
  if (!confirm('Generate ulang hari event? Data lama akan dihapus.')) return
  await axios.post(`/api/v1/event-days/generate/${eventId.value}`)
  fetchData()
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = { id: null, date: '', label: '' }
  $('#eventDayModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = { ...item }
  $('#eventDayModal').modal('show')
}

const submitForm = async () => {
  if (isEdit.value) {
    await axios.put(`/api/v1/event-days/${form.value.id}`, form.value)
  } else {
    await axios.post('/api/v1/event-days', {
      ...form.value,
      event_id: eventId.value,
    })
  }
  $('#eventDayModal').modal('hide')
  fetchData(meta.value.current_page)
}

const deleteItem = async (item) => {
  if (!confirm('Hapus hari event ini?')) return
  await axios.delete(`/api/v1/event-days/${item.id}`)
  fetchData(meta.value.current_page)
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchData(page)
}

/* =============================
 * WATCHERS
 * ============================= */
watch(perPage, () => fetchData(1))
watch(eventId, () => fetchData(1))

onMounted(() => {
  fetchData()
})
</script>

<template>
  <!-- ================= HEADER ================= -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Event Days</h1>
          <p class="mb-0 text-muted text-sm">
            Manajemen hari dari event
          </p>
        </div>

        <div>
          <button
            class="btn btn-success btn-sm mr-2"
            :disabled="!eventId"
            @click="generateDays"
          >
            <i class="fas fa-magic mr-1"></i> Generate dari Event
          </button>

          <button class="btn btn-primary btn-sm" @click="openCreateModal">
            + Tambah Hari
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- ================= CONTENT ================= -->
  <section class="content">
    <div class="container-fluid">
      <div class="card">

        <!-- FILTER -->
        <div class="card-header">
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
            <span class="text-sm text-muted ml-1">Entri</span>
          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Tanggal</th>
                <th>Label</th>
                <th style="width:180px" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="4" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="4" class="text-center text-muted">
                  Belum ada hari event.
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.id">
                <td>
                  {{ index + 1 + (meta.current_page - 1) * meta.per_page }}
                </td>
                <td>{{ item.date }}</td>
                <td>{{ item.label || '-' }}</td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">

                    <!-- KELOLA JADWAL -->
                    <router-link
                      :to="{
                        name: 'admin.sessions',
                        query: { event_day_id: item.id }
                      }"
                      class="btn btn-info"
                      title="Kelola Jadwal"
                    >
                      <i class="fas fa-calendar-alt"></i>
                    </router-link>

                    <!-- EDIT -->
                    <button class="btn btn-warning" @click="openEditModal(item)">
                      <i class="fas fa-edit"></i>
                    </button>

                    <!-- DELETE -->
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
              Menampilkan {{ meta.from || 0 }} – {{ meta.to || 0 }}
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
    <div class="modal fade" id="eventDayModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">
              {{ isEdit ? 'Edit Hari Event' : 'Tambah Hari Event' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body">
            <form @submit.prevent="submitForm">
              <div class="form-group">
                <label>Tanggal</label>
                <input
                  v-model="form.date"
                  type="date"
                  class="form-control form-control-sm"
                  required
                />
              </div>

              <div class="form-group">
                <label>Label</label>
                <input
                  v-model="form.label"
                  class="form-control form-control-sm"
                  placeholder="Hari-1, Hari-2, dst"
                />
              </div>

              <div class="text-right">
                <button class="btn btn-primary btn-sm">
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
