<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import { useAuthUserStore } from '../../stores/AuthUserStore'
import Swal from 'sweetalert2'

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
})

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
const newSpeaker = ref({})
const newPanelist = ref({})
const newSponsor = ref({})
const filterCategory = ref('')


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

  moderator: '',
  lecture: '',
  case_presenter: '',
  pic: '',

  description: '',
  is_paid: true,
  quota: null,
})

/* =============================
 * FETCH DATA
 * ============================= */
const fetchData = async (page = 1) => {
  if (!eventId.value) return

  try {
    const res = await axios.get('/api/v1/activities', {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
        event_id: eventId.value,
        category: filterCategory.value, 
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

      newSpeaker.value[a.id] = { name: '' }

      newPanelist.value[a.id] = { name: '' }

      newSponsor.value[a.id] = {
        name: '',
        logo_url: ''
      }

    })
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal memuat activity',
    })
  }
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
  try {
    if (isEdit.value) {
      await axios.put(`/api/v1/activities/${form.value.id}`, form.value)
      Toast.fire({ icon: 'success', title: 'Activity berhasil diperbarui' })
    } else {
      await axios.post('/api/v1/activities', {
        ...form.value,
        event_id: eventId.value,
      })
      Toast.fire({ icon: 'success', title: 'Activity berhasil ditambahkan' })
    }

    $('#activityModal').modal('hide')
    fetchData(meta.value.current_page)
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal menyimpan activity',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  }
}


const deleteItem = async (item) => {
  const result = await Swal.fire({
    title: 'Hapus activity?',
    text: `Activity "${item.title}" akan dihapus`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus',
    cancelButtonText: 'Batal',
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/v1/activities/${item.id}`)
    Toast.fire({ icon: 'success', title: 'Activity berhasil dihapus' })
    fetchData(meta.value.current_page)
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal menghapus activity',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  }
}


/* =============================
 * TOPICS CRUD
 * ============================= */
const toggleTopics = (id) => {
  expanded.value[id] = !expanded.value[id]
}

const addTopic = async (activityId) => {
  const payload = newTopic.value[activityId]
  if (!payload.title) {
    Swal.fire({
      icon: 'warning',
      title: 'Judul topik wajib diisi',
    })
    return
  }

  try {
    await axios.post('/api/v1/activity-topics', {
      ...payload,
      activity_id: activityId,
    })
    Toast.fire({ icon: 'success', title: 'Topik berhasil ditambahkan' })
    fetchData(meta.value.current_page)
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal menambah topik',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  }
}


const updateTopic = async (topic) => {
  try {
    await axios.put(`/api/v1/activity-topics/${topic.id}`, topic)
    Toast.fire({
      icon: 'success',
      title: 'Topik diperbarui',
    })
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal memperbarui topik',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  }
}


const deleteTopic = async (topic) => {
  const result = await Swal.fire({
    title: 'Hapus topik?',
    text: `Topik "${topic.title}" akan dihapus`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus',
    cancelButtonText: 'Batal',
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/v1/activity-topics/${topic.id}`)
    Toast.fire({ icon: 'success', title: 'Topik berhasil dihapus' })
    fetchData(meta.value.current_page)
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal menghapus topik',
      text: e.response?.data?.message || 'Terjadi kesalahan sistem',
    })
  }
}


/* =============================
 * SPEAKERS
 * ============================= */
const addSpeaker = async(activityId) => {

  const payload = newSpeaker.value[activityId]

  if(!payload.name){
    Swal.fire({icon:'warning',title:'Nama speaker wajib diisi'})
    return
  }

  await axios.post('/api/v1/activity-speakers',{
    activity_id:activityId,
    name:payload.name
  })

  Toast.fire({icon:'success',title:'Speaker ditambahkan'})

  fetchData(meta.value.current_page)
}

const updateSpeaker = async(speaker)=>{

  await axios.put(`/api/v1/activity-speakers/${speaker.id}`,speaker)

}

const deleteSpeaker = async(speaker)=>{

  const result = await Swal.fire({
    title:'Hapus speaker?',
    icon:'warning',
    showCancelButton:true
  })

  if(!result.isConfirmed) return

  await axios.delete(`/api/v1/activity-speakers/${speaker.id}`)

  fetchData(meta.value.current_page)

}

/* =============================
 * PANELISTS
 * ============================= */
const addPanelist = async(activityId)=>{

  const payload = newPanelist.value[activityId]

  if(!payload.name){
    Swal.fire({icon:'warning',title:'Nama panelist wajib diisi'})
    return
  }

  await axios.post('/api/v1/activity-panelists',{
    activity_id:activityId,
    name:payload.name
  })

  Toast.fire({icon:'success',title:'Panelist ditambahkan'})

  fetchData(meta.value.current_page)

}

const updatePanelist = async(panelist)=>{

  await axios.put(`/api/v1/activity-panelists/${panelist.id}`,panelist)

}

