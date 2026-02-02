<template>
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Master Users</h1>
          <p class="mb-0 text-muted text-sm">
            Manajemen data pengguna sistem.
          </p>
        </div>
        <button class="btn btn-primary btn-sm" @click="openCreateModal">
          + Tambah User
        </button>
      </div>
    </div>
  </section>

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
              style="min-width: 220px"
              placeholder="Cari nama / email / username..."
            />
          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Username</th>
                <th style="width:120px">Role</th>
                <th style="width:90px" class="text-center">Multi Role</th>
                <th style="width:110px" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="7" class="text-center">Memuat data...</td>
              </tr>
              <tr v-else-if="items.length === 0">
                <td colspan="7" class="text-center text-muted">
                  Belum ada user.
                </td>
              </tr>
              <tr v-for="(item, index) in items" :key="item.id">
                <td>{{ index + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                <td><strong>{{ item.name }}</strong></td>
                <td>{{ item.email }}</td>
                <td>{{ item.username ?? '-' }}</td>
                <td>{{ item.role?.name }}</td>
                <td class="text-center">
                  <span
                    class="badge"
                    :class="item.can_multiple_role ? 'badge-success' : 'badge-secondary'"
                  >
                    {{ item.can_multiple_role ? 'Ya' : 'Tidak' }}
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
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari {{ meta.total || 0 }} data
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
    <div class="modal fade" id="userModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">
              {{ isEdit ? 'Edit User' : 'Tambah User' }}
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
                    <label>Nama</label>
                    <input v-model="form.name" class="form-control form-control-sm" required />
                  </div>
                  <div class="form-group">
                    <label>Email</label>
                    <input v-model="form.email" type="email" class="form-control form-control-sm" required />
                  </div>
                  <div class="form-group">
                    <label>Username</label>
                    <input v-model="form.username" class="form-control form-control-sm" />
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label>Role</label>
                    <select v-model="form.role_id" class="form-control form-control-sm" required>
                      <option value="">-- Pilih Role --</option>
                      <option v-for="r in roles" :key="r.id" :value="r.id">
                        {{ r.name }}
                      </option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Password</label>
                    <input
                      v-model="form.password"
                      type="password"
                      class="form-control form-control-sm"
                      :required="!isEdit"
                    />
                    <small class="text-muted" v-if="isEdit">
                      Kosongkan jika tidak ingin mengubah password.
                    </small>
                  </div>

                  <div class="form-group">
                    <label>
                      <input type="checkbox" v-model="form.can_multiple_role" />
                      Bisa Multi Role
                    </label>
                  </div>
                </div>
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
const roles = ref([])
const meta = ref({})
const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)
const isEdit = ref(false)
const isSubmitting = ref(false)

const form = ref({
  id: null,
  name: '',
  email: '',
  username: '',
  password: '',
  role_id: '',
  can_multiple_role: false,
})

const fetchData = async (page = 1) => {
  isLoading.value = true
  const res = await axios.get('/api/v1/users', {
    params: { page, per_page: perPage.value, search: search.value },
  })
  items.value = res.data.data.data
  meta.value = res.data.data
  isLoading.value = false
}

const fetchRoles = async () => {
  const res = await axios.get('/api/v1/roles', { params: { simple: true } })
  console.log(res);
  roles.value = res.data
  console.log('apasih isi roles');
}

const openCreateModal = () => {
  isEdit.value = false
  form.value = {
    id: null,
    name: '',
    email: '',
    username: '',
    password: '',
    role_id: '',
    can_multiple_role: false,
  }
  $('#userModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = { ...item, password: '' }
  $('#userModal').modal('show')
}

const submitForm = async () => {
  isSubmitting.value = true
  if (isEdit.value) {
    await axios.put(`/api/v1/users/${form.value.id}`, form.value)
  } else {
    await axios.post('/api/v1/users', form.value)
  }
  $('#userModal').modal('hide')
  fetchData(meta.value.current_page)
  isSubmitting.value = false
}

const deleteItem = async (item) => {
  if (!confirm(`Hapus user "${item.name}"?`)) return
  await axios.delete(`/api/v1/users/${item.id}`)
  fetchData(meta.value.current_page)
}

const changePage = (page) => fetchData(page)

watch(search, useDebounceFn(() => fetchData(1), 400))
watch(perPage, () => fetchData(1))

onMounted(() => {
  fetchData()
  fetchRoles()
})
</script>
