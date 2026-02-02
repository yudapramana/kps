<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { useRoute } from 'vue-router'

const route = useRoute()

/* ================= CONTEXT ================= */
const selectedEventDay = ref(route.query.event_day_id || '')

/* ================= STATE ================= */
const items = ref([])
const rooms = ref([])
const activities = ref([])
const event = ref(null)
const eventDay = ref(null)
const eventDays = ref([])

const activeRoomId = ref(null)

const isLoading = ref(false)
const isEdit = ref(false)

const form = ref({
  id: null,
  room_id: '',
  activity_id: '',
  start_time: '',
  end_time: '',
})

/* ================= FETCH ================= */
const fetchData = async () => {
  if (!selectedEventDay.value) return

  isLoading.value = true

  const res = await axios.get('/api/v1/sessions', {
    params: {
      event_day_id: selectedEventDay.value,
      room_id: activeRoomId.value,
    },
  })

  items.value = res.data.data.sessions
  rooms.value = res.data.data.rooms
  activities.value = res.data.data.activities
  event.value = res.data.data.event
  eventDay.value = res.data.data.event_day
  eventDays.value = res.data.data.event_days

  isLoading.value = false
}

/* ================= ACTIONS ================= */
const openCreateModal = () => {
  isEdit.value = false
  form.value = { room_id: '', activity_id: '', start_time: '', end_time: '' }
  $('#sessionModal').modal('show')
}

const openEditModal = (item) => {
  isEdit.value = true
  form.value = {
    id: item.id,
    room_id: item.room_id,
    activity_id: item.activity_id,
    start_time: item.start_time,
    end_time: item.end_time,
  }
  $('#sessionModal').modal('show')
}

const submitForm = async () => {
  if (isEdit.value) {
    await axios.put(`/api/v1/sessions/${form.value.id}`, form.value)
  } else {
    await axios.post('/api/v1/sessions', {
      ...form.value,
      event_day_id: selectedEventDay.value,
    })
  }
  $('#sessionModal').modal('hide')
  fetchData()
}

const deleteItem = async (item) => {
  if (!confirm('Hapus session ini?')) return
  await axios.delete(`/api/v1/sessions/${item.id}`)
  fetchData()
}

/* ================= ROOM FILTER ================= */
const toggleRoom = (roomId) => {
  activeRoomId.value = activeRoomId.value === roomId ? null : roomId
}

/* ================= WATCH ================= */
watch(selectedEventDay, () => {
  activeRoomId.value = null
  fetchData()
})

watch(activeRoomId, fetchData)

/* ================= INIT ================= */
onMounted(async () => {
  if (!selectedEventDay.value) {
    const res = await axios.get('/api/v1/event-days', { params: { per_page: 1 } })
    if (res.data.data.data.length) {
      selectedEventDay.value = res.data.data.data[0].id
    }
  }
  fetchData()
})
</script>

<template>
  <!-- ================= HEADER ================= -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="mb-1">Sessions</h1>
          <small class="text-muted d-block" v-if="eventDay">
            Event: <strong>{{ event?.name }}</strong><br>
            Hari: <strong>{{ eventDay.label }} ({{ eventDay.date }})</strong>
          </small>
        </div>

        <button class="btn btn-primary btn-sm" @click="openCreateModal">
          + Tambah Session
        </button>
      </div>
    </div>
  </section>

  <!-- ================= CONTENT ================= -->
  <section class="content">
    <div class="container-fluid">
      <div class="card">

        <!-- FILTER (SATU BARIS) -->
        <div class="card-header">
          <div class="d-flex align-items-center flex-wrap">

            <!-- EVENT DAY -->
            <select
              v-model="selectedEventDay"
              class="form-control form-control-sm mr-3"
              style="width: 220px"
            >
              <option v-for="d in eventDays" :key="d.id" :value="d.id">
                {{ d.label }} ({{ d.date }})
              </option>
            </select>

            <!-- ROOM BADGES -->
            <div class="d-flex flex-wrap align-items-center">
              <span
                class="badge mr-1 cursor-pointer"
                :class="!activeRoomId ? 'badge-primary' : 'badge-light'"
                @click="activeRoomId = null"
              >
                Semua Ruangan
              </span>

              <span
                v-for="r in rooms"
                :key="r.id"
                class="badge mr-1 cursor-pointer"
                :class="activeRoomId === r.id ? 'badge-primary' : 'badge-light'"
                @click="toggleRoom(r.id)"
              >
                {{ r.name }}
              </span>
            </div>

          </div>
        </div>

        <!-- TABLE -->
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover text-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:40px">#</th>
                <th style="width:120px">Waktu</th>
                <th>Ruangan</th>
                <th>Activity</th>
                <th style="width:120px">Kategori</th>
                <th style="width:110px" class="text-center">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="isLoading">
                <td colspan="6" class="text-center">Memuat data...</td>
              </tr>

              <tr v-else-if="items.length === 0">
                <td colspan="6" class="text-center text-muted">
                  Belum ada session.
                </td>
              </tr>

              <tr v-for="(s, i) in items" :key="s.id">
                <td>{{ i + 1 }}</td>
                <td>{{ s.start_time }} - {{ s.end_time }}</td>
                <td>{{ s.room.name }}</td>
                <td>
                  <strong>{{ s.activity.title }}</strong><br>
                  <small class="text-muted">{{ s.activity.code || '-' }}</small>
                </td>
                <td>
                  <span class="badge badge-info text-uppercase">
                    {{ s.activity.category }}
                  </span>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-warning" @click="openEditModal(s)">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger" @click="deleteItem(s)">
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
    <div class="modal fade" id="sessionModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5>{{ isEdit ? 'Edit Session' : 'Tambah Session' }}</h5>
            <button class="close" data-dismiss="modal">&times;</button>
          </div>

          <div class="modal-body">
            <form @submit.prevent="submitForm">
              <div class="row">
                <div class="col-md-4">
                  <label>Ruangan</label>
                  <select v-model="form.room_id" class="form-control form-control-sm" required>
                    <option value="">-- Pilih Ruangan --</option>
                    <option v-for="r in rooms" :key="r.id" :value="r.id">
                      {{ r.name }}
                    </option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label>Mulai</label>
                  <input v-model="form.start_time" type="time" class="form-control form-control-sm" required />
                </div>

                <div class="col-md-4">
                  <label>Selesai</label>
                  <input v-model="form.end_time" type="time" class="form-control form-control-sm" required />
                </div>
              </div>

              <div class="form-group mt-2">
                <label>Activity</label>
                <select v-model="form.activity_id" class="form-control form-control-sm" required>
                  <option value="">-- Pilih Activity --</option>
                  <option v-for="a in activities" :key="a.id" :value="a.id">
                    [{{ a.category }}] {{ a.title }}
                  </option>
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

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>
