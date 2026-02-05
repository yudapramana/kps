<template>
  <!-- HEADER -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">
            Rooms
          </h1>
          <p class="mb-0 text-muted text-sm">
            Manajemen ruangan untuk symposium, workshop, dan lainnya.
          </p>
        </div>

        <button
          class="btn btn-primary btn-sm"
          :disabled="!eventId"
          @click="openCreateModal"
        >
          + Tambah Ruangan
        </button>
      </div>
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

            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm w-auto"
              style="min-width:220px"
              placeholder="Cari nama ruangan..."
            />
          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Nama Ruangan</th>
                <th style="width:140px">Kategori</th>
                <th style="width:120px" class="text-center">Kapasitas</th>
                <th style="width:110px" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="5" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="5" class="text-center text-muted">
                  Belum ada ruangan.
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.id">
                <td>
                  {{ index + 1 + (meta.current_page - 1) * meta.per_page }}
                </td>
                <td><strong>{{ item.name }}</strong></td>
                <td>
                  <span class="badge badge-info text-uppercase">
                    {{ item.category }}
                  </span>
                </td>
                <td class="text-center">
                  {{ item.capacity ?? '-' }}
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

    <!-- MODAL -->
    <div class="modal fade" id="roomModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">
              {{ isEdit ? 'Edit Ruangan' : 'Tambah Ruangan' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body">
            <form @submit.prevent="submitForm">
              <div class="form-group">
                <label>Nama Ruangan</label>
                <input v-model="form.name" class="form-control form-control-sm" required />
              </div>

              <div class="form-group">
                <label>Kategori</label>
                <select v-model="form.category" class="form-control form-control-sm" required>
                  <option value="">-- Pilih Kategori --</option>
                  <option value="symposium">Symposium</option>
                  <option value="workshop">Workshop</option>
                  <option value="jeopardy">Jeopardy</option>
                </select>
              </div>

              <div class="form-group">
                <label>Kapasitas</label>
                <input
                  v-model.number="form.capacity"
                  type="number"
                  min="0"
                  class="form-control form-control-sm"
                />
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
import { ref, computed, watch, onMounted } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'
import { useAuthUserStore } from '@/stores/AuthUserStore'
import Swal from 'sweetalert2'

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
})


const authUserStore = useAuthUserStore()

const eventData = computed(() => authUserStore.eventData || null)
const eventId   = computed(() => eventData.value?.id || null)

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
  category: '',
  capacity: null,
})

const fetchData = async (page = 1) => {
  if (!eventId.value) return

  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/rooms', {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
        event_id: eventId.value,
      },
    })

    items.value = res.data.data.data
    meta.value  = res.data.data
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal memuat data ruangan',
    })
  } finally {
    isLoading.value = false
  }
}


const openCreateModal = () => {
  isEdit.value = false
  form.value = { id: null, name: '', category: '', capacity: null }
  $('#roomModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = { ...item }
  $('#roomModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true

  const payload = {
    ...form.value,
    event_id: eventId.value,
  }

  try {
    if (isEdit.value) {
      await axios.put(`/api/v1/rooms/${form.value.id}`, payload)
      Toast.fire({
        icon: 'success',
        title: 'Ruangan berhasil diperbarui',
      })
    } else {
      await axios.post('/api/v1/rooms', payload)
      Toast.fire({
        icon: 'success',
        title: 'Ruangan berhasil ditambahkan',
      })
    }

    $('#roomModal').modal('hide')
    fetchData(meta.value.current_page)
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal menyimpan ruangan',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  } finally {
    isSubmitting.value = false
  }
}


const deleteItem = async (item) => {
  const result = await Swal.fire({
    title: 'Hapus ruangan?',
    text: `Ruangan "${item.name}" akan dihapus`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus',
    cancelButtonText: 'Batal',
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/v1/rooms/${item.id}`)
    Toast.fire({
      icon: 'success',
      title: 'Ruangan berhasil dihapus',
    })
    fetchData(meta.value.current_page)
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal menghapus ruangan',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  }
}


const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchData(page)
}

watch(search, useDebounceFn(() => fetchData(1), 400))
watch(perPage, () => fetchData(1))
watch(eventId, () => fetchData(1))

onMounted(() => {
  if (eventId.value) fetchData()
})
</script>
