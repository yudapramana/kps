<template>
  <!-- HEADER -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Banks</h1>
          <p class="mb-0 text-muted text-sm">
            Manajemen rekening bank tujuan pembayaran.
          </p>
        </div>

        <button class="btn btn-primary btn-sm" @click="openCreateModal">
          + Tambah Bank
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
              style="min-width:220px"
              placeholder="Cari bank / no rekening..."
            />
          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Bank</th>
                <th>No. Rekening</th>
                <th>Atas Nama</th>
                <th style="width:120px">Admin Fee</th>
                <th style="width:80px" class="text-center">Status</th>
                <th style="width:110px" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="7" class="text-center text-muted">
                  Belum ada bank.
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.id">
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td>
                  <strong>{{ item.name }}</strong>
                  <div class="text-muted text-xs">{{ item.code }}</div>
                </td>
                <td>{{ item.account_number }}</td>
                <td>{{ item.account_name }}</td>
                <td>Rp {{ formatPrice(item.admin_fee) }}</td>
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

    <!-- MODAL -->
    <div class="modal fade" id="bankModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5>{{ isEdit ? 'Edit Bank' : 'Tambah Bank' }}</h5>
            <button class="close" data-dismiss="modal">&times;</button>
          </div>

          <div class="modal-body">
            <form @submit.prevent="submitForm">
              <div class="form-group">
                <label>Kode Bank</label>
                <input v-model="form.code" class="form-control form-control-sm" required />
              </div>

              <div class="form-group">
                <label>Nama Bank</label>
                <input v-model="form.name" class="form-control form-control-sm" required />
              </div>

              <div class="form-group">
                <label>No. Rekening</label>
                <input v-model="form.account_number" class="form-control form-control-sm" required />
              </div>

              <div class="form-group">
                <label>Atas Nama</label>
                <input v-model="form.account_name" class="form-control form-control-sm" required />
              </div>

              <div class="form-group">
                <label>Admin Fee</label>
                <input v-model.number="form.admin_fee" type="number" min="0" class="form-control form-control-sm" />
              </div>

              <div class="form-group">
                <label>Status</label>
                <select v-model="form.is_active" class="form-control form-control-sm">
                  <option :value="true">Aktif</option>
                  <option :value="false">Nonaktif</option>
                </select>
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

const form = ref({
  id: null,
  code: '',
  name: '',
  account_number: '',
  account_name: '',
  admin_fee: 0,
  is_active: true,
})

const fetchData = async (page = 1) => {
  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/banks', {
      params: { page, per_page: perPage.value, search: search.value },
    })

    items.value = res.data.data.data
    meta.value  = res.data.data
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal memuat data bank',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  } finally {
    isLoading.value = false
  }
}


const openCreateModal = () => {
  isEdit.value = false
  form.value = {
    id: null, code: '', name: '',
    account_number: '', account_name: '',
    admin_fee: 0, is_active: true,
  }
  $('#bankModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = { ...item }
  $('#bankModal').modal('show')
}

const submitForm = async () => {
  const payload = { ...form.value }

  try {
    if (isEdit.value) {
      await axios.put(`/api/v1/banks/${form.value.id}`, payload)
      Toast.fire({
        icon: 'success',
        title: 'Bank berhasil diperbarui',
      })
    } else {
      await axios.post('/api/v1/banks', payload)
      Toast.fire({
        icon: 'success',
        title: 'Bank berhasil ditambahkan',
      })
    }

    $('#bankModal').modal('hide')
    fetchData(meta.value.current_page)
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal menyimpan bank',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  }
}


const deleteItem = async (item) => {
  const result = await Swal.fire({
    title: 'Hapus bank?',
    text: `Bank "${item.name}" akan dihapus`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus',
    cancelButtonText: 'Batal',
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/v1/banks/${item.id}`)
    Toast.fire({
      icon: 'success',
      title: 'Bank berhasil dihapus',
    })
    fetchData(meta.value.current_page)
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal menghapus bank',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  }
}


const formatPrice = (val) => Number(val).toLocaleString('id-ID')

watch(search, useDebounceFn(() => fetchData(1), 400))
watch(perPage, () => fetchData(1))

onMounted(() => fetchData())
</script>
