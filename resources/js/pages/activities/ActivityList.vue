<script setup>
import { ref, onMounted, watch, computed } from 'vue'
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
const search = ref('')
const perPage = ref(10)
const isEdit = ref(false)

const expanded = ref({})
const newTopic = ref({})

const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  from: 0,
  to: 0,
  total: 0,
})

const categories = ['plenary', 'symposium', 'workshop', 'jeopardy', 'poster']

const form = ref({
  id: null,
  event_id: '',
  category: '',
  code: '',
  title: '',
  description: '',
  is_paid: true,
  quota: null,
})

/* =============================
 * FETCH DATA
 * ============================= */
const fetchData = async (page = 1) => {
  if (!eventId.value) return

  const res = await axios.get('/api/v1/activities', {
    params: {
      page,
      per_page: perPage.value,
      search: search.value,
      event_id: eventId.value,
    },
  })

  items.value = res.data.data.data
  meta.value = res.data.data

  items.value.forEach(a => {
    expanded.value[a.id] = expanded.value[a.id] ?? false
    newTopic.value[a.id] = {
      title: '',
      type: 'lecture',
      order: a.topics.length + 1,
    }
  })
}

/* =============================
 * PAGINATION
 * ============================= */
const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  fetchData(page)
}

/* =============================
 * ACTIVITY CRUD
 * ============================= */
