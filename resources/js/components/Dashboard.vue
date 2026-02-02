<script setup>
import { computed, onMounted } from "vue"
import { useAuthUserStore } from "../stores/AuthUserStore.js"
import { useScreenDisplayStore } from "../stores/ScreenDisplayStore.js"
import { useMasterDataStore } from "../stores/MasterDataStore.js"

const authUserStore = useAuthUserStore()
const screenDisplayStore = useScreenDisplayStore()
const masterDataStore = useMasterDataStore()



/* ================= EVENT ACTIVE ================= */
const activeEvent = computed(() => authUserStore.eventData || {})

/* ================= AGENDA ================= */
const today = new Date().toISOString().slice(0, 10)

/* ================= HELPERS ================= */
const formatDate = (date) => {
  if (!date) return "-"
  return new Date(date).toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  })
}

const formatEventDate = (start, end) => {
  if (!start) return "-"
  const s = formatDate(start)
  if (!end) return s
  return `${s} – ${formatDate(end)}`
}

const isEarlyBirdActive = computed(() => {
  if (!activeEvent.value?.early_bird_end_date) return false
  return activeEvent.value.early_bird_end_date >= today
})
</script>

<template>
  <!-- ================= HEADER ================= -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Beranda</h1>
        </div>
        <div class="col-sm-6" v-if="!screenDisplayStore.isMobile">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">Beranda</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= CONTENT ================= -->
  <div class="content">
    <div class="container-fluid">
      <div class="row">

        <!-- ===== WELCOME ===== -->
        <div class="col-12">
          <div class="card welcome-card">
            <div class="card-body">
              <h4 class="mb-0">
                👋 Selamat datang,
                <strong>{{ authUserStore.user?.name }}</strong>
              </h4>
            </div>
          </div>
        </div>

        <!-- ===== ACTIVE EVENT ===== -->
        <div class="col-md-6 col-sm-12">
          <div class="card event-card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h5 class="font-weight-bold text-primary mb-1">
                    {{ activeEvent.name || 'Event Aktif' }}
                  </h5>

                  <p class="text-muted text-sm mb-2" v-if="activeEvent.theme">
                    {{ activeEvent.theme }}
                  </p>
                </div>

                <span class="badge badge-success">
                  Event Aktif
                </span>
              </div>

              <hr class="my-2">

              <div class="text-sm mb-1">
                <i class="far fa-calendar-alt mr-1"></i>
                {{ formatEventDate(activeEvent.start_date, activeEvent.end_date) }}
              </div>

              <div class="text-sm mb-1">
                <i class="fas fa-map-marker-alt mr-1"></i>
                {{ activeEvent.location }} – {{ activeEvent.venue }}
              </div>

              <div class="mt-2" v-if="activeEvent.early_bird_end_date">
                <span
                  class="badge"
                  :class="isEarlyBirdActive ? 'badge-warning' : 'badge-secondary'"
                >
                  Early Bird {{ isEarlyBirdActive ? 'Aktif' : 'Berakhir' }}
                </span>
                <small class="text-muted ml-2">
                  sampai {{ formatDate(activeEvent.early_bird_end_date) }}
                </small>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
/* ================= WELCOME ================= */
.welcome-card {
  background: linear-gradient(135deg, #f8f9fa, #ffffff);
  border-radius: 12px;
}

/* ================= EVENT CARD ================= */
.event-card {
  border-left: 4px solid #007bff;
  background: linear-gradient(135deg, #f8faff, #ffffff);
  border-radius: 12px;
}

/* ================= AGENDA ================= */
.agenda-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.agenda-item {
  display: flex;
  align-items: flex-start;
  padding: 12px 10px;
  border-bottom: 1px solid #f0f0f0;
}

.agenda-item:last-child {
  border-bottom: none;
}

.agenda-item.active {
  background: #f0fff4;
  border-left: 4px solid #28a745;
}

.agenda-icon {
  width: 42px;
  height: 42px;
  background: #fff1f0;
  color: #e5533d;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  font-size: 18px;
}

.agenda-title {
  font-weight: 600;
  font-size: 14px;
}

.agenda-date,
.agenda-notes {
  font-size: 12px;
  color: #6c757d;
}
</style>