const deletePanelist = async(panelist)=>{

  const result = await Swal.fire({
    title:'Hapus panelist?',
    icon:'warning',
    showCancelButton:true
  })

  if(!result.isConfirmed) return

  await axios.delete(`/api/v1/activity-panelists/${panelist.id}`)

  fetchData(meta.value.current_page)

}

/* =============================
 * SPONSORS
 * ============================= */
const addSponsor = async(activityId)=>{

  const payload = newSponsor.value[activityId]

  if(!payload.name){
    Swal.fire({icon:'warning',title:'Nama sponsor wajib diisi'})
    return
  }

  await axios.post('/api/v1/activity-sponsors',{
    activity_id:activityId,
    name:payload.name,
    logo_url:payload.logo_url
  })

  Toast.fire({icon:'success',title:'Sponsor ditambahkan'})

  fetchData(meta.value.current_page)

}

const updateSponsor = async(sponsor)=>{

  await axios.put(`/api/v1/activity-sponsors/${sponsor.id}`,sponsor)

}

const deleteSponsor = async(sponsor)=>{

  const result = await Swal.fire({
    title:'Hapus sponsor?',
    icon:'warning',
    showCancelButton:true
  })

  if(!result.isConfirmed) return

  await axios.delete(`/api/v1/activity-sponsors/${sponsor.id}`)

  fetchData(meta.value.current_page)

}


/* =============================
 * WATCHERS
 * ============================= */
