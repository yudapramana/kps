<template>
  <!-- HEADER -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Paper Types</h1>
          <p class="mb-0 text-muted text-sm">
            Manajemen tipe paper (Abstract, Case, dll).
          </p>
        </div>

        <button
          class="btn btn-primary btn-sm"
          @click="openCreateModal"
        >
          + Tambah Paper Type
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
              placeholder="Cari code / nama..."
            />
          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">#</th>
                <th style="width:160px">Code</th>
                <th>Nama</th>
                <th>Deskripsi</th>
                <th style="width:110px" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="5" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="5" class="text-center text-muted">
                  Belum ada paper type.
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.id">
                <td>
                  {{ index + 1 + (meta.current_page - 1) * meta.per_page }}
                </td>
                <td>
                  <span class="badge badge-info text-uppercase">
                    {{ item.code }}
                  </span>
                </td>
                <td><strong>{{ item.name }}</strong></td>
                <td>{{ item.description || '-' }}</td>
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
    <div class="modal fade" id="paperTypeModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">
              {{ isEdit ? 'Edit Paper Type' : 'Tambah Paper Type' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body">
            <form @submit.prevent="submitForm">
              <div class="form-group">
                <label>Code</label>
                <input
                  v-model="form.code"
                  class="form-control form-control-sm text-uppercase"
                  :disabled="isEdit"
                  required
                />
              </div>

              <div class="form-group">
                <label>Nama</label>
                <input
                  v-model="form.name"
                  class="form-control form-control-sm"
                  required
                />
              </div>

              <div class="form-group">
                <label>Deskripsi</label>
                <textarea
                  v-model="form.description"
                  class="form-control form-control-sm"
                  rows="3"
                ></textarea>
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
  code: '',
  name: '',
  description: '',
})

const fetchData = async (page = 1) => {
  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/paper-types', {
      params: { page, per_page: perPage.value, search: search.value },
    })
    items.value = res.data.data.data
    meta.value = res.data.data
  } catch {
    Swal.fire({ icon: 'error', title: 'Gagal memuat paper type' })
  } finally {
    isLoading.value = false
  }
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = { id: null, code: '', name: '', description: '' }
  $('#paperTypeModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = { ...item }
  $('#paperTypeModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true
  try {
    if (isEdit.value) {
      await axios.put(`/api/v1/paper-types/${form.value.id}`, form.value)
      Toast.fire({ icon: 'success', title: 'Paper type diperbarui' })
    } else {
      await axios.post('/api/v1/paper-types', form.value)
      Toast.fire({ icon: 'success', title: 'Paper type ditambahkan' })
    }
    $('#paperTypeModal').modal('hide')
    fetchData(meta.value.current_page)
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal menyimpan data',
      text: e.response?.data?.message || 'Terjadi kesalahan',
    })
  } finally {
    isSubmitting.value = false
  }
}

const deleteItem = async (item) => {
  const result = await Swal.fire({
    title: 'Hapus paper type?',
    text: `"${item.name}" akan dihapus`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus',
    cancelButtonText: 'Batal',
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/v1/paper-types/${item.id}`)

    Toast.fire({
      icon: 'success',
      title: 'Paper type berhasil dihapus',
    })

    fetchData(meta.value.current_page)
  } catch (e) {
    if (e.response?.status === 422) {
      Swal.fire({
        icon: 'warning',
        title: 'Tidak bisa dihapus',
        text: e.response.data.message,
      })
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Gagal menghapus',
        text: 'Terjadi kesalahan sistem',
      })
    }
  }
}


const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchData(page)
}

watch(search, useDebounceFn(() => fetchData(1), 400))
watch(perPage, () => fetchData(1))
onMounted(fetchData)
</script>
