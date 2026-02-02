<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import { useAuthUserStore } from '@/stores/AuthUserStore'

/* ================= CONTEXT ================= */
const authUserStore = useAuthUserStore()
const eventData = computed(() => authUserStore.eventData || null)

/* ================= STATE ================= */
const items = ref([])
const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)
const isEdit = ref(false)

const meta = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
})

const form = ref({
  id: null,
  name: '',
})

/* ================= FETCH ================= */
const fetchData = async (page = 1) => {
  isLoading.value = true

  const res = await axios.get('/api/v1/participant-categories', {
    params: {
      search: search.value,
      per_page: perPage.value,
      page,
    },
  })

  items.value = res.data.data.data
  meta.value = res.data.data
  isLoading.value = false
}

/* ================= ACTIONS ================= */
const openCreateModal = () => {
  isEdit.value = false
  form.value = { id: null, name: '' }
  $('#participantCategoryModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = { ...item }
  $('#participantCategoryModal').modal('show')
}

const submitForm = async () => {
  if (isEdit.value) {
    await axios.put(
      `/api/v1/participant-categories/${form.value.id}`,
      form.value
    )
  } else {
    await axios.post('/api/v1/participant-categories', form.value)
  }

  $('#participantCategoryModal').modal('hide')
  fetchData(meta.value.current_page)
}

const deleteItem = async (item) => {
  if (!confirm(`Hapus kategori "${item.name}"?`)) return
  await axios.delete(`/api/v1/participant-categories/${item.id}`)
  fetchData(meta.value.current_page)
}

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchData(page)
}

/* ================= WATCH ================= */
watch(search, () => fetchData(1))
watch(perPage, () => fetchData(1))

onMounted(() => fetchData())
</script>

<template>
  <!-- ================= HEADER ================= -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Participant Categories</h1>
          <small class="text-muted d-block" v-if="eventData">
            Event: <strong>{{ eventData.name }}</strong>
          </small>
        </div>

        <button class="btn btn-primary btn-sm" @click="openCreateModal">
          + Tambah Kategori
        </button>
      </div>
    </div>
  </section>

  <!-- ================= CONTENT ================= -->
  <section class="content">
    <div class="container-fluid">
      <div class="card">

        <!-- FILTER -->
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
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
              placeholder="Cari kategori..."
            />
          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Nama Kategori</th>
                <th style="width:110px" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="3" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="3" class="text-center text-muted">
                  Belum ada kategori peserta.
                </td>
              </tr>

              <tr v-for="(item, i) in items" :key="item.id">
                <td>
                  {{ i + 1 + (meta.current_page - 1) * meta.per_page }}
                </td>
                <td>
                  <strong>{{ item.name }}</strong>
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
    <div class="modal fade" id="participantCategoryModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5>
              {{ isEdit ? 'Edit Kategori Peserta' : 'Tambah Kategori Peserta' }}
            </h5>
            <button class="close" data-dismiss="modal">&times;</button>
          </div>

          <div class="modal-body">
            <form @submit.prevent="submitForm">
              <div class="form-group">
                <label>Nama Kategori</label>
                <input
                  v-model="form.name"
                  class="form-control form-control-sm"
                  required
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