const openCreateModal = () => {
  isEdit.value = false
  form.value = { event_id: eventId.value, is_paid: true }
  $('#activityModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = { ...item }
  $('#activityModal').modal('show')
}

const submitForm = async () => {
  if (isEdit.value) {
    await axios.put(`/api/v1/activities/${form.value.id}`, form.value)
  } else {
    await axios.post('/api/v1/activities', {
      ...form.value,
      event_id: eventId.value,
    })
  }
  $('#activityModal').modal('hide')
  fetchData(meta.value.current_page)
}

const deleteItem = async (item) => {
  if (!confirm('Hapus activity ini?')) return
  await axios.delete(`/api/v1/activities/${item.id}`)
  fetchData(meta.value.current_page)
}

/* =============================
 * TOPICS CRUD
 * ============================= */
const toggleTopics = (id) => {
  expanded.value[id] = !expanded.value[id]
}

const addTopic = async (activityId) => {
  const payload = newTopic.value[activityId]
  if (!payload.title) return

  await axios.post('/api/v1/activity-topics', {
    ...payload,
    activity_id: activityId,
  })
  fetchData(meta.value.current_page)
}

const updateTopic = async (topic) => {
  await axios.put(`/api/v1/activity-topics/${topic.id}`, topic)
}

const deleteTopic = async (topic) => {
  if (!confirm('Hapus topik ini?')) return
  await axios.delete(`/api/v1/activity-topics/${topic.id}`)
  fetchData(meta.value.current_page)
}

/* =============================
 * WATCHERS
 * ============================= */
watch(search, () => fetchData(1))
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
          <h1 class="mb-1">Activities</h1>
          <p class="mb-0 text-muted text-sm">
            Manajemen Kegiatan dari Event
          </p>
        </div>

        <button class="btn btn-primary btn-sm" @click="openCreateModal">
          + Tambah Activity
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
              placeholder="Cari judul / kode..."
            />
          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Activity</th>
                <th style="width:120px">Kategori</th>
                <th style="width:80px">Quota</th>
                <th style="width:80px">Paid</th>
                <th style="width:220px">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <template v-for="(item, i) in items" :key="item.id">
                <tr>
                  <td>{{ i + 1 + (meta.current_page - 1) * meta.per_page }}</td>
                  <td>
                    <strong>{{ item.title }}</strong><br />
                    <small class="text-muted">{{ item.code || '-' }}</small>
                  </td>
                  <td>{{ item.category }}</td>
                  <td>{{ item.quota ?? '-' }}</td>
                  <td>
                    <span
                      class="badge"
                      :class="item.is_paid ? 'badge-success' : 'badge-secondary'"
                    >
                      {{ item.is_paid ? 'Paid' : 'Free' }}
                    </span>
                  </td>
                  <td>
                    <button
                      class="btn btn-outline-info btn-xs mr-1"
                      @click="toggleTopics(item.id)"
                    >
                      <i
                        class="fas"
                        :class="expanded[item.id] ? 'fa-chevron-up' : 'fa-chevron-down'"
                      ></i>
                      Topics ({{ item.topics.length }})
                    </button>

                    <button class="btn btn-warning btn-xs mr-1" @click="openEditModal(item)">
                      <i class="fas fa-edit"></i>
                    </button>

                    <button class="btn btn-danger btn-xs" @click="deleteItem(item)">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>

                <!-- TOPICS -->
                <tr v-if="expanded[item.id]">
                  <td colspan="6" class="bg-light">
                    <div class="pl-3">

                      <div
                        v-for="t in item.topics"
                        :key="t.id"
                        class="d-flex align-items-center mb-1"
                      >
                        <input
                          v-model.number="t.order"
                          type="number"
                          class="form-control form-control-sm mr-1"
                          style="width:60px"
                          @change="updateTopic(t)"
                        />

                        <input
                          v-model="t.title"
                          class="form-control form-control-sm mr-1"
                          @blur="updateTopic(t)"
                        />

                        <select
                          v-model="t.type"
                          class="form-control form-control-sm mr-1"
                          style="width:140px"
                          @change="updateTopic(t)"
                        >
                          <option value="lecture">Lecture</option>
                          <option value="case">Case</option>
                          <option value="video">Video</option>
                          <option value="discussion">Discussion</option>
                        </select>

                        <button class="btn btn-danger btn-xs" @click="deleteTopic(t)">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>

                      <!-- ADD TOPIC -->
                      <div class="d-flex align-items-center mt-2">
                        <input
                          v-model.number="newTopic[item.id].order"
                          type="number"
                          class="form-control form-control-sm mr-1"
                          style="width:60px"
                        />
                        <input
                          v-model="newTopic[item.id].title"
                          class="form-control form-control-sm mr-1"
                          placeholder="Judul topik"
                        />
                        <select
                          v-model="newTopic[item.id].type"
                          class="form-control form-control-sm mr-1"
                          style="width:140px"
                        >
                          <option value="lecture">Lecture</option>
                          <option value="case">Case</option>
                          <option value="video">Video</option>
                          <option value="discussion">Discussion</option>
                        </select>
                        <button class="btn btn-success btn-xs" @click="addTopic(item.id)">
                          <i class="fas fa-plus"></i>
                        </button>
                      </div>

                    </div>
                  </td>
                </tr>
              </template>

              <tr v-if="items.length === 0">
                <td colspan="6" class="text-center text-muted">
                  Belum ada activity.
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

    <!-- MODAL ACTIVITY -->
    <div class="modal fade" id="activityModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5>{{ isEdit ? 'Edit Activity' : 'Tambah Activity' }}</h5>
            <button class="close" data-dismiss="modal">&times;</button>
          </div>

          <div class="modal-body">
            <form @submit.prevent="submitForm">
              <div class="row">
                <div class="col-md-6">
                  <input v-model="form.title" class="form-control form-control-sm mb-2" placeholder="Judul" required />
                  <input v-model="form.code" class="form-control form-control-sm mb-2" placeholder="Kode" />
                  <select v-model="form.category" class="form-control form-control-sm mb-2" required>
                    <option value="">Kategori</option>
                    <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <input v-model.number="form.quota" type="number" class="form-control form-control-sm mb-2" placeholder="Quota" />
                  <label class="text-sm">
                    <input type="checkbox" v-model="form.is_paid" /> Paid
                  </label>
                </div>
              </div>
              <textarea v-model="form.description" class="form-control form-control-sm" placeholder="Deskripsi"></textarea>
              <div class="text-right mt-2">
                <button class="btn btn-primary btn-sm">Simpan</button>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>

  </section>
</template>
