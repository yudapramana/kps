<template>
  <!-- ================= HEADER ================= -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Participants</h1>
          <p class="mb-0 text-muted text-sm">
            Daftar peserta yang terdaftar pada sistem.
          </p>
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
          <div class="d-flex justify-content-between align-items-center w-100 flex-wrap">

            <!-- LEFT FILTER -->
            <div class="d-flex align-items-center mb-2 mb-md-0">
              <!-- PER PAGE -->
              <div class="mr-3">
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

              <!-- CATEGORY -->
              <div>
                <select
                  v-model="selectedCategory"
                  class="form-control form-control-sm"
                >
                  <option value="">-- Semua Kategori --</option>
                  <option
                    v-for="c in categories"
                    :key="c.id"
                    :value="c.id"
                  >
                    {{ c.name }}
                  </option>
                </select>
              </div>
            </div>

            <!-- SEARCH -->
            <input
              v-model="search"
              type="text"
              class="form-control form-control-sm w-auto"
              style="min-width:260px"
              placeholder="Cari nama / email / NIK / instansi..."
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
                <th>Kontak</th>
                <th>Instansi</th>
                <th style="width:160px">Kategori</th>
                <th style="width:120px">Registrasi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="6" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="6" class="text-center text-muted">
                  Belum ada peserta.
                </td>
              </tr>

              <tr v-for="(item, index) in items" :key="item.id">
                <td>
                  {{ index + 1 + (meta.current_page - 1) * meta.per_page }}
                </td>

                <td>
                  <strong>{{ item.full_name }}</strong><br>
                  <small class="text-muted">NIK: {{ item.nik || '-' }}</small>
                </td>

                <td>
                  {{ item.email || '-' }}<br>
                  <small class="text-muted">{{ item.mobile_phone || '-' }}</small>
                </td>

                <td>
                  {{ item.institution || '-' }}
                </td>

                <td>
                  <span class="badge badge-info">
                    {{ item.participant_category?.name }}
                  </span>
                </td>

                <td>
                  <span
                    class="badge"
                    :class="item.registration_type === 'sponsored'
                      ? 'badge-success'
                      : 'badge-secondary'"
                  >
                    {{ item.registration_type }}
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
  </section>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'

const items = ref([])
const categories = ref([])

const meta = ref({})
const search = ref('')
const perPage = ref(10)
const selectedCategory = ref('')
const isLoading = ref(false)

const fetchData = async (page = 1) => {
  isLoading.value = true

  const res = await axios.get('/api/v1/participants', {
    params: {
      page,
      per_page: perPage.value,
      search: search.value,
      participant_category_id: selectedCategory.value || null,
    },
  })

  items.value = res.data.data.data
  meta.value = res.data.data
  categories.value = res.data.categories
  isLoading.value = false
}

const changePage = (page) => fetchData(page)

watch(search, useDebounceFn(() => fetchData(1), 400))
watch(perPage, () => fetchData(1))
watch(selectedCategory, () => fetchData(1))

onMounted(fetchData)
</script>
