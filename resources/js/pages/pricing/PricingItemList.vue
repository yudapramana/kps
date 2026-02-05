<template>
  <!-- ================= HEADER ================= -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Pricing</h1>
          <p class="mb-0 text-muted text-sm">
            Manajemen harga berdasarkan kategori peserta & paket
          </p>
        </div>

        <button class="btn btn-primary btn-sm" @click="openCreateModal">
          + Tambah Pricing
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
          <div class="d-flex justify-content-between align-items-center w-100">
            <div class="d-flex">
              <select
                v-model="filterCategory"
                class="form-control form-control-sm mr-2"
              >
                <option value="">Semua Kategori</option>
                <option
                  v-for="c in categories"
                  :key="c.id"
                  :value="c.id"
                >
                  {{ c.name }}
                </option>
              </select>

              <select
                v-model="filterBird"
                class="form-control form-control-sm"
              >
                <option value="">Semua Bird</option>
                <option value="early">Early</option>
                <option value="late">Late</option>
              </select>
            </div>
          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Kategori Peserta</th>
                <th style="width:80px">Bird</th>
                <th>Paket</th>
                <th style="width:160px">Harga</th>
                <th style="width:110px" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="6" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="6" class="text-center text-muted">
                  Belum ada pricing.
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.id">
                <td>{{ index + 1 }}</td>

                <td>
                  <strong>{{ item.participant_category.name }}</strong>
                </td>

                <td>
                  <span
                    class="badge"
                    :class="item.bird_type === 'early'
                      ? 'badge-success'
                      : 'badge-secondary'"
                  >
                    {{ item.bird_type }}
                  </span>
                </td>

                <td>
                  {{ item.package_label }}
                </td>

                <td>
                  Rp {{ formatPrice(item.price) }}
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

      </div>
    </div>

    <!-- ================= MODAL ================= -->
    <div class="modal fade" id="pricingModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5>{{ isEdit ? 'Edit Pricing' : 'Tambah Pricing' }}</h5>
            <button class="close" data-dismiss="modal">&times;</button>
          </div>

          <div class="modal-body">
            <form @submit.prevent="submitForm">

              <div class="form-group">
                <label>Kategori Peserta</label>
                <select
                  v-model="form.participant_category_id"
                  class="form-control form-control-sm"
                  required
                >
                  <option value="">-- Pilih --</option>
                  <option
                    v-for="c in categories"
                    :key="c.id"
                    :value="c.id"
                  >
                    {{ c.name }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label>Bird Type</label>
                <select
                  v-model="form.bird_type"
                  class="form-control form-control-sm"
                  required
                >
                  <option value="">-- Pilih --</option>
                  <option value="early">Early</option>
                  <option value="late">Late</option>
                </select>
              </div>

              <div class="form-group">
                <label>Paket</label>
                <select
                  v-model.number="form.workshop_count"
                  class="form-control form-control-sm"
                  required
                >
                  <option :value="0">Symposium</option>
                  <option :value="1">Symposium + 1 Workshop</option>
                  <option :value="2">Symposium + 2 Workshop</option>
                </select>
              </div>

              <div class="form-group">
                <label>Harga (Rp)</label>
                <input
                  v-model.number="form.price"
                  type="number"
                  min="0"
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

<script setup>
import { ref, onMounted, watch } from 'vue'
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
const categories = ref([])
const isLoading = ref(false)
const isEdit = ref(false)

const filterCategory = ref('')
const filterBird = ref('')

const form = ref({
  id: null,
  participant_category_id: '',
  bird_type: '',
  workshop_count: 0,
  price: 0,
})

const fetchData = async () => {
  isLoading.value = true

  try {
    const res = await axios.get('/api/v1/pricing-items', {
      params: {
        participant_category_id: filterCategory.value,
        bird_type: filterBird.value,
      },
    })

    items.value = res.data.data
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal memuat pricing',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  } finally {
    isLoading.value = false
  }
}


const fetchCategories = async () => {
  try {
    const res = await axios.get('/api/v1/participant-categories')
    categories.value = res.data.data.data
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal memuat kategori peserta',
    })
  }
}


const openCreateModal = () => {
  isEdit.value = false
  form.value = {
    id: null,
    participant_category_id: '',
    bird_type: '',
    workshop_count: 0,
    price: 0,
  }
  $('#pricingModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = {
    id: item.id,
    participant_category_id: item.participant_category_id,
    bird_type: item.bird_type,
    workshop_count: item.workshop_count,
    price: item.price,
  }
  $('#pricingModal').modal('show')
}

const submitForm = async () => {
  try {
    if (isEdit.value) {
      await axios.put(`/api/v1/pricing-items/${form.value.id}`, form.value)
      Toast.fire({
        icon: 'success',
        title: 'Pricing berhasil diperbarui',
      })
    } else {
      await axios.post('/api/v1/pricing-items', form.value)
      Toast.fire({
        icon: 'success',
        title: 'Pricing berhasil ditambahkan',
      })
    }

    $('#pricingModal').modal('hide')
    fetchData()
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal menyimpan pricing',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  }
}


const deleteItem = async (item) => {
  const result = await Swal.fire({
    title: 'Hapus pricing?',
    text: `Pricing ${item.package_label} akan dihapus`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus',
    cancelButtonText: 'Batal',
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/v1/pricing-items/${item.id}`)
    Toast.fire({
      icon: 'success',
      title: 'Pricing berhasil dihapus',
    })
    fetchData()
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal menghapus pricing',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  }
}


const formatPrice = (val) =>
  Number(val).toLocaleString('id-ID')

watch([filterCategory, filterBird], fetchData)

onMounted(() => {
  fetchCategories()
  fetchData()
})
</script>