watch(search, () => fetchData(1))
watch(perPage, () => fetchData(1))
watch(eventId, () => fetchData(1))
watch(filterCategory, () => fetchData(1))


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
              <span class="text-sm text-muted ml-1 mr-2">Entri</span>

              |
              <select
                v-model="filterCategory"
                class="form-control form-control-sm d-inline-block w-auto ml-2"
                style="min-width:160px"
              >
                <option value="">Semua Kategori</option>
                <option v-for="c in categories" :key="c" :value="c">
                  {{ c }}
                </option>
              </select>
              

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
                    <div class="d-flex align-items-center">

                      <button
                        class="btn btn-xs btn-outline-secondary mr-2"
                        @click="toggleTopics(item.id)"
                      >
                        <i
                          class="fas"
                          :class="expanded[item.id] ? 'fa-chevron-down' : 'fa-chevron-right'"
                        ></i>
                      </button>

                      <div>
                        <strong>{{ item.title }}</strong><br>
                        <small class="text-muted">
                          {{ item.code || '-' }}
                        </small>
                      </div>

                    </div>
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
                    <div class="dropdown">
                      <button class="btn btn-xs btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                        Aksi
                      </button>
                      <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="#" @click.prevent="toggleTopics(item.id)">
                          <i class="fas fa-layer-group mr-2">
                          </i>
                          Kelola Detail
                        </a>
                        <a class="dropdown-item" href="#" @click.prevent="openEditModal(item)">
                          <i class="fas fa-edit mr-2">
                          </i>
                          Edit Activity
                        </a>
                        <div class="dropdown-divider">
                        </div>
                        <!-- <a class="dropdown-item text-danger" href="#" @click.prevent="deleteItem(item)">
                          <i class="fas fa-trash mr-2">
                          </i>
                          Hapus
                        </a> -->
                      </div>
                    </div>
                  </td>
                </tr>

                <!-- DROPDOWN -->
                <tr v-if="expanded[item.id]">
                  <td colspan="6" class="bg-light">
                    <div class="p-3">
                      <div class="row">
                        <!-- TOPICS -->
                        <div class="col-md-12 mb-3">
                          <div class="card card-outline card-primary">
                            <div class="card-header py-1">
                              <strong>
                                Topics
                              </strong>
                            </div>
                            <div class="card-body p-2">
                              <div v-for="t in item.topics" :key="t.id" class="d-flex mb-1">
                                <input v-model.number="t.order" type="number" class="form-control form-control-sm mr-1"
                                style="width:60px" @change="updateTopic(t)" />
                                <input v-model="t.title" class="form-control form-control-sm mr-1" @blur="updateTopic(t)"
                                />
                                <select v-model="t.type" class="form-control form-control-sm mr-1" style="width:130px"
                                @change="updateTopic(t)">
                                  <option value="lecture">
                                    Lecture
                                  </option>
                                  <option value="case">
                                    Case
                                  </option>
                                  <option value="video">
                                    Video
                                  </option>
                                  <option value="discussion">
                                    Discussion
                                  </option>
                                </select>
                                <button class="btn btn-xs btn-danger" @click="deleteTopic(t)">
                                  <i class="fas fa-times">
                                  </i>
                                </button>
                              </div>
                              <div class="d-flex mt-2">
                                <input v-model.number="newTopic[item.id].order" type="number" class="form-control form-control-sm mr-1"
                                style="width:60px" />
                                <input v-model="newTopic[item.id].title" class="form-control form-control-sm mr-1"
                                placeholder="Judul topik" />
                                <select v-model="newTopic[item.id].type" class="form-control form-control-sm mr-1"
                                style="width:130px">
                                  <option value="lecture">
                                    Lecture
                                  </option>
                                  <option value="case">
                                    Case
                                  </option>
                                  <option value="video">
                                    Video
                                  </option>
                                  <option value="discussion">
                                    Discussion
                                  </option>
                                </select>
                                <button class="btn btn-xs btn-success" @click="addTopic(item.id)">
                                  <i class="fas fa-plus">
                                  </i>
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                        <!-- SPEAKERS -->
                        <div class="col-md-6 mb-3">
                          <div class="card card-outline card-primary">
                            <div class="card-header py-1">
                              <strong>
                                Speakers
                              </strong>
                            </div>
                            <div class="card-body p-2">
                              <div v-for="s in item.speakers" :key="s.id" class="d-flex mb-1">
                                <input v-model="s.name" class="form-control form-control-sm mr-1" @blur="updateSpeaker(s)"
                                />
                                <button class="btn btn-xs btn-danger" @click="deleteSpeaker(s)">
                                  <i class="fas fa-times">
                                  </i>
                                </button>
                              </div>
                              <div class="d-flex">
                                <input v-model="newSpeaker[item.id].name" class="form-control form-control-sm mr-1"
                                placeholder="Nama Speaker" />
                                <button class="btn btn-xs btn-success" @click="addSpeaker(item.id)">
                                  <i class="fas fa-plus">
                                  </i>
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                        <!-- PANELISTS -->
                        <div class="col-md-6 mb-3">
                          <div class="card card-outline card-info">

                            <div class="card-header py-1">
                              <strong>Panelists</strong>
                            </div>

                            <div class="card-body p-2">

                              <!-- LIST PANELISTS -->
                              <div
                                v-for="p in item.panelists"
                                :key="p.id"
                                class="d-flex align-items-center mb-1"
                              >

                                <input
                                  v-model="p.name"
                                  class="form-control form-control-sm mr-1"
                                  placeholder="Nama Panelist"
                                  @blur="updatePanelist(p)"
                                />

                                <button
                                  class="btn btn-xs btn-danger"
                                  @click="deletePanelist(p)"
                                >
                                  <i class="fas fa-times"></i>
                                </button>

                              </div>

                              <!-- ADD PANELIST -->
                              <div class="d-flex mt-2">

                                <input
                                  v-model="newPanelist[item.id].name"
                                  class="form-control form-control-sm mr-1"
                                  placeholder="Tambah panelist"
                                />

                                <button
                                  class="btn btn-xs btn-success"
                                  @click="addPanelist(item.id)"
                                >
                                  <i class="fas fa-plus"></i>
                                </button>

                              </div>

                            </div>
                          </div>

                        </div>
                        <!-- SPONSORS -->
                        <div class="col-md-12 mb-3">
                          <div class="card card-outline card-warning">
                            <div class="card-header py-1">
                              <strong>
                                Sponsors
                              </strong>
                            </div>
                            <div class="card-body p-2">
                              <!-- LIST SPONSORS -->
                              <div v-for="s in item.sponsors" :key="s.id" class="d-flex align-items-center mb-1">
                                <input v-model="s.name" class="form-control form-control-sm mr-1" placeholder="Nama Sponsor"
                                @blur="updateSponsor(s)" />
                                <input v-model="s.logo_url" class="form-control form-control-sm mr-1"
                                placeholder="Logo URL" @blur="updateSponsor(s)" />
                                <button class="btn btn-xs btn-danger" @click="deleteSponsor(s)">
                                  <i class="fas fa-times">
                                  </i>
                                </button>
                              </div>
                              <!-- ADD SPONSOR -->
                              <div class="d-flex mt-2">
                                <input v-model="newSponsor[item.id].name" class="form-control form-control-sm mr-1"
                                placeholder="Nama sponsor" />
                                <input v-model="newSponsor[item.id].logo_url" class="form-control form-control-sm mr-1"
                                placeholder="Logo URL" />
                                <button class="btn btn-xs btn-success" @click="addSponsor(item.id)">
                                  <i class="fas fa-plus">
                                  </i>
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
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
                  <input v-model="form.moderator"
                  class="form-control form-control-sm mb-2"
                  placeholder="Moderator" />

                  <input v-model="form.lecture"
                  class="form-control form-control-sm mb-2"
                  placeholder="Lecture" />

                  <input v-model="form.case_presenter"
                  class="form-control form-control-sm mb-2"
                  placeholder="Case Presenter" />

                  <input v-model="form.pic"
                  class="form-control form-control-sm mb-2"
                  placeholder="PIC" />
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
